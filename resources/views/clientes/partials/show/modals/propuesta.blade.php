<div id="modalPropuesta" class="modal-backdrop">
    <div class="modal-box">
        <form method="POST" action="{{ route('clientes.promesas.store', $dni) }}" id="formPropuesta">
            @csrf

            <div class="modal-header">
                <h3 class="font-bold text-lg text-slate-800">Nueva Propuesta de Pago</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" data-modal-close>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="modal-body space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Operaciones Seleccionadas</label>
                    <div id="opsResumen" class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200 min-h-[40px]"></div>
                    <div id="opsHidden"></div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="form-group">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipo de Acuerdo</label>
                        <select name="tipo" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand">
                            <option value="convenio">Convenio (Fraccionado)</option>
                            <option value="cancelacion">Cancelación (Pago Único)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Monto Total (S/)</label>
                        <input type="number" id="cvTotal" name="monto" step="0.01" class="w-full rounded-xl border-slate-200 text-sm py-2.5 focus:border-brand focus:ring-brand font-bold text-slate-700" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nota / Observación</label>
                    <textarea name="nota" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:border-brand focus:ring-brand" placeholder="Detalles del acuerdo..."></textarea>
                </div>

                <div class="bg-slate-50/80 p-5 rounded-xl border border-slate-200/60">
                    <div class="flex items-end gap-3 mb-4">
                        <div class="w-20">
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Cuotas</label>
                            <input type="number" id="cvNro" name="nro_cuotas" value="1" min="1" class="w-full rounded-lg border-slate-200 text-sm py-1.5 text-center">
                        </div>

                        <div class="flex-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Fecha Inicio</label>
                            <input type="date" id="cvFechaIni" name="fecha_pago" class="w-full rounded-lg border-slate-200 text-sm py-1.5" required>
                        </div>

                        <button type="button" id="cvGen" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                            Generar
                        </button>
                    </div>

                    <div class="max-h-[150px] overflow-y-auto border border-slate-200 rounded-lg bg-white shadow-inner">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase sticky top-0">
                                <tr>
                                    <th class="py-2 px-3 text-center border-b border-slate-100">#</th>
                                    <th class="py-2 px-3 text-left border-b border-slate-100">Fecha</th>
                                    <th class="py-2 px-3 text-right border-b border-slate-100">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="tblCronoBody"></tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center mt-3 px-1">
                        <span class="text-xs text-slate-400">Total calculado:</span>
                        <span class="text-sm font-bold text-slate-800">S/ <span id="cvSuma">0.00</span></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-action btn-outline border-slate-200 text-slate-500" data-modal-close>Cancelar</button>
                <button type="submit" class="btn-action btn-primary shadow-lg shadow-brand/20">Guardar Propuesta</button>
            </div>
        </form>
    </div>
</div>