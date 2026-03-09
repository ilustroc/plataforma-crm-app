<?php

namespace App\Http\Controllers;

use App\Application\Reportes\Promesas\ExportPromesasReport;
use App\Application\Reportes\Promesas\ListPromesasReport;
use App\Http\Requests\Reportes\ReportePromesasRequest;

class ReportePromesasController extends Controller
{
    public function index(ReportePromesasRequest $request, ListPromesasReport $list)
    {
        $filters = $request->filters();
        $rows    = $list->handle($filters, 25);

        // Para tu vista (mantengo exactamente lo que pasabas)
        $from   = $filters->from;
        $to     = $filters->to;
        $estado = $filters->estado;
        $gestor = $filters->gestor;
        $q      = $filters->q;

        $view = view('reportes.promesas', compact('rows','from','to','estado','gestor','q'));

        return $request->shouldRenderPartial()
            ? $view->fragment('tabla-resultados')
            : $view;
    }

    public function export(ReportePromesasRequest $request, ExportPromesasReport $export)
    {
        $file = $export->handle($request->filters());

        return response()
            ->download($file->tempPath, $file->downloadName)
            ->deleteFileAfterSend(true);
    }
}
