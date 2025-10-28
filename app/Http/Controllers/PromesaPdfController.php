<?php

namespace App\Http\Controllers;

use App\Models\PromesaPago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ilovepdf\Ilovepdf;                 // ⬅️  iLovePDF
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use PhpOffice\PhpWord\Settings;
use ZipArchive;
use Throwable;

class PromesaPdfController extends Controller
{
public function acuerdo(PromesaPago $promesa)
{
    $docxOut = null;
    try {
        // ── 0) Credenciales iLovePDF
        $pub = env('ILOVEPDF_PUBLIC_KEY');
        $sec = env('ILOVEPDF_SECRET_KEY');
        if (!$pub || !$sec) abort(500, 'Faltan ILOVEPDF_PUBLIC_KEY / ILOVEPDF_SECRET_KEY');

        // ── 1) Plantilla DOCX (per-DNI o genérica)
        $tplPerDni = storage_path('app/templates/Acuerdo_de_Pago_DNI_'.$promesa->dni.'.docx');
        $tplGener  = storage_path('app/templates/Acuerdo_de_Pago.docx');
        $tpl       = is_file($tplPerDni) ? $tplPerDni : $tplGener;
        if (!is_file($tpl)) abort(404, 'No se encontró la plantilla: '.$tplPerDni.' ni '.$tplGener);

        // ── 2) Datos base de cliente + operaciones
        $promesa->loadMissing(['operaciones', 'cuotas']);
        $ops = $promesa->operaciones?->pluck('operacion')->filter()->values()->all()
             ?: (empty($promesa->operacion) ? [] : [$promesa->operacion]);

        $q = DB::table('clientes_cuentas')->where('dni', $promesa->dni);
        if ($ops) $q->whereIn('operacion', $ops);
        $cc = $q->orderByDesc('saldo_capital')->first();

        $titular       = $cc->cliente ?? $cc->nombre_cliente ?? $cc->titular ?? '—';
        $entidad       = $cc->entidad ?? '—';
        $saldoCapital  = (float)($cc->saldo_capital ?? 0);
        $operacionText = $ops ? implode(', ', $ops) : ($promesa->operacion ?? '—');

        // ── 3) Construir filas de cuotas
        $rows = [];
        $fmtMoney = function ($v) {
            $v = (float)$v;
            return fmod($v,1.0)==0.0 ? number_format($v,0,'.',',') : number_format($v,2,'.',',');
        };
        $fmtDate = fn(Carbon $d) => $d->format('d/m/Y');

        if ($promesa->tipo === 'cancelacion') {
            $fecha1 = $promesa->fecha_pago ?? $promesa->fecha_promesa ?? now();
            $monto  = ($promesa->monto ?? null) ?: ($promesa->monto_convenio ?? 0);
            $rows[] = ['nro_cuotas'=>'01','fecha_pago'=>$fmtDate(Carbon::parse($fecha1)),'monto_cuota'=>$fmtMoney($monto)];
        } else {
            if ($promesa->cuotas && $promesa->cuotas->count()) {
                foreach ($promesa->cuotas as $c) {
                    $num = str_pad($c->nro,2,'0',STR_PAD_LEFT).($c->es_balon?' (Balón)':'');
                    $rows[] = [
                        'nro_cuotas' => $num,
                        'fecha_pago' => $fmtDate($c->fecha instanceof Carbon ? $c->fecha : Carbon::parse($c->fecha)),
                        'monto_cuota'=> $fmtMoney($c->monto),
                    ];
                }
            } else {
                $n = max(1, (int)($promesa->nro_cuotas ?? 1));
                $first = Carbon::parse($promesa->fecha_pago ?? $promesa->fecha_promesa ?? now());
                $mCuota = (float)($promesa->monto_cuota ?? 0);
                if ($mCuota<=0 && (float)($promesa->monto_convenio ?? 0)>0) $mCuota=((float)$promesa->monto_convenio)/$n;
                for ($i=0; $i<$n; $i++) {
                    $d = $first->copy()->addMonthsNoOverflow($i);
                    $rows[] = ['nro_cuotas'=>str_pad($i+1,2,'0',STR_PAD_LEFT),'fecha_pago'=>$fmtDate($d),'monto_cuota'=>$fmtMoney($mCuota)];
                }
            }
        }

        // ── 4) Llenar DOCX
        $doc = new TemplateProcessor($tpl);
        $doc->setValue('titular', $titular);
        $doc->setValue('dni', $promesa->dni);
        $doc->setValue('entidad', $entidad);
        $doc->setValue('operacion', $operacionText);
        $doc->setValue('saldo_capital', number_format($saldoCapital,2,'.',','));

        if (method_exists($doc,'cloneRowAndSetValues')) {
            $doc->cloneRowAndSetValues('nro_cuotas', $rows);
        } else {
            $doc->cloneRow('nro_cuotas', count($rows));
            foreach ($rows as $i=>$r) {
                $idx=$i+1; foreach ($r as $k=>$v) $doc->setValue("{$k}#{$idx}", $v);
            }
        }

        $tmpDir   = storage_path('app/tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
        $baseName = "Acuerdo_{$promesa->dni}_{$promesa->id}";
        $docxOut  = $tmpDir . "/{$baseName}.docx";
        $doc->saveAs($docxOut);

        // ───────────────────────────────────────────────────────────────
        // 5) CONVERTIR DOCX → PDF con iLovePDF (usando officepdf)
        //    y manejar descarga empaquetada (ZIP) si aplica.
        // ───────────────────────────────────────────────────────────────
        $ilovepdf = new Ilovepdf($pub, $sec);
        $task     = $ilovepdf->newTask('officepdf');
        $task->addFile($docxOut);
        if (method_exists($task,'setOutputFilename')) {
            $task->setOutputFilename($baseName . '.pdf');   // sugerir nombre
        }
        // Si la SDK empaqueta por defecto, intenta desactivar empaquetado:
        if (method_exists($task,'setPackaged')) $task->setPackaged(false);

        $task->execute();
        $task->download($tmpDir);

        // Resolver PDF real (puede venir como .pdf o .zip)
        $pdfOut = $tmpDir . "/{$baseName}.pdf";
        if (!is_file($pdfOut)) {
            // Si llegó como zip, extraer y localizar el PDF
            foreach (glob($tmpDir.'/*.zip') as $zipFile) {
                $zip = new ZipArchive();
                if ($zip->open($zipFile) === true) {
                    $zip->extractTo($tmpDir);
                    $zip->close();
                }
            }
            // Buscar el PDF más reciente
            $candidates = array_filter(glob($tmpDir.'/*.pdf'), 'is_file');
            if ($candidates) {
                usort($candidates, fn($a,$b)=>filemtime($b)<=>filemtime($a));
                $pdfOut = $candidates[0];
            }
        }

        if (!is_file($pdfOut)) {
            throw new RuntimeException('No se encontró el PDF generado por iLovePDF.');
        }

        return response()->file($pdfOut, [
            'Content-Type'  => 'application/pdf',
            'Cache-Control' => 'private, max-age=0, no-store, no-cache, must-revalidate',
        ]);

    } catch (Throwable $e) {
        Log::error('Error iLovePDF DOCX→PDF, usando fallback', [
            'promesa_id' => $promesa->id ?? null,
            'msg'        => $e->getMessage(),
        ]);

        // Fallback: convertir con mPDF (PhpWord → PDF)
        try {
            if (!empty($docxOut) && is_file($docxOut)) {
                Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
                Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));
                $phpWord = IOFactory::load($docxOut);
                $pdfOut  = dirname($docxOut) . '/' . pathinfo($docxOut, PATHINFO_FILENAME) . '.pdf';
                IOFactory::createWriter($phpWord, 'PDF')->save($pdfOut);

                return response()->file($pdfOut, [
                    'Content-Type'  => 'application/pdf',
                    'Cache-Control' => 'private, max-age=0, no-store, no-cache, must-revalidate',
                ]);
            }
        } catch (Throwable $e2) {
            Log::error('Fallback mPDF también falló', ['msg'=>$e2->getMessage()]);
        }

        // Al menos ofrece el DOCX si existe
        if (!empty($docxOut) && is_file($docxOut)) {
            return response()->download($docxOut, "Acuerdo_{$promesa->dni}.docx");
        }
        abort(500, 'No se pudo transformar el Word a PDF.');
    }
}

}
