<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;

class PanelController extends Controller
{
    public function index(Request $r)
    {
        $user  = Auth::user();
        $role  = strtolower($user->role ?? '');
        $isSupervisor = ($role === 'supervisor');

        /* ===== PENDIENTES ===== */
        $ppQuery = PromesaPago::query()
            ->select('id','dni','operacion','fecha_promesa','monto','monto_convenio','nota','tipo')
            ->orderByDesc('created_at');

        $ppPendCount = (clone $ppQuery)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->count();

        $ppPend = (clone $ppQuery)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->limit(5)->get()
            ->map(function($p){
                $p->monto_mostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
                return $p;
            });

        $cnaQuery = CnaSolicitud::query()->orderByDesc('created_at');
        $cnaPendCount = (clone $cnaQuery)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->count();
        $cnaPend = (clone $cnaQuery)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->select('id','dni','nro_carta','operaciones','observacion','created_at')
            ->limit(5)->get();

        /* ===== PRÓXIMOS VENCIMIENTOS (7 días) ===== */
        $venc = collect(); $vencCount = 0;
        if (Schema::hasTable('promesa_cuotas')) {
            $hoy = Carbon::today();
            $hasta = Carbon::today()->addDays(7);

            $venc = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$hoy->toDateString(), $hasta->toDateString()])
                ->select('p.dni','p.operacion','p.tipo','c.promesa_id','c.nro','c.fecha','c.monto')
                ->orderBy('c.fecha')
                ->limit(8)
                ->get();

            $vencCount = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$hoy->toDateString(), $hasta->toDateString()])
                ->count();
        }

        /* ===== PAGOS RECIENTES (últimos 10) ===== */
        $pagos = collect();
        if (Schema::hasTable('pagos_propia') ||
            Schema::hasTable('pagos_caja_cusco_castigada') ||
            Schema::hasTable('pagos_caja_cusco_extrajudicial')) {

            $q1 = Schema::hasTable('pagos_propia') ? DB::table('pagos_propia')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('operacion as oper'),
                    DB::raw("UPPER(COALESCE(gestor, equipos, '-')) as gestor"),
                    DB::raw("UPPER(COALESCE(status, '-')) as estado"),
                ]) : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $q2 = Schema::hasTable('pagos_caja_cusco_castigada') ? DB::table('pagos_caja_cusco_castigada')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('pagare as oper'),
                    DB::raw("'-' as gestor"),
                    DB::raw("UPPER('-') as estado"),
                ]) : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $q3 = Schema::hasTable('pagos_caja_cusco_extrajudicial') ? DB::table('pagos_caja_cusco_extrajudicial')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('pagare as oper'),
                    DB::raw("'-' as gestor"),
                    DB::raw("UPPER('-') as estado"),
                ]) : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $union = $q1->unionAll($q2)->unionAll($q3);
            $pagos = DB::query()->fromSub($union,'p')
                ->orderByDesc('fecha')->limit(10)->get();
        }

        /* ===== KPIs rápidos ===== */
        $hoy = Carbon::today()->toDateString();
        $kpiPromHoy = PromesaPago::whereDate('created_at',$hoy)->count();
        $kpiPagosHoy = Schema::hasTable('pagos_propia')
            ? (float) DB::table('pagos_propia')->whereDate('fecha_de_pago',$hoy)->sum('pagado_en_soles')
            : 0.0;

        return view('panel.resumen', compact(
            'isSupervisor',
            'ppPendCount','ppPend',
            'cnaPendCount','cnaPend',
            'venc','vencCount',
            'pagos','kpiPromHoy','kpiPagosHoy'
        ));
    }
}
