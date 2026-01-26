@extends('layouts.app')
@section('title','Dashboard')
@section('crumb','Estadísticas')

@push('head')
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
@endpush

@section('content')

    {{-- 1. BARRA DE FILTROS --}}
    <form id="filtrosDash" method="GET" action="{{ route('dashboard') }}" class="filter-card">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            
            {{-- Mes --}}
            <div class="md:col-span-4 lg:col-span-3">
                <label class="form-label">Periodo</label>
                <input type="month" name="mes" class="form-input" 
                       value="{{ $mes }}">
            </div>

            {{-- Gestor (Select Dinámico) --}}
            <div class="md:col-span-5 lg:col-span-4">
                <label class="form-label">Gestor</label>
                <select name="gestor" class="form-input cursor-pointer">
                    <option value="">Todos los gestores</option>
                    @foreach($gestores as $g)
                        <option value="{{ $g }}" {{ $gestor == $g ? 'selected' : '' }}>
                            {{ $g }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Botones --}}
            <div class="md:col-span-3 lg:col-span-5 flex gap-2">
                <button type="submit" class="btn-filter">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filtrar
                </button>
                @if(request()->has('mes') || request()->has('gestor'))
                    <a href="{{ route('dashboard') }}" class="btn-reset">
                        Limpiar
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- 2. KPIs (Grid de Tarjetas) --}}
    <div class="kpi-grid">
        {{-- KPI: Cantidad Pagos --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-label">
                    <svg class="h-4 w-4 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    N° Pagos
                </div>
                <div class="kpi-value">{{ number_format($kpis['pagos_count']) }}</div>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-brand w-[70%]"></div> {{-- Barra decorativa estática --}}
            </div>
        </div>

        {{-- KPI: Monto Recaudado --}}
        <div class="kpi-card">
            <div>
                <div class="kpi-label text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recaudo Total
                </div>
                <div class="kpi-value text-emerald-700">S/ {{ number_format($kpis['pagos_sum'], 2) }}</div>
            </div>
            <div class="mt-4 h-1 w-full bg-emerald-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 w-full"></div>
            </div>
        </div>

        {{-- KPI: Ticket Promedio (Calculado en vista) --}}
        @php 
            $ticket = $kpis['pagos_count'] > 0 ? $kpis['pagos_sum'] / $kpis['pagos_count'] : 0;
        @endphp
        <div class="kpi-card">
            <div>
                <div class="kpi-label text-sky-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Ticket Promedio
                </div>
                <div class="kpi-value text-slate-700">S/ {{ number_format($ticket, 2) }}</div>
            </div>
            <div class="mt-4 text-xs text-slate-400 font-medium">
                Promedio por operación
            </div>
        </div>

        {{-- KPI: Placeholder PDP (Futuro) --}}
        <div class="kpi-card bg-slate-50 border-dashed border-slate-300 shadow-none opacity-70">
            <div>
                <div class="kpi-label text-slate-400">PDP Generadas</div>
                <div class="kpi-value text-slate-400">—</div>
            </div>
            <div class="mt-4 text-xs text-slate-400">Próximamente</div>
        </div>
    </div>

    {{-- 3. VISUALIZACIONES --}}
    <div class="viz-grid">
        {{-- Gráfica de Pagos --}}
        <div class="chart-card lg:col-span-2">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Evolución de Pagos</h3>
                    <p class="chart-subtitle">Comportamiento de recaudación (Últimos 12 meses)</p>
                </div>
                {{-- Botón opcional o filtro --}}
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                </div>
            </div>
            
            <div class="relative h-[300px] w-full">
                <canvas id="linePagos" data-chart='@json($chart)'></canvas>
            </div>
        </div>
    </div>

@endsection