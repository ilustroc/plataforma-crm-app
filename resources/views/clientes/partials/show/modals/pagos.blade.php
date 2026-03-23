<div id="modalPagos" class="modal-backdrop">
    <div class="modal-box max-w-3xl">
        <div class="modal-header py-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Todos los pagos del cliente</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Total:
                    <span class="font-semibold text-slate-700">{{ $pagos->count() }}</span>
                    · Recuperado:
                    <span class="font-semibold text-emerald-700">S/ {{ number_format((float) $pagos->sum('monto'), 2) }}</span>
                </p>
            </div>

            <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="modal-body p-0">
            <div class="table-container max-h-[420px] custom-scroll">
                <table class="table-compact">
                    <thead class="bg-white">
                        <tr>
                            <th>Fecha</th>
                            <th>Operación</th>
                            <th>Moneda</th>
                            <th class="text-right">Monto</th>
                            <th>Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $p)
                            <tr>
                                <td class="text-sm font-medium text-slate-700">
                                    {{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}
                                </td>

                                <td class="text-sm font-mono font-semibold text-slate-600">
                                    {{ $p->operacion }}
                                </td>

                                <td class="text-xs font-medium text-slate-500 uppercase">
                                    {{ $p->moneda ?? 'PEN' }}
                                </td>

                                <td class="text-right">
                                    <span class="inline-flex items-center rounded-lg px-2 py-1 text-sm font-bold text-emerald-700">
                                        S/ {{ number_format((float) $p->monto, 2) }}
                                    </span>
                                </td>

                                <td class="max-w-[130px] truncate text-xs font-medium uppercase tracking-wide text-slate-500">
                                    {{ $p->gestor }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm italic text-slate-400">
                                    No hay pagos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer py-3">
            <button type="button" class="btn-action btn-outline px-4 py-2" data-modal-close>
                Cerrar
            </button>
        </div>
    </div>
</div>