<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;

class CnaController extends Controller
{
    // =====================  CREAR SOLICITUD  =====================
    public function store(Request $request, string $dni)
    {
        $data = $request->validate([
            'producto'              => ['nullable','string','max:150'],
            'titular'               => ['nullable','string','max:150'],
            'nota'                  => ['nullable','string','max:1000'],
            'observacion'           => ['nullable','string','max:1000'],
            'fecha_pago_realizado'  => ['required','date'],
            'monto_pagado'          => ['required','numeric','min:0.01','max:999999999.99'],
            'operaciones'           => ['required','array','min:1'],
            'operaciones.*'         => ['string','max:50'],
        ], [], [
            'fecha_pago_realizado'  => 'fecha de pago realizado',
            'monto_pagado'          => 'monto pagado',
            'operaciones'           => 'operaciones',
        ]);

        $ops = array_values(array_filter(array_map('strval', $data['operaciones'] ?? [])));
        if (empty($ops)) {
            return back()->withErrors('Selecciona al menos una operación para la CNA.');
        }

        // Si no envían titular, lo inferimos
        $titular = $data['titular'] ?? DB::table('clientes_cuentas')
            ->where('dni',$dni)->whereNotNull('titular')->value('titular');

        $solicitud = DB::transaction(function () use ($dni, $data, $ops, $titular) {
            $last = DB::table('cna_solicitudes')->lockForUpdate()->max('correlativo');
            $next = ((int)$last) + 1;
            $nro  = str_pad((string)$next, 6, '0', STR_PAD_LEFT);

            return CnaSolicitud::create([
                'correlativo'          => $next,
                'nro_carta'            => $nro,
                'dni'                  => $dni,
                'titular'              => $titular,
                'producto'             => $data['producto']   ?? null,
                'operaciones'          => $ops,
                'nota'                 => $data['nota']       ?? null,
                'observacion'          => $data['observacion'] ?? null,
                'fecha_pago_realizado' => $data['fecha_pago_realizado'],
                'monto_pagado'         => $data['monto_pagado'],
                'workflow_estado'      => 'pendiente',
                'user_id'              => Auth::id(),
            ]);
        });

        return back()->with('ok', "Solicitud de CNA enviada. N.º {$solicitud->nro_carta}");
    }

