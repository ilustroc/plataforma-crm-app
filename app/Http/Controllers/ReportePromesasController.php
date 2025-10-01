<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\PromesaPago;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportePromesasController extends Controller
{
    public function index(Request $r)
    {
    try{
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = $r->query('estado');
        $gestor = $r->query('gestor');
        $q      = $r->query('q');

        [$rows, $fechaCol] = $this->buildQuery($from, $to, $estado, $gestor, $q);

        // 👇 Siempre la misma vista, sin ramas para "partial"
        return view('reportes.pdp', compact('rows','from','to','estado','gestor','q','fechaCol'));
    }catch(\Throwable $e){
        \Log::error('PDP index ERROR', [
            'msg'=>$e->getMessage(), 'file'=>$e->getFile(), 'line'=>$e->getLine()
        ]);
        return redirect()->back()->withErrors('Ocurrió un error en el reporte. Revisa __last-error.');
    }
}

    public function export(Request $r)
    {
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = $r->query('estado');
        $gestor = $r->query('gestor');
        $q      = $r->query('q');

        [$qb, $fechaCol] = $this->buildQuery($from, $to, $estado, $gestor, $q, returnQuery:true);

        $xlsx = new Spreadsheet();
        $sheet= $xlsx->getActiveSheet();
        $row  = 1;

        $headers = ['DNI','Operaciones','Fecha promesa','Monto prometido','Estado','Gestor','Creado por','Creado el'];
        $sheet->fromArray($headers, null, "A{$row}"); $row++;

        (clone $qb)->orderBy('id')->chunkById(800, function ($items) use (&$row, $sheet, $fechaCol) {
            foreach ($items as $p) {
                $ops = method_exists($p, 'operaciones')
                    ? $p->operaciones->pluck('operacion')->implode(', ')
                    : (is_array($p->operaciones ?? null) ? implode(', ', $p->operaciones) : '');

                $fecha = $p->{$fechaCol}
                       ? (optional($p->{$fechaCol})->format('d/m/Y') ?: (string)$p->{$fechaCol})
                       : null;

                $monto = $p->monto_prometido ?? $p->monto_total ?? $p->importe ?? $p->monto ?? 0;

                $sheet->fromArray([
                    (string)($p->dni ?? ''),
                    $ops,
                    $fecha,
                    (float)$monto,
                    (string)($p->workflow_estado ?? $p->estado ?? ''),
                    (string)($p->gestor ?? ($p->user->name ?? '')),
                    (string)($p->user->name ?? ''),
                    optional($p->created_at)->format('d/m/Y H:i'),
                ], null, "A{$row}");

                $sheet->setCellValueExplicit("A{$row}", (string)($p->dni ?? ''), DataType::TYPE_STRING);
                $row++;
            }
        }, 'id');

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c='A'; $c <= $lastCol; $c++) $sheet->getColumnDimension($c)->setAutoSize(true);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);

        return response()->download($tmp, 'promesas_propia_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'=>'no-store, no-cache, must-revalidate', 'Pragma'=>'no-cache',
        ])->deleteFileAfterSend(true);
    }

    /** return [paginator|builder, fechaCol] */
    private function buildQuery(?string $from, ?string $to, ?string $estado, ?string $gestor, ?string $q, bool $returnQuery=false)
    {
        $cols = Schema::getColumnListing('promesas_pago');
        $fechaCol = in_array('fecha_promesa',$cols) ? 'fecha_promesa'
                 : (in_array('fecha',$cols) ? 'fecha' : 'created_at');

        $qb = PromesaPago::query()
            ->with(['operaciones:promesa_id,operacion', 'user:id,name']);

        if ($q) {
            $qb->where(function($qq) use ($q){
                $qq->where('dni','like',"%$q%")
                   ->orWhere('observacion','like',"%$q%")
                   ->orWhere('nota','like',"%$q%");
            });
        }
        if ($from)   $qb->whereDate($fechaCol, '>=', $from);
        if ($to)     $qb->whereDate($fechaCol, '<=', $to);
        if ($estado) $qb->where(function($qq) use ($estado){
                      $qq->where('workflow_estado','like',"%$estado%")
                         ->orWhere('estado','like',"%$estado%");
                    });
        if ($gestor) $qb->where(function($qq) use ($gestor){
                      $qq->where('gestor','like',"%$gestor%")
                         ->orWhereHas('user', fn($u)=>$u->where('name','like',"%$gestor%"));
                    });

        return $returnQuery
            ? [$qb->orderByDesc($fechaCol), $fechaCol]
            : [$qb->orderByDesc($fechaCol)->paginate(10)->withQueryString(), $fechaCol];
    }
}