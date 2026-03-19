<div class="section-card h-full">
    <div class="section-header py-4">
        <h2 class="section-title text-base">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pagos Registrados
        </h2>
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
                @forelse($pagos as $p)
                    <tr>
                        <td class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                        <td class="text-xs font-mono text-slate-400">{{ $p->operacion }}</td>
                        <td class="text-right font-bold text-emerald-600 text-xs">S/ {{ number_format((float) $p->monto, 2) }}</td>
                        <td class="text-[10px] text-slate-400 uppercase truncate max-w-[80px]">{{ $p->gestor }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-6 text-slate-400 italic text-xs">
                            No hay pagos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>