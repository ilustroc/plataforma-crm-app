<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;

class ReportePromesasController extends Controller
{
    private string $table    = 'promesas_pago'; 
    private string $opsTable = 'promesa_operaciones';

    public function index(Request $r)
    {
        $qb   = $this->baseQuery($r);
        $rows = $qb->paginate(25)->withQueryString();

        $from   = $r->query('from', now()->startOfMonth()->toDateString());
        $to     = $r->query('to',   now()->toDateString());
        $estado = $r->query('estado', '');
        $gestor = $r->query('gestor', '');
        $q      = $r->query('q', '');

        if ($r->ajax() || $r->query('partial')) {
            return view('reportes.promesas', compact('rows','from','to','estado','gestor','q'))
                ->fragment('tabla-resultados');
        }

        return view('reportes.promesas', compact('rows','from','to','estado','gestor','q'));
    }

    public function export(Request $r)
    {
        $qb   = $this->baseQuery($r)->orderBy('p_created_at');
        $rows = $qb->get();

        $xlsx  = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet(); $row = 1;

        $headers = [
            'DOCUMENTO','CLIENTE','NIVEL 3','CONTACTO','AGENTE','OPERACION','ENTIDAD',
            'FECHA GESTION','OBSERVACION','MONTO PROMESA','NRO CUOTAS',
            'FECHA PROMESA','GESTOR'
        ];

        $sheet->fromArray($headers, null, "A{$row}"); $row++;

        foreach ($rows as $r) {
            $sheet->fromArray([
                (string)$r->documento,
                (string)$r->cliente,
                'Compromiso de pago',
                'CONTACTO',
                (string)$r->agente,
                (string)$r->operacion,
                (string)$r->entidad,
                (string)$r->fecha_gestion,
                (string)$r->observacion,
                $r->monto_promesa !== null ? (float)$r->monto_promesa : '',
                $r->nro_cuotas     !== null ? (int)$r->nro_cuotas     : '',
                (string)$r->fecha_promesa,
                (string)$r->gestor,
            ], null, "A{$row}");

            $sheet->setCellValueExplicit("A{$row}", (string)$r->documento, DataType::TYPE_STRING);
            $row++;
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c='A'; $c <= $lastCol; $c++) $sheet->getColumnDimension($c)->setAutoSize(true);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);

        return response()->download($tmp, 'reporte_promesas_'.now()->format('Ymd_His').'.xlsx')
                         ->deleteFileAfterSend(true);
    }

    private function baseQuery(Request $r)
    {
        $qb = DB::table("{$this->table} as p")
            ->leftJoin("{$this->opsTable} as po", 'po.promesa_id', '=', 'p.id')
            // Join con Carteras usando COALESCE para soportar operaciones en tabla principal o detalle
            ->leftJoin('carteras as c', DB::raw('COALESCE(po.operacion, p.operacion)'), '=', 'c.operacion')
            ->leftJoin('users as u',  'u.id',  '=', 'p.user_id')
            ->leftJoin('users as su', 'su.id', '=', 'u.supervisor_id');

        $qb->selectRaw("
            COALESCE(p.dni, c.documento)                                as documento,
            COALESCE(c.nombre, '')                                      as cliente,
            COALESCE(u.name, '')                                        as agente,
            COALESCE(po.operacion, p.operacion, '')                      as operacion,
            COALESCE(c.entidad, '')                                      as entidad,
            DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s')               as fecha_gestion,
            COALESCE(p.nota, '')                                        as observacion,
            CASE WHEN p.monto > 0 THEN p.monto WHEN p.tipo = 'convenio' THEN p.monto_convenio ELSE NULL END AS monto_promesa,
            CASE WHEN IFNULL(p.nro_cuotas,0) > 0 THEN p.nro_cuotas ELSE NULL END as nro_cuotas,
            DATE_FORMAT(COALESCE(p.fecha_pago, p.fecha_promesa), '%Y-%m-%d') as fecha_promesa,
            COALESCE(su.name, '')                                       as gestor,
            p.created_at                                                as p_created_at
        ");

        // ====== Filtros ======
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = trim((string)$r->query('estado',''));
        $gestor = trim((string)$r->query('gestor',''));
        $q      = trim((string)$r->query('q',''));

        if ($from) $qb->whereDate('p.created_at','>=',$from);
        if ($to)   $qb->whereDate('p.created_at','<=',$to);

        if ($estado !== '' && Schema::hasColumn($this->table, 'workflow_estado')) {
            $qb->where('p.workflow_estado','like',"%{$estado}%");
        }

        if ($gestor !== '') {
            $qb->where('su.name','like',"%{$gestor}%");
        }

        if ($q !== '') {
            $qb->where(function($w) use ($q){
                $w->where('p.dni','like',"%{$q}%")
                  ->orWhere(DB::raw('COALESCE(po.operacion, p.operacion)'), 'like', "%{$q}%")
                  ->orWhere('c.nombre','like',"%{$q}%")
                  ->orWhere('p.nota','like',"%{$q}%");
            });
        }

        return $qb->orderByDesc('p.created_at');
    }
}