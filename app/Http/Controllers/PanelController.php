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

        /* ===== PROMESAS PENDIENTES (según rol) ===== */
        $ppBase = PromesaPago::query()
            ->select('id','dni','operacion','fecha_promesa','monto','monto_convenio','nota','tipo')
            ->orderByDesc('created_at');

        $ppPendCount = (clone $ppBase)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->count();

        $ppPend = (clone $ppBase)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->limit(5)->get()
            ->map(function($p){
                $p->monto_mostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
                return $p;
            });

        /* ===== CNA PENDIENTES (según rol) ===== */
        $cnaBase = CnaSolicitud::query()->orderByDesc('created_at');

        $cnaPendCount = (clone $cnaBase)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->count();

        $cnaPend = (clone $cnaBase)
            ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                               fn($q)=>$q->where('workflow_estado','preaprobada'))
            ->select('id','dni','nro_carta','operaciones','observacion','created_at')
            ->limit(5)->get();

        /* ===== PRÓXIMOS VENCIMIENTOS (7 días) ===== */
        $venc = collect(); $vencCount = 0;
        if (Schema::hasTable('promesa_cuotas')) {
            $hoy   = Carbon::today()->toDateString();
            $hasta = Carbon::today()->addDays(7)->toDateString();

            $venc = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$hoy, $hasta])
                ->select('p.dni','p.operacion','p.tipo','c.promesa_id','c.nro','c.fecha','c.monto')
                ->orderBy('c.fecha')
                ->limit(8)
                ->get();

            $vencCount = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$hoy, $hasta])
                ->count();
        }

        /* ===== PAGOS RECIENTES (últimos 10) – se mantiene por si luego lo usas ===== */
        $pagos = collect();
        if (
            Schema::hasTable('pagos_propia') ||
            Schema::hasTable('pagos_caja_cusco_castigada') ||
            Schema::hasTable('pagos_caja_cusco_extrajudicial')
        ) {
            $q1 = Schema::hasTable('pagos_propia')
                ? DB::table('pagos_propia')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('operacion as oper'),
                    DB::raw("UPPER(COALESCE(gestor, equipos, '-')) as gestor"),
                    DB::raw("UPPER(COALESCE(status, '-')) as estado"),
                ])
                : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $q2 = Schema::hasTable('pagos_caja_cusco_castigada')
                ? DB::table('pagos_caja_cusco_castigada')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('pagare as oper'),
                    DB::raw("'-' as gestor"),
                    DB::raw("UPPER('-') as estado"),
                ])
                : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $q3 = Schema::hasTable('pagos_caja_cusco_extrajudicial')
                ? DB::table('pagos_caja_cusco_extrajudicial')->select([
                    DB::raw('DATE(fecha_de_pago) as fecha'),
                    DB::raw('pagado_en_soles as monto'),
                    DB::raw('pagare as oper'),
                    DB::raw("'-' as gestor"),
                    DB::raw("UPPER('-') as estado"),
                ])
                : DB::query()->selectRaw("'0000-00-00' as fecha, 0 as monto, '' as oper, '-' as gestor, '-' as estado")->whereRaw('0=1');

            $union = $q1->unionAll($q2)->unionAll($q3);
            $pagos = DB::query()->fromSub($union,'p')
                ->orderByDesc('fecha')
                ->limit(10)
                ->get();
        }

        /* ===== KPIs (hoy) ===== */
        $hoy = Carbon::today()->toDateString();
        $kpiPromHoy  = PromesaPago::whereDate('created_at',$hoy)->count();
        $kpiPagosHoy = Schema::hasTable('pagos_propia')
            ? (float) DB::table('pagos_propia')->whereDate('fecha_de_pago',$hoy)->sum('pagado_en_soles')
            : 0.0;

        /* ===== GRÁFICO: pagos del mes (unión de todas las carteras) ===== */
        $mes = $r->input('mes', Carbon::today()->format('Y-m')); // 'YYYY-MM'
        try {
            $ini = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        } catch (\Exception $e) {
            $ini = Carbon::today()->startOfMonth();
            $mes = $ini->format('Y-m');
        }
        $fin = (clone $ini)->endOfMonth();

        $unionParts = [];

        if (Schema::hasTable('pagos_propia')) {
            $unionParts[] = DB::table('pagos_propia')
                ->whereBetween('fecha_de_pago', [$ini->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(fecha_de_pago) as f, pagado_en_soles as s');
        }
        if (Schema::hasTable('pagos_caja_cusco_castigada')) {
            $unionParts[] = DB::table('pagos_caja_cusco_castigada')
                ->whereBetween('fecha_de_pago', [$ini->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(fecha_de_pago) as f, pagado_en_soles as s');
        }
        if (Schema::hasTable('pagos_caja_cusco_extrajudicial')) {
            $unionParts[] = DB::table('pagos_caja_cusco_extrajudicial')
                ->whereBetween('fecha_de_pago', [$ini->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(fecha_de_pago) as f, pagado_en_soles as s');
        }

        $daily = collect();
        if (count($unionParts)) {
            $union = array_shift($unionParts);
            foreach ($unionParts as $q) {
                $union->unionAll($q);
            }

            $daily = DB::query()->fromSub($union, 't')
                ->selectRaw('f, SUM(s) as total')
                ->groupBy('f')
                ->orderBy('f')
                ->get()
                ->keyBy('f');
        }

        $days       = $ini->daysInMonth;
        $chartLabels = [];
        $chartData   = [];
        $sumMes      = 0.0;

        for ($d = 1; $d <= $days; $d++) {
            $date = $ini->copy()->day($d)->toDateString();
            $chartLabels[] = str_pad($d,2,'0',STR_PAD_LEFT);
            $val = (float) ($daily[$date]->total ?? 0);
            $chartData[] = round($val, 2);
            $sumMes += $val;
        }

        /* ===== TABLA COMPLETA: pagos_propia (filtro por mes + búsqueda + paginación) ===== */
        $busq = trim($r->input('q', ''));

        $propias = collect();
        $propiasTotal = 0.0;

        if (Schema::hasTable('pagos_propia')) {
            $basePropias = DB::table('pagos_propia')
                ->when($mes, fn($q)=>$q->whereBetween('fecha_de_pago', [$ini->toDateString(), $fin->toDateString()]))
                ->when($busq !== '', function($q) use ($busq){
                    $q->where(function($w) use ($busq){
                        $w->where('dni','like',"%{$busq}%")
                          ->orWhere('operacion','like',"%{$busq}%")
                          ->orWhere('entidad','like',"%{$busq}%")
                          ->orWhere('equipos','like',"%{$busq}%")
                          ->orWhere('nombre_cliente','like',"%{$busq}%")
                          ->orWhere('gestor','like',"%{$busq}%")
                          ->orWhere('status','like',"%{$busq}%");
                    });
                });

            $propias = (clone $basePropias)
                ->select('dni','operacion','entidad','equipos','nombre_cliente','producto','moneda',
                         'fecha_de_pago','monto_pagado','pagado_en_soles','gestor','status')
                ->orderByDesc('fecha_de_pago')
                ->paginate(25)
                ->withQueryString();

            $propiasTotal = (float) (clone $basePropias)->sum('pagado_en_soles');
        }

        return view('panel.resumen', compact(
            'isSupervisor',
            'ppPendCount','ppPend',
            'cnaPendCount','cnaPend',
            'venc','vencCount',
            'pagos','kpiPromHoy','kpiPagosHoy',
            'mes','chartLabels','chartData','sumMes',
            'propias','propiasTotal','busq'
        ));
    }
}
