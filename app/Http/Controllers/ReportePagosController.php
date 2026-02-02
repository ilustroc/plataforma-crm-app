<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagos;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Support\Facades\DB;

class ReportePagosController extends Controller
{
    public function index(Request $r)
    {
        $query = $this->buildQuery($r);
        
        $pagos = $query->select(
            'pagos.*', 
            'carteras.nombre as cliente_nombre',
            'carteras.producto as cliente_producto',
            'carteras.entidad as cliente_cartera' // Mapeamos Entidad como "Cartera"
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
                'carteras.nombre as cliente_nombre',
                'carteras.producto as cliente_producto',
                'carteras.entidad as cliente_cartera'
            );

        $filename = 'reporte_pagos_' . now()->format('Ymd_His') . '.xlsx';

        $xlsx = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet();
        
        $headers = ['DOCUMENTO', 'CLIENTE', 'CARTERA', 'OPERACION', 'PRODUCTO', 'MONEDA', 'FECHA', 'MONTO', 'GESTOR'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        
        // Usamos chunk para memoria eficiente
        $query->orderBy('pagos.id')->chunk(1000, function ($items) use (&$row, $sheet) {
            foreach ($items as $p) {
                // Documento
                $sheet->setCellValueExplicit("A{$row}", $p->documento, DataType::TYPE_STRING);
                
                // Nombre Cliente (Si no hay match en cartera, mostramos ---)
                $sheet->setCellValueExplicit("B{$row}", $p->cliente_nombre ?? '---', DataType::TYPE_STRING);
                
                // Cartera (Entidad)
                $sheet->setCellValueExplicit("C{$row}", $p->cliente_cartera ?? '-', DataType::TYPE_STRING);
                
                // Operación
                $sheet->setCellValueExplicit("D{$row}", $p->operacion, DataType::TYPE_STRING);
                
                // Producto
                $sheet->setCellValueExplicit("E{$row}", $p->cliente_producto ?? '-', DataType::TYPE_STRING);
                
                // Moneda
                $sheet->setCellValue("F{$row}", $p->moneda);
                
                // Fecha
                $fechaFmt = $p->fecha ? \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') : '';
                $sheet->setCellValue("G{$row}", $fechaFmt); 
                
                // Monto (Numérico)
                $sheet->setCellValue("H{$row}", (float)$p->monto);
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

                // Gestor
                $sheet->setCellValueExplicit("I{$row}", $p->gestor, DataType::TYPE_STRING);
                
                $row++;
            }
        });

        // Autoajustar columnas
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
        // 1. Base Query con JOIN a la nueva tabla 'carteras'
        $q = Pagos::query()
            ->leftJoin('carteras', 'pagos.operacion', '=', 'carteras.operacion');

        // 2. Filtro General (Buscador)
        if ($val = $r->input('q')) {
            $q->where(function($sq) use ($val) {
                $sq->where('pagos.documento', 'like', "%$val%")
                   ->orWhere('pagos.operacion', 'like', "%$val%")
                   ->orWhere('carteras.nombre', 'like', "%$val%"); // Busca por nombre en cartera
            });
        }

        // 3. Filtros Específicos
        if ($val = $r->input('from'))   $q->whereDate('pagos.fecha', '>=', $val);
        if ($val = $r->input('to'))     $q->whereDate('pagos.fecha', '<=', $val);
        if ($val = $r->input('gestor')) $q->where('pagos.gestor', 'like', "%$val%");

        // Filtro opcional por Cartera/Entidad si lo agregas en la vista
        if ($val = $r->input('cartera')) $q->where('carteras.entidad', 'like', "%$val%");

        return $q;
    }
}