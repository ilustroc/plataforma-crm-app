<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportePromesasController extends Controller
{
    // Cambia estos nombres si tus tablas reales son otras:
    private string $table    = 'promesas_pago';            // principal
    private string $opsTable = 'promesas_operaciones';     // detalle de operaciones (opcional)

    public function index(Request $r)
    {
        [$qb] = $this->buildQuery($r, true);
        $rows = $qb->paginate(10)->withQueryString();

        // filtros para la vista
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = $r->query('estado');
        $gestor = $r->query('gestor');
        $q      = $r->query('q');

        return view('reportes.pdp', compact('rows','from','to','estado','gestor','q'));
    }

    public function export(Request $r)
    {
        [$qb] = $this->buildQuery($r, true);

        $xlsx = new Spreadsheet();
        $sheet= $xlsx->getActiveSheet(); $row = 1;

        $headers = ['DNI','Operaciones','Fecha promesa','Monto prometido','Estado','Gestor','Creado el'];
        $sheet->fromArray($headers, null, "A{$row}"); $row++;

        // Export en chunks (sin alias de columna conflictivo)
        (clone $qb)->orderBy('pp.id')->chunk(1000, function ($items) use (&$row, $sheet) {
            foreach ($items as $r) {
                $sheet->fromArray([
                    (string)($r->dni ?? ''),
                    (string)($r->operaciones ?? ''),
                    (string)($r->fecha ?? ''),
                    (float) ($r->monto ?? 0),
                    (string)($r->estado ?? ''),
                    (string)($r->gestor ?? ''),
                    (string)($r->created_at ?? ''),
                ], null, "A{$row}");
                $sheet->setCellValueExplicit("A{$row}", (string)($r->dni ?? ''), DataType::TYPE_STRING);
                $row++;
            }
        });

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c='A'; $c <= $lastCol; $c++) $sheet->getColumnDimension($c)->setAutoSize(true);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($xlsx))->save($tmp);

        return response()->download($tmp, 'promesas_propia_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'=>'no-store, no-cache, must-revalidate',
            'Pragma'=>'no-cache',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Arma el query con alias normalizados:
     * fecha:  fecha_promesa | fecha | created_at
     * monto:  monto_prometido | monto_total | importe | monto
     * estado: workflow_estado | estado
     * gestor: pp.gestor o users.name (si existe user_id)
     * operaciones: GROUP_CONCAT(operacion) o pp.operaciones
     */
    private function buildQuery(Request $r, bool $returnQuery = false)
    {
        if (!Schema::hasTable($this->table)) {
            abort(500, "No existe la tabla {$this->table}.");
        }

        $cols = Schema::getColumnListing($this->table);

        $fechaCol = collect(['fecha_promesa','fecha','created_at'])->first(fn($c)=>in_array($c,$cols)) ?? 'created_at';
        $montoCol = collect(['monto_prometido','monto_total','importe','monto'])->first(fn($c)=>in_array($c,$cols));
        $estadoCol= collect(['workflow_estado','estado'])->first(fn($c)=>in_array($c,$cols));
        $gestCol  = in_array('gestor',$cols) ? 'gestor' : null;

        $qb = DB::table("{$this->table} as pp")
            ->select([
                'pp.id',
                'pp.dni',
                DB::raw("DATE_FORMAT(pp.{$fechaCol}, '%Y-%m-%d') as fecha"),
                $montoCol ? DB::raw("pp.{$montoCol} as monto")   : DB::raw("NULL as monto"),
                $estadoCol? DB::raw("pp.{$estadoCol} as estado") : DB::raw("NULL as estado"),
                'pp.created_at',
            ]);

        // Gestor
        if ($gestCol) {
            $qb->addSelect(DB::raw("pp.{$gestCol} as gestor"));
        } elseif (in_array('user_id',$cols) && Schema::hasTable('users')) {
            $qb->leftJoin('users as u', 'u.id', '=', 'pp.user_id')
               ->addSelect(DB::raw("COALESCE(u.name,'') as gestor"));
        } else {
            $qb->addSelect(DB::raw("'' as gestor"));
        }

        // Operaciones desde tabla detalle (si existe) o desde campo pp.operaciones
        if (Schema::hasTable($this->opsTable)) {
            $opsSub = DB::table($this->opsTable)
                ->select('promesa_id', DB::raw("GROUP_CONCAT(operacion ORDER BY operacion SEPARATOR ', ') as operaciones"))
                ->groupBy('promesa_id');

            $qb->leftJoinSub($opsSub, 'ops', 'ops.promesa_id', '=', 'pp.id')
               ->addSelect(DB::raw("COALESCE(ops.operaciones,'') as operaciones"));
        } elseif (in_array('operaciones', $cols)) {
            $qb->addSelect(DB::raw("pp.operaciones as operaciones"));
        } else {
            $qb->addSelect(DB::raw("'' as operaciones"));
        }

        // === Filtros
        $from   = $r->query('from');
        $to     = $r->query('to');
        $estado = $r->query('estado');
        $gestor = $r->query('gestor');
        $q      = $r->query('q');

        if ($from)   $qb->whereDate("pp.{$fechaCol}", '>=', $from);
        if ($to)     $qb->whereDate("pp.{$fechaCol}", '<=', $to);
        if ($estado && $estadoCol) $qb->where("pp.{$estadoCol}", 'like', "%{$estado}%");

        if ($gestor) {
            if ($gestCol) $qb->where("pp.{$gestCol}", 'like', "%{$gestor}%");
            elseif (Schema::hasTable('users') && in_array('user_id',$cols)) {
                $qb->whereExists(function($q2) use ($gestor){
                    $q2->from('users')
                       ->whereColumn('users.id','pp.user_id')
                       ->where('users.name','like',"%{$gestor}%");
                });
            }
        }

        if ($q) {
            $qb->where(function($qq) use ($q, $cols){
                $qq->where('pp.dni','like',"%{$q}%");
                if (in_array('nota',$cols))        $qq->orWhere('pp.nota','like',"%{$q}%");
                if (in_array('observacion',$cols)) $qq->orWhere('pp.observacion','like',"%{$q}%");
            });
        }

        $qb->orderByDesc("pp.{$fechaCol}")->orderByDesc('pp.id');

        return [$qb];
    }
}
