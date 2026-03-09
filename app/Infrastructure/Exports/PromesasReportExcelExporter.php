<?php

namespace App\Infrastructure\Exports;

use App\Application\Reportes\Promesas\ExportedFile;
use Illuminate\Database\Query\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PromesasReportExcelExporter
{
    public function export(Builder $qb): ExportedFile
    {
        $download = 'reporte_promesas_' . now()->format('Ymd_His') . '.xlsx';

        $xlsx  = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet();

        $headers = [
            'DOCUMENTO','CLIENTE','NIVEL 3','CONTACTO','AGENTE','OPERACION','ENTIDAD',
            'FECHA GESTION','OBSERVACION','MONTO PROMESA','NRO CUOTAS',
            'FECHA PROMESA','GESTOR'
        ];

        $sheet->fromArray($headers, null, 'A1');
        $row = 2;

        // Orden estable para chunk
        $qb->orderBy('p_created_at')->orderBy('documento')->chunk(1000, function ($items) use (&$row, $sheet) {
            foreach ($items as $r) {
                // Documento siempre como texto (DNI con ceros si aplica)
                $sheet->setCellValueExplicit("A{$row}", (string) $r->documento, DataType::TYPE_STRING);

                $sheet->setCellValueExplicit("B{$row}", (string) $r->cliente, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", 'Compromiso de pago', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", 'CONTACTO', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", (string) $r->agente, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("F{$row}", (string) $r->operacion, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("G{$row}", (string) $r->entidad, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("H{$row}", (string) $r->fecha_gestion, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("I{$row}", (string) $r->observacion, DataType::TYPE_STRING);

                // Monto / cuotas
                $sheet->setCellValue("J{$row}", $r->monto_promesa !== null ? (float) $r->monto_promesa : '');
                $sheet->setCellValue("K{$row}", $r->nro_cuotas !== null ? (int) $r->nro_cuotas : '');

                $sheet->setCellValueExplicit("L{$row}", (string) $r->fecha_promesa, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("M{$row}", (string) $r->gestor, DataType::TYPE_STRING);

                $row++;
            }
        });

        // Formato monto columna J
        if ($row > 2) {
            $sheet->getStyle("J2:J" . ($row - 1))
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // AutoSize seguro por índice
        $lastIndex = count($headers);
        for ($i = 1; $i <= $lastIndex; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);

        return new ExportedFile($tmp, $download);
    }
}
