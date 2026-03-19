<?php

namespace App\Http\Controllers\Integracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cartera;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarterasImportController extends Controller
{
    public function templateCarterasMaster()
    {
        $headers = [
            'CUENTA', 'OPERACION', 'DOCUMENTO', 'NOMBRE',
            'DEPARTAMENTO', 'PROVINCIA', 'DISTRITO', 'DIRECCION',
            'FECHA_COMPRA', 'ENTIDAD', 'COSECHA', 'FECHA_CASTIGO',
            'PRODUCTO', 'MONEDA', 'SALDO_CAPITAL', 'INTERESES', 'DEUDA_TOTAL'
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            fclose($out);
        }, 'plantilla_cartera_master.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importCarterasMaster(Request $r)
    {
        $r->validate([
            'archivo' => ['required', 'file', 'max:40960']
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file'     => 'El archivo enviado no es válido.',
            'archivo.max'      => 'El archivo supera el tamaño permitido de 40 MB.',
            'archivo.uploaded' => 'El archivo no se pudo subir. Revisa upload_max_filesize, post_max_size o el formulario.',
        ]);

        if (!$r->hasFile('archivo') || !$r->file('archivo')->isValid()) {
            return back()->withErrors([
                'archivo' => 'El archivo no se pudo subir correctamente.'
            ]);
        }

        $ext = strtolower($r->file('archivo')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            return back()->withErrors([
                'archivo' => 'Solo se permiten archivos CSV o TXT.'
            ]);
        }

        $path = $r->file('archivo')->getRealPath();

        [$ok, $skip, $err] = $this->doImport($path);

        if (!empty($err)) {
            return back()
                ->with('ok', "Proceso finalizado. Importados/actualizados: $ok. Omitidos: $skip.")
                ->withErrors(['archivo' => implode(' | ', array_slice($err, 0, 3))]);
        }

        return back()->with('ok', "Proceso finalizado. Importados/actualizados: $ok. Omitidos: $skip.");
    }

    private function doImport(string $filepath): array
    {
        $fh = fopen($filepath, 'r');
        if (!$fh) {
            return [0, 0, ['No se pudo abrir el archivo']];
        }

        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);
            return [0, 0, ['Archivo vacío']];
        }

        $delimiters = [
            ';'  => substr_count($first, ';'),
            ','  => substr_count($first, ','),
            "\t" => substr_count($first, "\t"),
        ];

        arsort($delimiters);
        $delimiter = array_key_first($delimiters);

        rewind($fh);

        $headers = fgetcsv($fh, 0, $delimiter);
        if (!$headers) {
            fclose($fh);
            return [0, 0, ['No se pudo leer encabezados']];
        }

        $map = array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = trim($h);
            $h = Str::upper(Str::ascii($h));
            return $h;
        }, $headers);

        $cleanNum = function ($v) {
            $v = trim((string)$v);
            if ($v === '') return 0;

            $v = str_replace(' ', '', $v);

            if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $v)) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else {
                $v = str_replace(',', '', $v);
            }

            return is_numeric($v) ? (float)$v : 0;
        };

        $cleanDate = function ($v) {
            $v = trim((string)$v);

            if ($v === '') return null;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                return $v;
            }

            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }

            return null;
        };

        $ok = 0;
        $skip = 0;
        $err = [];
        $rowNum = 1;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                $rowNum++;

                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }

                $raw = [];
                foreach ($row as $i => $val) {
                    if (isset($map[$i])) {
                        $raw[$map[$i]] = trim((string)$val);
                    }
                }

                if (empty($raw['OPERACION']) || empty($raw['DOCUMENTO'])) {
                    $skip++;
                    $err[] = "Fila $rowNum: falta OPERACION o DOCUMENTO.";
                    continue;
                }

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
                    'moneda'        => $raw['MONEDA'] ?? 'PEN',
                    'saldo_capital' => $cleanNum($raw['SALDO_CAPITAL'] ?? 0),
                    'intereses'     => $cleanNum($raw['INTERESES'] ?? 0),
                    'deuda_total'   => $cleanNum($raw['DEUDA_TOTAL'] ?? 0),
                ];

                Cartera::updateOrCreate(
                    ['operacion' => $data['operacion']],
                    $data
                );

                $ok++;

                if ($ok % 500 === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($fh);
            return [0, 0, ["Error fatal en fila $rowNum: " . $e->getMessage()]];
        }

        fclose($fh);

        return [$ok, $skip, $err];
    }
}