<?php

namespace App\Http\Controllers;

use App\Models\PromesaPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use Carbon\Carbon;
use Throwable;

class PromesaPdfController extends Controller
{
    public function acuerdo(PromesaPago $promesa)
    {
        try {
            // ---- 1) Plantilla ----
            $tpl = storage_path('app/templates/Acuerdo_de_Pago_DNI_{dni}.docx');
            if (!is_file($tpl)) {
                abort(404, 'No se encontró la plantilla DOCX en: '.$tpl);
            }

            // Carga relaciones necesarias
            $promesa->loadMissing(['operaciones', 'cuotas']);

            // ---- 2) Datos base (clientes_cuentas) ----
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

            // ---- 3) Construir filas de cuotas ----
            $rows = [];
            $fmtMoney = function ($v) {
                $v = (float)$v;
                return fmod($v, 1.0) == 0.0 ? number_format($v, 0, '.', ',') : number_format($v, 2, '.', ',');
            };
            $fmtDate = fn(Carbon $d) => $d->format('d/m/Y');

            if ($promesa->tipo === 'cancelacion') {
                // Una sola fila
                $fecha1 = $promesa->fecha_pago ?? $promesa->fecha_promesa ?? now();
                $monto  = ($promesa->monto ?? null) ?: ($promesa->monto_convenio ?? 0);
                $rows[] = [
                    'nro_cuotas' => '01',
                    'fecha_pago' => $fmtDate(Carbon::parse($fecha1)),
                    'monto_cuota'=> $fmtMoney($monto),
                ];
            } else {
                // CONVENIO: usa cronograma guardado si existe
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
                    // Fallback: autogenerado (por compatibilidad)
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

            // ---- 4) Llenar DOCX ----
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
                    foreach ($r as $k => $v) {
                        $doc->setValue("{$k}#{$idx}", $v);
                    }
                }
            }

            // ---- 5) Guardar y convertir a PDF con mPDF ----
            $tmpDir  = storage_path('app/tmp');
            if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

            $docxOut = $tmpDir . "/Acuerdo_{$promesa->dni}_{$promesa->id}.docx";
            $pdfOut  = $tmpDir . "/Acuerdo_{$promesa->dni}_{$promesa->id}.pdf";

            $doc->saveAs($docxOut);

            Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
            Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));

            $phpWord = IOFactory::load($docxOut);
            IOFactory::createWriter($phpWord, 'PDF')->save($pdfOut);

            return response()->file($pdfOut, [
                'Content-Type' => 'application/pdf',
                'Cache-Control'=> 'private, max-age=0, no-store, no-cache, must-revalidate',
            ]);
        } catch (Throwable $e) {
            Log::error('Error generando Acuerdo PDF', [
                'promesa_id' => $promesa->id ?? null,
                'msg'        => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            if (!empty($docxOut ?? null) && is_file($docxOut)) {
                return response()->download($docxOut, "Acuerdo_{$promesa->dni}.docx");
            }
            abort(500, 'No se pudo generar el PDF del acuerdo.');
        }
    }
}