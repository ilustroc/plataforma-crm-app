<?php

namespace App\Http\Controllers;

use App\Models\GestionLote;
use App\Models\GestionPropia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlaceholdersGestionesController extends Controller
{
    public function index()
    {
        $ultimoLotePropia = GestionLote::where('tipo','propia')->latest('id')->first();
        $gestionesPropia = collect();
        if ($ultimoLotePropia) {
            $gestionesPropia = GestionPropia::where('lote_id',$ultimoLotePropia->id)
                ->latest('id')->take(25)->get();
        }

        return view('placeholders.gestiones.vista', compact('ultimoLotePropia','gestionesPropia'));
    }

    // Descargar plantilla CSV con encabezados exactos
    public function templatePropia()
    {
        $headers = [
            'DOCUMENTO','CLIENTE','NIVEL 3','CONTACTO','AGENTE','OPERACION','ENTIDAD','EQUIPO',
            'FECHA GESTION','FECHA CITA','TELEFONO','OBSERVACION','MONTO PROMESA','NRO CUOTAS',
            'FECHA PROMESA','PROCEDENCIA LLAMADA'
        ];
        $csv = implode(',', $headers) . "\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_gestiones_propia.csv"',
        ]);
    }

    public function importPropia(Request $r)
    {
        $r->validate(['archivo' => ['required','file','mimes:csv,txt','max:20480']]);

        $file = $r->file('archivo');
        $path = $file->storeAs('integracion/gestiones', time().'_'.$file->getClientOriginalName());

        $lote = GestionLote::create([
            'tipo' => 'propia',
            'archivo' => $path,
            'usuario_id' => Auth::id(),
            'total_registros' => 0,
        ]);

        [$ok, $skip, $errores] = $this->importCsvPropia(Storage::path($path), $lote->id);
        $lote->update(['total_registros' => $ok]);

        return redirect()
            ->route('integracion.gestiones')
            ->with('ok', "GESTIONES PROPIA ▸ OK: {$ok}. Omitidos: {$skip}.")
            ->with('warn', implode("\n", array_slice($errores, 0, 8)));
    }

    /* ===================== Helpers de parseo ===================== */

    private function detectDelimiter(string $firstLine): string {
        return (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    }

    private function norm(string $h): string {
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $h); // BOM
        $h = str_replace("\xC2\xA0", ' ', $h);         // NBSP
        $h = strtoupper(trim($h));
        $h = str_replace(
            [' ', '-', 'Á','É','Í','Ó','Ú','Ü','Ñ','º','°','.','/','\\'],
            ['_','_','A','E','I','O','U','U','N','','','_','_','_'],
            $h
        );
        $h = preg_replace('/[^A-Z0-9_]/', '_', $h);
        return trim($h, '_');
    }

    private function toUtf8(?string $s): ?string{
        if ($s === null) return null; $s = trim($s);
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
    }

    private function parseDate(?string $v): ?string {
        if (!$v) return null;
        $v = trim($v);
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $v)) return $v;                     // YYYY-MM-DD
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $v, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        return null;
    }

    private function parseDateTime(?string $v): ?string {
        if (!$v) return null;
        $v = trim($v);
        // YYYY-MM-DD HH:MM[:SS]
        if (preg_match('#^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}(:\d{2})?)$#', $v, $m)) return "{$m[1]} {$m[2]}";
        // DD/MM/YYYY HH:MM[:SS]
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})\s+(\d{2}:\d{2}(:\d{2})?)$#', $v, $m)) return "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
        // Solo fecha → parseDate
        $d = $this->parseDate($v);
        return $d ? "{$d} 00:00:00" : null;
    }

    private function parseNumber(?string $v): ?float {
        if ($v === null) return null; $v = trim($v); if ($v==='') return null;
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            $v = str_replace(['.', ' '], '', $v); $v = str_replace(',', '.', $v);
        } elseif (strpos($v, ',') !== false) {
            $v = str_replace(['.', ' '], '', $v); $v = str_replace(',', '.', $v);
        } else { $v = str_replace(' ', '', $v); }
        return is_numeric($v) ? (float)$v : null;
    }

    /* ===================== Importador PROPIA ===================== */

    private function importCsvPropia(string $filepath, int $loteId): array
    {
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0,0,['[PROPIA] No se pudo abrir el archivo.']];

        $first = fgets($fh);
        if ($first === false) { fclose($fh); return [0,0,['[PROPIA] Archivo vacío.']]; }
        $del = $this->detectDelimiter($first); rewind($fh);

        $headers = fgetcsv($fh, 0, $del);
        if (!$headers) { fclose($fh); return [0,0,['[PROPIA] No se pudieron leer los encabezados.']]; }

        // Mapa índice -> encabezado normalizado
        $map = [];
        foreach ($headers as $i => $h) { $map[$i] = $this->norm($h); }

        // Diccionario esperado (CSV_NORMALIZADO => campo_BD)
        $expect = [
            'DOCUMENTO'            => 'documento',
            'CLIENTE'              => 'cliente',
            'NIVEL_3'              => 'nivel_3',
            'CONTACTO'             => 'contacto',
            'AGENTE'               => 'agente',
            'OPERACION'            => 'operacion',
            'ENTIDAD'              => 'entidad',
            'EQUIPO'               => 'equipo',
            'FECHA_GESTION'        => 'fecha_gestion',
            'FECHA_CITA'           => 'fecha_cita',
            'TELEFONO'             => 'telefono',
            'OBSERVACION'          => 'observacion',
            'MONTO_PROMESA'        => 'monto_promesa',
            'NRO_CUOTAS'           => 'nro_cuotas',
            'FECHA_PROMESA'        => 'fecha_promesa',
            'PROCEDENCIA_LLAMADA'  => 'procedencia_llamada',
        ];

        $ok=0; $skip=0; $err=[]; $rowNum=1;

        while (($row = fgetcsv($fh, 0, $del)) !== false) {
            $rowNum++;
            $data = ['lote_id' => $loteId];

            foreach ($row as $i => $val) {
                $key = $map[$i] ?? null; if (!$key || !isset($expect[$key])) continue;
                $attr = $expect[$key];
                $val  = $this->toUtf8((string)$val);

                switch ($key) {
                    case 'FECHA_GESTION':   $data[$attr] = $this->parseDate($val); break;
                    case 'FECHA_CITA':      $data[$attr] = $this->parseDateTime($val); break;
                    case 'FECHA_PROMESA':   $data[$attr] = $this->parseDate($val); break;
                    case 'MONTO_PROMESA':   $data[$attr] = $this->parseNumber($val); break;
                    case 'NRO_CUOTAS':      $data[$attr] = is_numeric($val)? (int)$val : null; break;
                    default:                $data[$attr] = $val !== '' ? $val : null;
                }
            }

            // Claves mínimas (al menos DNI/Documento o Operación o Cliente)
            if (empty($data['documento']) && empty($data['operacion']) && empty($data['cliente'])) {
                $skip++; $err[]="[PROPIA] Fila {$rowNum}: sin claves mínimas (DOCUMENTO/OPERACION/CLIENTE)."; continue;
            }

            try { GestionPropia::create($data); $ok++; }
            catch (\Throwable $e) { $skip++; $err[]="[PROPIA] Fila {$rowNum}: ".$e->getMessage(); }
        }

        fclose($fh);
        return [$ok,$skip,$err];
    }
}
