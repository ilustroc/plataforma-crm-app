<?php

namespace App\Http\Controllers\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integracion\StorePagosImportRequest;
use App\Infrastructure\Imports\PagosCsvImporter;

class PagosImportController extends Controller
{
    // GET /integracion/pagos
    public function create()
    {
        return view('integracion.pagos'); // <-- ajusta si tu vista tiene otro nombre
    }

    // POST /integracion/pagos/store
    public function store(StorePagosImportRequest $r, PagosCsvImporter $importer)
    {
        $path = $r->file('archivo')->getRealPath();

        [$ok, $skip, $errors] = $importer->import($path);

        // si no leyó nada y hay errores: muéstralo como error real
        if ($ok === 0 && $skip === 0 && !empty($errors)) {
            return back()
                ->withErrors($errors[0])
                ->with('warn', implode("\n", array_slice($errors, 0, 8)));
        }

        return back()
            ->with('ok', "Importación completada. Registrados: $ok. Omitidos: $skip.")
            ->with('warn', !empty($errors) ? implode("\n", array_slice($errors, 0, 8)) : null);
    }

    // GET /integracion/pagos/template
    public function template()
    {
        $headers = ['DOCUMENTO','OPERACION','MONEDA','FECHA','MONTO','GESTOR'];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($out, $headers, ';');  // usa ; para Excel en ES
            fclose($out);
        }, 'plantilla_pagos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
