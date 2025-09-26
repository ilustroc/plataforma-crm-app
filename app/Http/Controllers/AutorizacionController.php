<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\PromesaPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\CnaSolicitud;

class AutorizacionController extends Controller
{
    public function index(Request $req)
    {
        try {
            $user   = Auth::user();
            $q      = trim((string)($req->q ?? ''));
            $status = $req->status;

            // Base (sin paginación)
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

            // Bandeja por rol (regla de negocio)
            if (in_array(strtolower($user->role), ['supervisor'])) {
                $base->where('promesas_pago.workflow_estado','pendiente');
            } else {
                $base->where('promesas_pago.workflow_estado','preaprobada');
            }

            // Filtro manual por status (opcional)
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
                ->get(); // SIN paginación

            // ====== CARGA DE CRONOGRAMAS (tabla real: promesa_cuotas) ======
            $ids = $rows->pluck('id')->filter()->all();
            $cuotasById = collect();

            if (!empty($ids) && Schema::hasTable('promesa_cuotas')) { // <— singular
                $cuotasById = DB::table('promesa_cuotas')            // <— singular
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

            return view('autorizacion.index', [
                'rows'         => $rows, // colección simple
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
    // ===== CNA: Bandeja =====
    public function cnaIndex(Request $req)
    {
        $user = Auth::user();
        $q    = trim((string)$req->q);

        $base = CnaSolicitud::query()
            ->when($q !== '', function($w) use ($q){
                $w->where('dni','like',"%$q%")
                ->orWhere('operacion','like',"%$q%")
                ->orWhere('nro_carta','like',"%$q%");
            });

        // misma regla que promesas
        if (in_array(strtolower($user->role), ['supervisor'])) {
            $base->where('workflow_estado','pendiente');
        } else {
            $base->where('workflow_estado','preaprobada');
        }

        $rows = $base->orderByDesc('id')->get();

        return view('autorizacion.cna', [
            'rows' => $rows,
            'q'    => $q,
            'isSupervisor' => in_array(strtolower($user->role), ['supervisor']),
        ]);
    }

    // ===== CNA: crear desde ficha del cliente =====
    public function cnaStore(string $dni, Request $r)
    {
        $r->validate([
            'operacion'       => 'required|string|max:50',
            'nro_carta'       => 'nullable|string|max:50',
            'fecha'           => 'required|date',
            'monto_negociado' => 'required|numeric|min:0.01',
        ]);

        CnaSolicitud::create([
            'dni'             => $dni,
            'operacion'       => $r->operacion,
            'nro_carta'       => $r->nro_carta ?: $this->nextCnaNumber(),
            'fecha'           => $r->fecha,
            'monto_negociado' => $r->monto_negociado,
            'workflow_estado' => 'pendiente',
            'user_id'         => Auth::id(),
        ]);

        return back()->with('ok','Solicitud de CNA registrada y enviada para autorización.');
    }

    private function nextCnaNumber(): string
    {
        $last = DB::table('cna_solicitudes')->orderByDesc('id')->value('nro_carta');
        $num  = (int)preg_replace('/\D/','', (string)$last);
        return str_pad($num + 1, 5, '0', STR_PAD_LEFT); // 00001, 00002, ...
    }

    // ===== CNA: decisiones =====
    public function cnaPreaprobar(Request $req, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('supervisor');
        if (($cna->workflow_estado ?? 'pendiente') !== 'pendiente')
            return back()->withErrors('Solo se puede pre-aprobar una CNA pendiente.');

        $cna->update([
            'workflow_estado'    => 'preaprobada',
            'pre_aprobado_por'   => Auth::id(),
            'pre_aprobado_at'    => now(),
            'nota_preaprobacion' => trim((string)$req->input('nota_estado')) ?: null,
            'rechazado_por'      => null,
            'rechazado_at'       => null,
            'nota_rechazo'       => null,
        ]);
        return back()->with('ok','CNA pre-aprobada.');
    }

    public function cnaRechazarSup(Request $req, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('supervisor');
        if (($cna->workflow_estado ?? 'pendiente') !== 'pendiente')
            return back()->withErrors('Solo se puede rechazar una CNA pendiente.');

        $cna->update([
            'workflow_estado' => 'rechazada_sup',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);
        return back()->with('ok','CNA rechazada por supervisor.');
    }

    public function cnaAprobar(Request $req, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('administrador');
        if (($cna->workflow_estado ?? '') !== 'preaprobada')
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');

        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
            'nota_aprobacion' => trim((string)$req->input('nota_estado')) ?: null,
            'rechazado_por'   => null,
            'rechazado_at'    => null,
            'nota_rechazo'    => null,
        ]);
        return back()->with('ok','CNA aprobada.');
    }

    public function cnaRechazarAdmin(Request $req, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('administrador');
        if (($cna->workflow_estado ?? '') !== 'preaprobada')
            return back()->withErrors('Solo se puede rechazar una CNA pre-aprobada.');

        $cna->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);
        return back()->with('ok','CNA rechazada por administrador.');
    }

}
