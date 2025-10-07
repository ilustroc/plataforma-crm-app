<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClienteCuenta;


class ClientesCargaController extends Controller
{
  public function templateClientesMaster()
  {
      $headers = [
          'CARTERA','TIPO_DOC','DNI','OPERACIÓN','CONCATENAR','AGENTE','TITULAR','AÑO_CASTIGO',
          'MONEDA','ENTIDAD','PRODUCTO','COSECHA','DEPARTAMENTO','ZONA','UBICACIÓN_GEOGRAFICA',
          'FECHA_COMPRA','EDAD','SEXO','ESTADO_CIVIL','TELF1','TELF2','TELF3',
          'SALDO_CAPITAL','INTERES','DEUDA_TOTAL','HASTA','CAPITAL_DESCUENTO', // ← NUEVOS
          'LABORAL','VEHICULOS','PROPIEDADES','CONSOLIDADO_VEHICULOS_PROPIEDADES','CLASIFICACION',
          'SCORE','CORREO_ELECTRONICO','DIRECCION','PROVINCIA','DISTRITO',
      ];

      return response()->streamDownload(function () use ($headers) {
          $out = fopen('php://output', 'w');
          fwrite($out, "\xEF\xBB\xBF"); // BOM
          fputcsv($out, $headers);
          fclose($out);
      }, 'template_clientes_master.csv', [
          'Content-Type'  => 'text/csv; charset=UTF-8',
          'Cache-Control' => 'no-store, no-cache, must-revalidate',
      ]);
  }
  public function importClientesMaster(Request $r)
  {
    $r->validate(['archivo'=>['required','file','mimes:csv,txt','max:40960']]);
    $path = $r->file('archivo')->getRealPath();
    [$ok,$skip,$err] = $this->doImportClientesMaster($path);

    return back()->with('ok', "Importados: $ok, Omitidos: $skip")
                ->with('warn', implode("\n", array_slice($err,0,10)));
  }
  private function doImportClientesMaster(string $filepath): array
  {
    $fh = fopen($filepath,'r'); if(!$fh) return [0,0,['No se pudo abrir el archivo']];
    $first = fgets($fh); if($first===false){ fclose($fh); return [0,0,['Archivo vacío']]; }
    $del = (substr_count($first,';')>substr_count($first,','))?';':','; rewind($fh);
    $headers = fgetcsv($fh,0,$del); if(!$headers){ fclose($fh); return [0,0,['Sin encabezados']]; }

    // ---------- Normalizadores ----------
    $norm = function(string $s): string {
      $s = preg_replace('/^\xEF\xBB\xBF/u','',$s); // BOM
      $s = str_replace("\xC2\xA0",' ',$s);         // NBSP
      $s = strtoupper(trim($s));
      $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N']);
      $s = preg_replace('/[^A-Z0-9]+/','_',$s);
      return trim($s,'_');
    };
    $map = [];
    foreach($headers as $i=>$h) $map[$i] = $norm($h);

    $alias = [
      'TIPO_DOC'=>'TIPO_DOC','TIPO_DOCUMENTO'=>'TIPO_DOC',
      'OPERACION'=>'OPERACION','OPERACIÓN'=>'OPERACION',
      'CONCATENAR_DNI_Y_OPERACION'=>'CONCATENAR','CONCATENAR'=>'CONCATENAR',
      'UBICACION_GEOGRAFICA'=>'UBICACION_GEOGRAFICA','UBICACIÓN_GEOGRÁFICA'=>'UBICACION_GEOGRAFICA',
      'SALDO_CAPITAL'=>'SALDO_CAPITAL','INTERES'=>'INTERES','INTERÉS'=>'INTERES',
      'DEUDA_TOTAL'=>'DEUDA_TOTAL','CLASIFICACION'=>'CLASIFICACION','CLASIFICACIÓN'=>'CLASIFICACION',
      'CORREO_ELECTRONICO'=>'CORREO_ELECTRONICO','CORREO_ELECTRÓNICO'=>'CORREO_ELECTRONICO',
      // explícitos por si acaso
      'HASTA'=>'HASTA','CAPITAL_DESCUENTO'=>'CAPITAL_DESCUENTO',
    ];

    $toUtf8 = function(?string $s): ?string {
      if ($s===null) return null; $s = trim($s);
      if ($s==='') return null;
      if (!mb_check_encoding($s,'UTF-8')) {
        $s = mb_convert_encoding($s,'UTF-8','UTF-8, ISO-8859-1, Windows-1252');
      }
      return preg_replace('/[\x00-\x1F\x7F]/u','',$s);
    };

    // ¡DEFÍNELO PRIMERO!  ← clave para evitar el 500
    $num = function(?string $v): ?float {
      if ($v===null) return null; $v=trim($v); if($v==='') return null;
      // quita espacios/miles y acepta coma decimal
      if (str_contains($v,',') && str_contains($v,'.')) { $v=str_replace(['.',' '],'',$v); $v=str_replace(',','.',$v); }
      elseif (str_contains($v,',')) { $v=str_replace(['.',' '],'',$v); $v=str_replace(',','.',$v); }
      else { $v=str_replace(' ','',$v); }
      return is_numeric($v)?(float)$v:null;
    };

    // Luego sí, el porcentaje a fracción 0..1
    $frac = function(?string $v) use ($num): ?float {
      if ($v === null) return null;
      $raw = trim($v);
      if ($raw === '') return null;
      // con % explícito
      if (str_ends_with($raw, '%')) {
        $n = $num(rtrim($raw, '%'));
        return $n===null?null:max(0,min(1,$n/100));
      }
      // número "crudo"
      $n = $num($raw);
      if ($n === null) return null;
      return $n > 1 ? max(0,min(1,$n/100)) : max(0,min(1,$n));
    };

    $date = function(?string $v): ?string {
      if(!$v) return null; $v=trim($v);
      if (preg_match('#^\d{4}$#',$v)) return $v.'-01-01';
      if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#',$v,$m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
      if (preg_match('#^\d{4}-\d{2}-\d{2}$#',$v)) return $v;
      return null;
    };

    // ---------- Bucle ----------
    $ok=0; $skip=0; $err=[]; $rowNum=1;

    while(($row=fgetcsv($fh,0,$del))!==false){
      $rowNum++; $data=[];
      foreach($row as $i=>$val){
        $k = $map[$i] ?? null; if(!$k) continue;
        $k = $alias[$k] ?? $k;
        $val = $toUtf8((string)$val);

        switch ($k) {
          case 'CARTERA':                $data['cartera']=$val; break;
          case 'TIPO_DOC':               $data['tipo_doc']=$val; break;
          case 'DNI':                    $data['dni']=$val; break;
          case 'OPERACION':              $data['operacion']=$val; break;
          case 'CONCATENAR':             $data['concatenar']=$val; break;
          case 'AGENTE':                 $data['agente']=$val; break;
          case 'TITULAR':                $data['titular']=$val; break;
          case 'ANO_CASTIGO':
          case 'ANIO_CASTIGO':
          case 'AÑO_CASTIGO':            $data['anio_castigo']=$val? (int)$val:null; break;
          case 'MONEDA':                 $data['moneda']=$val; break;
          case 'ENTIDAD':                $data['entidad']=$val; break;
          case 'PRODUCTO':               $data['producto']=$val; break;
          case 'COSECHA':                $data['cosecha']=$val; break;
          case 'DEPARTAMENTO':           $data['departamento']=$val; break;
          case 'ZONA':                   $data['zona']=$val; break;
          case 'UBICACION_GEOGRAFICA':   $data['ubicacion_geografica']=$val; break;
          case 'FECHA_COMPRA':           $data['fecha_compra']=$date($val); break;
          case 'EDAD':                   $data['edad']=$val? (int)$val:null; break;
          case 'SEXO':                   $data['sexo']=$val; break;
          case 'ESTADO_CIVIL':           $data['estado_civil']=$val; break;
          case 'TELF1':                  $data['telf1']=$val; break;
          case 'TELF2':                  $data['telf2']=$val; break;
          case 'TELF3':                  $data['telf3']=$val; break;

          case 'SALDO_CAPITAL':          $data['saldo_capital']=$num($val); break;
          case 'INTERES':                $data['interes']=$num($val); break;
          case 'DEUDA_TOTAL':            $data['deuda_total']=$num($val); break;

          // NUEVOS
          case 'HASTA':                  $data['hasta'] = $frac($val); break;               // 0.6 | 60 | 60%
          case 'CAPITAL_DESCUENTO':      $data['capital_descuento'] = $num($val); break;   // monto S/

          case 'LABORAL':                $data['laboral']=$val; break;
          case 'VEHICULOS':
          case 'VEHÍCULOS':              $data['vehiculos']=$val; break;
          case 'PROPIEDADES':            $data['propiedades']=$val; break;
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES_':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES__': // varios normalizados
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES___':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES____':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES_____':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES______':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES_______':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES________':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES_________':
          case 'CONSOLIDADO_VEHICULOS_PROPIEDADES__________':
              $data['consolidado_veh_prop']=$val; break;
          case 'CLASIFICACION':          $data['clasificacion']=$val; break;
          case 'SCORE':                  $data['score']=$val? (int)$val:null; break;
          case 'CORREO_ELECTRONICO':     $data['correo_electronico']=$val; break;
          case 'DIRECCION':              $data['direccion']=$val; break;
          case 'PROVINCIA':              $data['provincia']=$val; break;
          case 'DISTRITO':               $data['distrito']=$val; break;
        }
      }

      // concatenar si falta
      if (empty($data['concatenar']) && !empty($data['dni']) && !empty($data['operacion'])) {
        $data['concatenar'] = $data['dni'].$data['operacion'];
      }

      if (empty($data['dni']) && empty($data['operacion']) && empty($data['concatenar'])) {
        $skip++; $err[]="Fila {$rowNum}: sin clave (DNI/OPERACION/CONCATENAR)."; continue;
      }

      try {
        if (!empty($data['dni']) && !empty($data['operacion'])) {
          ClienteCuenta::updateOrCreate(
            ['dni'=>$data['dni'], 'operacion'=>$data['operacion']],
            $data
          );
        } else {
          ClienteCuenta::updateOrCreate(
            ['concatenar'=>$data['concatenar']],
            $data
          );
        }
        $ok++;
      } catch(\Throwable $e){
        $skip++; $err[]="Fila {$rowNum}: ".$e->getMessage();
      }
    }

    fclose($fh);
    return [$ok,$skip,$err];
  }
}