<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class CnaController extends Controller
{
    /**
     * Guarda la solicitud de CNA desde /clientes/{dni}.
     * Asigna correlativo + nro_carta con lock y adjunta operaciones.
     */
    public function store(Request $request, string $dni)
    {
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

        $ops = array_values(array_filter(array_map('strval', $data['operaciones'] ?? [])));
        if (empty($ops)) {
            return back()->withErrors('Selecciona al menos una operación para la CNA.');
        }

        // Si no envían titular, lo inferimos de clientes_cuentas
        $titular = $data['titular'] ?? DB::table('clientes_cuentas')
            ->where('dni', $dni)
            ->whereNotNull('titular')
            ->value('titular');

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

    // ====== Flujo de autorización ======

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

        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
        ]);

        // Genera DOCX desde plantilla y lo convierte a PDF
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
            'motivo_rechazo'  => substr((string)$request->input('nota_estado', ''), 0, 500),
        ]);

        return back()->with('ok', 'CNA rechazada por administrador.');
    }

    // ====== Descargas ======

    /** Descarga el PDF; si falta, lo genera desde la plantilla y devuelve. */
    public function pdf(CnaSolicitud $cna)
    {
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        if (!$cna->pdf_path || !Storage::exists($cna->pdf_path)) {
            $this->generateOutputsFromTemplate($cna);
        }

        if (!$cna->pdf_path || !Storage::exists($cna->pdf_path)) {
            abort(404, 'No se pudo generar el PDF.');
        }

        return Storage::download($cna->pdf_path, basename($cna->pdf_path));
    }

    /** (Opcional) descarga del DOCX generado. */
    public function docx(CnaSolicitud $cna)
    {
        if ($cna->workflow_estado !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }
        abort_unless($cna->docx_path && Storage::exists($cna->docx_path), 404, 'Archivo DOCX no encontrado.');
        return Storage::download($cna->docx_path, basename($cna->docx_path));
    }

    // ====== Helpers ======

    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user || !in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /**
     * Rellena storage/app/templates/cna_template.docx con datos de la CNA,
     * clona una fila de la tabla por operación (Producto/Operación/Entidad),
     * guarda DOCX y convierte a PDF con DomPDF.
     */
    private function generateOutputsFromTemplate(CnaSolicitud $cna): void
    {
        // Rutas
        $tplPath  = storage_path('app/templates/cna_template.docx');
        if (!is_file($tplPath)) {
            // Si falta la plantilla, no continuamos
            throw new \RuntimeException('Plantilla cna_template.docx no encontrada en storage/app/templates/');
        }

        $docxDir  = 'cna/docx';
        $pdfDir   = 'cna/pdfs';
        Storage::makeDirectory($docxDir);
        Storage::makeDirectory($pdfDir);

        $docxName = "CNA {$cna->nro_carta} - {$cna->dni}.docx";
        $pdfName  = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        $docxRel  = $docxDir.'/'.$docxName;
        $pdfRel   = $pdfDir.'/'.$pdfName;

        // === Datos por operación (producto/entidad) ===
        $ops = (array) $cna->operaciones;
        $ops = array_values(array_filter(array_map('strval', $ops)));

        $byOp = collect();
        if ($ops) {
            $byOp = DB::table('clientes_cuentas')
                ->select('operacion','producto','entidad')
                ->whereIn('operacion', $ops)
                ->get()
                ->keyBy('operacion');
        }

        // === Relleno de la plantilla ===
        $tp = new TemplateProcessor($tplPath);

        // Campos simples (ponemos ambas variantes de mayúsc/minúsc por si acaso)
        $titular = $cna->titular ?? DB::table('clientes_cuentas')->where('dni',$cna->dni)->value('titular');

        foreach ([
            'nro_carta' => $cna->nro_carta,
            'NRO_CARTA' => $cna->nro_carta,
            'dni'       => $cna->dni,
            'DNI'       => $cna->dni,
            'titular'   => (string)($titular ?? ''),
            'TITULAR'   => (string)($titular ?? ''),
        ] as $k => $v) {
            $tp->setValue($k, $v);
        }

        // Clonado de filas: variable "OPERACION" debe existir en la fila de la tabla
        $rows = max(count($ops), 1);
        $tp->cloneRow('OPERACION', $rows);

        if ($rows === 1) {
            $op = $ops[0] ?? '—';
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

        // === Conversión a PDF con DomPDF ===
        // Requiere dompdf instalado (lo traes con barryvdh/laravel-dompdf)
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord  = IOFactory::load(storage_path('app/'.$docxRel), 'Word2007');
        $pdfWriter= IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save(storage_path('app/'.$pdfRel));

        // Persistir rutas
        $cna->fill([
            'docx_path' => $docxRel,
            'pdf_path'  => $pdfRel,
        ])->save();
    }
}
