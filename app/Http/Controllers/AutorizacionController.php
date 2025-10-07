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
        try {
            $user   = Auth::user();
            $q      = trim((string)($req->q ?? ''));
            $status = $req->status;

            // ===== PROMESAS (sin joins que dupliquen filas) =====
            $promesas = PromesaPago::query()
                ->with(['operaciones']) // detalle de operaciones
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('dni','like',"%{$q}%")
                        ->orWhere('nota','like',"%{$q}%")
                        ->orWhere('operacion','like',"%{$q}%") // legacy
                        ->orWhereHas('operaciones', fn($qq)=>$qq->where('operacion','like',"%{$q}%"));
                    });
                });

            if (strtolower($user->role) === 'supervisor') {
                $promesas->where('workflow_estado','pendiente');
            } else {
                $promesas->where('workflow_estado','preaprobada');
            }
            if (!empty($status)) {
                $promesas->where('workflow_estado', $status);
            }

            /** @var \Illuminate\Support\Collection $rows */
            $rows = $promesas->orderByDesc('fecha_promesa')->get();

            // ===== Universo de operaciones y DNIs (para traer cuentas) =====
            $opsByPromesa = $rows->mapWithKeys(function($p){
                $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
                    ? $p->operaciones->pluck('operacion')->map(fn($x)=>(string)$x)->values()
                    : collect(array_filter(array_map('trim', explode(',', (string)($p->operacion ?? '')))));
                return [$p->id => $ops];
            });

            $opsUniverse = $opsByPromesa->flatten()->filter()->unique()->values()->all();
            $dnis        = $rows->pluck('dni')->filter()->unique()->values()->all();

            // ===== Traer cuentas: Operación + DNI (incluye HASTA y CAPITAL_DESCUENTO) =====
            $ccAll = collect();
            if (!empty($dnis)) {
                $ccAll = DB::table('clientes_cuentas')
                    ->select(
                        'dni','operacion','anio_castigo','entidad','producto',
                        'saldo_capital','deuda_total','hasta','capital_descuento'
                    )
                    ->whereIn('dni', $dnis)
                    ->when(!empty($opsUniverse), fn($q2) => $q2->whereIn('operacion', $opsUniverse))
                    ->get()
                    ->mapWithKeys(fn($r) => [ ($r->dni.'|'.$r->operacion) => $r ]);
            }

            // ===== Agregados + JSON de cuentas por promesa (para el modal acordeón) =====
            $rows = $rows->map(function($p) use ($opsByPromesa, $ccAll) {
                $ops = $opsByPromesa[$p->id] ?? collect();

                // para listados
                $p->operacion = $ops->implode(', ');  // ej. "123, 456"
                $p->ops_list  = $ops->values();

                $titulares = collect(); $entidades = collect(); $productos = collect();
                $sumCap = 0.0; $sumDeu = 0.0;

                $cuentas = $ops->map(function($op) use ($p, $ccAll, &$titulares, &$entidades, &$productos, &$sumCap, &$sumDeu){
                    $k  = $p->dni.'|'.$op;
                    $cc = $ccAll[$k] ?? null;
                    if (!$cc) return null;
                    if ($cc->entidad)  $entidades->push($cc->entidad);
                    if ($cc->producto) $productos->push($cc->producto);
                    $sumCap += (float)($cc->saldo_capital ?? 0);
                    $sumDeu += (float)($cc->deuda_total ?? 0);

                    return [
                        'operacion'         => (string)($cc->operacion ?? ''),
                        'anio_castigo'      => (int)($cc->anio_castigo ?? 0),
                        'entidad'           => (string)($cc->entidad ?? '—'),
                        'producto'          => (string)($cc->producto ?? '—'),
                        'saldo_capital'     => (float)($cc->saldo_capital ?? 0),
                        'deuda_total'       => (float)($cc->deuda_total ?? 0),
                        'hasta'             => is_null($cc->hasta) ? null : (float)$cc->hasta,        // fracción 0..1
                        'capital_descuento' => (float)($cc->capital_descuento ?? 0),                  // monto S/
                    ];
                })->filter()->values();

                $p->entidad        = $entidades->unique()->implode(' / ');
                $p->producto       = $productos->unique()->implode(' / ');
                $p->saldo_capital  = $sumCap;
                $p->deuda_total    = $sumDeu;
                $p->monto_campania = max(0, $sumDeu - $sumCap);
                $p->porc_descuento = $sumDeu > 0 ? round(100 * ($p->monto_campania) / $sumDeu, 2) : null;

                $p->cuentas_json   = $cuentas; // ← para el acordeón del modal
                return $p;
            });

            // ===== Cronogramas =====
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

            // ===== CNA (igual que venías usando) =====
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

            // Mapa op->producto para CNA
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
        } catch (\Throwable $e) {
            \Log::error('Autorizacion.index ERROR', [
                'msg'   => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return response('Error en Autorización: '.$e->getMessage(), 500);
        }
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
