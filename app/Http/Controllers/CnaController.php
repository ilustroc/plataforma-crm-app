<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;
use Ilovepdf\Ilovepdf;

class CnaController extends Controller
{
    // -------------------- CREAR SOLICITUD --------------------
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
        if (empty($ops)) return back()->withErrors('Selecciona al menos una operación para la CNA.');

        $titular = $data['titular'] ?? DB::table('clientes_cuentas')
            ->where('dni', $dni)->whereNotNull('titular')->value('titular');

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

    // -------------------- FLUJO APROBACIÓN --------------------
    public function preaprobar(CnaSolicitud $cna)
    {
        $this->authorizeRole('supervisor');
        if ($cna->workflow_estado !== 'pendiente')
            return back()->withErrors('Solo se puede pre-aprobar una solicitud pendiente.');

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
        if ($cna->workflow_estado !== 'pendiente')
            return back()->withErrors('Solo se puede rechazar una solicitud pendiente.');

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
        if ($cna->workflow_estado !== 'preaprobada')
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');

        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
        ]);

        // Genera DOCX desde plantilla y lo CONVIERTE a PDF (LibreOffice/unoconv)
        $this->generateOutputsFromTemplate($cna);

        return back()->with('ok', 'CNA aprobada y archivos generados.');
    }

    public function rechazarAdmin(Request $request, CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');
        if ($cna->workflow_estado !== 'preaprobada')
            return back()->withErrors('Solo se puede rechazar una solicitud pre-aprobada.');

        $cna->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'motivo_rechazo'  => substr((string)$request->input('nota_estado',''), 0, 500),
        ]);

        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    // -------------------- DESCARGAS --------------------
    public function pdf($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') abort(403, 'Solo disponible para CNA aprobadas.');

        // si falta el PDF, intentar convertir ahora desde el DOCX (sin re-plantilla)
        if ((!$cna->pdf_path || !Storage::exists($cna->pdf_path)) && $cna->docx_path && Storage::exists($cna->docx_path)) {
            $pdfDir  = 'cna/pdfs';
            Storage::makeDirectory($pdfDir);
            $pdfRel  = $pdfDir.'/'.pathinfo($cna->docx_path, PATHINFO_FILENAME).'.pdf';

            $ok = $this->convertDocxToPdf(Storage::path($cna->docx_path), Storage::path($pdfRel));
            if ($ok) {
                $cna->update(['pdf_path' => $pdfRel]);
            }
        }

        if ($cna->pdf_path && Storage::exists($cna->pdf_path)) {
            return Storage::download($cna->pdf_path, basename($cna->pdf_path));
        }

        // si no se pudo, ofrecer el DOCX
        if ($cna->docx_path && Storage::exists($cna->docx_path)) {
            return Storage::download($cna->docx_path, basename($cna->docx_path));
        }

        abort(404, 'Archivo no encontrado.');
    }

    public function docx($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') abort(403, 'Solo disponible para CNA aprobadas.');
        if (!$cna->docx_path || !Storage::exists($cna->docx_path)) abort(404, 'Archivo DOCX no encontrado.');
        return Storage::download($cna->docx_path, basename($cna->docx_path));
    }

    // -------------------- HELPERS --------------------
    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /**
     * Rellena storage/app/templates/cna_template.docx y crea:
     *  - DOCX en storage/app/cna/docx
     *  - PDF en storage/app/cna/pdfs (conversión externa: soffice/unoconv)
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

        $docxName = "CNA {$cna->nro_carta} - {$cna->dni}.docx";
        $pdfName  = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        $docxRel  = $docxDir.'/'.$docxName;
        $pdfRel   = $pdfDir.'/'.$pdfName;

        // ====== 1) Rellenar DOCX con TemplateProcessor ======
        $tp = new \PhpOffice\PhpWord\TemplateProcessor($tplPath);

        // Datos principales
        $titular = $cna->titular ?? \DB::table('clientes_cuentas')->where('dni',$cna->dni)->value('titular');
        foreach ([
            'nro_carta' => $cna->nro_carta,
            'NRO_CARTA' => $cna->nro_carta,
            'dni'       => $cna->dni,
            'DNI'       => $cna->dni,
            'titular'   => (string)($titular ?? ''),
            'TITULAR'   => (string)($titular ?? ''),
            // Agrega otros placeholders si tu plantilla los usa:
            'FECHA_PAGO' => optional($cna->fecha_pago_realizado)->format('d/m/Y'),
            'MONTO_PAGADO' => number_format((float)$cna->monto_pagado, 2),
            'OBSERVACION'  => (string)($cna->observacion ?? ''),
        ] as $k => $v) {
            $tp->setValue($k, $v);
        }

        // Filas Producto/Operación/Entidad (una por operación)
        $ops = array_values(array_filter(array_map('strval', (array)$cna->operaciones)));
        $byOp = collect();
        if ($ops) {
            $byOp = \DB::table('clientes_cuentas')
                ->select('operacion','producto','entidad')
                ->whereIn('operacion', $ops)
                ->get()
                ->keyBy('operacion');
        }

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

        // Guardar DOCX resultante
        $tp->saveAs(storage_path('app/'.$docxRel));

        // ====== 2) Convertir DOCX→PDF vía iLovePDF ======
        try {
            $this->convertDocxToPdfViaIlovepdf(
                storage_path('app/'.$docxRel),
                storage_path('app/'.$pdfRel)
            );
            $cna->pdf_path = $pdfRel;
        } catch (\Throwable $e) {
            Log::error('Error iLovePDF DOCX→PDF: '.$e->getMessage(), ['cna_id'=>$cna->id]);
            $cna->pdf_path = null; // dejamos al menos el DOCX
        }

        // Persistir rutas
        $cna->docx_path = $docxRel;
        $cna->save();
    }

    /**
     * Convierte un DOCX en PDF usando:
     * 1) soffice --headless (LibreOffice)
     * 2) unoconv
     */
    private function convertDocxToPdfViaIlovepdf(string $docxAbs, string $pdfAbs): void
    {
        $public = config('services.ilovepdf.public');
        $secret = config('services.ilovepdf.secret');
        if (!$public || !$secret) {
            throw new \RuntimeException('Faltan claves de iLovePDF (config/services.ilovepdf).');
        }

        $ilovepdf = new \Ilovepdf\Ilovepdf($public, $secret);
        $task = $ilovepdf->newTask('officepdf'); // convierte Office → PDF
        $task->addFile($docxAbs);
        $task->execute();

        $outDir = dirname($pdfAbs);
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }
        $task->download($outDir);

        // Renombrar al nombre final si hace falta
        $generated = $outDir.'/'.basename($docxAbs, '.docx').'.pdf';
        if (!is_file($generated)) {
            $latest = collect(glob($outDir.'/*.pdf'))
                ->sortByDesc(fn($p) => filemtime($p))
                ->first();
            if ($latest) $generated = $latest;
        }
        if (!is_file($generated)) {
            throw new \RuntimeException('No se pudo localizar el PDF descargado por iLovePDF.');
        }
        if ($generated !== $pdfAbs) {
            @unlink($pdfAbs);
            rename($generated, $pdfAbs);
        }
    }

    private function hasBinary(string $bin): bool
    {
        $proc = new Process(['bash','-lc',"command -v {$bin} || which {$bin}"]);
        $proc->run();
        return trim($proc->getOutput()) !== '';
    }
}
