@extends('layouts.app')
@section('title','Administración')
@section('crumb','Administración')

@push('head')
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
@endpush

@section('content')

    {{-- KPIS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-100 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Supervisores</p>
                <p class="text-2xl font-bold text-slate-800">{{ $supervisores->count() }}</p>
            </div>
            <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Asesores Totales</p>
                <p class="text-2xl font-bold text-slate-800">{{ $supervisores->sum('asesores_count') }}</p>
            </div>
            <div class="h-10 w-10 bg-sky-50 text-sky-600 rounded-lg flex items-center justify-center">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- FORMULARIOS DE CREACIÓN --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Crear Supervisor --}}
        @include('admin.users.form-create', ['role' => 'supervisor', 'title' => 'Crear Supervisor', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'])
        
        {{-- Crear Asesor --}}
        @include('admin.users.form-create', ['role' => 'asesor', 'title' => 'Crear Asesor', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'])
    </div>

    {{-- TABLA ESTRUCTURA --}}
    <div class="admin-card">
        <div class="admin-card-header justify-between">
            <h3 class="admin-card-title flex items-center gap-2">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Estructura Organizacional
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Supervisor</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th class="text-center">Asesores</th>
                        <th>Detalle Equipo</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supervisores as $sup)
                        <tr class="group">
                            <td class="font-bold text-slate-700">{{ $sup->name }}</td>
                            <td class="font-mono text-xs">{{ $sup->email }}</td>
                            <td>
                                <span class="badge {{ $sup->is_active ? 'status-on' : 'status-off' }}">
                                    {{ $sup->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold">{{ $sup->asesores_count }}</span>
                            </td>
                            <td>
                                @if($sup->asesores->count())
                                    <div class="space-y-2">
                                        @foreach($sup->asesores as $asesor)
                                            <div class="flex items-center justify-between bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-2 w-2 rounded-full {{ $asesor->is_active ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                                                    <span class="text-xs font-medium">{{ $asesor->name }}</span>
                                                </div>
                                                
                                                <div class="flex gap-1">
                                                    {{-- Botón Reasignar --}}
                                                    <button class="btn-sm-action" data-modal-target="modal-reassign-{{ $asesor->id }}" title="Reasignar">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                    </button>
                                                    {{-- Botón Password --}}
                                                    <button class="btn-sm-action" data-modal-target="modal-pw-{{ $asesor->id }}" title="Contraseña">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 19l-1 1-1 1-2-2-2 2-1-1-1-1 2-2 1 1 2 2 1-1 4-4a6 6 0 010-8zm-6 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    </button>
                                                    {{-- Botón Toggle --}}
                                                    <form action="{{ route('admin.users.toggle', $asesor) }}" method="POST" onsubmit="return confirm('¿Cambiar estado?')">
                                                        @csrf @method('PATCH')
                                                        <button class="btn-sm-action {{ $asesor->is_active ? 'text-red-500 hover:text-red-700' : 'text-emerald-500 hover:text-emerald-700' }}">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                        </button>
                                                    </form>
                                                </div>

                                                {{-- INCLUDES PARA MODALES DE CADA ASESOR --}}
                                                @include('admin.users.form-edit', ['user' => $asesor, 'type' => 'password'])
                                                @include('admin.users.form-edit', ['user' => $asesor, 'type' => 'reassign', 'supervisores' => $listaSupervisores])
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Sin equipo asignado</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <button class="btn-sm-action" data-modal-target="modal-pw-{{ $sup->id }}">Pass</button>
                                    <form action="{{ route('admin.users.toggle', $sup) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="btn-sm-action">{{ $sup->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                </div>
                                @include('admin.users.form-edit', ['user' => $sup, 'type' => 'password'])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-slate-400">No hay supervisores registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection