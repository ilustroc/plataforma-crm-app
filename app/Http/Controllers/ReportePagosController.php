<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagos;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportePagosController extends Controller
{
    public function index(Request $r)
    {
        $query = $this->buildQuery($r);
        
        $pagos = $query->select(
            'pagos.*', 
            'clientes_cuentas.nombre as cliente_nombre',
            'clientes_cuentas.producto as cliente_producto',
            'clientes_cuentas.cartera as cliente_cartera'
        )
        ->orderByDesc('pagos.fecha')
        ->paginate(15)
        ->withQueryString();

        return view('reportes.pagos', compact('pagos'))
            ->fragmentIf($r->boolean('partial'), 'tabla-resultados');
    }

    public function export(Request $r)
    {
        $query = $this->buildQuery($r)
            ->select(
                'pagos.*', 
                'clientes_cuentas.nombre as cliente_nombre',
                'clientes_cuentas.producto as cliente_producto',
                'clientes_cuentas.cartera as cliente_cartera'
            );

        $filename = 'reporte_pagos_' . now()->format('Ymd_His') . '.xlsx';

        $xlsx = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet();
        
        $headers = ['DOCUMENTO', 'CLIENTE', 'CARTERA', 'OPERACION', 'PRODUCTO', 'MONEDA', 'FECHA', 'MONTO', 'GESTOR'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        
        $query->orderBy('id')->chunkById(1000, function ($items) use (&$row, $sheet) {
            foreach ($items as $p) {
                $sheet->setCellValueExplicit("A{$row}", $p->documento, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", $p->cliente_nombre ?? '---', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $p->cliente_cartera ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", $p->operacion, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", $p->cliente_producto ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValue("F{$row}", $p->moneda);
                $sheet->setCellValue("G{$row}", \Carbon\Carbon::parse($p->fecha)->format('d/m/Y')); 
                $sheet->setCellValue("H{$row}", (float)$p->monto);
                $sheet->setCellValueExplicit("I{$row}", $p->gestor, DataType::TYPE_STRING);
                $row++;
            }
        });

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($xlsx);
        $tempFile = tempnam(sys_get_temp_dir(), 'pagos_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function buildQuery(Request $r)
    {
        $q = Pagos::query()
            ->leftJoin('clientes_cuentas', 'pagos.operacion', '=', 'clientes_cuentas.operacion');

        if ($val = $r->input('q')) {
            $q->where(function($sq) use ($val) {
                $sq->where('pagos.documento', 'like', "%$val%")
                   ->orWhere('pagos.operacion', 'like', "%$val%")
                   ->orWhere('clientes_cuentas.nombre', 'like', "%$val%");
            });
        }

        if ($val = $r->input('from')) $q->whereDate('pagos.fecha', '>=', $val);
        if ($val = $r->input('to'))   $q->whereDate('pagos.fecha', '<=', $val);
        if ($val = $r->input('gestor')) $q->where('pagos.gestor', 'like', "%$val%");

        return $q;
    }
}