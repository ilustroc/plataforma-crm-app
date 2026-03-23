<div class="section-card">
    <div class="section-header">
        <div>
            <h2 class="section-title">
                <div class="p-1.5 rounded-lg bg-brand/5 text-brand">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                Cartera de Cuentas
            </h2>
            <p class="text-xs text-slate-400 mt-1 pl-9">Selecciona operaciones para gestionar acuerdos.</p>
        </div>

        <div class="flex gap-3">
            <button class="btn-action btn-primary btn-disabled transition-all" id="btnPropuesta" data-modal-target="modalPropuesta" disabled>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Propuesta
                <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold selection-count">0</span>
            </button>

            <button class="btn-action btn-outline btn-disabled transition-all" id="btnSolicitarCna" data-modal-target="modalCna" disabled>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                CNA
                <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] min-w-[20px] text-center font-bold text-slate-500 selection-count">0</span>
            </button>
        </div>
    </div>

    <div class="table-container max-h-[500px] custom-scroll">
        <table class="table-compact">
            <thead>
                <tr>
                    <th class="w-8 text-center">
                        <input type="checkbox" id="chkAll" class="checkbox-brand">
                    </th>
                    <th>Entidad / Cosecha</th>
                    <th>Producto</th>
                    <th>Operación</th>
                    <th>Fecha Castigo</th>
                    <th class="text-right">Capital</th>
                    <th class="text-right">Intereses</th>
                    <th class="text-right">Total Deuda</th>
                    <th class="w-10"></th>
                </tr>
            </thead>

            <tbody>
                @foreach($cuentas as $c)
                    <tr class="group hover:bg-slate-50/70 transition-colors">
                        <td class="text-center">
                            <input type="checkbox" class="chkOp checkbox-brand" value="{{ $c->operacion }}">
                        </td>

                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700 text-xs">{{ $c->entidad }}</span>
                                <span class="font-mono text-xs font-medium text-slate-600 select-all">{{ $c->cosecha }}</span>
                            </div>
                        </td>

                        <td class="font-mono text-xs font-medium text-slate-600 select-all">
                            {{ $c->producto }}
                        </td>

                        <td class="font-mono text-xs font-medium text-slate-600 select-all">
                            {{ $c->operacion }}
                        </td>

                        <td>
                            <div class="flex flex-col text-[11px] text-slate-500">
                                <span>{{ optional($c->fecha_castigo)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                        </td>

                        <td class="text-right font-mono text-xs text-slate-600">
                            {{ number_format((float) $c->saldo_capital, 2) }}
                        </td>

                        <td class="text-right font-mono text-xs text-slate-600">
                            {{ number_format((float) $c->intereses, 2) }}
                        </td>

                        <td class="text-right font-bold font-mono text-xs text-rose-600">
                            {{ number_format((float) $c->deuda_total, 2) }}
                        </td>

                        <td class="text-center"> <div class="tooltip-wrap">
                                <button type="button" class="tooltip-btn">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>

                                <div class="info-tooltip">
                                    <p class="info-tooltip-title">Ubicación</p>
                                    <p class="break-words text-white">
                                        {{ $c->direccion ?: 'Sin dirección registrada' }}
                                    </p>
                                    <p class="mt-2 text-slate-400">
                                        {{ $c->distrito ?: '-' }} - {{ $c->provincia ?: '-' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>