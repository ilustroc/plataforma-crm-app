@extends('layouts.app')
@section('title','Integración ▸ Cargar Cartera')
@section('crumb','Integración / Cargar Cartera')

@push('head')
    @vite(['resources/css/importar_carteras.css', 'resources/js/importar_carteras.js'])
@endpush

@section('content')

    {{-- Tarjeta de Carga --}}
    <div class="import-card">
        {{-- Header --}}
        <div class="import-header">
            <div>
                <h2 class="import-title">Carga de Cartera (Master)</h2>
                <p class="import-desc">Actualiza la base de datos de clientes y deudas (Tabla: <code>carteras</code>).</p>
            </div>
            <a href="{{ route('integracion.carteras.template') }}" class="btn-template">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"/></svg>
                Bajar Plantilla
            </a>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('integracion.carteras.import') }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf
            
            <div class="upload-area">
                <input type="file" name="archivo" id="fileInput" class="file-input" accept=".csv" required>
                <div class="pointer-events-none">
                    <svg class="upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-slate-700 font-medium text-lg">Haz clic o arrastra tu archivo CSV</p>
                    <p class="text-xs text-slate-400 mt-2">Columnas clave: OPERACION, DOCUMENTO, SALDO_CAPITAL, etc.</p>
                </div>
            </div>

            {{-- Feedback Visual --}}
            <div id="fileFeedback" class="file-feedback">
                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span id="fileName">Archivo seleccionado</span>
            </div>

            <button type="submit" id="btnSubmit" class="btn-upload" disabled>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Procesar Carga de Cartera
            </button>
        </form>
    </div>

@endsection