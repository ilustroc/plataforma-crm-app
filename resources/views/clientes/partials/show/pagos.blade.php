<div class="section-card h-full">
    <div class="section-header py-4">
        <div class="flex items-center justify-between w-full gap-4">
            <h2 class="section-title text-base">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                Pagos Registrados
            </h2>

            <button
                type="button"
                class="btn-action btn-outline"
                data-modal-target="modalPagos"
            >
                Ver todos ({{ $pagos->count() }})
            </button>
        </div>
    </div>

    <div class="table-container max-h-[300px] custom-scroll">
        <table class="table-compact">
            <thead class="bg-white">
                <tr>
                    <th>Fecha</th>
                    <th>Operación</th>
                    <th class="text-right">Monto</th>
                    <th>Gestor</th>
                </tr>
            </thead>

            <tbody>
                @forelse($pagos->take(6) as $p)
                    <tr>
                        <td class="text-sm font-medium text-slate-700">
                            {{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}
                        </td>

                        <td class="text-sm font-mono font-semibold text-slate-600">
                            {{ $p->operacion }}
                        </td>

                        <td class="text-right">
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-sm font-bold text-emerald-700">
                                S/ {{ number_format((float) $p->monto, 2) }}
                            </span>
                        </td>

                        <td class="max-w-[140px] truncate text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ $p->gestor }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-6 text-slate-400 italic text-sm">
                            No hay pagos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>