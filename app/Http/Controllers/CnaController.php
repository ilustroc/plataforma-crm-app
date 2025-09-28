<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;

class CnaController extends Controller
{
    // Guardar solicitud desde /clientes/{dni} (modal CNA)
    public function store(string $dni, Request $r)
    {
        $r->validate([
            'nro_carta'     => 'required|string|max:50',
            'producto'      => 'nullable|string|max:120',
            'nota'          => 'nullable|string|max:500',
            'operaciones'   => 'required|array|min:1',
            'operaciones.*' => 'string|max:50',
        ]);

        CnaSolicitud::create([
            'nro_carta'      => trim($r->nro_carta),
            'dni'            => $dni,
            'titular'        => $r->input('titular'), // si luego decides enviarlo
            'producto'       => $r->input('producto'),
            'operaciones'    => array_values($r->operaciones),
            'nota'           => $r->input('nota'),
            'workflow_estado'=> 'pendiente',
            'user_id'        => Auth::id(),
        ]);

        return back()->with('ok', 'Solicitud de CNA enviada para autorización.');
    }

    // ===== Flujo de autorización (igual a promesas) =====
    public function preaprobar(CnaSolicitud $cna)
    {
        $this->authorizeActionFor('supervisor');
        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede pre-aprobar una solicitud pendiente.');
        }
        $cna->update([
            'workflow_estado' => 'preaprobada',
            'pre_aprobado_por'=> Auth::id(),
            'pre_aprobado_at' => now(),
            'rechazado_por'   => null,'rechazado_at'=>null,'motivo_rechazo'=>null,
        ]);
        return back()->with('ok', 'CNA pre-aprobada.');
    }

    public function rechazarSup(Request $r, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('supervisor');
        if ($cna->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede rechazar una solicitud pendiente.');
        }
        $cna->update([
            'workflow_estado'=>'rechazada_sup',
            'rechazado_por'=>Auth::id(),
            'rechazado_at'=>now(),
            'motivo_rechazo'=>substr((string)$r->nota_estado,0,500),
        ]);
        return back()->with('ok', 'CNA rechazada por supervisor.');
    }

    public function aprobar(Cna $cna)
    {
        $this->authorizeActionFor('administrador');

        if (($cna->workflow_estado ?? '') !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una CNA pre-aprobada.');
        }

        // 1) Cambia estado
        $cna->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => auth()->id(),
            'aprobado_at'     => now(),
        ]);

        // 2) Genera archivos
        $this->makeOutputs($cna);

        return back()->with('ok', 'CNA aprobada y archivos generados.');
    }

    public function rechazarAdmin(Request $r, CnaSolicitud $cna)
    {
        $this->authorizeActionFor('administrador');
        if ($cna->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede rechazar una solicitud pre-aprobada.');
        }
        $cna->update([
            'workflow_estado'=>'rechazada',
            'rechazado_por'=>Auth::id(),
            'rechazado_at'=>now(),
            'motivo_rechazo'=>substr((string)$r->nota_estado,0,500),
        ]);
        return back()->with('ok', 'CNA rechazada por administrador.');
    }
    
    private function authorizeActionFor(string $role)
    {
        $user = auth()->user();
        if (!in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /** Entrega el PDF para descarga (o lo genera on-the-fly si falta) */
    public function pdf(Cna $cna)
    {
        if (($cna->workflow_estado ?? '') !== 'aprobada') {
            abort(403, 'Solo disponible para CNA aprobadas.');
        }

        $file = $cna->pdf_path ? storage_path('app/'.$cna->pdf_path) : null;
        if ($file && is_file($file)) {
            return response()->download($file, basename($file));
        }

        // Fallback: generar al vuelo y descargar
        $fileName = "CNA {$cna->nro_carta} - {$cna->dni}.pdf";
        return Pdf::loadView('cna.pdf', ['cna' => $cna])
            ->setPaper('A4')
            ->download($fileName);
    }
}
