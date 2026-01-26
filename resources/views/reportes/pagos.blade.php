@extends('layouts.app')
@section('title','Reporte de Pagos')
@section('crumb','Reporte de Pagos')

@push('head')
    @vite(['resources/css/reporte_pagos.css', 'resources/js/reporte_pagos.js'])
@endpush

@section('content')

    {{-- Filtros y Acciones --}}
    <form id="filterForm" action="{{ route('reportes.pagos.index') }}" data-export-url="{{ route('reportes.pagos.export') }}" class="filter-bar">
        
        <div class="form-group flex-grow-[2]">
            <label class="form-label">Buscar</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="q" class="form-input pl-9" placeholder="Cliente, DNI, Operación...">
            </div>
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Desde</label>
            <input type="date" name="from" class="form-input" value="{{ date('Y-m-01') }}">
        </div>
        <div class="form-group flex-grow">
            <label class="form-label">Hasta</label>
            <input type="date" name="to" class="form-input" value="{{ date('Y-m-t') }}">
        </div>

        <div class="form-group flex-grow">
            <label class="form-label">Gestor</label>
            <input type="text" name="gestor" class="form-input" placeholder="Nombre...">
        </div>

        <div class="flex items-end gap-2 pb-0.5">
            <button type="submit" class="btn-search">Buscar</button>
            <a href="{{ route('reportes.pagos.export') }}" id="btnExport" class="btn-export" title="Descargar Excel">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"/></svg>
            </a>
        </div>
    </form>

    {{-- Tabla de Resultados --}}
    <div id="tableContainer" class="table-card min-h-[300px]">
        @fragment('tabla-resultados')
            <div class="table-responsive">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Operación</th>
                            <th>Cartera / Producto</th>
                            <th>Fecha</th>
                            <th class="text-right">Monto</th>
                            <th>Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $p)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $p->documento }}</td>
                            
                            {{-- NOMBRE DEL CLIENTE --}}
                            <td class="text-xs font-bold text-slate-600 uppercase max-w-[200px] truncate" title="{{ $p->cliente_nombre }}">
                                {{ $p->cliente_nombre ?? '---' }}
                            </td>
                            
                            <td class="font-mono text-xs text-slate-500">{{ $p->operacion }}</td>
                            
                            <td class="text-xs text-slate-500">
                                <div class="flex flex-col">
                                    <span class="font-bold">{{ $p->cliente_cartera ?? '-' }}</span>
                                    <span class="text-[10px]">{{ $p->cliente_producto }}</span>
                                </div>
                            </td>

                            <td>{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                            
                            <td class="text-right font-bold text-slate-800">
                                <span class="text-[10px] text-slate-400 mr-1">{{ $p->moneda }}</span>
                                {{ number_format((float)$p->monto, 2) }}
                            </td>
                            
                            <td class="text-xs uppercase">{{ $p->gestor }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400 italic">
                                No se encontraron registros con los filtros actuales.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pagos->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="text-xs text-slate-500">
                    Mostrando {{ $pagos->firstItem() }} - {{ $pagos->lastItem() }} de {{ $pagos->total() }}
                </div>
                <div>
                    {{ $pagos->onEachSide(1)->links('pagination::simple-tailwind') }}
                </div>
            </div>
            @endif
        @endfragment
    </div>

@endsection