<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use App\Models\PagoPropia;
use App\Models\PagoCajaCuscoCastigada;
use App\Models\PagoCajaCuscoExtrajudicial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\WorkflowMailer;

class AutorizacionController extends Controller
{
    public function index(Request $req)
    {
        $user   = Auth::user();
        $q      = trim((string)($req->q ?? ''));
        $status = $req->status;

        // ===== PROMESAS (sin duplicar)
        $promesas = PromesaPago::query()
            ->with(['operaciones'])                                 // pivot de operaciones
            ->leftJoin('users as u','u.id','=','promesas_pago.user_id')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('promesas_pago.dni','like',"%{$q}%")
                      ->orWhere('promesas_pago.nota','like',"%{$q}%")
                      ->orWhere('promesas_pago.operacion','like',"%{$q}%")
                      ->orWhereHas('operaciones', fn($qq)=>$qq->where('operacion','like',"%{$q}%"));
                });
            });

        if (strtolower($user->role) === 'supervisor') {
            $promesas->where('promesas_pago.workflow_estado','pendiente');
        } else {
            $promesas->where('promesas_pago.workflow_estado','preaprobada');
        }
        if (!empty($status)) {
            $promesas->where('promesas_pago.workflow_estado', $status);
        }

        $rows = $promesas->select('promesas_pago.*','u.name as creador_nombre')
                         ->orderByDesc('promesas_pago.fecha_promesa')
                         ->get();

        // ===== Prefetch por DNI para fallback (por si la promesa no guardó pivote)
        $dnis = $rows->pluck('dni')->filter()->unique()->values()->all();
        $opsByDni = [];
        if ($dnis) {
            $opsByDni = DB::table('clientes_cuentas')
                ->select('dni','operacion')
                ->whereIn('dni',$dnis)
                ->get()
                ->groupBy('dni')
                ->map(fn($g)=>$g->pluck('operacion')->filter()->values()->all())
                ->all();
        }

        // ===== Traer cuentas por operación (para el acordeón + agregados)
        // Primero arma el universo de operaciones de todas las promesas
        $opsAll = $rows->flatMap(function($p) use ($opsByDni){
                if ($p->relationLoaded('operaciones') && $p->operaciones->count()) {
                    return $p->operaciones->pluck('operacion');
                }
                if (!empty($p->operacion)) {
                    return collect(array_filter(array_map('trim', explode(',', (string)$p->operacion))));
                }
                // fallback: todas las ops del DNI
                return collect($opsByDni[$p->dni] ?? []);
            })
            ->filter()->unique()->values()->all();

        $ccByOp = [];
        if (!empty($opsAll)) {
            $ccByOp = DB::table('clientes_cuentas')
                ->select([
                    'operacion','dni','cartera','titular','entidad','producto','moneda',
                    'saldo_capital','deuda_total','agente','anio_castigo',
                    'clasificacion','hasta','capital_descuento'
                ])
                ->whereIn('operacion', $opsAll)
                ->get()
                ->keyBy('operacion');
        }

        // ===== Armar cada fila (agregados + JSON de cuentas)
        $rows = $rows->map(function($p) use ($opsByDni,$ccByOp) {

            // Operaciones de la promesa: pivote → legacy → fallback por DNI
            $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
                ? $p->operaciones->pluck('operacion')->map(fn($x)=>(string)$x)->values()
                : collect(array_filter(array_map('trim', explode(',', (string)($p->operacion ?? '')))));
            if ($ops->isEmpty()) {
                $ops = collect($opsByDni[$p->dni] ?? []);
            }

            // Texto para la columna
            $p->operacion = $ops->implode(', ');
            $p->ops_list  = $ops->values();

            // Acumuladores + per-cuenta
            $sumCap=0.0; $sumDeu=0.0;
            $agentes = collect(); $clasifs = collect(); $carteras = collect(); $titulares = collect();
            $cuentas = [];

            foreach ($ops as $op) {
                $cc = $ccByOp[$op] ?? null;
                if (!$cc) continue;

                $sumCap += (float)($cc->saldo_capital ?? 0);
                $sumDeu += (float)($cc->deuda_total   ?? 0);

                if ($cc->agente)        $agentes->push($cc->agente);
                if ($cc->clasificacion) $clasifs->push($cc->clasificacion);
                if ($cc->cartera)       $carteras->push($cc->cartera);
                if ($cc->titular)       $titulares->push($cc->titular);

                $cuentas[] = [
                    'operacion'          => (string)$cc->operacion,
                    'anio_castigo'       => $cc->anio_castigo,
                    'entidad'            => (string)($cc->entidad ?? ''),
                    'producto'           => (string)($cc->producto ?? ''),
                    'saldo_capital'      => (float)($cc->saldo_capital ?? 0),
                    'deuda_total'        => (float)($cc->deuda_total ?? 0),
                    'hasta'              => is_null($cc->hasta) ? null : (float)$cc->hasta, // 0–1
                    'capital_descuento'  => (float)($cc->capital_descuento ?? 0),           // S/
                ];
            }

            $uniq = fn($c)=>$c->filter()->unique()->implode(' / ');
            $p->asesor_nombre = $uniq($agentes);          // Equipo = AGENTE
            $p->clasificacion = $uniq($clasifs);          // Clasificación SBS
            $p->cartera       = $uniq($carteras);
            $p->titular       = $uniq($titulares);

            $p->deuda_total   = $sumDeu;                  // Deuda total = suma de deudas
            $p->saldo_capital = $sumCap;                  // (por si lo usas en cálculos)

            $p->cuentas_json  = $cuentas;                 // << para el acordeón

            return $p;
        });

        // ===== Cronogramas (igual que antes)
        $ids = $rows->pluck('id')->filter()->all();
        $cuotasById = collect();
        if (!empty($ids) && Schema::hasTable('promesa_cuotas')) {
            $cuotasById = DB::table('promesa_cuotas')
                ->select('promesa_id','nro','fecha','monto','es_balon')
                ->whereIn('promesa_id', $ids)
                ->orderBy('promesa_id')->orderBy('nro')
                ->get()
                ->groupBy('promesa_id');
        }
        $rows = $rows->map(function($p) use ($cuotasById){
            $list = $cuotasById[$p->id] ?? collect();
            $p->has_balon   = (int)$list->contains('es_balon', 1);
            $p->cuotas_json = $list->map(function($c){
                return [
                    'nro'      => (int)($c->nro ?? 0),
                    'fecha'    => (string)($c->fecha ?? '—'),
                    'monto'    => (float)($c->monto ?? 0),
                    'es_balon' => (bool)($c->es_balon ?? false),
                ];
            })->values();
            return $p;
        });

        // ===== CNA (sin cambios relevantes)
        $cnaBase = CnaSolicitud::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function($x) use ($q){
                    $x->where('dni','like',"%{$q}%")
                      ->orWhere('nro_carta','like',"%{$q}%")
                      ->orWhere('producto','like',"%{$q}%")
                      ->orWhere('observacion','like',"%{$q}%");
                });
            });

        if (strtolower($user->role) === 'supervisor') {
            $cnaBase->where('workflow_estado','pendiente');
        } else {
            $cnaBase->where('workflow_estado','preaprobada');
        }
        if (!empty($status)) {
            $cnaBase->where('workflow_estado', $status);
        }

        $cnaRows = $cnaBase->orderByDesc('created_at')
            ->paginate(10, ['*'], 'page_cna')
            ->withQueryString();

        // Mapa para mostrar producto en la tabla CNA (opcional)
        $opsAllCna = collect($cnaRows->items())
            ->flatMap(fn($c) => (array)($c->operaciones ?? []))
            ->filter()->map(fn($op)=>(string)$op)->unique()->values()->all();

        $prodByOp = [];
        if (!empty($opsAllCna)) {
            $prodByOp = DB::table('clientes_cuentas')
                ->select('operacion','producto')
                ->whereIn('operacion', $opsAllCna)
                ->get()
                ->mapWithKeys(fn($r) => [(string)$r->operacion => (string)($r->producto ?? '—')])
                ->all();
        }

        return view('autorizacion.index', [
            'rows'         => $rows,
            'cnaRows'      => $cnaRows,
            'prodByOp'     => $prodByOp,
            'q'            => $q,
            'isSupervisor' => strtolower($user->role) === 'supervisor',
        ]);

    }
    // ===== SUPERVISOR =====
    public function preaprobar(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('supervisor');

        if (($promesa->workflow_estado ?? 'pendiente') !== 'pendiente') {
            return back()->withErrors('Solo se puede pre-aprobar una promesa Pendiente.');
        }

        $promesa->update([
            'workflow_estado'     => 'preaprobada',
            'pre_aprobado_por'    => Auth::id(),
            'pre_aprobado_at'     => now(),
            'nota_preaprobacion'  => trim((string)$req->input('nota_estado')) ?: null,
            'rechazado_por'       => null,
            'rechazado_at'        => null,
            'nota_rechazo'        => null,
        ]);

        WorkflowMailer::promesaPreaprobada($promesa);
        return back()->with('ok', 'Promesa pre-aprobada.');
    }
    public function rechazarSup(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('supervisor');

        if (($promesa->workflow_estado ?? 'pendiente') !== 'pendiente') {
            return back()->withErrors('Solo se puede rechazar una promesa Pendiente.');
        }

        $promesa->update([
            'workflow_estado' => 'rechazada_sup',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);

        return back()->with('ok', 'Promesa rechazada por supervisor.');
    }
    // ===== ADMIN =====
    public function aprobar(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('administrador');

        if (($promesa->workflow_estado ?? '') !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una promesa Pre-aprobada.');
        }

        $promesa->update([
            'workflow_estado'    => 'aprobada',
            'aprobado_por'       => Auth::id(),
            'aprobado_at'        => now(),
            'nota_aprobacion'    => trim((string)$req->input('nota_estado')) ?: null,
            'rechazado_por'      => null,
            'rechazado_at'       => null,
            'nota_rechazo'       => null,
        ]);

        WorkflowMailer::promesaResuelta($promesa, true, $req->input('nota_estado'));
        return back()->with('ok', 'Promesa APROBADA.');
    }
    public function rechazarAdmin(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('administrador');

        if (($promesa->workflow_estado ?? '') !== 'preaprobada') {
            return back()->withErrors('Solo se puede rechazar una promesa Pre-aprobada.');
        }

        $promesa->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);

        WorkflowMailer::promesaResuelta($promesa, false, $req->input('nota_estado'));
        return back()->with('ok', 'Promesa rechazada por administrador.');
    }
    private function authorizeActionFor(string $role)
    {
        $user = Auth::user();
        if (!in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }
    // ===== LISTA PAGOS =====
    public function pagosDni(string $dni)
    {
        $propia = PagoPropia::where('dni',$dni)->select(
            DB::raw('DATE(fecha_de_pago) as fecha'),
            DB::raw('pagado_en_soles as monto'),
            'operacion as oper',
            DB::raw("UPPER(COALESCE(gestor, equipos, '-')) as gestor"),
            DB::raw("UPPER(COALESCE(status, '-')) as estado")
        );

        $castig = PagoCajaCuscoCastigada::where('dni',$dni)->select(
            DB::raw('DATE(fecha_de_pago) as fecha'),
            DB::raw('pagado_en_soles as monto'),
            DB::raw('pagare as oper'),
            DB::raw("'-' as gestor"),
            DB::raw("UPPER('-') as estado")
        );

        $extra = PagoCajaCuscoExtrajudicial::where('dni',$dni)->select(
            DB::raw('DATE(fecha_de_pago) as fecha'),
            DB::raw('pagado_en_soles as monto'),
            DB::raw('pagare as oper'),
            DB::raw("'-' as gestor"),
            DB::raw("UPPER('-') as estado")
        );

        $rows = $propia->get()->concat($castig->get())->concat($extra->get())
            ->sortByDesc('fecha')->values()->map(function($r){
                return [
                    'oper'   => (string)($r->oper ?? ''),
                    'fecha'  => (string)($r->fecha ?? ''),
                    'monto'  => (float) ($r->monto ?? 0),
                    'gestor' => (string)($r->gestor ?? ''),
                    'estado' => strtoupper((string)($r->estado ?? '')),
                ];
            });

        return response()->json(['dni'=>$dni,'pagos'=>$rows], 200);
    }
}
