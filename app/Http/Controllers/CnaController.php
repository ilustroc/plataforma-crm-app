<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CnaController extends Controller
{
    /**
     * Guarda la solicitud de CNA enviada desde /clientes/{dni}.
     * - Asigna correlativo + nro_carta automáticamente (con lock).
     * - Adjunta operaciones seleccionadas.
     */
    public function store(Request $request, string $dni)
    {
        // VALIDACIÓN
        $data = $request->validate([
            'producto'              => ['nullable', 'string', 'max:150'],
            'titular'               => ['nullable', 'string', 'max:150'],
            'nota'                  => ['nullable', 'string', 'max:1000'],
            'observacion'           => ['nullable', 'string', 'max:1000'],
            'fecha_pago_realizado'  => ['required', 'date'],
            'monto_pagado'          => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'operaciones'           => ['required', 'array', 'min:1'],
            'operaciones.*'         => ['string', 'max:50'],
        ], [], [
            'fecha_pago_realizado'  => 'fecha de pago realizado',
            'monto_pagado'          => 'monto pagado',
            'operaciones'           => 'operaciones',
        ]);

        // NORMALIZACIÓN
        $ops = array_values(array_filter(array_map('strval', $data['operaciones'] ?? [])));
        if (empty($ops)) {
            return back()->withErrors('Selecciona al menos una operación para la CNA.');
        }

        // PERSISTENCIA ATÓMICA (evita colisiones de correlativo)
        $solicitud = DB::transaction(function () use ($dni, $data, $ops) {
            $last = DB::table('cna_solicitudes')->lockForUpdate()->max('correlativo');
            $next = ((int)$last) + 1;
            $nro  = str_pad((string)$next, 6, '0', STR_PAD_LEFT);

            return CnaSolicitud::create([
                'correlativo'          => $next,
                'nro_carta'            => $nro,
                'dni'                  => $dni,
                'titular'              => $data['titular']    ?? null,
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

    // ======== Flujo de autorización (igual que promesas) ========

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
            'motivo_rechazo'  => substr((string)$request->input('nota_estado', ''), 0, 500),
        ]);

        return back()->with('ok', 'CNA rechazada por supervisor.');
    }

    public function aprobar(CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');

        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');
        }

        // Cambia estado
        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
        ]);

        // Genera archivos (PDF, y DOCX si hay plantilla)
        $this->generateOutputs($cna);

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
            'motivo_rechazo'  => substr((string)$request->input('nota_estado', ''), 0, 500),
        ]);

        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    // ======== Descargas ========

    /** Descarga el PDF si existe (genera al vuelo si falta). */
    public function pdf(CnaSolicitud $cna)
    {
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        // Si está guardado en disco, servir
        if ($cna->pdf_path && Storage::exists($cna->pdf_path)) {
            return Storage::download($cna->pdf_path, basename($cna->pdf_path));
        }

        // Generar on-the-fly
        $fileName = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        $pdf = Pdf::loadView('cna.pdf', ['cna' => $cna])->setPaper('A4');
        return $pdf->download($fileName);
    }

    /** Descarga el DOCX si existe. (Opcional) */
    public function docx(CnaSolicitud $cna)
    {
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }
        if (!$cna->docx_path || !Storage::exists($cna->docx_path)) {
            abort(404, 'Archivo DOCX no encontrado.');
        }
        return Storage::download($cna->docx_path, basename($cna->docx_path));
    }

    // ======== Helpers ========

    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /**
     * Genera y guarda archivos para la CNA:
     * - PDF en storage/app/cna/pdfs/
     * - DOCX (si hay plantilla) en storage/app/cna/docx/
     * Guarda las rutas relativas en la DB.
     */
    private function generateOutputs(CnaSolicitud $cna): void
    {
        // Directorios
        $pdfDir  = 'cna/pdfs';
        $docxDir = 'cna/docx';
        $tplDir  = 'cna/templates';

        Storage::makeDirectory($pdfDir);
        Storage::makeDirectory($docxDir);
        Storage::makeDirectory($tplDir); // por si quieres subir tu plantilla aquí

        // ==== PDF (Blade) ====
        $pdfName = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        $pdfPath = "{$pdfDir}/{$pdfName}";
        $pdf = Pdf::loadView('cna.pdf', ['cna' => $cna])->setPaper('A4');
        Storage::put($pdfPath, $pdf->output());

        // ==== DOCX (opcional, si hay plantilla y PhpWord instalado) ====
        $docxPath = null;
        $templateCandidate = storage_path('app/'.$tplDir.'/cna_template.docx');
        if (class_exists(\PhpOffice\PhpWord\TemplateProcessor::class) && is_file($templateCandidate)) {
            try {
                $docxName = "CNA {$cna->nro_carta} - {$cna->dni}.docx";
                $docxPath = "{$docxDir}/{$docxName}";

                $tp = new \PhpOffice\PhpWord\TemplateProcessor($templateCandidate);
                $tp->setValue('nro_carta', $cna->nro_carta);
                $tp->setValue('dni', $cna->dni);
                $tp->setValue('titular', (string)($cna->titular ?? ''));
                $tp->setValue('producto', (string)($cna->producto ?? ''));
                $tp->setValue('operacion', implode(', ', (array)$cna->operaciones));
                $tp->setValue('fecha_hoy', now()->format('d/m/Y'));
                $tp->saveAs(storage_path('app/'.$docxPath));
            } catch (\Throwable $e) {
                // Si algo falla con DOCX, no detenemos el flujo; solo no guardamos docx_path
                $docxPath = null;
            }
        }

        // Guardar paths
        $cna->fill([
            'pdf_path'  => $pdfPath,
            'docx_path' => $docxPath,
        ])->save();
    }
}
