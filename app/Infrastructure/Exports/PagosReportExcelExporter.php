<?php

namespace App\Infrastructure\Exports;

use App\Application\Reportes\Pagos\ExportedFile;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PagosReportExcelExporter
{
    public function export(Builder $query): ExportedFile
    {
        $filename = 'reporte_pagos_' . now()->format('Ymd_His') . '.xlsx';

        $xlsx = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet();

        $headers = ['DOCUMENTO', 'CLIENTE', 'CARTERA', 'OPERACION', 'PRODUCTO', 'MONEDA', 'FECHA', 'MONTO', 'GESTOR'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;

        // Mejor que chunk(): chunkById para export grandes
        $query->orderBy('pagos.id')->chunkById(1000, function ($items) use (&$row, $sheet) {
            foreach ($items as $p) {
                $sheet->setCellValueExplicit("A{$row}", (string) $p->documento, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", (string) ($p->cliente_nombre ?? '---'), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", (string) ($p->cliente_cartera ?? '-'), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", (string) $p->operacion, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", (string) ($p->cliente_producto ?? '-'), DataType::TYPE_STRING);
                $sheet->setCellValue("F{$row}", $p->moneda);

                $fecha = $p->fecha;
                $fechaFmt = '';
                if ($fecha instanceof CarbonInterface) {
                    $fechaFmt = $fecha->format('d/m/Y');
                } elseif (!empty($fecha)) {
                    $fechaFmt = Carbon::parse($fecha)->format('d/m/Y');
                }
                $sheet->setCellValue("G{$row}", $fechaFmt);

                $sheet->setCellValue("H{$row}", (float) $p->monto);
                $sheet->setCellValueExplicit("I{$row}", (string) $p->gestor, DataType::TYPE_STRING);

                $row++;
            }
        }, 'pagos.id', 'id'); // importante por el JOIN

        // Formato numérico una sola vez (más rápido)
        if ($row > 2) {
            $sheet->getStyle("H2:H" . ($row - 1))
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($xlsx);
        $tempFile = tempnam(sys_get_temp_dir(), 'pagos_');
        $writer->save($tempFile);

        return new ExportedFile($tempFile, $filename);
    }
}
