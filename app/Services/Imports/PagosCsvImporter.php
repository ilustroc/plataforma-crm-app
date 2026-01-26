<?php

namespace App\Services\Imports;

use App\Models\Pagos;
use Illuminate\Support\Facades\DB;

class PagosCsvImporter
{
    private array $required = ['DOCUMENTO', 'FECHA', 'MONTO'];

    public function import(string $filepath): array
    {
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0, 0, ['No se pudo abrir el archivo']];

        $firstLine = fgets($fh);
        if ($firstLine === false) { fclose($fh); return [0, 0, ['Archivo vacío']]; }
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($fh);

        $headers = fgetcsv($fh, 0, $delimiter);
        if (!$headers) { fclose($fh); return [0, 0, ['Archivo vacío']]; }

        $map = array_map(fn($h) => strtoupper(trim((string)$h)), $headers);

        $ok = 0; $skip = 0; $errors = [];
        $rowNum = 1;
        $batch = [];
        $batchSize = 1000;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                $rowNum++;

                if (count($row) !== count($map)) {
                    $row = array_pad($row, count($map), null);
                }

                $raw = array_combine($map, $row);

                $data = [
                    'documento'  => $raw['DOCUMENTO'] ?? $raw['DNI'] ?? null,
                    'operacion'  => $raw['OPERACION'] ?? $raw['PAGARE'] ?? null,
                    'moneda'     => $raw['MONEDA'] ?? 'PEN',
                    'fecha'      => $this->parseDate($raw['FECHA'] ?? $raw['FECHA_PAGO'] ?? null),
                    'monto'      => $this->parseNumber($raw['MONTO'] ?? $raw['IMPORTE'] ?? null),
                    'gestor'     => $raw['GESTOR'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (!$data['documento'] || !$data['monto'] || !$data['fecha']) {
                    $skip++;
                    $errors[] = "Fila {$rowNum}: Falta Documento, Monto o Fecha.";
                    continue;
                }

                $batch[] = $data;
                $ok++;

                if (count($batch) >= $batchSize) {
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
            return [0, 0, ["Error fatal en fila {$rowNum}: " . $e->getMessage()]];
        }

        fclose($fh);
        return [$ok, $skip, $errors];
    }

    private function parseDate(?string $v): ?string
    {
        if (!$v) return null;
        $v = trim($v);

        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) return substr($v, 0, 10);

        return null;
    }

    private function parseNumber(?string $v): ?float
    {
        if (!$v) return null;

        $v = strtoupper(trim($v));
        $v = str_replace(['S/', '$', 'USD', 'PEN', ' '], '', $v);

        if (str_contains($v, '.') && str_contains($v, ',')) {
            $lastDot = strrpos($v, '.');
            $lastComma = strrpos($v, ',');

            if ($lastComma > $lastDot) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } else {
                $v = str_replace(',', '', $v);
            }
        } elseif (str_contains($v, ',')) {
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float)$v : null;
    }
}
