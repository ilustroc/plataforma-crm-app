@extends('layouts.app')
@section('title','Autorización de Promesas')
@section('crumb','Autorización')

@push('head')
    @vite(['resources/css/autorizacion.css', 'resources/js/autorizacion.js'])
@endpush

@section('content')

{{-- Inicializamos Alpine aquí --}}
<div x-data="autorizacion" class="max-w-7xl mx-auto">

    {{-- HEADER & BUSCADOR --}}
    <div class="auth-card">
        <div class="auth-header">
            <div>
                <h1 class="auth-title">
                    <svg class="h-6 w-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Bandeja de Autorización
                </h1>
            </div>

            <form method="GET" action="{{ route('autorizacion') }}" class="flex gap-2 w-full sm:w-auto">
                <input type="text" name="q" value="{{ $q }}" class="form-input w-64" placeholder="Buscar DNI, Operación...">
                <button class="btn-action btn-primary px-4 shadow-lg shadow-brand/20">Buscar</button>
                @if($q)
                    <a href="{{ route('autorizacion') }}" class="btn-action btn-outline">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    {{-- TABLA PROMESAS --}}
    <div class="auth-card">
        <div class="table-container">
            <table class="auth-table">
                <thead>
                    <tr>
                        <th class="text-center w-24">DNI</th>
                        <th class="text-center w-32">Operación</th>
                        <th class="text-center w-28">Fecha</th>
                        <th class="text-right w-32">Monto</th>
                        <th>Detalle / Nota</th>
                        <th class="text-right w-48">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $p)
                        @php
                            $monto = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
                            // Preparamos el JSON para Alpine
                            $jsonData = json_encode([
                                'dni'     => $p->dni,
                                'nombre'  => $p->titular_txt ?? '—',
                                'tipo'    => $p->tipo,
                                'fecha'   => optional($p->fecha_promesa)->format('d/m/Y'),
                                'monto'   => $monto,
                                'cuentas' => $p->cuentas_json,
                                'nota'    => $p->nota,
                                'crono'   => $p->cuotas_json ?? []
                            ]);
                        @endphp
                        <tr class="group hover:bg-slate-50/50">
                            <td class="text-center font-mono text-xs text-slate-600">{{ $p->dni }}</td>
                            <td class="text-center font-mono text-xs text-slate-500">{{ Str::limit($p->operacion_txt, 15) }}</td>
                            <td class="text-center text-xs text-slate-500">{{ optional($p->fecha_promesa)->format('d/m/Y') }}</td>
                            <td class="text-right font-bold text-slate-700 text-xs">S/ {{ number_format($monto, 2) }}</td>
                            <td class="text-xs text-slate-500 truncate max-w-[200px]" title="{{ $p->nota }}">{{ $p->nota }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    {{-- Botón Ver Ficha (Ojo) --}}
                                    <button @click="openFicha({{ $jsonData }})" class="btn-action btn-outline h-7 px-2" title="Ver Detalle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>

                                    @if($isSupervisor)
                                        {{-- Botones Supervisor --}}
                                        <button @click="openNota('{{ route('autorizacion.preaprobar', $p) }}', 'Pre-aprobar Promesa')" class="btn-action btn-primary h-7 px-2 bg-emerald-600 hover:bg-emerald-700 border-transparent text-white" title="Pre-aprobar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button @click="openRechazo('{{ route('autorizacion.rechazar.sup', $p) }}')" class="btn-action btn-danger-outline h-7 px-2 text-rose-600 border-rose-200 hover:bg-rose-50" title="Rechazar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @else
                                        {{-- Botones Admin --}}
                                        <button @click="openNota('{{ route('autorizacion.aprobar', $p) }}', 'Aprobar Promesa')" class="btn-action btn-primary h-7 px-2 bg-brand hover:bg-brand/90 border-transparent text-white" title="Aprobar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button @click="openRechazo('{{ route('autorizacion.rechazar.admin', $p) }}')" class="btn-action btn-danger-outline h-7 px-2 text-rose-600 border-rose-200 hover:bg-rose-50" title="Rechazar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-12 text-slate-400 italic">No hay promesas pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL FICHA DETALLE --}}
    <div x-show="modalFichaOpen" x-cloak class="modal-backdrop">
        <div class="modal-box" @click.away="modalFichaOpen = false">
            <div class="modal-header">
                <h3 class="font-bold text-lg text-slate-800">Detalle de Propuesta</h3>
                <button @click="modalFichaOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="modal-body space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Cliente</span> <span class="font-bold text-slate-700" x-text="ficha.nombre"></span></div>
                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">DNI</span> <span class="font-mono text-slate-600" x-text="ficha.dni"></span></div>
                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Tipo</span> <span class="uppercase badge badge-info" x-text="ficha.tipo"></span></div>
                    <div><span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Monto</span> <span class="font-bold text-brand" x-text="fmtMoney(ficha.monto)"></span></div>
                </div>

                <div class="bg-white p-3 rounded-xl text-sm text-slate-600 border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Nota del Asesor</span>
                    <p x-text="ficha.nota || 'Sin nota adjunta.'"></p>
                </div>

                {{-- Tabla Cuentas --}}
                <div x-show="ficha.cuentas && ficha.cuentas.length > 0">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Cuentas Involucradas</h4>
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                <tr><th class="px-4 py-2.5">Operación</th><th class="px-4 py-2.5">Entidad / Producto</th><th class="px-4 py-2.5 text-right">Deuda Total</th></tr>
                            </thead>
                            <tbody>
                                <template x-for="c in ficha.cuentas" :key="c.operacion">
                                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                                        <td class="px-4 py-2 font-mono text-slate-600 font-medium" x-text="c.operacion"></td>
                                        <td class="px-4 py-2 text-slate-600">
                                            <span x-text="c.entidad"></span> - <span class="text-slate-400" x-text="c.producto"></span>
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-slate-700" x-text="fmtMoney(c.deuda_total)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Cronograma --}}
                <div x-show="ficha.crono && ficha.crono.length > 0">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-2 mt-4">Cronograma de Pagos</h4>
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm max-h-40 overflow-y-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 sticky top-0">
                                <tr><th class="px-4 py-2.5 text-center">Cuota</th><th class="px-4 py-2.5">Fecha</th><th class="px-4 py-2.5 text-right">Importe</th></tr>
                            </thead>
                            <tbody>
                                <template x-for="row in ficha.crono" :key="row.nro">
                                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                                        <td class="px-4 py-2 text-center text-slate-400 font-mono" x-text="row.nro"></td>
                                        <td class="px-4 py-2 text-slate-600" x-text="formatDate(row.fecha)"></td>
                                        <td class="px-4 py-2 text-right font-mono font-medium text-slate-700" x-text="fmtMoney(row.monto)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="modalFichaOpen = false" class="btn-action btn-outline w-full sm:w-auto">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- MODAL APROBACIÓN (Nota) --}}
    <div x-show="modalNotaOpen" x-cloak class="modal-backdrop">
        <div class="modal-box max-w-md" @click.away="modalNotaOpen = false">
            <form :action="actionUrl" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="font-bold text-lg text-slate-800" x-text="actionTitle"></h3>
                    <button type="button" @click="modalNotaOpen = false" class="text-slate-400">✕</button>
                </div>
                <div class="modal-body">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nota (Opcional)</label>
                    <textarea name="nota_estado" id="txtNota" rows="3" class="form-input" placeholder="Comentarios..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="modalNotaOpen = false" class="btn-action btn-outline">Cancelar</button>
                    <button type="submit" class="btn-action btn-primary bg-emerald-600 hover:bg-emerald-700 border-transparent text-white">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL RECHAZO --}}
    <div x-show="modalRechazoOpen" x-cloak class="modal-backdrop">
        <div class="modal-box max-w-md" @click.away="modalRechazoOpen = false">
            <form :action="actionUrl" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="font-bold text-lg text-red-600">Rechazar Solicitud</h3>
                    <button type="button" @click="modalRechazoOpen = false" class="text-slate-400">✕</button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-red-50 text-red-700 text-xs rounded-xl border border-red-100 mb-3">
                        Indica el motivo del rechazo.
                    </div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Motivo</label>
                    <textarea name="nota_estado" id="txtRechazo" rows="3" class="form-input border-red-200 focus:border-red-500 bg-red-50/30" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="modalRechazoOpen = false" class="btn-action btn-outline">Cancelar</button>
                    <button type="submit" class="btn-action btn-primary bg-red-600 hover:bg-red-700 border-transparent text-white">Rechazar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection