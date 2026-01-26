@extends('layouts.app')
@section('title','Panel General')
@section('crumb','Resumen')

@push('head')
    @vite(['resources/css/resumen.css', 'resources/js/resumen.js'])
@endpush

@section('content')
@php
  $role = $role ?? strtolower(auth()->user()->role ?? '');
  $isAsesor = $isAsesor ?? ($role === 'asesor');
  
  $kpiPromHoy = $kpiPromHoy ?? 0;
  $kpiPagosHoy = $kpiPagosHoy ?? 0;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start pb-10">
    
    {{-- ==================== COLUMNA IZQUIERDA (Principal) ==================== --}}
    <div class="lg:col-span-8 space-y-8">

        {{-- 1. KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {{-- KPI 1 --}}
        <div class="kpi-box group">
            <div class="kpi-icon-wrap bg-purple-50 text-purple-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            </div>

            <div class="min-w-0">
            <div class="kpi-title">Promesas para hoy</div>
            <div class="kpi-value">{{ number_format($kpiPromHoy) }}</div>
            </div>
        </div>

        {{-- KPI 2 --}}
        <div class="kpi-box group">
            <div class="kpi-icon-wrap bg-emerald-50 text-emerald-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            </div>

            <div class="min-w-0">
            <div class="kpi-title">Recaudo del día</div>
            <div class="kpi-value">S/ {{ number_format($kpiPagosHoy, 2) }}</div>
            </div>
        </div>
        </div>

        {{-- 2. GRÁFICA --}}
        <div class="dashboard-card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Evolución de Recaudo</h3>
                    <p class="text-xs text-slate-400">Comparativa mensual de ingresos</p>
                </div>
                
                <div class="flex items-center bg-slate-50 rounded-lg p-1 border border-slate-100">
                    @php $curr = \Carbon\Carbon::createFromFormat('Y-m', $mes ?? date('Y-m')); @endphp
                    
                    <a href="{{ url()->current().'?mes='.$curr->copy()->subMonth()->format('Y-m') }}" 
                       class="w-8 h-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-white hover:text-brand hover:shadow-sm transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>

                    <div class="relative px-2">
                        <input type="month" id="mesPicker" value="{{ $curr->format('Y-m') }}" 
                               class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 cursor-pointer text-center w-32">
                    </div>

                    <a href="{{ url()->current().'?mes='.$curr->copy()->addMonth()->format('Y-m') }}"
                       class="w-8 h-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-white hover:text-brand hover:shadow-sm transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <div class="relative h-80 w-full">
                <canvas id="chartPagos" data-chart='@json(["labels" => $chartLabels ?? [], "data" => $chartData ?? []])'></canvas>
            </div>
        </div>
    </div>


    {{-- ==================== COLUMNA DERECHA (Timeline) ==================== --}}
    <div class="lg:col-span-4 h-full min-h-[500px]">
        <div class="sticky top-24 h-[calc(100vh-8rem)]">
            <div class="timeline-container">
                
                <div class="timeline-header">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand/10 text-brand">
                            <i class="bi bi-bell-fill text-sm"></i>
                        </span>
                        <span class="font-bold text-slate-800">Actividades</span>
                    </div>
                    @if($isAsesor)
                        <span class="text-xs font-semibold bg-brand text-white px-2 py-1 rounded-md">Tus tareas</span>
                    @else
                        <span class="text-xs font-semibold bg-slate-100 text-slate-500 px-2 py-1 rounded-md">General</span>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto custom-scroll p-2">

                    {{-- ASESOR --}}
                    @if($isAsesor)
                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 mt-2">Pendientes</div>
                        @forelse($misSup as $p)
                            <a href="{{ route('clientes.show', $p->dni) }}" class="timeline-item group">
                                <div class="timeline-line"></div>
                                <div class="relative z-10 mt-1 flex h-2.5 w-2.5 shrink-0 items-center justify-center rounded-full bg-amber-400 ring-4 ring-white"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-bold text-slate-800 group-hover:text-brand transition-colors">{{ $p->dni }}</p>
                                        <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Sup</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $p->operacion ?: 'Gestión' }} • {{ $p->tipo }}</p>
                                </div>
                            </a>
                        @empty
                             <div class="p-6 text-center text-sm text-slate-400 italic">No tienes promesas pendientes.</div>
                        @endforelse

                    {{-- ADMIN/SUPERVISOR --}}
                    @else
                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 mt-2">Por Aprobar</div>
                        @forelse($ppPend as $p)
                            <a href="{{ route('autorizacion') }}" class="timeline-item group">
                                <div class="timeline-line"></div>
                                <div class="relative z-10 mt-1 flex h-2.5 w-2.5 shrink-0 items-center justify-center rounded-full bg-brand ring-4 ring-white animate-pulse"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-bold text-slate-800 group-hover:text-brand transition-colors">{{ $p->dni }}</p>
                                        <span class="text-xs font-bold text-slate-700">S/ {{ number_format($p->monto_mostrar, 0) }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }}</p>
                                </div>
                            </a>
                        @empty
                            <div class="px-6 py-4 text-center text-sm text-slate-400 italic bg-slate-50/50 rounded-lg mx-4">
                                Todo limpio por aquí
                            </div>
                        @endforelse

                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 mt-4">Vencimientos (7 días)</div>
                        @forelse($venc as $v)
                            <div class="timeline-item">
                                <div class="timeline-line"></div>
                                <div class="relative z-10 mt-1 flex h-2.5 w-2.5 shrink-0 items-center justify-center rounded-full bg-amber-500 ring-4 ring-white"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-semibold text-slate-700">{{ $v->dni }}</p>
                                        <span class="text-xs font-medium text-slate-500">{{ \Carbon\Carbon::parse($v->fecha)->format('d/m') }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">S/ {{ number_format((float)$v->monto, 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-2 text-sm text-slate-400 italic">Sin vencimientos cercanos.</div>
                        @endforelse
                    @endif
                </div>
                
                @unless($isAsesor)
                <div class="p-3 border-t border-slate-50 bg-slate-50/50 text-center">
                    <a href="{{ route('autorizacion') }}" class="text-xs font-bold text-brand hover:text-brand-700 transition-colors uppercase tracking-wide">
                        Ver Bandeja Completa
                    </a>
                </div>
                @endunless
            </div>
        </div>
    </div>

</div>
@endsection