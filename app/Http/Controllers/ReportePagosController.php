<?php

namespace App\Http\Controllers;

use App\Application\Reportes\Pagos\ExportPagosReport;
use App\Application\Reportes\Pagos\ListPagosReport;
use App\Http\Requests\Reportes\ReportePagosRequest;

class ReportePagosController extends Controller
{
    public function index(ReportePagosRequest $request, ListPagosReport $list)
    {
        $pagos = $list->handle($request->filters(), 15);

        return view('reportes.pagos', compact('pagos'))
            ->fragmentIf($request->partial(), 'tabla-resultados');
    }

    public function export(ReportePagosRequest $request, ExportPagosReport $export)
    {
        $file = $export->handle($request->filters());

        return response()
            ->download($file->tempPath, $file->downloadName)
            ->deleteFileAfterSend(true);
    }
}
