@extends('layouts.app')
@section('title','Administración')
@section('crumb','Administración')

@push('head')
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
@endpush

@section('content')

    {{-- BARRA DE ACCIONES --}}
    <div class="admin-page-head">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Administración</h1>
            <p class="text-slate-500 text-sm mt-1">Gestión de usuarios y estructura organizacional.</p>
        </div>

        <div class="admin-actions">
            {{-- Botón Crear Supervisor --}}
            <button type="button" class="btn-secondary" data-modal-target="modal-create-supervisor">
                <svg class="h-5 w-5 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Supervisor</span>
            </button>

            {{-- Botón Crear Asesor --}}
            <button type="button" class="btn-primary" data-modal-target="modal-create-asesor">
                <svg class="h-5 w-5 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span>Asesor</span>
            </button>
        </div>
    </div>

    {{-- TABLA ESTRUCTURA --}}
    <div class="admin-card">
        <div class="admin-card-header justify-between">
            <h3 class="admin-card-title flex items-center gap-2">
                <div class="p-1.5 bg-slate-100 text-slate-500 rounded-lg">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
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
                    <tr class="group border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors">
                        <td class="font-bold text-slate-700">{{ $sup->name }}</td>
                        <td class="font-mono text-xs text-slate-500">{{ $sup->email }}</td>

                        <td>
                            <span class="badge {{ $sup->is_active ? 'status-on' : 'status-off' }}">
                                {{ $sup->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs font-bold">
                                {{ $sup->asesores_count }}
                            </span>
                        </td>

                        <td class="py-4">
                            @if($sup->asesores->count())
                                <div class="team-list">
                                    @foreach($sup->asesores as $asesor)
                                        <div class="team-item">
                                            <div class="flex items-center gap-2.5">
                                                {{-- Avatar con iniciales --}}
                                                <div class="relative">
                                                    <div class="avatar-initials">
                                                        {{ substr($asesor->name, 0, 2) }}
                                                    </div>
                                                    <div class="status-dot {{ $asesor->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                                                </div>

                                                <div>
                                                    <div class="text-xs font-bold text-slate-700">{{ $asesor->name }}</div>
                                                    <div class="text-[10px] text-slate-400">{{ $asesor->email }}</div>
                                                </div>
                                            </div>

                                            <div class="flex gap-1">
                                                {{-- Reasignar --}}
                                                <button type="button" class="btn-sm-action" data-modal-target="modal-reassign-{{ $asesor->id }}" title="Reasignar">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                    </svg>
                                                </button>

                                                {{-- Password --}}
                                                <button type="button" class="btn-sm-action" data-modal-target="modal-pw-{{ $asesor->id }}" title="Contraseña">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 19l-1 1-1 1-2-2-2 2-1-1-1-1 2-2 1 1 2 2 1-1 4-4a6 6 0 010-8zm-6 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                </button>

                                                {{-- Toggle --}}
                                                <form action="{{ route('admin.users.toggle', $asesor) }}" method="POST" onsubmit="return confirm('¿Cambiar estado?')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-sm-action {{ $asesor->is_active ? 'text-red-500 hover:bg-red-50 hover:border-red-200' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200' }}">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>

                                            @include('admin.users.form-edit', ['user' => $asesor, 'type' => 'password'])
                                            @include('admin.users.form-edit', ['user' => $asesor, 'type' => 'reassign', 'supervisores' => $listaSupervisores])
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 border-2 border-dashed border-slate-100 rounded-lg text-center">
                                    <span class="text-xs text-slate-400 font-medium">Sin equipo asignado</span>
                                </div>
                            @endif
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <button type="button" class="btn-sm-action" data-modal-target="modal-pw-{{ $sup->id }}">Pass</button>

                                <form action="{{ route('admin.users.toggle', $sup) }}" method="POST" onsubmit="return confirm('¿Cambiar estado?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-sm-action {{ $sup->is_active ? 'text-slate-600' : 'text-emerald-600 font-bold' }}">
                                        {{ $sup->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>

                            @include('admin.users.form-edit', ['user' => $sup, 'type' => 'password'])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center text-slate-400 italic">
                            No hay supervisores registrados en el sistema.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODALES --}}
    @include('admin.users.form-create', ['role' => 'supervisor', 'title' => 'Crear Supervisor'])
    @include('admin.users.form-create', ['role' => 'asesor', 'title' => 'Crear Asesor'])

@endsection
