<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\WorkflowMailer;

// Modelos Actualizados
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use App\Models\Pagos;    // Único modelo de pagos
use App\Models\Cartera;  // Nueva tabla master

class AutorizacionController extends Controller
{
    public function index(Request $req)
    {
        $user   = Auth::user();
        $q      = trim((string)($req->q ?? ''));
        $status = $req->status;

        // ==========================================
        // 1. PROMESAS (Base de la bandeja)
        // ==========================================
        $promesas = PromesaPago::query()
            ->with(['operaciones']) // Relación con detalle de operaciones
            ->leftJoin('users as u', 'u.id', '=', 'promesas_pago.user_id')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('promesas_pago.dni', 'like', "%{$q}%")
                      ->orWhere('promesas_pago.nota', 'like', "%{$q}%")
                      // Buscar también en las operaciones relacionadas
                      ->orWhereHas('operaciones', fn($qq) => $qq->where('operacion', 'like', "%{$q}%"));
                });
            });

        // Filtro por Rol
        if (strtolower($user->role) === 'supervisor') {
            $promesas->where('promesas_pago.workflow_estado', 'pendiente');
        } else {
            // Admin / Sistemas ven las preaprobadas para dar el OK final
            $promesas->where('promesas_pago.workflow_estado', 'preaprobada');
        }

        if (!empty($status)) {
            $promesas->where('promesas_pago.workflow_estado', $status);
        }

        $rows = $promesas->select('promesas_pago.*', 'u.name as creador_nombre')
                         ->orderByDesc('promesas_pago.fecha_promesa')
                         ->get();

        // ==========================================
        // 2. DATOS DE CARTERA (Complemento)
        // ==========================================
        
        // Obtener operaciones para cruzar información
        // A) De las promesas cargadas
        $opsInPromesas = $rows->flatMap(function($p) {
            if ($p->relationLoaded('operaciones') && $p->operaciones->isNotEmpty()) {
                return $p->operaciones->pluck('operacion');
            }
            // Fallback legacy (campo string separado por comas)
            return array_filter(array_map('trim', explode(',', (string)$p->operacion)));
        })->unique()->filter();

        // B) Buscar información en CARTERA
        $carteraInfo = collect();
        if ($opsInPromesas->isNotEmpty()) {
            $carteraInfo = Cartera::whereIn('operacion', $opsInPromesas)
                ->get()
                ->keyBy('operacion');
        }

        // ==========================================
        // 3. ARMAR LA VISTA (Filas)
        // ==========================================
        $rows = $rows->map(function($p) use ($carteraInfo) {
            
            // Determinar operaciones de esta promesa
            $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
                ? $p->operaciones->pluck('operacion')
                : collect(array_filter(array_map('trim', explode(',', (string)$p->operacion))));

            // Texto para mostrar en la columna
            $p->operacion_txt = $ops->implode(', ');

            // Acumuladores
            $sumCap = 0.0; 
            $sumDeu = 0.0;
            $entidades = collect();
            $titulares = collect();
            
            $detalleCuentas = [];

            foreach ($ops as $op) {
                // Buscamos en la info traída de Cartera
                $cc = $carteraInfo[$op] ?? null;
                
                // Si no existe en cartera (ej: operación antigua), usamos valores defecto
                $cap = (float)($cc->saldo_capital ?? 0);
                $deu = (float)($cc->deuda_total ?? 0);
                
                $sumCap += $cap;
                $sumDeu += $deu;

                if ($cc) {
                    if ($cc->entidad) $entidades->push($cc->entidad);
                    if ($cc->nombre)  $titulares->push($cc->nombre);
                }

                $detalleCuentas[] = [
                    'operacion'     => $op,
                    'entidad'       => $cc->entidad ?? '-',
                    'producto'      => $cc->producto ?? '-',
                    'saldo_capital' => $cap,
                    'deuda_total'   => $deu,
                    'anio_castigo'  => $cc->fecha_castigo ? Carbon::parse($cc->fecha_castigo)->year : '-',
                ];
            }

            // Datos agregados para la tarjeta
            $p->entidades_txt = $entidades->unique()->implode(' / ') ?: 'Sin Entidad';
            $p->titular_txt   = $titulares->unique()->implode(' / ') ?: 'Sin Nombre';
            
            // Actualizamos montos visuales (opcional, si queremos mostrar la deuda real vs promesa)
            $p->total_deuda_sistema   = $sumDeu;
            $p->total_capital_sistema = $sumCap;
            
            // Array para el acordeón "Ver detalles"
            $p->cuentas_json = $detalleCuentas;

            return $p;
        });

        // ==========================================
        // 4. CNA (Cartas de No Adeudo)
        // ==========================================
        $cnaQuery = CnaSolicitud::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function($x) use ($q){
                    $x->where('dni', 'like', "%{$q}%")
                      ->orWhere('nro_carta', 'like', "%{$q}%")
                      ->orWhere('titular', 'like', "%{$q}%");
                });
            });

        if (strtolower($user->role) === 'supervisor') {
            $cnaQuery->where('workflow_estado', 'pendiente');
        } else {
            $cnaQuery->where('workflow_estado', 'preaprobada');
        }

        $cnaRows = $cnaQuery->orderByDesc('created_at')
            ->paginate(10, ['*'], 'page_cna')
            ->withQueryString();

        return view('autorizacion.index', [
            'rows'         => $rows,
            'cnaRows'      => $cnaRows,
            'q'            => $q,
            'isSupervisor' => strtolower($user->role) === 'supervisor',
        ]);
    }

    // ==========================================
    // ACCIONES DE FLUJO (Supervisor)
    // ==========================================
    
    public function preaprobar(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('supervisor');

        if ($promesa->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede pre-aprobar una promesa Pendiente.');
        }

        $promesa->update([
            'workflow_estado'    => 'preaprobada',
            'pre_aprobado_por'   => Auth::id(),
            'pre_aprobado_at'    => now(),
            'nota_preaprobacion' => trim((string)$req->input('nota_estado')) ?: null,
            // Limpiar rechazos previos si es re-evaluación
            'rechazado_por'      => null,
            'rechazado_at'       => null,
            'nota_rechazo'       => null,
        ]);

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::promesaPreaprobada($promesa);
        }
        return back()->with('ok', 'Promesa pre-aprobada.');
    }

    public function rechazarSup(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('supervisor');

        if ($promesa->workflow_estado !== 'pendiente') {
            return back()->withErrors('Solo se puede rechazar una promesa Pendiente.');
        }

        $promesa->update([
            'workflow_estado' => 'rechazada_sup',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);
        
        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::promesaRechazadaSup($promesa, $req->input('nota_estado'));
        }
        return back()->with('ok', 'Promesa rechazada por supervisor.');
    }

    // ==========================================
    // ACCIONES DE FLUJO (Admin)
    // ==========================================

    public function aprobar(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('administrador');

        if ($promesa->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede aprobar una promesa Pre-aprobada.');
        }

        $promesa->update([
            'workflow_estado' => 'aprobada',
            'aprobado_por'    => Auth::id(),
            'aprobado_at'     => now(),
            'nota_aprobacion' => trim((string)$req->input('nota_estado')) ?: null,
        ]);

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::promesaResuelta($promesa, true, $req->input('nota_estado'));
        }
        return back()->with('ok', 'Promesa APROBADA.');
    }

    public function rechazarAdmin(Request $req, PromesaPago $promesa)
    {
        $this->authorizeActionFor('administrador');

        if ($promesa->workflow_estado !== 'preaprobada') {
            return back()->withErrors('Solo se puede rechazar una promesa Pre-aprobada.');
        }

        $promesa->update([
            'workflow_estado' => 'rechazada',
            'rechazado_por'   => Auth::id(),
            'rechazado_at'    => now(),
            'nota_rechazo'    => substr((string)$req->input('nota_estado'), 0, 500),
        ]);

        if (class_exists(WorkflowMailer::class)) {
            WorkflowMailer::promesaResuelta($promesa, false, $req->input('nota_estado'));
        }
        return back()->with('ok', 'Promesa rechazada por administrador.');
    }

    // ==========================================
    // UTILIDADES
    // ==========================================

    private function authorizeActionFor(string $role)
    {
        $user = Auth::user();
        if (!in_array(strtolower($user->role), [$role, 'sistemas'])) {
            abort(403, 'No autorizado.');
        }
    }

    /**
     * API Endpoint: Trae el historial de pagos para el modal de detalle
     * Reemplaza las 3 tablas antiguas por la única tabla 'pagos'
     */
    public function pagosDni(string $dni)
    {
        // En la tabla Pagos, el campo de identidad es 'documento'
        $rows = Pagos::where('documento', $dni)
            ->select(
                DB::raw('DATE(fecha) as fecha'),
                DB::raw('monto as monto'),
                'operacion as oper',
                DB::raw("UPPER(COALESCE(gestor, '-')) as gestor"),
                DB::raw("'PROCESADO' as estado") // Asumimos estado OK si está en la tabla pagos
            )
            ->orderByDesc('fecha')
            ->get();

        return response()->json([
            'dni'   => $dni,
            'pagos' => $rows
        ], 200);
    }
}