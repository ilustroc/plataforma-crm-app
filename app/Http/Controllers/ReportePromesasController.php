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
    // Tablas
    private string $table    = 'promesas_pago';          // principal
    private string $opsTable = 'promesa_operaciones';    // detalle (¡ojo: singular!)

    public function index(Request $r)
    {
        $qb   = $this->baseQuery($r);
        $rows = $qb->paginate(25)->withQueryString();

        // Filtros para la vista
        $from   = $r->query('from', Carbon::today()->startOfMonth()->toDateString());
        $to     = $r->query('to',   Carbon::today()->toDateString());
        $estado = $r->query('estado', '');
        $gestor = $r->query('gestor', '');
        $q      = $r->query('q', '');

        return view('reportes.pdp', compact('rows','from','to','estado','gestor','q'));
    }

    public function export(Request $r)
    {
        $qb   = $this->baseQuery($r)->orderBy('p_created_at');
        $rows = $qb->get();

        $xlsx  = new Spreadsheet();
        $sheet = $xlsx->getActiveSheet(); $row = 1;

        $headers = [
            'DOCUMENTO','CLIENTE','NIVEL 3','CONTACTO','AGENTE','OPERACION','ENTIDAD','CARTERA',
            'FECHA GESTION','FECHA CITA','TELEFONO','OBSERVACION','MONTO PROMESA','NRO CUOTAS',
            'FECHA PROMESA','PROCEDENCIA LLAMADA','GESTOR','CARTERA'
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
                (string)$r->cartera_agente,
                (string)$r->fecha_gestion, // Y-m-d H:i:s
                '', // FECHA CITA
                '', // TELEFONO
                (string)$r->observacion,
                $r->monto_promesa !== null ? (float)$r->monto_promesa : '',
                $r->nro_cuotas     !== null ? (int)$r->nro_cuotas     : '',
                (string)$r->fecha_promesa,   // Y-m-d
                'Web/>',
                (string)$r->gestor,
                (string)$r->cartera_final,
            ], null, "A{$row}");

            // DNI como texto
            $sheet->setCellValueExplicit("A{$row}", (string)$r->documento, DataType::TYPE_STRING);

            $row++;
        }

        // Auto-size
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c='A'; $c <= $lastCol; $c++) $sheet->getColumnDimension($c)->setAutoSize(true);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);

        return response()->download($tmp, 'reporte_promesas_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Query que devuelve UNA FILA POR OPERACIÓN con todas las columnas del layout.
     */
    private function baseQuery(Request $r)
    {
        if (!Schema::hasTable($this->table)) {
            abort(500, "No existe la tabla {$this->table}.");
        }

        // LEFT JOIN al detalle: si hay múltiples operaciones => múltiples filas
        $qb = DB::table("{$this->table} as p")
            ->leftJoin("{$this->opsTable} as po", 'po.promesa_id', '=', 'p.id')
            // operación efectiva = po.operacion (si existe) o p.operacion (legacy)
            ->leftJoin('clientes_cuentas as cc', DB::raw('COALESCE(po.operacion, p.operacion)'), '=', 'cc.operacion')
            // usuario que creó
            ->leftJoin('users as u',  'u.id',  '=', 'p.user_id')
            // supervisor del creador
            ->leftJoin('users as su', 'su.id', '=', 'u.supervisor_id');

        // Campos calculados y alias con el ORDEN exacto requerido
        $qb->selectRaw("
            COALESCE(p.dni, cc.dni)                                      as documento,
            COALESCE(cc.titular, '')                                     as cliente,
            COALESCE(u.name, '')                                         as agente,
            COALESCE(po.operacion, p.operacion, '')                      as operacion,
            COALESCE(cc.entidad, '')                                     as entidad,
            COALESCE(cc.agente, '')                                      as cartera_agente,   -- CARTERA (1)
            DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s')               as fecha_gestion,
            COALESCE(p.nota, '')                                         as observacion,
            CASE WHEN IFNULL(p.monto,0) > 0 THEN p.monto ELSE NULL END   as monto_promesa,
            CASE WHEN IFNULL(p.nro_cuotas,0) > 0 THEN p.nro_cuotas ELSE NULL END as nro_cuotas,
            DATE_FORMAT(COALESCE(p.fecha_pago, p.fecha_promesa), '%Y-%m-%d') as fecha_promesa,
            COALESCE(su.name, '')                                        as gestor,
            COALESCE(cc.cartera, '')                                     as cartera_final,    -- CARTERA (2)
            p.created_at                                                 as p_created_at
        ");

        // ====== Filtros ======
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = trim((string)$r->query('estado',''));
        $gestor = trim((string)$r->query('gestor',''));
        $q      = trim((string)$r->query('q',''));

        // Fechas por FECHA GESTIÓN (created_at)
        if ($from) $qb->whereDate('p.created_at','>=',$from);
        if ($to)   $qb->whereDate('p.created_at','<=',$to);

        // Estado por workflow_estado si existe
        if ($estado !== '' && Schema::hasColumn($this->table, 'workflow_estado')) {
            $qb->where('p.workflow_estado','like',"%{$estado}%");
        }

        // Filtro Gestor (supervisor)
        if ($gestor !== '') {
            $qb->where(function($w) use ($gestor){
                $w->where('su.name','like',"%{$gestor}%")
                  ->orWhere('su.email','like',"%{$gestor}%");
            });
        }

        // Búsqueda general
        if ($q !== '') {
            $qb->where(function($w) use ($q){
                $w->where('p.dni','like',"%{$q}%")
                  ->orWhere(DB::raw('COALESCE(po.operacion, p.operacion)'), 'like', "%{$q}%")
                  ->orWhere('cc.titular','like',"%{$q}%")
                  ->orWhere('cc.entidad','like',"%{$q}%")
                  ->orWhere('p.nota','like',"%{$q}%");
            });
        }

        // Orden
        $qb->orderByDesc('p.created_at')->orderByDesc('p.id');

        return $qb;
    }
}
