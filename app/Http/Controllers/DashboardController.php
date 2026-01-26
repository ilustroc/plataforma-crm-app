<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Pagos;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        // 1. Filtros Básicos
        $mesParam = $r->query('mes', now('America/Lima')->format('Y-m')); // YYYY-MM
        $gestor   = $r->query('gestor'); // Filtrar por nombre de gestor si es necesario

        // 2. Definir Rangos de Fecha
        try {
            $inicioMes = Carbon::createFromFormat('Y-m', $mesParam, 'America/Lima')->startOfMonth();
        } catch (\Throwable $e) {
            $inicioMes = now('America/Lima')->startOfMonth();
            $mesParam  = $inicioMes->format('Y-m');
        }
        $finMes = (clone $inicioMes)->endOfMonth();

        // Rango anual para la gráfica (últimos 12 meses)
        $ini12 = (clone $inicioMes)->subMonths(11)->startOfMonth();
        $fin12 = (clone $finMes)->endOfMonth();

        // 3. Query Base
        $query = Pagos::query();

        // (Opcional) Filtro de gestor si tu tabla pagos tiene esa columna llena
        if ($gestor) {
            $query->where('gestor', 'like', "%$gestor%");
        }

        // 4. Calcular KPIs del Mes Actual
        // Clonamos el query para no afectar las siguientes consultas
        $kpiQuery = (clone $query)->whereBetween('fecha', [$inicioMes, $finMes]);

        $kpis = [
            'pagos_count' => $kpiQuery->count(),
            'pagos_sum'   => (float) $kpiQuery->sum('monto'),
            // Aquí podrías agregar lógica para PDP si tienes una tabla de promesas relacionada
            'pdp_gen'     => 0, 
            'pdp_cumpl'   => 0
        ];

        // 5. Datos para la Gráfica (Evolución 12 meses)
        // Agrupamos por año-mes y sumamos el monto
        $serieData = (clone $query)
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as ym, SUM(monto) as total")
            ->whereBetween('fecha', [$ini12, $fin12])
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        // Rellenar meses vacíos con 0
        $labels = [];
        $values = [];
        $cursor = $ini12->copy();
        
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            // Formato etiqueta: "Ene", "Feb"...
            $labels[] = ucfirst($cursor->locale('es')->isoFormat('MMM')); 
            $values[] = (float) ($serieData[$key] ?? 0);
            $cursor->addMonth();
        }

        // 6. Obtener lista de gestores para el select (agrupando los que existen en pagos)
        $gestores = Pagos::select('gestor')->distinct()->whereNotNull('gestor')->orderBy('gestor')->pluck('gestor');

        return view('dashboard.index', [
            'mes'      => $mesParam,
            'gestor'   => $gestor,
            'gestores' => $gestores,
            'kpis'     => $kpis,
            'chart'    => [
                'labels' => $labels,
                'data'   => $values
            ]
        ]);
    }
}