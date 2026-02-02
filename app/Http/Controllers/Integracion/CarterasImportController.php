<?php

namespace App\Http\Controllers\Integracion; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cartera;
use Illuminate\Support\Facades\DB;

class CarterasImportController extends Controller
{
    /**
     * Genera y descarga la plantilla CSV con los campos de la tabla 'carteras'.
     */
    public function templateCarterasMaster()
    {
        // Encabezados exactos para la tabla 'carteras'
        $headers = [
            'CUENTA', 'OPERACION', 'DOCUMENTO', 'NOMBRE', 
            'DEPARTAMENTO', 'PROVINCIA', 'DISTRITO', 'DIRECCION',
            'FECHA_COMPRA', 'ENTIDAD', 'COSECHA', 'FECHA_CASTIGO',
            'PRODUCTO', 'MONEDA', 'SALDO_CAPITAL', 'INTERESES', 'DEUDA_TOTAL'
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($out, $headers);
            fclose($out);
        }, 'plantilla_cartera_master.csv', [
            'Content-Type'  => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Procesa la importación del CSV.
     */
    public function importCarterasMaster(Request $r)
    {
        $r->validate(['archivo' => ['required', 'file', 'mimes:csv,txt', 'max:40960']]); // 40MB
        
        $path = $r->file('archivo')->getRealPath();
        
        // Ejecutar importación
        [$ok, $skip, $err] = $this->doImport($path);

        return back()
            ->with('ok', "Proceso finalizado. Registros importados/actualizados: $ok. Omitidos: $skip.")
            ->with('warn', count($err) > 0 ? implode("\n", array_slice($err, 0, 5)) : null);
    }

    private function doImport(string $filepath): array
    {
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0, 0, ['No se pudo abrir el archivo']];

        // Detectar delimitador
        $first = fgets($fh);
        $delimiter = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
        rewind($fh);

        // Leer encabezados
        $headers = fgetcsv($fh, 0, $delimiter);
        if (!$headers) return [0, 0, ['Archivo vacío']];

        // Normalizar encabezados (Mayúsculas, sin acentos)
        $map = array_map(function($h) {
            $h = strtoupper(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))); // BOM
            return str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $h);
        }, $headers);

        // Helpers de limpieza
        $cleanNum = fn($v) => is_numeric(str_replace([',',' '],['.',''],$v)) ? (float)str_replace([',',' '],['.',''],$v) : 0;
        $cleanDate = function($v) {
            $v = trim($v);
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v)) return $v; // YYYY-MM-DD
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $v, $m)) return "$m[3]-$m[2]-$m[1]"; // DD/MM/YYYY
            return null;
        };

        $ok = 0; $skip = 0; $err = []; $rowNum = 1;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                $rowNum++;
                
                // Mapear datos
                $raw = [];
                foreach ($row as $i => $val) {
                    if (isset($map[$i])) $raw[$map[$i]] = trim($val);
                }

                // Validar campo clave (OPERACION y DOCUMENTO son vitales)
                if (empty($raw['OPERACION']) || empty($raw['DOCUMENTO'])) {
                    $skip++;
                    $err[] = "Fila $rowNum: Falta Operación o Documento.";
                    continue;
                }

                // Preparar array para Eloquent
                $data = [
                    'cuenta'        => $raw['CUENTA'] ?? null,
                    'operacion'     => $raw['OPERACION'],
                    'documento'     => $raw['DOCUMENTO'],
                    'nombre'        => $raw['NOMBRE'] ?? 'SIN NOMBRE',
                    'departamento'  => $raw['DEPARTAMENTO'] ?? null,
                    'provincia'     => $raw['PROVINCIA'] ?? null,
                    'distrito'      => $raw['DISTRITO'] ?? null,
                    'direccion'     => $raw['DIRECCION'] ?? null,
                    'fecha_compra'  => $cleanDate($raw['FECHA_COMPRA'] ?? ''),
                    'entidad'       => $raw['ENTIDAD'] ?? null,
                    'cosecha'       => $raw['COSECHA'] ?? null,
                    'fecha_castigo' => $cleanDate($raw['FECHA_CASTIGO'] ?? ''),
                    'producto'      => $raw['PRODUCTO'] ?? null,
                    'moneda'        => $raw['MONEDA'] ?? 'SOLES',
                    'saldo_capital' => $cleanNum($raw['SALDO_CAPITAL'] ?? 0),
                    'intereses'     => $cleanNum($raw['INTERESES'] ?? 0),
                    'deuda_total'   => $cleanNum($raw['DEUDA_TOTAL'] ?? 0),
                ];

                // Upsert: Busca por 'operacion', actualiza lo demás
                Cartera::updateOrCreate(
                    ['operacion' => $data['operacion']], 
                    $data
                );

                $ok++;

                // Commit parcial para no saturar memoria
                if ($ok % 500 === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return [0, 0, ["Error fatal en fila $rowNum: " . $e->getMessage()]];
        }

        fclose($fh);
        return [$ok, $skip, $err];
    }
}