    // =====================  FLUJO DE APROBACIÓN  =====================
    public function preaprobar(CnaSolicitud $cna)
    {
        $this->authorizeRole('supervisor');
        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede pre-aprobar una solicitud pendiente.');
        }
        $cna->update([
            'workflow_estado' => 'preaprobada',
            'pre_aprobado_por'=> Auth::id(),
            'pre_aprobado_at' => now(),
            'rechazado_por'   => null,
            'rechazado_at'    => null,
            'motivo_rechazo'  => null,
        ]);
        return back()->with('ok', 'CNA pre-aprobada.');
    }

    public function rechazarSup(Request $request, CnaSolicitud $cna)
    {
        $this->authorizeRole('supervisor');
        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede rechazar una solicitud pendiente.');
        }
        $cna->update([
            'workflow_estado' => 'rechazada_sup',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'motivo_rechazo'  => substr((string)$request->input('nota_estado',''), 0, 500),
        ]);
        return back()->with('ok', 'CNA rechazada por supervisor.');
    }

    public function aprobar(CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');
        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');
        }

        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
        ]);

        // Genera DOCX + PDF (LibreOffice → PDF; fallback Blade/DomPDF)
        $this->generateOutputsFromTemplate($cna);

        return back()->with('ok', 'CNA aprobada y archivos generados.');
    }

    public function rechazarAdmin(Request $request, CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');
        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede rechazar una solicitud pre-aprobada.');
        }
        $cna->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'motivo_rechazo'  => substr((string)$request->input('nota_estado',''), 0, 500),
        ]);
        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    // =====================  DESCARGAS  =====================
    public function pdf($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        // Si falta el PDF pero hay DOCX, intentamos convertir ahora
        if ((!$cna->pdf_path || !Storage::exists($cna->pdf_path)) && $cna->docx_path && Storage::exists($cna->docx_path)) {
            $this->convertDocxToPdfPreferLibreOffice(storage_path('app/'.$cna->docx_path), $cna);
            $cna->refresh();
        }

        if ($cna->pdf_path && Storage::exists($cna->pdf_path)) {
            return Storage::download($cna->pdf_path, basename($cna->pdf_path));
        }

        // Último recurso: genera PDF con Blade y devuélvelo al vuelo
        $rows = $this->rowsByOperacion($cna);
        $fileName = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        return Pdf::loadView('cna.pdf', ['cna'=>$cna, 'rows'=>$rows])->setPaper('A4')->download($fileName);
    }

    public function docx($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') abort(403, 'Solo disponible para CNA aprobadas.');
        if (!$cna->docx_path || !Storage::exists($cna->docx_path)) abort(404, 'Archivo DOCX no encontrado.');
        return Storage::download($cna->docx_path, basename($cna->docx_path));
    }

    // =====================  HELPERS  =====================
    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) abort(403, 'No autorizado.');
    }

    /**
     * Rellena la plantilla DOCX y genera PDF con LibreOffice (si existe).
     * Fallback: Blade/DomPDF.
     */
    private function generateOutputsFromTemplate(CnaSolicitud $cna): void
    {
        $tplPath = storage_path('app/templates/cna_template.docx');
        if (!is_file($tplPath)) {
            throw new \RuntimeException('Plantilla cna_template.docx no encontrada en storage/app/templates/');
        }

        $docxDir = 'cna/docx';
        $pdfDir  = 'cna/pdfs';
        Storage::makeDirectory($docxDir);
        Storage::makeDirectory($pdfDir);

        $docxRel = $docxDir.'/'.sprintf('CNA %s - %s.docx', $cna->nro_carta, $cna->dni);
        $pdfRel  = $pdfDir .'/'.sprintf('CNA %s - %s.pdf',  $cna->nro_carta, $cna->dni);

        // ===== Relleno del DOCX =====
        $tp = new TemplateProcessor($tplPath);

        // Campos simples
        $titular = $cna->titular ?? DB::table('clientes_cuentas')->where('dni',$cna->dni)->value('titular');
        $tp->setValue('nro_carta', $cna->nro_carta);
        $tp->setValue('NRO_CARTA', $cna->nro_carta);
        $tp->setValue('dni',       $cna->dni);
        $tp->setValue('DNI',       $cna->dni);
        $tp->setValue('titular',   (string)($titular ?? ''));
        $tp->setValue('TITULAR',   (string)($titular ?? ''));

        // Filas por operación
        $ops = array_values(array_filter(array_map('strval', (array)$cna->operaciones)));
        $byOp = DB::table('clientes_cuentas')
            ->select('operacion','producto','entidad')
            ->whereIn('operacion', $ops)->get()->keyBy('operacion');

        $rows = max(count($ops), 1);
        $tp->cloneRow('OPERACION', $rows);

        if ($rows === 1) {
            $op  = $ops[0] ?? '—';
            $row = $byOp->get($op);
            $tp->setValue('OPERACION#1', $op ?: '—');
            $tp->setValue('PRODUCTO#1',  $row->producto ?? '—');
            $tp->setValue('ENTIDAD#1',   $row->entidad ?? '—');
        } else {
            foreach ($ops as $i => $op) {
                $row = $byOp->get($op);
                $n   = $i + 1;
                $tp->setValue("OPERACION#{$n}", $op ?: '—');
                $tp->setValue("PRODUCTO#{$n}",  $row->producto ?? '—');
                $tp->setValue("ENTIDAD#{$n}",   $row->entidad ?? '—');
            }
        }

        // Guardar DOCX
        $tp->saveAs(storage_path('app/'.$docxRel));

        // ===== Convertir DOCX → PDF (preferir LibreOffice) =====
        $ok = $this->convertDocxToPdfPreferLibreOffice(storage_path('app/'.$docxRel), $cna, $pdfRel);

        // Si no se pudo con LibreOffice, intentamos con Blade/DomPDF (se ve mejor que PhpWord→PDF)
        if (!$ok) {
            try {
                $rowsBlade = $this->rowsByOperacion($cna);
                $pdf = Pdf::loadView('cna.pdf', ['cna'=>$cna, 'rows'=>$rowsBlade])->setPaper('A4');
                Storage::put($pdfRel, $pdf->output());
                $cna->pdf_path = $pdfRel;
            } catch (\Throwable $e) {
                Log::error('Fallback Blade PDF para CNA falló: '.$e->getMessage(), ['cna_id'=>$cna->id]);
                $cna->pdf_path = null;
            }
        }

        $cna->docx_path = $docxRel;
        $cna->save();
    }

    /**
     * Intenta convertir el DOCX a PDF usando LibreOffice (soffice --headless).
     * Devuelve true/false y setea $cna->pdf_path si salió bien.
     */
    private function convertDocxToPdfPreferLibreOffice(string $docxAbs, CnaSolicitud $cna, ?string $pdfRel = null): bool
    {
        try {
            $bin = trim((string) (env('LIBREOFFICE_BIN') ?: ''));
            if ($bin === '' || !is_executable($bin)) {
                // Probar rutas comunes
                foreach (['/usr/bin/soffice','/usr/lib/libreoffice/program/soffice','/usr/local/bin/soffice'] as $p) {
                    if (is_executable($p)) { $bin = $p; break; }
                }
            }
            if ($bin === '' || !is_executable($bin)) {
                return false; // no hay libreoffice
            }

            $outDir = dirname($docxAbs); // genera a mismo directorio
            $process = new Process([$bin, '--headless', '--norestore', '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $outDir, $docxAbs]);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('LibreOffice conversión falló', ['cna_id'=>$cna->id, 'error'=>$process->getErrorOutput()]);
                return false;
            }

            // LibreOffice genera con mismo nombre .pdf
            $generated = $outDir.'/'.preg_replace('/\.docx$/i', '.pdf', basename($docxAbs));
            if (!is_file($generated)) {
                return false;
            }

            // Mover a storage (si nos dieron destino)
            if ($pdfRel) {
                Storage::put($pdfRel, file_get_contents($generated));
                @unlink($generated);
                $cna->pdf_path = $pdfRel;
            }
            return true;

        } catch (\Throwable $e) {
            Log::error('Error ejecutando LibreOffice: '.$e->getMessage(), ['cna_id'=>$cna->id]);
            return false;
        }
    }

    /** Arma filas [producto, operacion, entidad] para la vista Blade */
    private function rowsByOperacion(CnaSolicitud $cna): array
    {
        $ops = array_values(array_filter(array_map('strval', (array)$cna->operaciones)));
        if (empty($ops)) return [];
        $rows = DB::table('clientes_cuentas')
            ->select('operacion','producto','entidad')
            ->whereIn('operacion', $ops)->orderBy('operacion')->get();
        return $rows->map(fn($r)=>[
            'producto'  => (string)($r->producto ?? '—'),
            'operacion' => (string)($r->operacion ?? '—'),
            'entidad'   => (string)($r->entidad ?? '—'),
        ])->all();
    }
}
