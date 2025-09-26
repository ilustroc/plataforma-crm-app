<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteGestionesController extends Controller
{
    public function index(Request $r)
    {
        // Solo muestra la vista llana para descartar errores
        return view('reportes.gestiones');
    }

    public function export(Request $r)
    {
        return response("fecha,gestor,cliente,detalle\n", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gestiones.csv"',
        ]);
    }
}
