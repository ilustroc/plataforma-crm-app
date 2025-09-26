<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PagoPropia;
use App\Models\PagoCajaCuscoCastigada;
use App\Models\PagoCajaCuscoExtrajudicial;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportePagosController extends Controller
{
    public function index(Request $r)
    {
        // solo 2 args; normaliza 'extrajudicial' -> 'caja-cusco-extrajudicial'
        $cartera = $r->query('cartera', 'propia');
        if ($cartera === 'extrajudicial') $cartera = 'caja-cusco-extrajudicial';
    
        $from    = $r->query('from');
        $to      = $r->query('to');
        $gestor  = $r->query('gestor');
        $status  = $r->query('status');
        $q       = $r->query('q');
    
        $query = $this->buildQuery($cartera, $from, $to, $gestor, $status, $q);
        $rows  = $query->orderByDesc('fecha_de_pago')->paginate(10)->withQueryString();
    
        if ($r->ajax() || $r->boolean('partial')) {
            $view = match ($cartera) {
                'caja-cusco-castigada'    => 'reportes.pagos.castigada',
                'caja-cusco-extrajudicial'=> 'reportes.pagos.extrajudicial',
                default                   => 'reportes.pagos.propia',
            };
            return view($view, compact('cartera','rows','from','to','gestor','status','q'));
        }
    
        return view('reportes.pagos.index', compact(
            'cartera','rows','from','to','gestor','status','q'
        ));
    }

    public function export(Request $r)
    {
        $cartera = $r->query('cartera', 'propia');
        if ($cartera === 'extrajudicial') $cartera = 'caja-cusco-extrajudicial';
    
        $from    = $r->query('from');
        $to      = $r->query('to');
        $gestor  = $r->query('gestor');
        $status  = $r->query('status');
        $q       = $r->query('q');
    
        $query    = $this->buildQuery($cartera, $from, $to, $gestor, $status, $q);
        $filename = 'pagos_'.$cartera.'_'.now()->format('Ymd_His').'.xlsx';
    
        $xlsx  = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet();
        $row   = 1;
    
        // formateador de fecha dd/mm/YYYY
        $fmtDate = static function($v){
            if ($v instanceof \DateTimeInterface) return $v->format('d/m/Y');
            if (empty($v)) return null;
            try { return \Carbon\Carbon::parse($v)->format('d/m/Y'); } catch (\Throwable $e) { return null; }
        };
    
        if ($cartera === 'caja-cusco-castigada') {
            $headers = ['DNI','PAGARE','TITULAR','MONEDA','TIPO_RECUP','CARTERA','FECHA_DE_PAGO','PAGADO_EN_SOLES','GESTOR','STATUS'];
            $sheet->fromArray($headers, null, "A{$row}"); $row++;
    
            (clone $query)->orderBy('id')->chunkById(1000, function ($items) use (&$row, $sheet, $fmtDate) {
                foreach ($items as $r) {
                    $sheet->fromArray([
                        (string)$r->dni,
                        (string)$r->pagare,
                        $r->titular,
                        $r->moneda,
                        $r->tipo_de_recuperacion,
                        $r->cartera,
                        $fmtDate($r->fecha_de_pago),
                        (float)$r->pagado_en_soles,
                        $r->gestor,
                        $r->status,
                    ], null, "A{$row}");
                    $sheet->setCellValueExplicit("A{$row}", (string)$r->dni,     DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$row}", (string)$r->pagare,  DataType::TYPE_STRING);
                    $row++;
                }
            }, 'id');
    
        } elseif ($cartera === 'caja-cusco-extrajudicial') {
            // Extrajudicial: incluye ambas columnas de monto
            $headers = ['DNI','PAGARE','TITULAR','MONEDA','TIPO_RECUP','CARTERA','FECHA_DE_PAGO','MONTO_PAGADO','PAGADO_EN_SOLES','GESTOR','STATUS'];
            $sheet->fromArray($headers, null, "A{$row}"); $row++;
    
            (clone $query)->orderBy('id')->chunkById(1000, function ($items) use (&$row, $sheet, $fmtDate) {
                foreach ($items as $r) {
                    $sheet->fromArray([
                        (string)$r->dni,
                        (string)$r->pagare,
                        $r->titular,
                        $r->moneda,
                        $r->tipo_de_recuperacion,
                        $r->cartera,
                        $fmtDate($r->fecha_de_pago),
                        (float)($r->monto_pagado ?? 0),
                        (float)($r->pagado_en_soles ?? 0),
                        $r->gestor,
                        $r->status,
                    ], null, "A{$row}");
                    // Forzar texto
                    $sheet->setCellValueExplicit("A{$row}", (string)$r->dni,    DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$row}", (string)$r->pagare, DataType::TYPE_STRING);
                    $row++;
                }
            }, 'id');
    
        } else { // propia
            $headers = ['DNI','OPERACION','ENTIDAD','EQUIPOS','CLIENTE','PRODUCTO','MONEDA','FECHA_DE_PAGO','PAGADO_EN_SOLES','GESTOR','STATUS'];
            $sheet->fromArray($headers, null, "A{$row}"); $row++;
    
            (clone $query)->orderBy('id')->chunkById(1000, function ($items) use (&$row, $sheet, $fmtDate) {
                foreach ($items as $r) {
                    $sheet->fromArray([
                        (string)$r->dni,
                        (string)$r->operacion,
                        $r->entidad,
                        $r->equipos,
                        $r->nombre_cliente,
                        $r->producto,
                        $r->moneda,
                        $fmtDate($r->fecha_de_pago),
                        (float)$r->pagado_en_soles,
                        $r->gestor,
                        $r->status,
                    ], null, "A{$row}");
                    $sheet->setCellValueExplicit("A{$row}", (string)$r->dni,       DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$row}", (string)$r->operacion, DataType::TYPE_STRING);
                    $row++;
                }
            }, 'id');
        }
    
        // Auto-size columnas (según headers de la rama activa)
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c = 'A'; $c <= $lastCol; $c++) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);
    
        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    private function buildQuery(string $cartera, ?string $from, ?string $to, ?string $gestor, ?string $status, ?string $q)
    {
        switch ($cartera) {
            case 'caja-cusco-castigada':
                $qb = PagoCajaCuscoCastigada::query();
                if ($q) {
                    $qb->where(function($qq) use ($q){
                        $qq->where('dni','like',"%$q%")
                           ->orWhere('pagare','like',"%$q%")
                           ->orWhere('titular','like',"%$q%");
                    });
                }
                break;
    
            case 'caja-cusco-extrajudicial':
                $qb = PagoCajaCuscoExtrajudicial::query();
                if ($q) {
                    $qb->where(function($qq) use ($q){
                        $qq->where('dni','like',"%$q%")
                           ->orWhere('pagare','like',"%$q%")
                           ->orWhere('titular','like',"%$q%");
                    });
                }
                break;
    
            default: // propia
                $qb = PagoPropia::query();
                if ($q) {
                    $qb->where(function($qq) use ($q){
                        $qq->where('dni','like',"%$q%")
                           ->orWhere('operacion','like',"%$q%")
                           ->orWhere('nombre_cliente','like',"%$q%");
                    });
                }
                break;
        }
    
        if ($from)   $qb->whereDate('fecha_de_pago', '>=', $from);
        if ($to)     $qb->whereDate('fecha_de_pago', '<=', $to);
        if ($gestor) $qb->where('gestor','like',"%$gestor%");
        if ($status) $qb->where('status','like',"%$status%");
    
        return $qb;
    }

    private function fmtDate($v): ?string
    {
        if (!$v) return null;
        if ($v instanceof Carbon) return $v->format('Y-m-d');
        try { return Carbon::parse((string)$v)->format('Y-m-d'); } catch (\Throwable) { return null; }
    }
}
