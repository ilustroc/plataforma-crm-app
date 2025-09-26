<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\PagoPropia;
use App\Models\PagoCajaCuscoCastigada;
use App\Models\PagoCajaCuscoExtrajudicial;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        // cartera: propia | caja-cusco-castigada | caja-cusco-extrajudicial (también acepta "extrajudicial")
        $cartera = $r->query('cartera', 'propia');
        if ($cartera === 'extrajudicial') $cartera = 'caja-cusco-extrajudicial';
    
        $mesParam   = $r->query('mes', now('America/Lima')->format('Y-m')); // YYYY-MM
        $supervisor = (int) $r->query('supervisor_id', 0);
    
        // Rangos de fechas (Lima)
        try {
            $inicioMes = \Carbon\Carbon::createFromFormat('Y-m', $mesParam, 'America/Lima')->startOfMonth();
        } catch (\Throwable $e) {
            $inicioMes = now('America/Lima')->startOfMonth();
            $mesParam  = $inicioMes->format('Y-m');
        }
        $finMes = (clone $inicioMes)->endOfMonth();
    
        $ini12 = (clone $inicioMes)->subMonths(11)->startOfMonth();
        $fin12 = (clone $finMes)->endOfMonth();
    
        // Builder base + expresión de monto según cartera
        switch ($cartera) {
            case 'caja-cusco-castigada':
                $model = PagoCajaCuscoCastigada::query();
                $montoExpr = 'COALESCE(pagado_en_soles,0)';
                break;
            case 'caja-cusco-extrajudicial':
                $model = PagoCajaCuscoExtrajudicial::query();
                // Total = Monto pagado + Pagado en S/
                $montoExpr = 'COALESCE(monto_pagado,0)';
                break;
            default:
                $model = PagoPropia::query();
                $montoExpr = 'COALESCE(pagado_en_soles,0)';
                break;
        }
    
        // (Opcional) filtro por supervisor (si implementas mapping gestor<-asesores)
        if ($supervisor > 0) {
            $sup = User::find($supervisor);
            // TODO: $aliases = $sup?->asesores()->pluck('alias')->filter();
            // if ($aliases && $aliases->count()) { $model->whereIn('gestor', $aliases); }
        }
    
        // KPIs del mes
        $k = [
            'ccd_gen'      => 0,
            'pagos_num'    => (clone $model)->whereBetween('fecha_de_pago', [$inicioMes, $finMes])->count(),
            'pagos_monto'  => (float) (clone $model)->whereBetween('fecha_de_pago', [$inicioMes, $finMes])
                                    ->selectRaw("SUM($montoExpr) as total")->value('total') ?? 0.0,
            'pdp_gen'      => 0,
            'pdp_vig'      => 0,
            'pdp_cumpl'    => 0,
            'pdp_caidas'   => 0,
        ];
    
        // Serie últimos 12 meses
        $serieRaw = (clone $model)
            ->selectRaw("DATE_FORMAT(fecha_de_pago,'%Y-%m') as ym, SUM($montoExpr) as total")
            ->whereBetween('fecha_de_pago', [$ini12, $fin12])
            ->groupBy('ym')->orderBy('ym')
            ->pluck('total','ym');
    
        $meses = []; $serie_pagos = [];
        $cursor = $ini12->copy();
        for ($i=0; $i<12; $i++) {
            $key = $cursor->format('Y-m');
            $meses[] = strtoupper($cursor->locale('es')->isoFormat('MMM')); // SEP, OCT...
            $serie_pagos[] = (float) ($serieRaw[$key] ?? 0);
            $cursor->addMonth();
        }
    
        $supervisores = User::where('role','supervisor')->select('id','name')->orderBy('name')->get();
    
        return view('dashboard.index', [
            'mes'            => $mesParam,
            'cartera'        => $cartera,
            'supervisorId'   => $supervisor,
            'supervisores'   => $supervisores,
            'k'              => $k,
            'meses'          => $meses,
            'serie_pagos'    => $serie_pagos,
            'gestiones'      => collect(),
        ]);
    }
}
