<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
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

        // Titular (si no llega desde el form)
        $titular = $data['titular'] ?? DB::table('clientes_cuentas')
            ->where('dni', $dni)
            ->whereNotNull('titular')
            ->value('titular');

        // (Opcional) Derivar un "producto" de referencia con las operaciones elegidas
        $productoAuto = DB::table('clientes_cuentas')
            ->whereIn('operacion', $ops)
            ->whereNotNull('producto')
            ->pluck('producto')->filter()->unique()->implode(' / ') ?: null;

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
                'titular'              => $titular,
                'producto'             => $productoAuto,
                'operaciones'          => $ops,
                'nota'                 => $data['nota'] ?? null,
                'observacion'          => $data['observacion'] ?? null,
                'fecha_pago_realizado' => $data['fecha_pago_realizado'],
                'monto_pagado'         => $data['monto_pagado'],
                'workflow_estado'      => 'pendiente',
                'user_id'              => Auth::id(),
            ]);
        });

        WorkflowMailer::cnaPendiente($solicitud);
        return back()->with('ok', "Solicitud de CNA enviada. N.º {$solicitud->nro_carta}");
    }

    /* =========================================================
     * Flujo de aprobación
     * ======================================================= */

    // ── Supervisor

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

        WorkflowMailer::cnaPreaprobada($cna);
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

        WorkflowMailer::cnaRechazadaSup($cna, $req->input('nota_estado'));
        return back()->with('ok', 'CNA rechazada por supervisor.');
    }

    // ── Administrador
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

        // Generar DOCX + PDF desde plantilla
        $this->generateOutputsFromTemplate($cna);

        WorkflowMailer::cnaResuelta($cna, true, $req->input('nota_estado'));
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

        WorkflowMailer::cnaResuelta($cna, false, $req->input('nota_estado'));
        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    /* =========================================================
     * Descargas
     * ======================================================= */

    /** GET /cna/{cna}/pdf */
    public function pdf(int $id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        // Construye SIEMPRE el nombre esperado
        $base   = sprintf('CNA %s - %s', $cna->nro_carta, $cna->dni);
        $pdfRel = 'cna/pdfs/'.$base.'.pdf';

        if (Storage::exists($pdfRel)) {
            return Storage::download($pdfRel, $base.'.pdf');
        }

        // Si no existe, intenta servir el DOCX como respaldo
        $docxRel = 'cna/docx/'.$base.'.docx';
        if (Storage::exists($docxRel)) {
            return Storage::download($docxRel, $base.'.docx');
        }

        abort(404, 'Archivo no encontrado: '.$pdfRel);
    }

    public function docx(int $id)
    {
        $cna = CnaSolicitud::findOrFail($id);
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        $base   = sprintf('CNA %s - %s', $cna->nro_carta, $cna->dni);
        $docxRel= 'cna/docx/'.$base.'.docx';

        if (Storage::exists($docxRel)) {
            return Storage::download($docxRel, $base.'.docx');
        }

        // Respaldo: si el PDF existe, al menos entrega eso
        $pdfRel = 'cna/pdfs/'.$base.'.pdf';
        if (Storage::exists($pdfRel)) {
            return Storage::download($pdfRel, $base.'.pdf');
        }

        abort(404, 'Archivo no encontrado: '.$docxRel);
    }

    /* =========================================================
     * Helpers
     * ======================================================= */

    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /**
     * Llena storage/app/templates/cna_template.docx y crea:
     *  - DOCX en storage/app/cna/docx
     *  - PDF  en storage/app/cna/pdfs (vía iLovePDF)
     */
    private function generateOutputsFromTemplate(CnaSolicitud $cna): void
    {
        $tplPath = storage_path('app/templates/cna_template.docx');
        if (!is_file($tplPath)) {
            throw new \RuntimeException('Plantilla cna_template.docx no encontrada en storage/app/templates/');
        }

        $docxDir = 'cna/docx';
        $pdfDir  = 'cna/pdfs';
        \Storage::makeDirectory($docxDir);
        \Storage::makeDirectory($pdfDir);

        $docxName = "CNA {$cna->nro_carta} - {$cna->dni}.docx";
        $pdfName  = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        $docxRel  = $docxDir.'/'.$docxName;
        $pdfRel   = $pdfDir.'/'.$pdfName;

        // ---------- Rellenar DOCX ----------
        $tp = new TemplateProcessor($tplPath);

        $titular = $cna->titular ?? \DB::table('clientes_cuentas')
            ->where('dni', $cna->dni)->value('titular');

        // Fecha de pago (opcional)
        $fechaPago = '';
        if ($cna->fecha_pago_realizado) {
            try { $fechaPago = Carbon::parse($cna->fecha_pago_realizado)->format('d/m/Y'); }
            catch (\Throwable $e) { $fechaPago = (string)$cna->fecha_pago_realizado; }
        }

        // >>> NUEVO: fecha de aprobación en español <<<
        // Usa la fecha de aprobación si existe; si no, hoy.
        try {
            Carbon::setLocale('es');
            $aprobadoAt = $cna->aprobado_at
                ? Carbon::parse($cna->aprobado_at)
                : now();
            // “26 de setiembre de 2025”
            $aprobadoAtStr = $aprobadoAt->translatedFormat('d \\de F \\de Y');
            // Opcional: capitalizar el mes (si tu plantilla lo quiere así)
            // $aprobadoAtStr = mb_convert_case($aprobadoAtStr, MB_CASE_TITLE, 'UTF-8');
        } catch (\Throwable $e) {
            $aprobadoAtStr = now()->format('d/m/Y');
        }

        foreach ([
            'nro_carta'    => $cna->nro_carta,
            'NRO_CARTA'    => $cna->nro_carta,
            'dni'          => $cna->dni,
            'DNI'          => $cna->dni,
            'titular'      => (string)($titular ?? ''),
            'TITULAR'      => (string)($titular ?? ''),
            'FECHA_PAGO'   => $fechaPago,
            'MONTO_PAGADO' => number_format((float)$cna->monto_pagado, 2),
            'OBSERVACION'  => (string)($cna->observacion ?? ''),
            'APROBADO_AT'  => $aprobadoAtStr,
        ] as $k => $v) {
            $tp->setValue($k, $v);
        }

        // Operaciones
        $ops = is_array($cna->operaciones)
            ? $cna->operaciones
            : (json_decode($cna->operaciones ?? '[]', true) ?: []);
        $ops = array_values(array_filter(array_map('strval', $ops)));

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

        // Guardar DOCX
        $tp->saveAs(storage_path('app/'.$docxRel));

        // ---------- Convertir a PDF ----------
        try {
            $this->convertDocxToPdfViaIlovepdf(
                storage_path('app/'.$docxRel),
                storage_path('app/'.$pdfRel)
            );
            $cna->pdf_path = $pdfRel;
        } catch (\Throwable $e) {
            \Log::error('Error iLovePDF DOCX→PDF: '.$e->getMessage(), ['cna_id' => $cna->id]);
            $cna->pdf_path = null;
        }

        // Persistir rutas (y por si acaso aseguramos guardar aprobado_at existente)
        $cna->docx_path = $docxRel;
        $cna->save();
    }

    /**
     * Convierte DOCX → PDF con iLovePDF (task: officepdf).
     * Requiere claves en config/services.php:
     * 'ilovepdf' => ['public' => env('ILOVEPDF_PUBLIC_KEY'), 'secret' => env('ILOVEPDF_SECRET_KEY')]
     */
    private function convertDocxToPdfViaIlovepdf(string $docxAbs, string $pdfAbs): void
    {
        $public = config('services.ilovepdf.public');
        $secret = config('services.ilovepdf.secret');
        if (!$public || !$secret) {
            throw new \RuntimeException('Faltan claves de iLovePDF (config/services.ilovepdf).');
        }

        $sdk  = new Ilovepdf($public, $secret);
        $task = $sdk->newTask('officepdf'); // Office → PDF
        $task->addFile($docxAbs);
        $task->execute();

        $outDir = dirname($pdfAbs);
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        // Descarga; el SDK usa el nombre original (cambia a .pdf)
        $task->download($outDir);

        // Asegura nombre final exacto
        $expected = $outDir.'/'.basename($docxAbs, '.docx').'.pdf';
        if (!is_file($expected)) {
            $latest = collect(glob($outDir.'/*.pdf'))
                ->sortByDesc(fn($p) => filemtime($p))
                ->first();
            if ($latest) {
                $expected = $latest;
            }
        }
        if (!is_file($expected)) {
            throw new \RuntimeException('No se pudo localizar el PDF descargado por iLovePDF.');
        }
        if ($expected !== $pdfAbs) {
            @unlink($pdfAbs);
            rename($expected, $pdfAbs);
        }
    }
}
