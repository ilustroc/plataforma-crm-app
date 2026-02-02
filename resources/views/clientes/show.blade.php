@extends('layouts.app')
@section('title', 'Cliente '.$dni)
@section('crumb', 'Clientes / Detalle')

@push('head')
    @vite(['resources/css/cliente.css', 'resources/js/cliente.js'])
@endpush

@section('content')

    {{-- 1. ENCABEZADO DEL CLIENTE --}}
    <div class="client-header">
        
        {{-- Izquierda: Identidad --}}
        <div class="header-profile">            
            <div class="header-avatar bg-brand/10 text-brand border-brand/20">
                {{ substr($nombre, 0, 1) }}
            </div>
            
            <div class="header-info">
                <h1 class="text-xl font-bold text-slate-800">{{ $nombre }}</h1>
                
                <div class="header-meta flex flex-wrap gap-x-4 gap-y-1 mt-1">
                    {{-- DNI con copiado --}}
                    <button class="flex items-center gap-1.5 hover:text-brand transition-colors group" data-copy="{{ $dni }}" title="Copiar DNI">
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .884-.896 1.79-2.25 2.025m4.5 0c1.354-.235 2.25-1.14 2.25-2.025"/></svg>
                        <span class="font-mono font-medium text-slate-600">{{ $dni }}</span>
                    </button>
                    
                    {{-- Ubicación (Nuevo campo Cartera) --}}
                    @if($cuentas->isNotEmpty() && $cuentas->first()->departamento)
                        <span class="text-slate-300">|</span>
                        <div class="flex items-center gap-1 text-slate-500">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs uppercase">{{ $cuentas->first()->departamento }} - {{ $cuentas->first()->provincia }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Derecha: Métricas Clave --}}
        <div class="header-stats">
            @php
                $totCapital = (float)$cuentas->sum('saldo_capital');
                $totDeuda   = (float)$cuentas->sum('deuda_total');
                $totPagos   = (float)$pagos->sum('monto');
            @endphp

            <div class="stat-item">
                <span class="stat-label">Saldo Capital</span>
                <span class="stat-value text-slate-700">S/ {{ number_format($totCapital, 2) }}</span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label">Deuda Total</span>
                <span class="stat-value text-rose-600">S/ {{ number_format($totDeuda, 2) }}</span>
            </div>

            <div class="stat-item pl-6 border-l border-slate-100">
                <span class="stat-label">Recuperado</span>
                <span class="stat-value text-emerald-600">S/ {{ number_format($totPagos, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- 2. CARTERA DETALLADA --}}
    <div class="section-card">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <div class="p-1.5 rounded-lg bg-brand/5 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    Cartera de Cuentas
                </h2>
                <p class="text-xs text-slate-400 mt-1 pl-9">Selecciona operaciones para gestionar acuerdos.</p>
            </div>

            <div class="flex gap-3">
                <button class="btn-action btn-primary btn-disabled transition-all" id="btnPropuesta" data-modal-target="modalPropuesta" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Propuesta 
                    <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold selection-count">0</span>
                </button>
                
                <button class="btn-action btn-outline btn-disabled transition-all" id="btnSolicitarCna" data-modal-target="modalCna" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    CNA 
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold text-slate-500 selection-count">0</span>
                </button>
            </div>
        </div>

        <div class="table-container max-h-[500px] custom-scroll">
            <table class="table-compact">
                <thead>
                    <tr>
                        <th class="w-8 text-center">
                            <input type="checkbox" id="chkAll" class="checkbox-brand">
                        </th>
                        <th>Entidad / Cosecha</th>
                        <th>Producto</th>
                        <th>Operación</th>
                        <th>Fecha Castigo</th>
                        <th class="text-right">Capital</th>
                        <th class="text-right">Intereses</th>
                        <th class="text-right">Total Deuda</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($cuentas as $c)
                    <tr class="group hover:bg-slate-50/70 transition-colors">
                        <td class="text-center">
                            <input type="checkbox" class="chkOp checkbox-brand" value="{{ $c->operacion }}">
                        </td>

                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700 text-xs">{{ $c->entidad }}</span>
                                <span class="font-mono text-xs font-medium text-slate-600 select-all">{{ $c->cosecha }}</span>
                            </div>
                        </td>

                        <td class="font-mono text-xs font-medium text-slate-600 select-all">
                            {{ $c->producto }}
                        </td>

                        <td class="font-mono text-xs font-medium text-slate-600 select-all">
                            {{ $c->operacion }}
                        </td>

                        <td>
                            <div class="flex flex-col text-[11px] text-slate-500">
                                <span>{{ optional($c->fecha_castigo)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                        </td>

                        <td class="text-right font-mono text-xs text-slate-600">
                            {{ number_format((float)$c->saldo_capital, 2) }}
                        </td>

                        <td class="text-right font-mono text-xs text-slate-600">
                            {{ number_format((float)$c->intereses, 2) }}
                        </td>

                        <td class="text-right font-bold font-mono text-xs text-rose-600 bg-rose-50/30 rounded-r-lg">
                            {{ number_format((float)$c->deuda_total, 2) }}
                        </td>
                        
                        <td class="text-center relative group/tooltip">
                            {{-- Tooltip CSS Puro --}}
                            <div class="cursor-help text-slate-300 hover:text-brand transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="absolute right-8 top-0 w-64 bg-slate-800 text-white text-[11px] p-3 rounded-xl shadow-xl z-50 text-left invisible opacity-0 group-hover/tooltip:visible group-hover/tooltip:opacity-100 transition-all duration-200">
                                <p class="font-bold mb-1 text-slate-300">Ubicación:</p>
                                <p>{{ $c->direccion ?? 'Sin dirección registrada' }}</p>
                                <p class="mt-1 text-slate-400">{{ $c->distrito }} - {{ $c->provincia }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Grid inferior: Historial y Pagos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- 3. HISTORIAL DE PROMESAS --}}
        <div class="section-card h-full">
            <div class="section-header py-4">
                <h2 class="section-title text-base">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Historial de Acuerdos
                </h2>
            </div>
            <div class="table-container max-h-[300px] custom-scroll">
                <table class="table-compact">
                    <thead class="bg-white">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th class="text-right">Monto</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promesas as $pp)
                            @php
                                $estado = strtolower($pp->workflow_estado ?? 'pendiente');
                                $badge = match(true) {
                                    str_contains($estado, 'aprob') => 'badge-success',
                                    str_contains($estado, 'rechaz') => 'badge-danger',
                                    default => 'badge-warning'
                                };
                            @endphp
                            <tr>
                                <td class="text-xs text-slate-500">{{ $pp->created_at->format('d/m/y') }}</td>
                                <td class="text-[10px] font-bold uppercase text-slate-400">{{ $pp->tipo }}</td>
                                <td class="text-right font-bold text-slate-700 text-xs">S/ {{ number_format((float)$pp->monto, 2) }}</td>
                                <td><span class="badge {{ $badge }} scale-90 origin-left">{{ ucfirst($estado) }}</span></td>
                                <td class="text-right">
                                    @if($estado === 'aprobada')
                                        <a href="{{ route('promesas.acuerdo', $pp->id) }}" target="_blank" class="text-brand hover:underline text-[10px]">PDF</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-6 text-slate-400 italic text-xs">Sin historial reciente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. PAGOS REGISTRADOS --}}
        <div class="section-card h-full">
            <div class="section-header py-4">
                <h2 class="section-title text-base">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pagos Registrados
                </h2>
            </div>
            <div class="table-container max-h-[300px] custom-scroll">
                <table class="table-compact">
                    <thead class="bg-white">
                        <tr>
                            <th>Fecha</th>
                            <th>Operación</th>
                            <th class="text-right">Monto</th>
                            <th>Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $p)
                            <tr>
                                <td class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                                <td class="text-xs font-mono text-slate-400">{{ $p->operacion }}</td>
                                <td class="text-right font-bold text-emerald-600 text-xs">S/ {{ number_format((float)$p->monto, 2) }}</td>
                                <td class="text-[10px] text-slate-400 uppercase truncate max-w-[80px]">{{ $p->gestor }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-slate-400 italic text-xs">No hay pagos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== MODALES ===== --}}
    
    {{-- Modal Propuesta --}}
    <div id="modalPropuesta" class="modal-backdrop">
        <div class="modal-box">
            <form method="POST" action="{{ route('clientes.promesas.store', $dni) }}" id="formPropuesta">
                @csrf
                <div class="modal-header">
                    <h3 class="font-bold text-lg text-slate-800">Nueva Propuesta de Pago</h3>
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
                        <div class="form-group">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipo de Acuerdo</label>
                            <select name="tipo" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand">
                                <option value="convenio">Convenio (Fraccionado)</option>
                                <option value="cancelacion">Cancelación (Pago Único)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Monto Total (S/)</label>
                            <input type="number" id="cvTotal" name="monto" step="0.01" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand font-bold text-slate-700" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nota / Observación</label>
                        <textarea name="nota" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:border-brand focus:ring-brand" placeholder="Detalles del acuerdo..."></textarea>
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
                                <input type="date" id="cvFechaIni" name="fecha_pago" class="w-full rounded-lg border-slate-200 text-sm py-1.5" required>
                            </div>
                            <button type="button" id="cvGen" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                                Generar
                            </button>
                        </div>

                        <div class="max-h-[150px] overflow-y-auto border border-slate-200 rounded-lg bg-white shadow-inner">
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