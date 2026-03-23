<div id="modalPropuesta" class="modal-backdrop hidden">
    <div class="modal-box max-w-3xl">
        <form
            method="POST"
            action="{{ route('clientes.promesas.store', $dni) }}"
            id="formPropuesta"
            class="flex h-full flex-col"
        >
            @csrf

            <div class="modal-header proposal-header-simple">
                <div class="proposal-header-left">
                    <div class="proposal-header-icon-simple">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <div>
                        <h3 class="proposal-title-simple">Nueva Propuesta de Pago</h3>
                        <p class="proposal-subtitle-simple">
                            Completa los datos y genera el cronograma cuando corresponda.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-close-btn" data-modal-close>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="modal-body proposal-body-simple flex-1 overflow-y-auto space-y-4">
                {{-- Operaciones --}}
                <section class="proposal-block">
                    <div class="proposal-block-head">
                        <div>
                            <h4 class="proposal-block-title">Operaciones seleccionadas</h4>
                            <p class="proposal-block-subtitle">Estas operaciones formarán parte de la propuesta.</p>
                        </div>

                        <span class="proposal-badge-count">
                            <span class="selection-count">0</span> seleccionadas
                        </span>
                    </div>

                    <div id="opsResumen" class="proposal-ops-list-simple"></div>
                    <div id="opsHidden"></div>
                </section>

                {{-- Datos generales --}}
                <section class="proposal-block">
                    <div class="proposal-block-head">
                        <div>
                            <h4 class="proposal-block-title">Datos del acuerdo</h4>
                            <p class="proposal-block-subtitle">Selecciona el tipo y completa la información necesaria.</p>
                        </div>
                    </div>

                    <div class="proposal-form-grid">
                        <div class="field">
                            <label class="field-label">Tipo de acuerdo</label>
                            <select name="tipo" class="field-input" id="tipoPropuesta">
                                <option value="convenio">Convenio (Fraccionado)</option>
                                <option value="cancelacion">Cancelación (Pago Único)</option>
                            </select>
                        </div>

                        <div class="field proposal-col-full">
                            <label class="field-label">Nota / observación</label>
                            <textarea
                                name="nota"
                                rows="4"
                                class="field-input field-textarea resize-y min-h-[110px] max-h-[260px]"
                                placeholder="Agrega detalles del acuerdo..."
                            ></textarea>
                        </div>
                    </div>
                </section>

                {{-- CONVENIO --}}
                <section id="boxConvenio" class="proposal-block proposal-block-soft">
                    <div class="proposal-block-head">
                        <div>
                            <h4 class="proposal-block-title">Datos del convenio</h4>
                            <p class="proposal-block-subtitle">Completa cuotas, monto total y fecha inicial.</p>
                        </div>

                        <div class="proposal-total-inline">
                            Total: <span>S/ <span id="cvSuma">0.00</span></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="field">
                            <label class="field-label">Nro cuotas</label>
                            <input
                                type="number"
                                id="cvNro"
                                name="nro_cuotas"
                                value="1"
                                min="1"
                                class="field-input text-center"
                            >
                        </div>

                        <div class="field">
                            <label class="field-label">Monto convenio (S/)</label>
                            <input
                                type="number"
                                id="cvTotal"
                                name="monto_convenio"
                                step="0.01"
                                class="field-input field-input-strong"
                                placeholder="0.00"
                            >
                        </div>

                        <div class="field">
                            <label class="field-label">Monto de cuota (S/)</label>
                            <input
                                type="text"
                                id="cvMontoCuota"
                                class="field-input bg-slate-50"
                                placeholder="0.00"
                                readonly
                            >
                        </div>

                        <div class="field">
                            <label class="field-label">Fecha inicial</label>
                            <input
                                type="date"
                                id="cvFechaIni"
                                name="fecha_pago"
                                class="field-input"
                            >
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-start">
                        <button type="button" id="cvGen" class="btn-action btn-outline">
                            Generar cronograma
                        </button>
                    </div>

                    <div class="proposal-table-wrap-simple mt-4">
                        <table class="proposal-table-simple">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="tblCronoBody">
                                <tr>
                                    <td colspan="3" class="proposal-empty-row">
                                        Aún no se ha generado el cronograma.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- CANCELACIÓN --}}
                <section id="boxCancelacion" class="proposal-block hidden">
                    <div class="proposal-block-head">
                        <div>
                            <h4 class="proposal-block-title">Datos de cancelación</h4>
                            <p class="proposal-block-subtitle">Registra el monto y fecha a cancelar para cerrar el acuerdo.</p>
                        </div>
                    </div>

                    <div class="proposal-form-grid">
                        <div class="field">
                            <label class="field-label">Monto total (S/)</label>
                            <input
                                type="number"
                                id="cpMonto"
                                name="monto"
                                step="0.01"
                                class="field-input field-input-strong"
                                placeholder="0.00"
                            >
                        </div>

                        <div class="field">
                            <label class="field-label">Fecha de pago</label>
                            <input
                                type="date"
                                id="cpFechaPago"
                                name="fecha_pago_cancel"
                                class="field-input"
                            >
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer proposal-footer-simple shrink-0">
                <button type="button" class="btn-action btn-outline" data-modal-close>
                    Cancelar
                </button>
                <button type="submit" class="btn-action btn-primary shadow-lg shadow-brand/20">
                    Guardar propuesta
                </button>
            </div>
        </form>
    </div>
</div>