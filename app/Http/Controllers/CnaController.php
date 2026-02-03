<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use App\Models\Cartera; // <--- Nuevo modelo
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Support\WorkflowMailer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Ilovepdf\Ilovepdf;

class CnaController extends Controller
{
    /* =========================================================
     * Crear solicitud desde /clientes/{dni}
     * ======================================================= */
    public function store(Request $request, string $dni)
    {
        $data = $request->validate([
            'titular'              => ['nullable','string','max:150'],
            'nota'                 => ['nullable','string','max:1000'],
            'observacion'          => ['nullable','string','max:1000'],
            'fecha_pago_realizado' => ['required','date'],
            'monto_pagado'         => ['required','numeric','min:0.01','max:999999999.99'],
            'operaciones'          => ['required','array','min:1'], // Ahora viene como array directo
            'operaciones.*'        => ['string','max:50'],
        ]);

        $ops = array_values(array_filter(array_map('strval', $data['operaciones'] ?? [])));
        
        if (empty($ops)) {
            return back()->withErrors('Selecciona al menos una operación para la CNA.');
        }

        // Buscar titular en Cartera si no se envía
        $titular = $data['titular'] ?? Cartera::where('documento', $dni)
            ->whereNotNull('nombre')
            ->value('nombre'); // En Cartera el campo es 'nombre', no 'titular'

        // Derivar producto de referencia
        $productoAuto = Cartera::whereIn('operacion', $ops)
            ->whereNotNull('producto')
            ->pluck('producto')
            ->filter()
            ->unique()
            ->implode(' / ') ?: null;

        $solicitud = DB::transaction(function () use ($dni, $data, $ops, $titular, $productoAuto) {
            // Bloqueo para evitar colisiones de correlativo
            DB::table('cna_solicitudes')->lockForUpdate()->get();

            $last = (int) DB::table('cna_solicitudes')->max('correlativo');
            $next = $last + 1;
            $nro  = str_pad((string)$next, 6, '0', STR_PAD_LEFT);

            return CnaSolicitud::create([
                'correlativo'          => $next,
                'nro_carta'            => $nro,
                'dni'                  => $dni,
                'titular'              => $titular ?? 'DESCONOCIDO',
                'producto'             => $productoAuto,
                'operaciones'          => $ops, // El cast 'array' lo convierte a JSON
                'nota'                 => $data['nota'] ?? null,
                'observacion'          => $data['observacion'] ?? null,
                'fecha_pago_realizado' => $data['fecha_pago_realizado'],
                'monto_pagado'         => $data['monto_pagado'],
                'workflow_estado'      => 'pendiente',
                'user_id'              => Auth::id(),
            ]);
        });

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::cnaPendiente($solicitud);
        }
        
