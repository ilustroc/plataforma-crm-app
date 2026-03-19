<div id="modalCna" class="modal-backdrop">
    <div class="modal-box max-w-md">
        <form method="POST" action="{{ route('clientes.cna.store', $dni) }}">
            @csrf

            <div class="modal-header">
                <h3 class="font-bold text-lg text-slate-800">Solicitar CNA</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="modal-body space-y-5">
                <div class="p-4 bg-sky-50 text-sky-800 rounded-xl text-sm border border-sky-100 flex items-start gap-3">
                    <svg class="h-5 w-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        Se generará una solicitud para las <span class="font-bold selection-count">0</span> operaciones seleccionadas.
                    </div>
                </div>

                <div id="cnaOpsHidden"></div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Monto Pagado (S/)</label>
                        <input type="number" name="monto_pagado" step="0.01" class="w-full rounded-xl border-slate-200 py-2.5 focus:border-brand focus:ring-brand font-medium" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha de Pago</label>
                        <input type="date" name="fecha_pago_realizado" class="w-full rounded-xl border-slate-200 py-2.5 focus:border-brand focus:ring-brand text-slate-600" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-action btn-outline border-slate-200 text-slate-500" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-action btn-success shadow-lg shadow-emerald-500/20">Enviar Solicitud</button>
            </div>
        </form>
    </div>
</div>