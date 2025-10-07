<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutorizacionController extends Controller
{
    public function index(Request $req)
    {
        $user   = Auth::user();
        $q      = trim((string)($req->q ?? ''));
        $status = $req->status;

        // ===== PROMESAS (evitar joins que dupliquen)
        $promesas = PromesaPago::query()
            ->with(['operaciones'])                // detalle de operaciones
            ->leftJoin('users as u','u.id','=','promesas_pago.user_id') // creador
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('promesas_pago.dni', 'like', "%{$q}%")
                        ->orWhere('promesas_pago.nota', 'like', "%{$q}%")
                        ->orWhere('promesas_pago.operacion', 'like', "%{$q}%")
                        ->orWhereHas('operaciones', fn($qq)=>$qq->where('operacion','like',"%{$q}%"));
                });
            });

        // Bandeja por rol
        if (strtolower($user->role) === 'supervisor') {
            $promesas->where('promesas_pago.workflow_estado','pendiente');
        } else {
            $promesas->where('promesas_pago.workflow_estado','preaprobada');
        }
        if (!empty($status)) {
            $promesas->where('promesas_pago.workflow_estado', $status);
        }

        // Traemos columnas propias (ojo con el select, por el leftJoin a users)
        $rows = $promesas->select('promesas_pago.*','u.name as creador_nombre')
                            ->orderByDesc('promesas_pago.fecha_promesa')
                            ->get();

        // === Traer info de cuentas solo para las operaciones de estas promesas ===
        $opsAll = $rows->flatMap(function($p){
                if ($p->relationLoaded('operaciones') && $p->operaciones->count()) {
                    return $p->operaciones->pluck('operacion');
                }
                return collect(array_filter(array_map('trim', explode(',', (string)($p->operacion ?? '')))));
            })
            ->filter()->unique()->values()->all();

        $ccByOp = [];
        if (!empty($opsAll)) {
            $ccByOp = DB::table('clientes_cuentas')
                ->select([
                    'operacion','cartera','titular','entidad','producto','moneda',
                    'saldo_capital','deuda_total','agente','anio_castigo',
                    'hasta','capital_descuento'
                ])
                ->whereIn('operacion', $opsAll)
                ->get()
                ->keyBy('operacion');
        }

        // === Armar agregados y el JSON de "cuentas" (para el acordeón de la ficha) ===
        $rows = $rows->map(function($p) use ($ccByOp) {

            // Lista de operaciones
            $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
                ? $p->operaciones->pluck('operacion')->map(fn($x)=>(string)$x)->values()
                : collect(array_filter(array_map('trim', explode(',', (string)($p->operacion ?? '')))));

            // Texto legacy para la tabla
            $p->operacion = $ops->implode(', ');
            $p->ops_list  = $ops->values();

            // Acumuladores + datos por cuenta
            $sumCap = 0.0; $sumDeu = 0.0;
            $cuentas = [];

            $titulares = collect(); $carteras = collect();

            foreach ($ops as $op) {
                $cc = $ccByOp[$op] ?? null;
                if (!$cc) continue;

                $sumCap += (float)($cc->saldo_capital ?? 0);
                $sumDeu += (float)($cc->deuda_total ?? 0);

                if ($cc->titular)  $titulares->push($cc->titular);
                if ($cc->cartera)  $carteras->push($cc->cartera);

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

            // Agregados
            $p->saldo_capital   = $sumCap;               // capital agregado (por si te sirve)
            $p->deuda_total     = $sumDeu;               // **Deuda total = suma de deudas**
            $p->titular         = $titulares->filter()->unique()->implode(' / ');
            $p->cartera         = $carteras->filter()->unique()->implode(' / ');
            $p->asesor_nombre   = null;                  // puedes poblarlo si lo necesitas

            // JSON para la ficha (acordeón)
            $p->cuentas_json    = $cuentas;

            return $p;
        });

        // ===== Cronogramas (opcional, como lo tenías) =====
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
            $p->cuotas      = $list;
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

        // ===== CNA (igual que lo tenías) =====
        $cnaBase = CnaSolicitud::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function($x) use ($q){
                    $x->where('dni','like',"%{$q}%")
                        ->orWhere('nro_carta','like',"%{$q}%")
                        ->orWhere('producto','like',"%{$q}%")
                        ->orWhere('nota','like',"%{$q}%");
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

        // Mapa op->producto para CNA (si lo usas en la tabla)
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

        return back()->with('ok', 'Promesa rechazada por administrador.');
    }
    private function authorizeActionFor(string $role)
    {
        $user = Auth::user();
        if (!in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }
}
