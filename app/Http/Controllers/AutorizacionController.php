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
    
            // ===== PROMESAS (lista sin paginar, como ya lo tenías) =====
            $base = PromesaPago::query()
                ->from('promesas_pago')
                ->leftJoin('clientes_cuentas as cc', function($j){
                    $j->on('cc.dni','=','promesas_pago.dni');
                    $j->where(function($w){
                        $w->whereNull('promesas_pago.operacion')
                          ->orWhereColumn('cc.operacion','promesas_pago.operacion');
                    });
                })
                ->leftJoin('users as u','u.id','=','promesas_pago.user_id')
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('promesas_pago.dni','like',"%{$q}%")
                          ->orWhere('promesas_pago.operacion','like',"%{$q}%")
                          ->orWhere('promesas_pago.nota','like',"%{$q}%")
                          ->orWhere('cc.titular','like',"%{$q}%");
                    });
                });
    
            if (in_array(strtolower($user->role), ['supervisor'])) {
                $base->where('promesas_pago.workflow_estado','pendiente');
            } else {
                $base->where('promesas_pago.workflow_estado','preaprobada');
            }
            if (!empty($status)) {
                $base->where('promesas_pago.workflow_estado', $status);
            }
    
            $rows = $base->select([
                    'promesas_pago.*',
                    'cc.cartera','cc.titular','cc.entidad','cc.producto','cc.moneda',
                    'cc.zona','cc.departamento',
                    'cc.agente as asesor_nombre',
                    'cc.anio_castigo','cc.propiedades',
                    'cc.laboral as trabajo','cc.clasificacion',
                    'cc.deuda_total','cc.saldo_capital','cc.interes',
                    DB::raw('(COALESCE(cc.deuda_total,0) - COALESCE(cc.saldo_capital,0)) as monto_campania'),
                    DB::raw('CASE WHEN COALESCE(cc.deuda_total,0)>0
                              THEN ROUND(100*(COALESCE(cc.deuda_total,0)-COALESCE(cc.saldo_capital,0))/cc.deuda_total,2)
                              ELSE NULL END as porc_descuento'),
                    'u.name as creador_nombre',
                ])
                ->orderByDesc('promesas_pago.fecha_promesa')
                ->get();
    
            // Cronogramas (si existe la tabla singular promesa_cuotas)
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
    
            // ===== CNA (pendientes para supervisor / preaprobadas para admin) =====
            $cnaBase = CnaSolicitud::query()
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function($x) use ($q){
                        $x->where('dni','like',"%{$q}%")
                          ->orWhere('nro_carta','like',"%{$q}%")
                          ->orWhere('producto','like',"%{$q}%")
                          ->orWhere('nota','like',"%{$q}%");
                    });
                });
    
            if (in_array(strtolower($user->role), ['supervisor'])) {
                $cnaBase->where('workflow_estado','pendiente');
            } else {
                $cnaBase->where('workflow_estado','preaprobada');
            }
            if (!empty($status)) {
                $cnaBase->where('workflow_estado', $status);
            }
    
            // paginamos CNA por su propia página (?page_cna=)
            $cnaRows = $cnaBase->orderByDesc('created_at')
                ->paginate(10, ['*'], 'page_cna')
                ->withQueryString();
    
            // === MAPA Operación -> Producto (solo para las CNA de esta página) ===
            $opsAll = collect($cnaRows->items())
                ->flatMap(fn($c) => (array)($c->operaciones ?? []))
                ->filter()
                ->map(fn($op) => (string)$op)
                ->unique()
                ->values()
                ->all();
    
            $prodByOp = [];
            if (!empty($opsAll)) {
                $prodByOp = DB::table('clientes_cuentas')
                    ->select('operacion','producto')
                    ->whereIn('operacion', $opsAll)
                    ->get()
                    ->mapWithKeys(fn($r) => [(string)$r->operacion => (string)($r->producto ?? '—')])
                    ->all();
            }
    
            return view('autorizacion.index', [
                'rows'         => $rows,
                'cnaRows'      => $cnaRows,
                'prodByOp'     => $prodByOp, // ← clave para la vista
                'q'            => $q,
                'isSupervisor' => in_array(strtolower($user->role), ['supervisor']),
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
