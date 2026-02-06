@extends('layouts.app')
@section('title','Reporte ▸ Promesas de Pago')
@section('crumb','Reportes / Promesas')

@push('head')
    @vite(['resources/css/reporte_promesas.css', 'resources/js/reporte_promesas.js'])
@endpush

@section('content')
    {{-- Filtros y Acciones (Estilo Impulse) --}}
    <form id="filterForm" action="{{ route('reportes.promesas.index') }}" data-export-url="{{ route('reportes.promesas.export') }}" class="filter-bar">
        
        <div class="form-group flex-grow-[2]">
            <label class="form-label">Buscar</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="q" class="form-input pl-9" placeholder="DNI, Operación, Cliente..." value="{{ $q }}">
            </div>
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Desde</label>
            <input type="date" name="from" class="form-input" value="{{ $from }}">
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Hasta</label>
            <input type="date" name="to" class="form-input" value="{{ $to }}">
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Estado</label>
            <input type="text" name="estado" class="form-input" placeholder="Pendiente..." value="{{ $estado }}">
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Gestor</label>
            <input type="text" name="gestor" class="form-input" placeholder="Nombre..." value="{{ $gestor }}">
        </div>

        <div class="flex items-end gap-2 pb-0.5">
            <button type="submit" class="btn-search">Buscar</button>
            <a href="{{ route('reportes.promesas.export', request()->query()) }}" id="btnExport" class="btn-export" title="Descargar Excel">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"/></svg>
            </a>
        </div>
    </form>

    {{-- Tabla de Resultados --}}
    <div id="tableContainer" class="table-card min-h-[400px]">
        @fragment('tabla-resultados')
            <div id="pagMeta" class="hidden" data-page="{{ $rows->currentPage() }}" data-total="{{ $rows->total() }}"></div>
            
            <div class="table-responsive">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Operación</th>
                            <th>Entidad</th>
                            <th>Fecha Gestión</th>
                            <th>Observación</th>
                            <th class="text-right">Monto</th>
                            <th class="text-center">Cuotas</th>
                            <th>Fecha Promesa</th>
                            <th>Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $r->documento }}</td>
                            <td class="text-xs font-bold text-slate-600 uppercase max-w-[200px] truncate" title="{{ $r->cliente }}">
                                {{ $r->cliente }}
                            </td>
                            <td class="font-mono text-xs text-slate-500">{{ $r->operacion }}</td>
                            <td class="text-xs text-slate-500">{{ $r->entidad }}</td>
                            <td class="text-xs text-slate-500">{{ $r->fecha_gestion }}</td>
                            <td class="text-xs text-slate-400 max-w-[250px] truncate" title="{{ $r->observacion }}">
                                {{ $r->observacion }}
                            </td>
                            <td class="text-right font-bold text-slate-800">
                                {{ number_format((float)$r->monto_promesa, 2) }}
                            </td>
                            <td class="text-center text-slate-500">{{ $r->nro_cuotas ?? '-' }}</td>
                            <td class="text-nowrap font-bold text-brand">{{ $r->fecha_promesa }}</td>
                            <td class="text-xs uppercase text-slate-500">{{ $r->gestor }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-slate-400 italic">
                                No se encontraron promesas con los filtros actuales.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="text-xs text-slate-500">
                    Mostrando {{ $rows->firstItem() }} - {{ $rows->lastItem() }} de {{ $rows->total() }}
                </div>
                <div id="summary" class="hidden tiny fw-bold text-secondary"></div>
                <div>
                    {{ $rows->onEachSide(1)->links('pagination::simple-tailwind') }}
                </div>
            </div>
            @endif
        @endfragment
    </div>
@endsection