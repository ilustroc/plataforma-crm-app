<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\WorkflowMailer;

// Modelos
use App\Models\Cartera; // <--- Nuevo Modelo Principal
use App\Models\PromesaPago;
use App\Models\PromesaOperacion;
use App\Models\PromesaCuota;
use App\Models\Pagos;
use App\Models\CnaSolicitud;

class ClientesControllers extends Controller
{
    public function show(string $dni)
    {
        try {
            // ===== 1. Cuentas del cliente (Desde Tabla Cartera)
            // Usamos 'documento' que es el campo DNI en la nueva tabla
            $cuentas = Cartera::where('documento', $dni)
                ->orderByDesc('updated_at')
                ->get();

            abort_if($cuentas->isEmpty(), 404);
            
            $nombre = $cuentas->first()->nombre;

            // ===== 2. Pagos (Historial General)
            $pagos = Pagos::where('documento', $dni)
                ->select(
                    DB::raw('DATE(fecha) as fecha'),
                    DB::raw('monto as monto'),
                    DB::raw("COALESCE(operacion,'-') as operacion"),
                    DB::raw("UPPER(COALESCE(gestor,'-')) as gestor"),
                    DB::raw("'-' as estado"),
                    DB::raw("'PAGOS' as fuente"),
                    'moneda'
                )
                ->orderByDesc('fecha')
                ->get();

            $totPagos = (float) $pagos->sum('monto');

            // ===== 3. Promesas (Historial de Acuerdos)
            $promesas = PromesaPago::where('dni', $dni)
                ->when(method_exists(PromesaPago::class,'scopeWithDecisionRefs'), fn($q)=>$q->withDecisionRefs())
                ->with('operaciones') // Trae el detalle de operaciones
                ->orderByDesc('fecha_promesa')
                ->get();

            // ===== 4. CCD (Documentos Digitales - Legacy)
            $ccd = collect();
            $ccdByCodigo = collect();

            if (Schema::hasTable('ccd_clientes')) {
                $cols = DB::getSchemaBuilder()->getColumnListing('ccd_clientes');
                $sel  = collect(['id','dni','codigo','documento','nombre','pdf','archivo','ruta','url','created_at'])
                        ->filter(fn($c)=>in_array($c,$cols))->all();

                if (!empty($sel)) {
                    $ccd = DB::table('ccd_clientes')
                        ->select($sel)
                        ->where('dni', $dni)
                        ->orderByDesc('id')
                        ->get();

                    if (in_array('codigo', $cols)) {
                        $ccdByCodigo = $ccd->groupBy('codigo');
                    }
                }
            }

            // ===== 5. Mapeo de Pagos por Operación (Para la vista de tarjetas)
            $ops = $cuentas->pluck('operacion')->filter()->unique()->values();
            $pagosPorOperacion = collect();

            if ($ops->isNotEmpty()) {
                // Filtramos los pagos que coinciden con las operaciones de la cartera
                $pagosFlat = $pagos->whereIn('oper', $ops);
                $pagosPorOperacion = $pagosFlat->groupBy('oper');

                // Inyectamos el resumen de pagos dentro del objeto cuenta para facilitar la vista
                $cuentas = $cuentas->map(function ($c) use ($pagosPorOperacion) {
                    $opsKey = $c->operacion;
                    $grupo = $opsKey ? ($pagosPorOperacion[$opsKey] ?? collect()) : collect();

                    $c->pagos_sum  = (float) $grupo->sum('monto');
                    $c->pagos_list = $grupo->sortByDesc('fecha')->values();
                    return $c;
                });
            } else {
                $cuentas = $cuentas->map(function ($c) {
                    $c->pagos_sum  = 0.0;
                    $c->pagos_list = collect();
                    return $c;
                });
            }

            // ===== 6. CNAs (Cartas de No Adeudo)
            $cnasByOperacion = collect();
            if (Schema::hasTable('cna_solicitudes')) {
                $cnas = CnaSolicitud::where('dni', $dni)
                    ->select('id','dni','nro_carta','operaciones','workflow_estado','created_at','pdf_path','docx_path')
                    ->orderByDesc('created_at')
                    ->get();
            
                $map = [];
                foreach ($cnas as $row) {
                    // Decodificar JSON de operaciones (nuevo formato) o array legacy
                    $opsArr = is_array($row->operaciones) ? $row->operaciones : (json_decode($row->operaciones, true) ?: []);
                    
                    // Fallback para string separado por comas
                    if (!is_array($opsArr) && is_string($row->operaciones)) {
                        $opsArr = array_filter(array_map('trim', explode(',', $row->operaciones)));
                    }
            
                    foreach ($opsArr as $op) {
                        if (!$op) continue;
                        $map[$op] = $map[$op] ?? collect();
                        $map[$op]->push($row);
                    }
                }
                $cnasByOperacion = collect($map);
            }

            return view('clientes.show', compact(
                'dni','nombre','cuentas','pagos','promesas','ccd','pagosPorOperacion','totPagos',
                'ccdByCodigo', 'cnasByOperacion'
            ));

        } catch (\Throwable $e) {
            \Log::error('Clientes.show ERROR', ['dni'=>$dni,'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]);
            return back()->withErrors('Ocurrió un error cargando el cliente: ' . $e->getMessage());
        }
    }

    public function storePromesa(string $dni, Request $r)
    {
        $r->merge(['dni' => $dni]);

        // Si es cancelación, usamos fecha_pago específica
        if ($r->input('tipo') === 'cancelacion' && $r->filled('fecha_pago_cancel')) {
            $r->merge(['fecha_pago' => $r->input('fecha_pago_cancel')]);
        }

        // ===== VALIDACIÓN =====
        $rules = [
            'dni'          => 'required|string|max:30',
            'tipo'         => 'required|in:convenio,cancelacion',
            'nota'         => 'nullable|string|max:1000',
            
            // JSON stringified desde el frontend
            'operaciones'  => 'required', 

            // Validaciones condicionales
            'fecha_pago'   => 'exclude_unless:tipo,cancelacion|required|date',
            'monto'        => 'exclude_unless:tipo,cancelacion|required|numeric|min:0.01', // Input name="monto" en cancelación

            'nro_cuotas'     => 'exclude_unless:tipo,convenio|required|integer|min:1',
            'monto_convenio' => 'exclude_unless:tipo,convenio|required|numeric|min:0.01',
            // Cronograma manual si aplica
            'cron_fecha'     => 'exclude_unless:tipo,convenio|nullable|array',
            'cron_monto'     => 'exclude_unless:tipo,convenio|nullable|array',
        ];
        
        $r->validate($rules);

        // Decodificar operaciones (vienen como JSON string "[123, 456]")
        $opsInput = $r->input('operaciones');
        $opsArray = is_string($opsInput) ? json_decode($opsInput, true) : $opsInput;
        
        if (empty($opsArray) || !is_array($opsArray)) {
            return back()->withErrors(['operaciones' => 'Debes seleccionar al menos una operación.'])->withInput();
        }

        // ===== LÓGICA DE DATOS =====
        DB::beginTransaction();
        try {
            $base = [
                'dni'                 => $dni,
                'nota'                => $r->input('nota'),
                'tipo'                => $r->input('tipo'),
                'workflow_estado'     => 'pendiente',
                'cumplimiento_estado' => 'pendiente',
                'user_id'             => $r->user()->id ?? null,
            ];

            // A) Convenio
            if ($r->input('tipo') === 'convenio') {
                $n = max(1, (int)$r->input('nro_cuotas'));
                
                // Generar cronograma automático si no viene manual
                $montoTotal = (float)$r->input('monto_convenio');
                $fechaIni   = Carbon::parse($r->input('fecha_pago')); // name="fecha_pago" en el form de convenio
                
                // Calculo simple de cuota promedio para guardar en cabecera
                $avgCuota = $montoTotal / $n;

                $data = array_merge($base, [
                    'fecha_promesa'  => now()->toDateString(),
                    'fecha_pago'     => $fechaIni->toDateString(),
                    'cuota_dia'      => (int)$fechaIni->day,
                    'nro_cuotas'     => $n,
                    'monto'          => $montoTotal, // Usamos 'monto' como el total general en DB
                    'monto_convenio' => $montoTotal,
                    'monto_cuota'    => $avgCuota,
                ]);
            } 
            // B) Cancelación
            else { 
                $fecha = Carbon::parse($r->input('fecha_pago'));
                $monto = (float)$r->input('monto'); // El input se llama 'monto' en el modal de cancelacion? (Verificar vista)
                // En tu vista anterior era 'monto' en ambos, ajustado.

                $data = array_merge($base, [
                    'fecha_promesa' => now()->toDateString(),
                    'fecha_pago'    => $fecha->toDateString(),
                    'monto'         => $monto,
                    'nro_cuotas'    => 1,
                ]);
            }

            /** @var PromesaPago $promesa */
            $promesa = PromesaPago::create($data);

            // --- Guardar Detalle de Operaciones ---
            $opsRows = [];
            foreach ($opsArray as $op) {
                // Buscamos la entidad real en la Cartera
                $carteraInfo = Cartera::where('operacion', $op)->first();
                $entidadReal = $carteraInfo ? $carteraInfo->entidad : 'PROPIA';

                $opsRows[] = [
                    'promesa_id' => $promesa->id,
                    'operacion'  => $op,
                    'cartera'    => $entidadReal, 
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            PromesaOperacion::insert($opsRows);

            // Legacy support (campo string)
            $promesa->operacion = implode(', ', $opsArray);
            $promesa->save();

            // --- Generar Cuotas (Solo Convenio) ---
            if ($promesa->tipo === 'convenio') {
                $cuotasRows = [];
                $fechaCursor = Carbon::parse($promesa->fecha_pago);
                $saldo = $promesa->monto;
                $cuotaBase = floor(($promesa->monto / $promesa->nro_cuotas) * 100) / 100;
                
                for ($i = 1; $i <= $promesa->nro_cuotas; $i++) {
                    // Ajuste de centavos en la primera cuota
                    $montoEstaCuota = ($i === 1) 
                        ? ($promesa->monto - ($cuotaBase * ($promesa->nro_cuotas - 1))) 
                        : $cuotaBase;

                    $cuotasRows[] = [
                        'promesa_id' => $promesa->id,
                        'nro'        => $i,
                        'fecha'      => $fechaCursor->toDateString(),
                        'monto'      => $montoEstaCuota,
                        'es_balon'   => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    $fechaCursor->addMonth(); // Siguiente mes
                }
                PromesaCuota::insert($cuotasRows);
            }

            DB::commit();
            
            // Notificar (opcional)
            if (class_exists(WorkflowMailer::class)) {
                WorkflowMailer::promesaPendiente($promesa);
            }

            return back()->with('ok', 'Propuesta registrada y enviada para autorización.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('Error al guardar: ' . $e->getMessage())->withInput();
        }
    }

    // --- Helpers ---

    private function toIsoDate(?string $v): ?string
    {
        if (!$v) return null;
        try {
            return Carbon::parse($v)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeMoney(?string $v): ?string
    {
        if (!$v) return null;
        return preg_replace('/[^0-9\.]/', '', str_replace(',', '', $v));
    }
}