<?php

namespace App\Http\Controllers;

use App\Models\PagoLote;
use App\Models\PagoPropia;
use App\Models\PagoCajaCuscoCastigada;
use App\Models\PagoCajaCuscoExtrajudicial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlaceholdersPagosController extends Controller
{
    // GET /integracion/pagos
    public function index()
    {
        // PROPIA
        $ultimoLotePropia = PagoLote::where('tipo','propia')->latest('id')->first();
        $pagosPropia = collect();
        if ($ultimoLotePropia) {
            $pagosPropia = PagoPropia::where('lote_id', $ultimoLotePropia->id)
                ->latest('id')->take(25)->get();
        }

        // CAJA CUSCO ▸ CASTIGADA
        $ultimoLoteCusco = PagoLote::where('tipo','caja-cusco-castigada')->latest('id')->first();
        $pagosCusco = collect();
        if ($ultimoLoteCusco) {
            $pagosCusco = PagoCajaCuscoCastigada::where('lote_id', $ultimoLoteCusco->id)
                ->latest('id')->take(25)->get();
        }

        // CAJA CUSCO ▸ EXTRAJUDICIAL
        $ultimoLoteExtrajudicial = PagoLote::where('tipo','caja-cusco-extrajudicial')->latest('id')->first();
        $pagosExtrajudicial = collect();
        if ($ultimoLoteExtrajudicial) {
            $pagosExtrajudicial = PagoCajaCuscoExtrajudicial::where('lote_id', $ultimoLoteExtrajudicial->id)
                ->latest('id')->take(25)->get();
        }   

        return view('placeholders.pagos.vista', compact(
            'ultimoLotePropia','pagosPropia',
            'ultimoLoteCusco','pagosCusco',
            'ultimoLoteExtrajudicial','pagosExtrajudicial' // <-- NUEVO
        ));
    }

    // ================== PROPIA ==================
    public function template()
    {
        $headers = [
            'DNI','OPERACION','ENTIDAD','EQUIPOS','NOMBRE_CLIENTE',
            'PRODUCTO','MONEDA','FECHA_DE_PAGO','MONTO_PAGADO','CONCATENAR',
            'FECHA','PAGADO_EN_SOLES','GESTOR','STATUS'
        ];
        $csv = implode(',', $headers) . "\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_pagos_propia.csv"',
        ]);
    }

    public function import(Request $r)
    {
        $r->validate(['archivo' => ['required','file','mimes:csv,txt','max:20480']]);
        $file = $r->file('archivo');
        $path = $file->storeAs('integracion/pagos', time().'_'.$file->getClientOriginalName());

        $lote = PagoLote::create([
            'tipo' => 'propia',
            'archivo' => $path,
            'usuario_id' => Auth::id(),
            'total_registros' => 0,
        ]);

        [$ok, $skip, $errores] = $this->importCsvPropia(Storage::path($path), $lote->id);
        $lote->update(['total_registros' => $ok]);

        return redirect()
            ->route('integracion.pagos')
            ->with('ok', "PROPIA ▸ OK: {$ok}. Omitidos: {$skip}.")
            ->with('warn', implode("\n", array_slice($errores, 0, 5)));
    }

    // ================== CUSCO CASTIGADA ==================
    public function templateCajaCuscoCastigada()
    {
        $headers = [
            'ABOGADO','REGION','AGENCIA','TITULAR','DNI','PAGARE','MONEDA',
            'TIPO_DE_RECUPERACION','CONDICION','CARTERA','DEMANDA','FECHA_DE_PAGO',
            'PAGO_EN_SOLES','CONCATENAR','FECHA','PAGADO_EN_SOLES','GESTOR','STATUS'
        ];
        $csv = implode(',', $headers) . "\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_caja_cusco_castigada.csv"',
        ]);
    }

    public function importCajaCuscoCastigada(Request $r)
    {
        $r->validate(['archivo' => ['required','file','mimes:csv,txt','max:20480']]);
        $file = $r->file('archivo');
        $path = $file->storeAs('integracion/pagos', time().'_'.$file->getClientOriginalName());

        $lote = PagoLote::create([
            'tipo' => 'caja-cusco-castigada',
            'archivo' => $path,
            'usuario_id' => Auth::id(),
            'total_registros' => 0,
        ]);

        [$ok, $skip, $errores] = $this->importCsvCajaCusco(Storage::path($path), $lote->id);
        $lote->update(['total_registros' => $ok]);

        return redirect()
            ->route('integracion.pagos')
            ->with('ok', "CAJA CUSCO ▸ OK: {$ok}. Omitidos: {$skip}.")
            ->with('warn', implode("\n", array_slice($errores, 0, 5)));
    }

    // ================== CUSCO EXTRAJUDICIAL (NUEVO) ==================
    // GET /integracion/pagos/template/cusco-extrajudicial
    public function templateCajaCuscoExtrajudicial()
    {
        $headers = [
            'REGION','AGENCIA','TITULAR','DNI','PAGARE','MONEDA',
            'TIPO_DE_RECUPERACION','CONDICION','DEMANDA',
            'FECHA','PAGADO_EN_SOLES','MONTO_PAGADO',
            'VERIFICACION_DE_BPO','ESTADO_FINAL','CONCATENAR',
            'FECHA','PAGADO','GESTOR'
            // (sin STATUS, a menos que lo quieras)
        ];
        $csv = implode(',', $headers) . "\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_caja_cusco_extrajudicial.csv"',
        ]);
    }

    // POST /integracion/pagos/import/cusco-extrajudicial
    public function importCajaCuscoExtrajudicial(Request $r)
    {
        $r->validate(['archivo' => ['required','file','mimes:csv,txt','max:20480']]);
        $file = $r->file('archivo');
        $path = $file->storeAs('integracion/pagos', time().'_'.$file->getClientOriginalName());

        $lote = PagoLote::create([
            'tipo' => 'caja-cusco-extrajudicial',
            'archivo' => $path,
            'usuario_id' => Auth::id(),
            'total_registros' => 0,
        ]);

        [$ok, $skip, $errores] = $this->importCsvCajaCuscoExtrajudicial(Storage::path($path), $lote->id);
        $lote->update(['total_registros' => $ok]);

        return redirect()
            ->route('integracion.pagos')
            ->with('ok', "CAJA CUSCO (Extrajudicial) ▸ OK: {$ok}. Omitidos: {$skip}.")
            ->with('warn', implode("\n", array_slice($errores, 0, 5)));
    }

    // ================== HELPERS COMUNES ==================
    private function detectDelimiter(string $firstLine): string {
        return (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    }
    private function norm(string $h): string {
        // quita BOM y NBSP
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $h);   // BOM
        $h = str_replace("\xC2\xA0", ' ', $h);           // NBSP
        $h = strtoupper(trim($h));
        $h = str_replace(
            [' ', '-', 'Á','É','Í','Ó','Ú','Ü','Ñ','º','°','.'],
            ['_','_','A','E','I','O','U','U','N','','',''],
            $h
        );
        $h = preg_replace('/[^A-Z0-9_]/', '_', $h);
        $h = trim($h, '_');
        if ($h === 'N') $h = 'NRO';
        return $h;
    }
    private function parseDate(?string $v): ?string {
        if (!$v) return null;
        $v = trim($v);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $v, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) return $v;
        return null;
    }
    private function parseNumber(?string $v): ?float {
        if ($v === null) return null; $v = trim($v); if ($v==='') return null;
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) { $v = str_replace(['.', ' '], '', $v); $v = str_replace(',', '.', $v); }
        elseif (strpos($v, ',') !== false) { $v = str_replace(['.', ' '], '', $v); $v = str_replace(',', '.', $v); }
        else { $v = str_replace(' ', '', $v); }
        return is_numeric($v) ? (float)$v : null;
    }
    private function toUtf8(?string $s): ?string{
        if ($s === null) return null;
        $s = trim($s);
    
        // Si no está en UTF-8, conviértelo desde ISO-8859-1/Windows-1252
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
    
        // Elimina caracteres de control no imprimibles
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
    }
    
    // ======================================================
    // [PROPIA] Importación desde CSV
    // ======================================================
    
    /**
     * Importa pagos de la cartera PROPIA desde un CSV.
     *
     * ETIQUETAS DE PASOS:
     *  (A) Abrir archivo y detectar delimitador
     *  (B) Leer/normalizar encabezados y armar mapa
     *  (C) Mapeo esperado -> columnas BD
     *  (D) Recorrer filas, parsear valores y validar mínimos
     *  (E) Insertar y acumular métricas
     *
     * @param  string  $filepath  Ruta física del CSV
     * @param  int     $loteId    ID del lote asociado
     * @return array   [ok, skip, errores[]]
     */
    private function importCsvPropia(string $filepath, int $loteId): array
    {
        // (A) Abrir y detectar delimitador
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0, 0, ['[PROPIA] No se pudo abrir el archivo']];
    
        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);
            return [0, 0, ['[PROPIA] Archivo vacío']];
        }
        $del = $this->detectDelimiter($first);
        rewind($fh);
    
        // (B) Encabezados -> normalizados (MAP: índice -> nombre_normalizado)
        $headers = fgetcsv($fh, 0, $del);
        if (!$headers) {
            fclose($fh);
            return [0, 0, ['[PROPIA] No se pudieron leer los encabezados']];
        }
        $map = [];
        foreach ($headers as $i => $h) {
            $map[$i] = $this->norm($h); // e.g. "NOMBRE CLIENTE" => "NOMBRE_CLIENTE"
        }
    
        // (C) Diccionario esperado (CSV_NORMALIZADO => columna_BD)
        $expect = [
            'DNI'               => 'dni',
            'OPERACION'         => 'operacion',
            'ENTIDAD'           => 'entidad',
            'EQUIPOS'           => 'equipos',
            'NOMBRE_CLIENTE'    => 'nombre_cliente',
            'PRODUCTO'          => 'producto',
            'MONEDA'            => 'moneda',
            'FECHA_DE_PAGO'     => 'fecha_de_pago',
            'MONTO_PAGADO'      => 'monto_pagado',
            'CONCATENAR'        => 'concatenar',
            'FECHA'             => 'fecha',
            'PAGADO_EN_SOLES'   => 'pagado_en_soles',
            'GESTOR'            => 'gestor',
            'STATUS'            => 'status',
        ];
    
        // (E) Métricas y loop
        $ok = 0; $skip = 0; $err = [];
        $rowNum = 1;
    
        // (D) Leer filas de datos
        while (($row = fgetcsv($fh, 0, $del)) !== false) {
            $rowNum++;
            $data = ['lote_id' => $loteId];
    
            foreach ($row as $i => $val) {
                $key = $map[$i] ?? null;
                if (!$key || !isset($expect[$key])) continue;
    
                $attr = $expect[$key];
                $val  = $this->toUtf8((string)$val);  // <-- fuerza UTF-8 y limpia
    
                if (in_array($key, ['FECHA_DE_PAGO', 'FECHA'])) {
                    $data[$attr] = $this->parseDate($val);
                } elseif (in_array($key, ['MONTO_PAGADO', 'PAGADO_EN_SOLES'])) {
                    $data[$attr] = $this->parseNumber($val);
                } else {
                    // Todos los textos quedan en UTF-8 (DNI, OPERACION, NOMBRE_CLIENTE, etc.)
                    $data[$attr] = ($val !== '') ? $val : null;
                }
            }
    
            // Validación de claves mínimas
            if (empty($data['dni']) && empty($data['operacion']) && empty($data['nombre_cliente'])) {
                $skip++;
                $err[] = "[PROPIA] Fila {$rowNum}: sin claves mínimas (DNI/OPERACION/NOMBRE_CLIENTE).";
                continue;
            }
    
            try {
                PagoPropia::create($data);
                $ok++;
            } catch (\Throwable $e) {
                $skip++;
                $err[] = "[PROPIA] Fila {$rowNum}: " . $e->getMessage();
            }
        }
    
        fclose($fh);
        return [$ok, $skip, $err];
    }


    // ======================================================
    // [CAJA CUSCO ▸ CASTIGADA] Importación desde CSV
    // ======================================================
    
    /**
     * Importa pagos de CAJA CUSCO ▸ CASTIGADA desde un CSV.
     *
     * ETIQUETAS DE PASOS:
     *  (A) Abrir archivo y detectar delimitador
     *  (B) Leer/normalizar encabezados y armar mapa
     *  (C) Mapeo esperado -> columnas BD
     *  (D) Recorrer filas, parsear valores y validar mínimos
     *  (E) Insertar y acumular métricas
     *
     * @param  string  $filepath
     * @param  int     $loteId
     * @return array   [ok, skip, errores[]]
     */
    private function importCsvCajaCusco(string $filepath, int $loteId): array
    {
        // (A) Abrir y detectar delimitador
        $fh = fopen($filepath, 'r');
        if (!$fh) return [0, 0, ['[CUSCO_CAST] No se pudo abrir el archivo']];
    
        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);
            return [0, 0, ['[CUSCO_CAST] Archivo vacío']];
        }
        $del = $this->detectDelimiter($first);
        rewind($fh);
    
        // (B) Encabezados -> normalizados
        $headers = fgetcsv($fh, 0, $del);
        if (!$headers) {
            fclose($fh);
            return [0, 0, ['[CUSCO_CAST] No se pudieron leer los encabezados']];
        }
        $map = [];
        foreach ($headers as $i => $h) {
            $map[$i] = $this->norm($h);
        }
    
        // (C) Diccionario esperado (CSV_NORMALIZADO => columna_BD)
        $expect = [
            'ABOGADO'              => 'abogado',
            'REGION'               => 'region',
            'AGENCIA'              => 'agencia',
            'TITULAR'              => 'titular',
            'DNI'                  => 'dni',
            'PAGARE'               => 'pagare',
            'MONEDA'               => 'moneda',
            'TIPO_DE_RECUPERACION' => 'tipo_de_recuperacion',
            'CONDICION'            => 'condicion',
            'CARTERA'              => 'cartera',
            'DEMANDA'              => 'demanda',
            'FECHA_DE_PAGO'        => 'fecha_de_pago',
            'PAGO_EN_SOLES'        => 'pago_en_soles',
            'CONCATENAR'           => 'concatenar',
            'FECHA'                => 'fecha',
            'PAGADO_EN_SOLES'      => 'pagado_en_soles',
            'GESTOR'               => 'gestor',
            'STATUS'               => 'status',
        ];
    
        // (E) Métricas
        $ok = 0; $skip = 0; $err = [];
        $rowNum = 1;
    
        // (D) Recorrer filas
        while (($row = fgetcsv($fh, 0, $del)) !== false) {
            $rowNum++;
            $data = ['lote_id' => $loteId];
    
            foreach ($row as $i => $val) {
                $key = $map[$i] ?? null;
                if (!$key || !isset($expect[$key])) continue;
    
                $attr = $expect[$key];
                $val  = $this->toUtf8((string)$val);
    
                if (in_array($key, ['FECHA_DE_PAGO', 'FECHA'])) {
                    $data[$attr] = $this->parseDate($val);
                } elseif (in_array($key, ['PAGO_EN_SOLES', 'PAGADO_EN_SOLES'])) {
                    $data[$attr] = $this->parseNumber($val);
                } elseif ($key === 'NRO') {
                    $data[$attr] = is_numeric($val) ? (int)$val : null; // por si aparece "N°"/"Nº" normalizado a NRO
                } else {
                    $data[$attr] = $val ?: null;
                }
            }
    
            // Validación de claves mínimas
            if (empty($data['dni']) && empty($data['pagare']) && empty($data['titular'])) {
                $skip++;
                $err[] = "[CUSCO_CAST] Fila {$rowNum}: sin claves mínimas (DNI/PAGARE/TITULAR).";
                continue;
            }
    
            // Insertar
            try {
                PagoCajaCuscoCastigada::create($data);
                $ok++;
            } catch (\Throwable $e) {
                $skip++;
                $err[] = "[CUSCO_CAST] Fila {$rowNum}: " . $e->getMessage();
            }
        }
    
        fclose($fh);
        return [$ok, $skip, $err];
    }


    // Import CAJA CUSCO EXTRAJUDICIAL (nuevo)
    private function importCsvCajaCuscoExtrajudicial(string $filepath, int $loteId): array
    {
        $fh = fopen($filepath, 'r'); if(!$fh) return [0,0,['No se pudo abrir el archivo']];
        $first = fgets($fh); if($first===false){fclose($fh); return [0,0,['Archivo vacío']];}
        $del = $this->detectDelimiter($first); rewind($fh);
    
        $headers = fgetcsv($fh, 0, $del); if(!$headers){ fclose($fh); return [0,0,['No se pudieron leer los encabezados']]; }
    
        // normaliza encabezados
        $map = [];
        foreach($headers as $i=>$h){ $map[$i] = $this->norm($h); }
    
        // alias normalizados -> clave canónica
        $alias = [
            'FECHA_DE_PAGO'     => 'FECHA',
            'F_PAGO'            => 'FECHA',
            'PAGO_EN_SOLES'     => 'PAGADO_EN_SOLES',
            'PAGADO'            => 'PAGADO_EN_SOLES',
            'TIPO_RECUPERACION' => 'TIPO_DE_RECUPERACION',
            'VERIFICACION_BPO'  => 'VERIFICACION_DE_BPO',
            'ESTADO'            => 'ESTADO_FINAL',
            'NRO_PAGARE'        => 'PAGARE',
        ];
    
        $ok=0; $skip=0; $err=[]; $rowNum=1;
    
        while(($row=fgetcsv($fh,0,$del))!==false){
            $rowNum++;
            $data=['lote_id'=>$loteId];
    
            $seenFecha = 0;
            $seenPes   = 0;
    
            foreach($row as $i=>$val){
                $keyRaw = $map[$i] ?? null; if(!$keyRaw) continue;
                $key = $alias[$keyRaw] ?? $keyRaw;
                $val = $this->toUtf8((string)$val);
    
                switch ($key) {
                    case 'REGION':                  $data['region'] = $val ?: null; break;
                    case 'AGENCIA':                 $data['agencia'] = $val ?: null; break;
                    case 'TITULAR':                 $data['titular'] = $val ?: null; break;
                    case 'DNI':                     $data['dni'] = $val ?: null; break;
                    case 'PAGARE':                  $data['pagare'] = $val ?: null; break;
                    case 'MONEDA':                  $data['moneda'] = $val ?: null; break;
                    case 'TIPO_DE_RECUPERACION':    $data['tipo_de_recuperacion'] = $val ?: null; break;
                    case 'CONDICION':               $data['condicion'] = $val ?: null; break;
                    case 'DEMANDA':                 $data['demanda'] = $val ?: null; break;
    
                    case 'FECHA':
                        $seenFecha++;
                        $date = $this->parseDate($val);
                        if ($seenFecha === 1) $data['fecha_de_pago'] = $date;
                        else                  $data['fecha_alt']     = $date;
                        break;
    
                    case 'PAGADO_EN_SOLES':
                        $seenPes++;
                        $num = $this->parseNumber($val);
                        if ($seenPes === 1) $data['pagado_en_soles']     = $num;
                        else                $data['pagado_en_soles_alt'] = $num;
                        break;
    
                    case 'MONTO_PAGADO':           $data['monto_pagado'] = $this->parseNumber($val); break;
                    case 'VERIFICACION_DE_BPO':    $data['verificacion_de_bpo'] = $val ?: null; break;
                    case 'ESTADO_FINAL':           $data['estado_final'] = $val ?: null; break;
                    case 'CONCATENAR':             $data['concatenar'] = $val ?: null; break;
                    case 'GESTOR':                 $data['gestor'] = $val ?: null; break;
                    case 'STATUS':                 $data['status'] = $val ?: null; break;
                }
            }
    
            if (empty($data['dni']) && empty($data['pagare']) && empty($data['titular'])) {
                $skip++; $err[]="Fila {$rowNum}: sin claves mínimas (DNI/PAGARE/TITULAR)."; continue;
            }
    
            try { \App\Models\PagoCajaCuscoExtrajudicial::create($data); $ok++; }
            catch(\Throwable $e){ $skip++; $err[]="Fila {$rowNum}: ".$e->getMessage(); }
        }
    
        fclose($fh);
        return [$ok,$skip,$err];
    }


}