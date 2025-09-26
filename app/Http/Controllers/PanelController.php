<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\PagoPropia;
use App\Models\PagoCajaCuscoCastigada;
use App\Models\PagoCajaCuscoExtrajudicial;

class PanelController extends Controller
{
    public function index()
    {
        // Renderiza tu “Home / Panel”. Si ya tenías otro nombre de vista,
        // cámbialo aquí.
        return view('panel.index');
    }
    public function dashboard(Request $r)
    {
        // Filtros
        $cartera = $r->query('cartera', 'propia', 'extrajudicial');          // propia | caja-cusco-castigada | extrajudicial
        $mes     = $r->query('mes', now()->format('Y-m'));  // YYYY-MM
        $gestor  = $r->query('gestor');                     // opcional (alias / nombre)
        [$y,$m]  = explode('-', $mes);
        $from    = Carbon::createFromDate((int)$y,(int)$m,1)->startOfMonth();
        $to      = $from->copy()->endOfMonth();

        // Mapeo cartera -> modelo y columna de dinero
        $map = [
            'propia' => [
                'model' => PagoPropia::query(),
                'money' => 'pagado_en_soles',   // <<< importante
            ],
            'caja-cusco-castigada' => [
                'model' => PagoCajaCuscoCastigada::query(),
                'money' => 'pagado_en_soles',   // usamos este como “monto pagado”
            ],
            'caja-cusco-extrajudicial' => [
                'model' => PagoCajaCuscoExtrajudicial::query(),
                'money' => 'pagado_en_soles',   // usamos este como “monto pagado”
            ],        ];
        $cfg = $map[$cartera] ?? $map['propia'];
        $qb  = $cfg['model']->whereBetween('fecha_de_pago', [$from->toDateString(), $to->toDateString()]);
        if ($gestor) { $qb->where('gestor','like',"%{$gestor}%"); }

        // KPIs
        $k = [
            'pagos_num'   => (clone $qb)->count(),
            'pagos_monto' => (clone $qb)->sum($cfg['money']),
            // si luego agregas más KPIs, colócalos aquí
        ];

        // Serie últimos 12 meses (suma mensual)
        $meses = [];
        $serie_pagos = [];
        for ($i=11; $i>=0; $i--) {
            $d = now()->startOfMonth()->subMonths($i);
            $meses[] = strtoupper($d->format('M'));
            $serie_pagos[] = (clone $cfg['model'])
                ->whereBetween('fecha_de_pago', [$d->toDateString(), $d->copy()->endOfMonth()->toDateString()])
                ->sum($cfg['money']);
        }
        $supervisores = User::where('role', 'supervisor')
            ->orderBy('name')->get(['id','name']);

        $supervisorId = $r->query('supervisor_id');

        return view('dashboard.index', compact(
            'k','mes','cartera','meses','serie_pagos',
            'supervisores','supervisorId'
        ));
    }

    private function pagosQuery(string $cartera)
    {
        return match ($cartera) {
            'caja-cusco-castigada' => PagoCajaCuscoCastigada::query(),
            'extrajudicial'        => PagoCajaCuscoExtrajudicial::query(),
            default                => PagoPropia::query(),
        };
    }
}
