@extends('layouts.app')
@section('title','Cargar Pagos')
@section('crumb','Integración / Cargar Pagos')

@push('head')
    @vite(['resources/css/import_pagos.css', 'resources/js/import_pagos.js'])
@endpush

@section('content')

<div class="mx-auto w-[92%] max-w-4xl">
    {{-- Header compacto --}}
    <div class="mb-4">
        <h1 class="text-xl font-semibold text-slate-800">Carga masiva de pagos</h1>
        <p class="mt-1 text-sm text-slate-500">Sube tu archivo CSV para registrar los pagos en el sistema.</p>
    </div>

    @if(session('warn'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M5.062 20h13.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.33 17c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-xs whitespace-pre-wrap font-mono">{{ session('warn') }}</div>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        {{-- Header Card compacto --}}
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Archivo CSV</h3>
                <p class="mt-1 text-[11px] text-slate-500">
                    Columnas requeridas:
                    <span class="font-mono">DOCUMENTO, OPERACION, MONEDA, FECHA, MONTO, GESTOR</span>
                </p>
            </div>

            <a href="{{ route('integracion.pagos.template') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-brand hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Plantilla
            </a>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('integracion.pagos.import') }}" method="POST" enctype="multipart/form-data" class="p-4">
            @csrf

            {{-- Zona de carga (mantiene IDs/clases para tu JS/CSS) --}}
            <div class="upload-zone" id="dropZone">
                <input type="file" name="archivo" id="csvInput" class="file-input" accept=".csv" required>

                <div class="pointer-events-none relative z-10 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"/>
                        </svg>
                    </div>

                    <div class="text-left">
                        <p class="text-sm font-semibold text-slate-700">Arrastra o selecciona tu CSV</p>
                        <p class="text-xs text-slate-500">Clic para buscar (o arrastra aquí)</p>
                        <p id="fileName" class="mt-1 text-[11px] text-slate-400"></p>
                    </div>
                </div>
            </div>

            {{-- Validación JS --}}
            <div id="validationBox" class="mt-3 hidden">
                <div id="statusMsg" class="rounded-lg px-3 py-2 text-xs font-semibold flex items-center gap-2"></div>

                {{-- Tabla de errores (opcional) --}}
                <div class="mt-3 border border-slate-200 rounded-lg overflow-hidden hidden" id="tableWrapper">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold">
                            <tr>
                                <th class="px-3 py-2">Columna Faltante</th>
                                <th class="px-3 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="issuesBody"></tbody>
                    </table>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="mt-4 flex justify-end">
                <button type="submit" id="btnImport" class="btn-import" disabled>
                    Importar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
