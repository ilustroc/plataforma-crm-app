<div class="section-card h-full">
    <div class="section-header py-4">
        <h2 class="section-title text-base">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Historial de Acuerdos
        </h2>
    </div>

    <div class="table-container max-h-[300px] custom-scroll">
        <table class="table-compact">
            <thead class="bg-white">
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th class="text-right">Monto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($promesas as $pp)
                    @php
                        $estado = strtolower($pp->workflow_estado ?? 'pendiente');
                        $badge = match(true) {
                            str_contains($estado, 'aprob') => 'badge-success',
                            str_contains($estado, 'rechaz') => 'badge-danger',
                            default => 'badge-warning'
                        };
                    @endphp

                    <tr>
                        <td class="text-xs text-slate-500">{{ $pp->created_at->format('d/m/y') }}</td>
                        <td class="text-[10px] font-bold uppercase text-slate-400">{{ $pp->tipo }}</td>
                        <td class="text-right font-bold text-slate-700 text-xs">S/ {{ number_format((float) $pp->monto, 2) }}</td>
                        <td>
                            <span class="badge {{ $badge }} scale-90 origin-left">
                                {{ ucfirst($estado) }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if($estado === 'aprobada')
                                <a href="{{ route('promesas.acuerdo', $pp->id) }}" target="_blank" class="text-brand hover:underline text-[10px]">
                                    PDF
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-slate-400 italic text-xs">
                            Sin historial reciente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>