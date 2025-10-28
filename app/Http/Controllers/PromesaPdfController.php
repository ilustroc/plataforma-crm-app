<?php

namespace App\Http\Controllers;

use App\Models\PromesaPago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ilovepdf\Ilovepdf;                 // ⬅️  iLovePDF
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class PromesaPdfController extends Controller
{
    public function acuerdo(PromesaPago $promesa)
    {
        $docxOut = null;

        try {
            // ── 0) Validar credenciales iLovePDF ───────────────────────────────
            $pub = env('ILOVEPDF_PUBLIC_KEY');
            $sec = env('ILOVEPDF_SECRET_KEY');
            if (!$pub || !$sec) {
                abort(500, 'Faltan ILOVEPDF_PUBLIC_KEY o ILOVEPDF_SECRET_KEY en .env');
            }

            // ── 1) Plantilla DOCX (intenta por-DNI y luego genérica) ──────────
            $tplPerDni = storage_path('app/templates/Acuerdo_de_Pago_DNI_'.$promesa->dni.'.docx');
            $tplGener  = storage_path('app/templates/Acuerdo_de_Pago.docx');
            $tpl       = is_file($tplPerDni) ? $tplPerDni : $tplGener;

            if (!is_file($tpl)) {
                abort(404, 'No se encontró la plantilla DOCX en: '.$tplPerDni.' ni '.$tplGener);
            }

            // Carga relaciones
            $promesa->loadMissing(['operaciones', 'cuotas']);

            // ── 2) Datos base (clientes_cuentas) ───────────────────────────────
            $ops = [];
            if ($promesa->relationLoaded('operaciones') && $promesa->operaciones->count()) {
                $ops = $promesa->operaciones->pluck('operacion')->filter()->values()->all();
            } elseif (!empty($promesa->operacion)) {
                $ops = [$promesa->operacion];
            }

            $q = DB::table('clientes_cuentas')->where('dni', $promesa->dni);
            if (!empty($ops)) $q->whereIn('operacion', $ops);
            $cc = $q->orderByDesc('saldo_capital')->first();

            $titular       = $cc->cliente        ?? $cc->nombre_cliente ?? $cc->titular ?? '—';
            $entidad       = $cc->entidad        ?? '—';
            $saldoCapital  = (float)($cc->saldo_capital ?? 0);
            $operacionText = $ops ? implode(', ', $ops) : ($promesa->operacion ?? '—');

            // ── 3) Filas de cuotas ────────────────────────────────────────────
            $rows = [];
            $fmtMoney = function ($v) {
                $v = (float)$v;
                return fmod($v, 1.0) == 0.0 ? number_format($v, 0, '.', ',') : number_format($v, 2, '.', ',');
            };
            $fmtDate = fn(Carbon $d) => $d->format('d/m/Y');

            if ($promesa->tipo === 'cancelacion') {
                $fecha1 = $promesa->fecha_pago ?? $promesa->fecha_promesa ?? now();
                $monto  = ($promesa->monto ?? null) ?: ($promesa->monto_convenio ?? 0);
                $rows[] = [
                    'nro_cuotas' => '01',
                    'fecha_pago' => $fmtDate(Carbon::parse($fecha1)),
                    'monto_cuota'=> $fmtMoney($monto),
                ];
            } else {
                if ($promesa->relationLoaded('cuotas') && $promesa->cuotas->count() > 0) {
                    foreach ($promesa->cuotas as $c) {
                        $num = str_pad($c->nro, 2, '0', STR_PAD_LEFT) . ($c->es_balon ? ' (Balón)' : '');
                        $rows[] = [
                            'nro_cuotas' => $num,
                            'fecha_pago' => $fmtDate($c->fecha instanceof Carbon ? $c->fecha : Carbon::parse($c->fecha)),
                            'monto_cuota'=> $fmtMoney($c->monto),
                        ];
                    }
                } else {
                    $n     = max(1, (int)($promesa->nro_cuotas ?? 1));
                    $first = Carbon::parse($promesa->fecha_pago ?? $promesa->fecha_promesa ?? now());
                    $mCuota = (float)($promesa->monto_cuota ?? 0);
                    if ($mCuota <= 0 && (float)($promesa->monto_convenio ?? 0) > 0) {
                        $mCuota = ((float)$promesa->monto_convenio) / $n;
                    }
                    for ($i = 0; $i < $n; $i++) {
                        $d = $first->copy()->addMonthsNoOverflow($i);
                        $rows[] = [
                            'nro_cuotas' => str_pad($i+1, 2, '0', STR_PAD_LEFT),
                            'fecha_pago' => $fmtDate($d),
                            'monto_cuota'=> $fmtMoney($mCuota),
                        ];
                    }
                }
            }

            // ── 4) Llenar DOCX ────────────────────────────────────────────────
            $doc = new TemplateProcessor($tpl);
            $doc->setValue('titular',       $titular);
            $doc->setValue('dni',           $promesa->dni);
            $doc->setValue('entidad',       $entidad);
            $doc->setValue('operacion',     $operacionText);
            $doc->setValue('saldo_capital', number_format($saldoCapital, 2, '.', ','));

            if (method_exists($doc, 'cloneRowAndSetValues')) {
                $doc->cloneRowAndSetValues('nro_cuotas', $rows);
            } else {
                $doc->cloneRow('nro_cuotas', count($rows));
                foreach ($rows as $i => $r) {
                    $idx = $i + 1;
                    foreach ($r as $k => $v) $doc->setValue("{$k}#{$idx}", $v);
                }
            }

            $tmpDir  = storage_path('app/tmp');
            if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

            $baseName = "Acuerdo_{$promesa->dni}_{$promesa->id}";
            $docxOut  = $tmpDir . "/{$baseName}.docx";
            $pdfOut   = $tmpDir . "/{$baseName}.pdf";
            $doc->saveAs($docxOut);

            // ── 5) Convertir DOCX→PDF con iLovePDF ────────────────────────────
            $ilovepdf = new Ilovepdf($pub, $sec);
            $task     = $ilovepdf->newTask('officepdf');     // tipo de tarea: Office → PDF
            // si la lib soporta desempaquetar directo:
            if (method_exists($task, 'setPackaged')) $task->setPackaged(false);
            if (method_exists($task, 'setOutputFilename')) $task->setOutputFilename($baseName.'.pdf');

            $task->addFile($docxOut);
            $task->execute();
            $task->download($tmpDir);

            // Asegurar ruta del PDF resultante
            if (!is_file($pdfOut)) {
                // fallback: buscar el PDF descargado si la lib lo nombra distinto
                $candidates = glob($tmpDir.'/*.pdf');
                if ($candidates) { $pdfOut = $candidates[0]; }
            }

            if (!is_file($pdfOut)) {
                throw new \RuntimeException('No se encontró el PDF generado por iLovePDF.');
            }

            return response()->file($pdfOut, [
                'Content-Type'  => 'application/pdf',
                'Cache-Control' => 'private, max-age=0, no-store, no-cache, must-revalidate',
            ]);
        } catch (Throwable $e) {
            Log::error('Error generando Acuerdo PDF', [
                'promesa_id' => $promesa->id ?? null,
                'msg'        => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            // Si al menos tenemos el DOCX, lo ofrecemos de descarga
            if (!empty($docxOut) && is_file($docxOut)) {
                return response()->download($docxOut, "Acuerdo_{$promesa->dni}.docx");
            }

            abort(500, 'No se pudo generar el PDF del acuerdo.');
        }
    }
}
