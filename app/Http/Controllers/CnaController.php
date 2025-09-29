<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class CnaController extends Controller
{
    /* =========================
     *  CREAR SOLICITUD
     * ========================= */
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
        if (!$ops) {
            return back()->withErrors('Selecciona al menos una operación para la CNA.');
        }

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

    /* =========================
     *  FLUJO APROBACIÓN
     * ========================= */
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

    public function rechazarSup(Request $r, CnaSolicitud $cna)
    {
        $this->authorizeRole('supervisor');
        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede rechazar una solicitud pendiente.');
        }
        $cna->update([
            'workflow_estado' => 'rechazada_sup',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'motivo_rechazo'  => substr((string)$r->input('nota_estado',''), 0, 500),
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

        // Genera SOLO el DOCX; el PDF lo convertimos al descargar.
        $this->makeDocxFromTemplate($cna);

        return back()->with('ok', 'CNA aprobada.');
    }

    public function rechazarAdmin(Request $r, CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');
        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede rechazar una solicitud pre-aprobada.');
        }
        $cna->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'motivo_rechazo'  => substr((string)$r->input('nota_estado',''), 0, 500),
        ]);
        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    /* =========================
     *  DESCARGAS
     * ========================= */
    public function pdf($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        // Si ya existe el PDF, descárgalo
        if ($cna->pdf_path && Storage::exists($cna->pdf_path)) {
            return Storage::download($cna->pdf_path, basename($cna->pdf_path));
        }

        // Asegúrate de que exista el DOCX; si no, créalo
        if (!$cna->docx_path || !Storage::exists($cna->docx_path)) {
            $this->makeDocxFromTemplate($cna);
            $cna->refresh();
        }

        // Intenta convertir ahora el DOCX → PDF
        try {
            $pdfRel = $this->convertDocxToPdf($cna->docx_path, $cna->nro_carta, $cna->dni);
            $cna->update(['pdf_path' => $pdfRel]);

            return Storage::download($pdfRel, basename($pdfRel));
        } catch (\Throwable $e) {
            Log::error('CNA: error al convertir DOCX a PDF', [
                'cna_id' => $cna->id,
                'msg'    => $e->getMessage(),
            ]);

            // Último recurso: entrega el DOCX para que el usuario no vea 500
            return Storage::download($cna->docx_path, basename($cna->docx_path));
        }
    }

    public function docx($id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }
        if (!$cna->docx_path || !Storage::exists($cna->docx_path)) {
            abort(404, 'Archivo DOCX no encontrado.');
        }
        return Storage::download($cna->docx_path, basename($cna->docx_path));
    }

    /* =========================
     *  HELPERS
     * ========================= */
    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /** Crea el DOCX desde storage/app/templates/cna_template.docx y lo guarda en storage/app/cna/docx */
    private function makeDocxFromTemplate(CnaSolicitud $cna): void
    {
        $tplPath = storage_path('app/templates/cna_template.docx');
        if (!is_file($tplPath)) {
            throw new \RuntimeException('Falta la plantilla: storage/app/templates/cna_template.docx');
        }

        $docxDir = 'cna/docx';
        Storage::makeDirectory($docxDir);

        $docxRel  = $docxDir.'/'.("CNA {$cna->nro_carta} - {$cna->dni}.docx");

        // Datos de las operaciones seleccionadas
        $ops = array_values(array_filter(array_map('strval', (array)$cna->operaciones)));
        $byOp = collect();
        if ($ops) {
            $byOp = DB::table('clientes_cuentas')
                ->select('operacion','producto','entidad')
                ->whereIn('operacion', $ops)->get()->keyBy('operacion');
        }

        $titular = $cna->titular ?? DB::table('clientes_cuentas')->where('dni',$cna->dni)->value('titular');

        $tp = new TemplateProcessor($tplPath);
        // placeholders (aceptamos minúsculas/mayúsculas)
        foreach ([
            'nro_carta' => $cna->nro_carta, 'NRO_CARTA' => $cna->nro_carta,
            'dni' => $cna->dni, 'DNI' => $cna->dni,
            'titular' => (string)($titular ?? ''), 'TITULAR' => (string)($titular ?? ''),
            // Si la plantilla tiene campos de fecha/importe/observación, añádelos aquí:
            'fecha_pago_realizado' => optional($cna->fecha_pago_realizado)->format('d/m/Y'),
            'monto_pagado'         => number_format((float)$cna->monto_pagado, 2),
            'observacion'          => (string)($cna->observacion ?? ''),
        ] as $k => $v) { $tp->setValue($k, $v); }

        // Tabla por operación
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

        $tp->saveAs(storage_path('app/'.$docxRel));

        $cna->update([
            'docx_path' => $docxRel,
            // dejamos pdf_path nulo; se generará al descargar
            'pdf_path'  => null,
        ]);
    }

    /** Convierte un DOCX (ruta relativa en storage/app) a PDF y devuelve la ruta relativa del PDF guardado. */
    private function convertDocxToPdf(string $docxRel, string $nroCarta, string $dni): string
    {
        // Asegura dependencias para PhpWord → DomPDF
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new \RuntimeException('dompdf/dompdf no está instalado.');
        }

        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $pdfDir  = 'cna/pdfs';
        Storage::makeDirectory($pdfDir);

        $pdfRel  = $pdfDir.'/'.("CNA {$nroCarta} - {$dni}.pdf");

        // Carga y escribe
        $phpWord   = IOFactory::load(storage_path('app/'.$docxRel), 'Word2007');
        $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save(storage_path('app/'.$pdfRel));

        return $pdfRel;
    }
}
