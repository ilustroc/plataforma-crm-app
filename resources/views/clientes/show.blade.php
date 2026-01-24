@extends('layouts.app')
@section('title','Cliente '.$dni)
@section('crumb','Detalle de Cliente')

@push('head')
    @vite(['resources/css/cliente.css', 'resources/js/cliente.js'])
@endpush

@section('content')

    {{-- ALERTA FLOTANTE --}}
    @if(session('ok'))
    <div class="mb-6 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 backdrop-blur-sm shadow-sm animate-slide-in">
        <div class="bg-white p-1 rounded-full text-emerald-600 shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <span class="font-medium text-sm">{{ session('ok') }}</span>
    </div>
    @endif

    {{-- 1. ENCABEZADO DEL CLIENTE (Compacto - EL QUE TE GUSTÓ) --}}
    <div class="client-header">
        
        {{-- Izquierda: Identidad --}}
        <div class="header-profile">            
            <div class="header-avatar">
                {{ substr($titular, 0, 1) }}
            </div>
            
            <div class="header-info">
                <h1>{{ $titular }}</h1>
                
                <div class="header-meta">
                    {{-- DNI con copiado rápido --}}
                    <button class="flex items-center gap-1.5 hover:text-brand transition-colors group" data-copy="{{ $dni }}" title="Copiar DNI">
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .884-.896 1.79-2.25 2.025m4.5 0c1.354-.235 2.25-1.14 2.25-2.025"/></svg>
                        <span class="font-mono font-medium">{{ $dni }}</span>
                    </button>
                    
                    {{-- Separadores sutiles --}}
                    @if(isset($cuentas))
                        <span class="text-slate-200">|</span>
                        <span>{{ count($cuentas) }} cuentas</span>
                    @endif
                    
                    @if(isset($promesas) && count($promesas) > 0)
                        <span class="text-slate-200">|</span>
                        <span>{{ count($promesas) }} promesas</span>
                    @endif

                    @if(isset($pagos) && count($pagos) > 0)
                        <span class="text-slate-200">|</span>
                        <span>{{ count($pagos) }} pagos</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Derecha: Métricas Clave (Texto limpio, sin cajas) --}}
        <div class="header-stats">
            @php
                $totSaldo = (float)($cuentas->sum('saldo_capital') ?? 0);
                $totDeuda = (float)($cuentas->sum('deuda_total') ?? 0);
                $totPagos = (float)($pagos->sum('monto') ?? 0);
            @endphp

            <div class="stat-item">
                <span class="stat-label">Saldo Capital</span>
                <span class="stat-value">S/ {{ number_format($totSaldo, 2) }}</span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label">Deuda Total</span>
                <span class="stat-value text-red-600">S/ {{ number_format($totDeuda, 2) }}</span>
            </div>

            <div class="stat-item">
                <span class="stat-label">Pagos Reg.</span>
                <span class="stat-value text-emerald-600">S/ {{ number_format($totPagos, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- 2. SECCIÓN DE CUENTAS --}}
    <div class="section-card">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <div class="p-1.5 rounded-lg bg-slate-50 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    Cartera de Cuentas
                </h2>
            </div>

            <div class="flex gap-3">
                {{-- Botones de Acción --}}
                <button class="btn-action btn-disabled transition-all" id="btnPropuesta" data-modal-target="modalPropuesta" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Propuesta 
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold text-slate-500 selection-count">0</span>
                </button>
                <button class="btn-action btn-disabled transition-all" id="btnSolicitarCna" data-modal-target="modalCna" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    CNA 
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold text-slate-500 selection-count">0</span>
                </button>
            </div>
        </div>

        <div class="table-container max-h-[450px] custom-scroll">
            <table class="table-compact table-fixed">
                <thead>
                    <tr>
                        <th class="w-6 text-center">
                            <input type="checkbox" id="chkAll" class="checkbox-brand">
                        </th>
                        <th class="w-18">Cartera</th>
                        <th class="w-[140px]">Operación</th>
                        <th class="w-[180px]">Entidad</th>
                        <th class="w-[130px]">Producto</th>
                        <th class="w-22">Capital</th>
                        <th class="w-22">Deuda</th>
                        <th class="w-18">Pagos</th>
                        <th class="w-12">CNA</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($cuentas as $c)
                    <tr class="hover:bg-slate-50/70">
                        <td class="text-center">
                            <input type="checkbox" class="chkOp checkbox-brand" value="{{ $c->operacion }}" {{ empty($c->operacion) ? 'disabled' : '' }}>
                        </td>

                        <td class="font-mono text-xs text-slate-800 select-all">
                            {{ $c->cartera }}
                        </td>

                        <td class="font-mono text-xs text-slate-800 select-all">
                            {{ $c->operacion }}
                        </td>

                        <td class="text-xs text-slate-800 truncate" title="{{ $c->entidad }}">
                            {{ $c->entidad }}
                        </td>

                        <td class="text-xs text-slate-800 truncate" title="{{ $c->producto }}">
                            {{ $c->producto }}
                        </td>

                        <td class="font-mono text-xs text-slate-800 tabular-nums">
                            {{ number_format((float)$c->saldo_capital, 2) }}
                        </td>

                        <td class="font-mono text-xs text-slate-800 tabular-nums">
                            {{ number_format((float)$c->deuda_total, 2) }}
                        </td>

                        <td class="font-mono text-xs text-slate-800 tabular-nums">
                            @php $ps = (float)($c->pagos_sum ?? 0); @endphp

                            @if($ps > 0)
                                {{ number_format($ps, 2) }}
                            @else
                                -
                            @endif
                        </td>

                        <td class="text-center">
                            @php $cnas = collect($cnasByOperacion[$c->operacion] ?? []); @endphp
                            @if($cnas->isNotEmpty())
                                <span class="pill pill-brand" title="{{ $cnas->count() }} solicitudes">
                                    {{ $cnas->count() }}
                                </span>
                            @else
                                <span class="text-slate-200 text-xs">•</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 3. SECCIÓN DE PAGOS --}}
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">
                <div class="p-1.5 rounded-lg bg-slate-50 text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                Pagos Registrados
            </h2>
            
            <button class="btn-action btn-outline h-8 text-xs bg-white text-slate-500 hover:text-brand border-slate-200" id="btnCopyPag" data-bs-toggle="tooltip" title="Copiar tabla">
                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                Copiar
            </button>
        </div>

        <div class="table-container max-h-[300px] custom-scroll" id="tblPagosContainer">
            <table class="table-compact" id="tblPagos">
                <thead>
                    <tr>
                        <th>Operación</th>
                        <th>Fecha</th>
                        <th class="text-right">Monto</th>
                        <th>Gestor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $p)
                        @php
                            $oper = $p->oper ?? $p['oper'] ?? '-';
                            $fec  = $p->fecha ?? $p['fecha'] ?? null;
                            $mon  = $p->monto ?? $p['monto'] ?? 0;
                            $gest = $p->gestor ?? $p['gestor'] ?? '-';
                            $st   = strtoupper($p->estado ?? $p['estado'] ?? '-');
                            
                            $badgeClass = 'badge-neutral';
                            if (str_contains($st,'CANCEL')) $badgeClass = 'badge-success';
                            elseif (str_contains($st,'PEND')) $badgeClass = 'badge-warning';
                            elseif (preg_match('/CUOTA|ABONO|PARCIAL/', $st)) $badgeClass = 'badge-info';
                            elseif (preg_match('/RECHAZ|ANUL/', $st)) $badgeClass = 'badge-danger';
                        @endphp
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $oper }}</td>
                            <td>{{ $fec ? \Carbon\Carbon::parse($fec)->format('d/m/Y') : '-' }}</td>
                            <td class="text-right font-medium text-slate-800">S/ {{ number_format((float)$mon, 2) }}</td>
                            <td class="text-xs text-slate-500">{{ Str::limit($gest, 15) }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $st }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-slate-400 italic bg-slate-50/20">
                                No se encontraron pagos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. SECCIÓN DE PROMESAS --}}
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">
                <div class="p-1.5 rounded-lg bg-slate-50 text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                Historial de Promesas
            </h2>
        </div>

        <div class="table-container custom-scroll">
            <table class="table-compact">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="text-right">Monto</th>
                        <th>Operaciones</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promesas as $pp)
                    @php
                        $estado = strtolower($pp->workflow_estado ?? 'pendiente');
                        $rowClass = match(true) {
                            str_contains($estado, 'aprob') => 'bg-emerald-50/40 border-l-4 border-l-emerald-400',
                            str_contains($estado, 'pre') => 'bg-sky-50/40 border-l-4 border-l-sky-400',
                            str_contains($estado, 'rechaz') => 'bg-red-50/40 border-l-4 border-l-red-400',
                            default => 'hover:bg-slate-50 border-l-4 border-l-transparent'
                        };
                        
                        $badgeClass = match(true) {
                            str_contains($estado, 'aprob') => 'badge-success',
                            str_contains($estado, 'pre') => 'badge-info',
                            str_contains($estado, 'rechaz') => 'badge-danger',
                            default => 'badge-neutral'
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="font-medium text-slate-700">{{ optional($pp->fecha_promesa)->format('d/m/Y') }}</td>
                        <td class="text-xs uppercase font-bold text-slate-500 tracking-wide">{{ $pp->tipo }}</td>
                        <td class="text-right font-bold text-slate-800">S/ {{ number_format((float)$pp->monto_mostrar, 2) }}</td>
                        <td>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($pp->operaciones as $op)
                                    <span class="text-[10px] bg-white border border-slate-200 px-1.5 py-0.5 rounded text-slate-500 font-mono">{{ $op->operacion }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($estado) }}</span>
                        </td>
                        <td class="text-right">
                             @if($estado === 'aprobada')
                                <a href="{{ route('promesas.acuerdo', $pp) }}" target="_blank" class="btn-action btn-outline h-7 text-xs px-2 py-0 border-slate-200 text-slate-600 hover:text-brand hover:border-brand/50">
                                    <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    PDF
                                </a>
                             @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400 italic">No hay promesas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== MODALES ===== --}}
    
    {{-- Modal Propuesta --}}
    <div id="modalPropuesta" class="modal-backdrop">
        <div class="modal-box">
            <form method="POST" action="{{ route('clientes.promesas.store', $dni) }}" id="formPropuesta">
                @csrf
                <div class="modal-header">
                    <h3 class="font-bold text-lg text-slate-800">Nueva Propuesta</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="modal-body space-y-5">
                    {{-- Operaciones --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Operaciones Seleccionadas</label>
                        <div id="opsResumen" class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200 min-h-[40px]"></div>
                        <div id="opsHidden"></div> 
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipo de Acuerdo</label>
                            <select name="tipo" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand">
                                <option value="convenio">Convenio</option>
                                <option value="cancelacion">Cancelación</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Monto Total (S/)</label>
                            <input type="number" id="cvTotal" name="monto_convenio" step="0.01" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand font-bold text-slate-700" placeholder="0.00">
                        </div>
                    </div>

                    {{-- Cronograma --}}
                    <div class="bg-slate-50/80 p-5 rounded-xl border border-slate-200/60">
                        <div class="flex items-end gap-3 mb-4">
                            <div class="w-20">
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Cuotas</label>
                                <input type="number" id="cvNro" name="nro_cuotas" value="1" min="1" class="w-full rounded-lg border-slate-200 text-sm py-1.5 text-center">
                            </div>
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Fecha Inicio</label>
                                <input type="date" id="cvFechaIni" class="w-full rounded-lg border-slate-200 text-sm py-1.5">
                            </div>
                            <button type="button" id="cvGen" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                                Generar
                            </button>
                        </div>

                        <div class="max-h-[180px] overflow-y-auto border border-slate-200 rounded-lg bg-white shadow-inner">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase sticky top-0">
                                    <tr>
                                        <th class="py-2 px-3 text-center border-b border-slate-100">#</th>
                                        <th class="py-2 px-3 text-left border-b border-slate-100">Fecha</th>
                                        <th class="py-2 px-3 text-right border-b border-slate-100">Monto</th>
                                    </tr>
                                </thead>
                                <tbody id="tblCronoBody"></tbody>
                            </table>
                        </div>
                        <div class="flex justify-between items-center mt-3 px-1">
                            <span class="text-xs text-slate-400">Total calculado:</span>
                            <span class="text-sm font-bold text-slate-800">S/ <span id="cvSuma">0.00</span></span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action btn-outline border-slate-200 text-slate-500" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-action btn-primary shadow-lg shadow-brand/20">Guardar Propuesta</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal CNA --}}
    <div id="modalCna" class="modal-backdrop">
        <div class="modal-box max-w-md">
            <form method="POST" action="{{ route('clientes.cna.store', $dni) }}">
                @csrf
                <div class="modal-header">
                    <h3 class="font-bold text-lg text-slate-800">Solicitar CNA</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="modal-body space-y-5">
                    <div class="p-4 bg-sky-50 text-sky-800 rounded-xl text-sm border border-sky-100 flex items-start gap-3">
                        <svg class="h-5 w-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            Se generará una solicitud para las <span class="font-bold selection-count">0</span> operaciones seleccionadas.
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Monto Pagado (S/)</label>
                            <input type="number" name="monto_pagado" step="0.01" class="w-full rounded-xl border-slate-200 py-2.5 focus:border-brand focus:ring-brand font-medium" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha de Pago</label>
                            <input type="date" name="fecha_pago_realizado" class="w-full rounded-xl border-slate-200 py-2.5 focus:border-brand focus:ring-brand text-slate-600" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-action btn-outline border-slate-200 text-slate-500" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-action btn-success shadow-lg shadow-emerald-500/20">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

@endsection