        return back()->with('ok', "Solicitud de CNA enviada. N.º {$solicitud->nro_carta}");
    }

    /* =========================================================
     * Flujo de aprobación
     * ======================================================= */

    // ── Supervisor ──
    public function preaprobar(CnaSolicitud $cna)
    {
        $this->authorizeRole('supervisor');

        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede pre-aprobar una solicitud pendiente.');
        }

        $cna->update([
            'workflow_estado'  => 'preaprobada',
            'pre_aprobado_por' => Auth::id(),
            'pre_aprobado_at'  => now(),
            'rechazado_por'    => null,
            'rechazado_at'     => null,
            'motivo_rechazo'   => null,
        ]);

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::cnaPreaprobada($cna);
        }
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

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::cnaRechazadaSup($cna, $request->input('nota_estado'));
        }
        return back()->with('ok', 'CNA rechazada por supervisor.');
    }

    // ── Administrador ──
    public function aprobar(Request $request, CnaSolicitud $cna)
    {
        $this->authorizeRole('administrador');

        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');
        }

        // Actualizar estado PRIMERO
        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
        ]);

        // Generar DOCX + PDF desde plantilla
        try {
            $this->generateOutputsFromTemplate($cna);
        } catch (\Throwable $e) {
            Log::error("Error generando PDF para CNA {$cna->id}: " . $e->getMessage());
            // No revertimos la aprobación, pero avisamos (o podrías hacer rollback)
            return back()->with('ok', 'CNA aprobada, pero hubo un error generando el documento: ' . $e->getMessage());
        }

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::cnaResuelta($cna, true, $request->input('nota_estado'));
        }
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

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::cnaResuelta($cna, false, $request->input('nota_estado'));
        }
        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    /* =========================================================
     * Descargas
     * ======================================================= */

    public function pdf(int $id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') abort(403);

        $base   = sprintf('CNA %s - %s', $cna->nro_carta, $cna->dni);
        $pdfRel = 'cna/pdfs/'.$base.'.pdf';

        if (Storage::exists($pdfRel)) {
            return Storage::download($pdfRel, $base.'.pdf');
        }
        
        // Fallback al DOCX
        $docxRel = 'cna/docx/'.$base.'.docx';
        if (Storage::exists($docxRel)) {
            return Storage::download($docxRel, $base.'.docx');
        }

        abort(404, 'Archivo no encontrado.');
    }

    public function docx(int $id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') abort(403);

        $base    = sprintf('CNA %s - %s', $cna->nro_carta, $cna->dni);
        $docxRel = 'cna/docx/'.$base.'.docx';

        if (Storage::exists($docxRel)) {
            return Storage::download($docxRel, $base.'.docx');
        }

        abort(404, 'Archivo no encontrado.');
    }

    /* =========================================================
     * Helpers & Generación de Documentos
     * ======================================================= */

    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    private function generateOutputsFromTemplate(CnaSolicitud $cna): void
    {
        $tplPath = storage_path('app/templates/cna_template.docx');
        
        if (!file_exists($tplPath)) {
            throw new \RuntimeException('Plantilla cna_template.docx no encontrada en storage/app/templates/');
        }

        $docxDir = 'cna/docx';
        $pdfDir  = 'cna/pdfs';
        Storage::makeDirectory($docxDir);
        Storage::makeDirectory($pdfDir);

        $baseName = "CNA {$cna->nro_carta} - {$cna->dni}";
        $docxRel  = "{$docxDir}/{$baseName}.docx";
        $pdfRel   = "{$pdfDir}/{$baseName}.pdf";

        // 1. Preparar datos para la plantilla
        $tp = new TemplateProcessor($tplPath);

        // Buscar titular si falta
        $titular = $cna->titular ?? Cartera::where('documento', $cna->dni)->value('nombre');

        // Formatear fecha de pago
        $fechaPago = '';
        if ($cna->fecha_pago_realizado) {
            $fechaPago = Carbon::parse($cna->fecha_pago_realizado)->format('d/m/Y');
        }

        // Fecha de aprobación en texto (ej: "26 de septiembre de 2025")
        Carbon::setLocale('es');
        $aprobadoAt = $cna->aprobado_at ? Carbon::parse($cna->aprobado_at) : now();
        $aprobadoAtStr = $aprobadoAt->translatedFormat('d \\de F \\de Y');

        // Variables simples
        $vars = [
            'NRO_CARTA'    => $cna->nro_carta,
            'DNI'          => $cna->dni,
            'TITULAR'      => strtoupper($titular ?? ''),
            'FECHA_PAGO'   => $fechaPago,
            'MONTO_PAGADO' => number_format((float)$cna->monto_pagado, 2),
            'OBSERVACION'  => $cna->observacion ?? '',
            'APROBADO_AT'  => $aprobadoAtStr,
        ];

        foreach ($vars as $key => $val) {
            $tp->setValue($key, $val);
        }

        // 2. Llenar tabla de Operaciones (Clonación de filas)
        // Decodificar JSON si es necesario (el modelo lo hace, pero aseguramos array)
        $ops = $cna->operaciones; 
        if (is_string($ops)) $ops = json_decode($ops, true);
        if (!is_array($ops)) $ops = [];

        // Buscar detalles en Cartera
        $carteraInfo = collect();
        if (!empty($ops)) {
            $carteraInfo = Cartera::whereIn('operacion', $ops)
                ->select('operacion', 'producto', 'entidad')
                ->get()
                ->keyBy('operacion');
        }

        // Clonar filas en el Word (variable OPERACION)
        $count = max(count($ops), 1);
        $tp->cloneRow('OPERACION', $count);

        foreach ($ops as $i => $op) {
            $idx  = $i + 1; // El índice en phpword empieza en 1 tras clonar (ej: OPERACION#1)
            $info = $carteraInfo->get($op);

            $tp->setValue("OPERACION#{$idx}", $op);
            $tp->setValue("PRODUCTO#{$idx}",  $info->producto ?? '-');
            $tp->setValue("ENTIDAD#{$idx}",   $info->entidad ?? '-');
        }

        // 3. Guardar DOCX
        $tp->saveAs(storage_path("app/{$docxRel}"));

        // 4. Convertir a PDF (iLovePDF)
        try {
            $this->convertDocxToPdfViaIlovepdf(
                storage_path("app/{$docxRel}"),
                storage_path("app/{$pdfRel}")
            );
            $cna->pdf_path = $pdfRel;
        } catch (\Throwable $e) {
            Log::error('Fallo iLovePDF: '.$e->getMessage());
            $cna->pdf_path = null; 
        }

        $cna->docx_path = $docxRel;
        $cna->save();
    }

    private function convertDocxToPdfViaIlovepdf(string $docxAbs, string $pdfAbs): void
    {
        $public = config('services.ilovepdf.public');
        $secret = config('services.ilovepdf.secret');

        if (!$public || !$secret) return; // Si no hay keys, salimos sin error (solo no genera PDF)

        $ilovepdf = new Ilovepdf($public, $secret);
        $task = $ilovepdf->newTask('officepdf');
        $task->addFile($docxAbs);
        $task->execute();
        
        $outDir = dirname($pdfAbs);
        $task->download($outDir);

        // iLovePDF descarga con el nombre original pero extensión .pdf
        $downloadedName = str_replace('.docx', '.pdf', basename($docxAbs));
        $downloadedPath = $outDir . '/' . $downloadedName;

        if (file_exists($downloadedPath)) {
            rename($downloadedPath, $pdfAbs);
        }
    }
}