<?php

namespace App\Http\Controllers\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integracion\StorePagosImportRequest;
use App\Services\Imports\PagosCsvImporter;
use Illuminate\Support\Facades\Storage;

class PagosImportController extends Controller
{
    public function create()
    {
        return view('integracion.pagos');

    }

    public function template()
    {
        $headers = ['DOCUMENTO', 'OPERACION', 'MONEDA', 'FECHA', 'MONTO', 'GESTOR'];
        $csv = implode(',', $headers) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_pagos.csv"',
        ]);
    }

    public function store(StorePagosImportRequest $request, PagosCsvImporter $importer)
    {
        $file = $request->file('archivo');

        // Guardado temporal
        $path = $file->storeAs('temp', 'pagos_import_' . now()->format('Ymd_His') . '.csv');

        [$ok, $skip, $errores] = $importer->import(Storage::path($path));

        Storage::delete($path);

        return redirect()
            ->route('integracion.pagos.create')
            ->with('ok', "Importación completada. Registrados: {$ok}. Omitidos: {$skip}.")
            ->with('warn', count($errores) ? implode("\n", array_slice($errores, 0, 5)) : null);
    }
}
