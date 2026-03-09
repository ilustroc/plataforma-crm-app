<?php

namespace App\Infrastructure\Imports;

use App\Models\Pagos;
use Illuminate\Support\Facades\DB;

class PagosCsvImporter
{
    private array $required = ['DOCUMENTO', 'FECHA', 'MONTO'];

    // Alias comunes que suelen venir en CSV reales
    private array $aliases = [
        'DNI'            => 'DOCUMENTO',
        'DOC'            => 'DOCUMENTO',
        'DOC_CLIENTE'    => 'DOCUMENTO',
        'DOCUMENTO_ID'   => 'DOCUMENTO',

        'FECHA_PAGO'     => 'FECHA',
        'FECHA_DE_PAGO'  => 'FECHA',
        'PAGO_FECHA'     => 'FECHA',
        'FEC_PAGO'       => 'FECHA',

        'IMPORTE'        => 'MONTO',
        'IMPORTE_PAGO'   => 'MONTO',
        'MONTO_PAGO'     => 'MONTO',
        'PAGO'           => 'MONTO',

        'NRO_OPERACION'  => 'OPERACION',
        'N_OPERACION'    => 'OPERACION',
        'OPER'           => 'OPERACION',
    ];

    public function import(string $filepath): array
    {
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0, 0, ['No se pudo abrir el archivo']];

        // Detectar delimitador por primera línea
        $line = fgets($fh);
        if ($line === false) { fclose($fh); return [0, 0, ['Archivo vacío']]; }

        $delimiter = (substr_count($line, ';') > substr_count($line, ',')) ? ';' : ',';

        rewind($fh);
        $headers = fgetcsv($fh, 0, $delimiter);

        if (!$headers) { fclose($fh); return [0, 0, ['El archivo no tiene cabeceras legibles']]; }

        // Normalizar headers + aplicar alias -> claves canónicas
        $map = [];
        foreach ($headers as $h) {
            $norm = $this->normalizeHeader($h);
            $canon = $this->aliases[$norm] ?? $norm;
            $map[] = $canon;
        }

        // Validar columnas requeridas
        foreach ($this->required as $col) {
            if (!in_array($col, $map, true)) {
                fclose($fh);
                return [0, 0, [
                    "Falta la columna obligatoria: $col. Detectadas: " . implode(', ', $map)
                ]];
            }
        }

        $ok = 0; $skip = 0; $errors = []; $rowNum = 1;
        $batch = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                $rowNum++;

                // Saltar filas realmente vacías
                if (!array_filter($row, fn($x) => trim((string)$x) !== '')) continue;

                // Alinear cantidades
                if (count($row) !== count($map)) {
                    $row = array_slice(array_pad($row, count($map), null), 0, count($map));
                }

                $raw = array_combine($map, $row);
                if ($raw === false) {
                    $skip++;
                    $errors[] = "Fila {$rowNum}: no se pudo mapear columnas (cabeceras duplicadas o malformadas).";
                    continue;
                }

                $data = [
                    'documento'  => trim((string)($raw['DOCUMENTO'] ?? '')),
                    'operacion'  => trim((string)($raw['OPERACION'] ?? '')),
                    'moneda'     => trim((string)($raw['MONEDA'] ?? 'PEN')),
                    'fecha'      => $this->parseDate($raw['FECHA'] ?? null),
                    'monto'      => $this->parseNumber($raw['MONTO'] ?? null),
                    'gestor'     => isset($raw['GESTOR']) ? trim((string)$raw['GESTOR']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($data['documento'] === '' || $data['fecha'] === null || $data['monto'] === null) {
                    $skip++;
                    $errors[] = "Fila {$rowNum}: inválida (Doc={$data['documento']}, Fecha={$data['fecha']}, Monto={$data['monto']})";
                    continue;
                }

                $batch[] = $data;
                $ok++;

                if (count($batch) >= 500) {
                    Pagos::insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                Pagos::insert($batch);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($fh);
            return [0, 0, ["Error crítico en fila {$rowNum}: " . $e->getMessage()]];
        }

        fclose($fh);
        return [$ok, $skip, $errors];
    }

    private function normalizeHeader($h): string
    {
        $h = (string)$h;
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // BOM
        $h = trim($h, " \t\n\r\0\x0B\"'");

        $h = mb_strtoupper($h, 'UTF-8');

        // quitar tildes/ñ a ASCII si se puede
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
        if ($conv !== false) $h = $conv;

        // espacios y símbolos -> _
        $h = preg_replace('/[^A-Z0-9]+/', '_', $h);
        $h = trim($h, '_');

        return $h;
    }

    private function parseDate($v): ?string
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '') return null;

        // Excel serial (por si el CSV viene raro)
        if (is_numeric($v)) {
            $n = (float)$v;
            if ($n > 20000) {
                $unix = (int)(($n - 25569) * 86400);
                return gmdate('Y-m-d', $unix);
            }
        }

        // dd/mm/yyyy o dd-mm-yyyy (con o sin hora)
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})/', $v, $m)) {
            $y = (int)$m[3];
            if ($y < 100) $y += 2000;
            return sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[1]);
        }

        // yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) return substr($v, 0, 10);

        return null;
    }

    private function parseNumber($v): ?float
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '') return null;

        // deja solo dígitos y separadores
        $v = preg_replace('/[^0-9.,-]/', '', $v);

        $hasDot = strpos($v, '.') !== false;
        $hasCom = strpos($v, ',') !== false;

        // si tiene ambos, el último separador suele ser decimal
        if ($hasDot && $hasCom) {
            $lastDot = strrpos($v, '.');
            $lastCom = strrpos($v, ',');
            if ($lastCom > $lastDot) { // decimal = ,
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else { // decimal = .
                $v = str_replace(',', '', $v);
            }
        } elseif ($hasCom && !$hasDot) {
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float)$v : null;
    }
}
