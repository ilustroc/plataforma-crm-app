document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // Referencias globales
    // =========================
    const chkAll = document.getElementById('chkAll');
    const chks = document.querySelectorAll('.chkOp');
    const btnPropuesta = document.getElementById('btnPropuesta');
    const btnCna = document.getElementById('btnSolicitarCna');
    const countSpans = document.querySelectorAll('.selection-count');

    // =========================
    // Modal Propuesta
    // =========================
    const opsResumen = document.getElementById('opsResumen');
    const opsHidden = document.getElementById('opsHidden');

    const tipoPropuesta = document.getElementById('tipoPropuesta');

    // Convenio
    const boxConvenio = document.getElementById('boxConvenio');
    const cvTotal = document.getElementById('cvTotal');
    const cvNro = document.getElementById('cvNro');
    const cvFechaIni = document.getElementById('cvFechaIni');
    const cvMontoCuota = document.getElementById('cvMontoCuota');
    const cvGen = document.getElementById('cvGen');
    const tblCronoBody = document.getElementById('tblCronoBody');
    const cvSuma = document.getElementById('cvSuma');

    // Cancelación
    const boxCancelacion = document.getElementById('boxCancelacion');
    const cpMonto = document.getElementById('cpMonto');
    const cpFechaPago = document.getElementById('cpFechaPago');

    // =========================
    // Modal CNA
    // =========================
    const cnaOpsHidden = document.getElementById('cnaOpsHidden');

    // =========================
    // Estado
    // =========================
    let selectedOps = [];

    // =========================
    // Helpers selección
    // =========================
    function updateSelection() {
        selectedOps = Array.from(chks)
            .filter(c => c.checked)
            .map(c => c.value);

        const count = selectedOps.length;

        countSpans.forEach(span => {
            span.textContent = count;
        });

        if (count > 0) {
            if (btnPropuesta) {
                btnPropuesta.classList.remove('btn-disabled');
                btnPropuesta.disabled = false;
            }
            if (btnCna) {
                btnCna.classList.remove('btn-disabled');
                btnCna.disabled = false;
            }
        } else {
            if (btnPropuesta) {
                btnPropuesta.classList.add('btn-disabled');
                btnPropuesta.disabled = true;
            }
            if (btnCna) {
                btnCna.classList.add('btn-disabled');
                btnCna.disabled = true;
            }
        }

        if (chkAll) {
            chkAll.checked = (count === chks.length && count > 0);
            chkAll.indeterminate = (count > 0 && count < chks.length);
        }
    }

    function renderModalOpsPropuesta() {
        if (!opsResumen || !opsHidden) return;

        opsResumen.innerHTML = '';
        opsHidden.innerHTML = '';

        selectedOps.forEach(op => {
            const badge = document.createElement('span');
            badge.className =
                'text-[10px] bg-white border border-slate-200 px-2 py-1 rounded text-slate-600 font-mono font-bold';
            badge.textContent = op;
            opsResumen.appendChild(badge);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'operaciones[]';
            input.value = op;
            opsHidden.appendChild(input);
        });
    }

    function renderModalOpsCna() {
        if (!cnaOpsHidden) return;

        cnaOpsHidden.innerHTML = '';

        selectedOps.forEach(op => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'operaciones[]';
            input.value = op;
            cnaOpsHidden.appendChild(input);
        });
    }

    // =========================
    // Helpers propuesta
    // =========================
    function resetCronogramaTable() {
        if (!tblCronoBody) return;

        tblCronoBody.innerHTML = `
            <tr>
                <td colspan="3" class="proposal-empty-row">
                    Aún no se ha generado el cronograma.
                </td>
            </tr>
        `;
    }

    function resetProposalTotals() {
        if (cvSuma) cvSuma.textContent = '0.00';
        if (cvMontoCuota) cvMontoCuota.value = '';
    }

    function updateCuotaPreview() {
        if (!cvMontoCuota || !cvTotal || !cvNro) return;

        const total = parseFloat(cvTotal.value || '0');
        const cuotas = parseInt(cvNro.value || '0', 10);

        if (!total || !cuotas || cuotas <= 0) {
            cvMontoCuota.value = '';
            return;
        }

        const cuota = total / cuotas;
        cvMontoCuota.value = cuota.toFixed(2);
    }

    function toggleTipoPropuesta() {
        if (!tipoPropuesta || !boxConvenio || !boxCancelacion) return;

        const tipo = tipoPropuesta.value;

        if (tipo === 'convenio') {
            boxConvenio.classList.remove('hidden');
            boxCancelacion.classList.add('hidden');

            if (cvNro) cvNro.required = true;
            if (cvTotal) cvTotal.required = true;
            if (cvFechaIni) cvFechaIni.required = true;

            if (cpMonto) {
                cpMonto.required = false;
                cpMonto.value = '';
            }
            if (cpFechaPago) {
                cpFechaPago.required = false;
                cpFechaPago.value = '';
            }

            updateCuotaPreview();
        } else {
            boxConvenio.classList.add('hidden');
            boxCancelacion.classList.remove('hidden');

            if (cvNro) cvNro.required = false;
            if (cvTotal) cvTotal.required = false;
            if (cvFechaIni) cvFechaIni.required = false;

            if (cpMonto) cpMonto.required = true;
            if (cpFechaPago) cpFechaPago.required = true;

            if (cvMontoCuota) cvMontoCuota.value = '';
        }

        resetCronogramaTable();
        if (cvSuma) cvSuma.textContent = '0.00';
    }

    function generateCronograma() {
        if (!tblCronoBody || !cvTotal || !cvNro || !cvFechaIni) return;

        const total = parseFloat(cvTotal.value);
        const cuotas = parseInt(cvNro.value, 10);
        const fechaStr = cvFechaIni.value;

        if (!total || !cuotas || !fechaStr || cuotas <= 0) {
            resetCronogramaTable();
            resetProposalTotals();
            return;
        }

        tblCronoBody.innerHTML = '';

        const montoBase = Math.floor((total / cuotas) * 100) / 100;
        const diff = Math.round((total - (montoBase * cuotas)) * 100) / 100;
        const primerMonto = montoBase + diff;
        const currentDate = new Date(fechaStr + 'T00:00:00');

        let html = '';

        for (let i = 1; i <= cuotas; i++) {
            const monto = (i === 1) ? primerMonto : montoBase;

            const day = String(currentDate.getDate()).padStart(2, '0');
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const year = currentDate.getFullYear();
            const dateFmt = `${day}/${month}/${year}`;

            html += `
                <tr>
                    <td class="py-2 px-3 text-center border-b border-slate-100 text-slate-500">${i}</td>
                    <td class="py-2 px-3 text-center border-b border-slate-100 text-slate-700">${dateFmt}</td>
                    <td class="py-2 px-3 text-center border-b border-slate-100 font-mono font-medium">S/ ${monto.toFixed(2)}</td>
                </tr>
            `;

            currentDate.setMonth(currentDate.getMonth() + 1);
        }

        tblCronoBody.innerHTML = html;

        if (cvSuma) cvSuma.textContent = total.toFixed(2);
        if (cvMontoCuota) cvMontoCuota.value = (total / cuotas).toFixed(2);
    }

    // =========================
    // Helpers modal
    // =========================
    function openModal(targetId) {
        const modal = document.getElementById(targetId);
        if (!modal) return;

        if (targetId === 'modalPropuesta') {
            renderModalOpsPropuesta();
            resetCronogramaTable();
            resetProposalTotals();
            toggleTipoPropuesta();
        }

        if (targetId === 'modalCna') {
            renderModalOpsCna();
        }

        modal.classList.remove('hidden');

        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('show');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 180);
    }

    // =========================
    // Listeners selección
    // =========================
    if (chkAll) {
        chkAll.addEventListener('change', (e) => {
            const isChecked = e.target.checked;

            chks.forEach(c => {
                if (!c.disabled) c.checked = isChecked;
            });

            updateSelection();
        });
    }

    chks.forEach(c => {
        c.addEventListener('change', updateSelection);
    });

    // =========================
    // Listeners modales
    // =========================
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    modalTriggers.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    const closeBtns = document.querySelectorAll('[data-modal-close]');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(btn.closest('.modal-backdrop'));
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.show').forEach(modal => {
                closeModal(modal);
            });
        }
    });

    // =========================
    // Listeners propuesta
    // =========================
    if (tipoPropuesta) {
        tipoPropuesta.addEventListener('change', toggleTipoPropuesta);
    }

    if (cvTotal) {
        cvTotal.addEventListener('input', updateCuotaPreview);
    }

    if (cvNro) {
        cvNro.addEventListener('input', updateCuotaPreview);
    }

    if (cvGen) {
        cvGen.addEventListener('click', generateCronograma);
    }

    // =========================
    // Copiar DNI
    // =========================
    const btnCopy = document.querySelector('[data-copy]');
    if (btnCopy) {
        btnCopy.addEventListener('click', () => {
            const text = btnCopy.getAttribute('data-copy');

            navigator.clipboard.writeText(text).then(() => {
                const original = btnCopy.innerHTML;
                btnCopy.innerHTML = `<span class="text-brand text-xs font-bold">Copiado!</span>`;

                setTimeout(() => {
                    btnCopy.innerHTML = original;
                }, 1500);
            });
        });
    }

    // =========================
    // Estado inicial
    // =========================
    updateSelection();
    resetCronogramaTable();
    resetProposalTotals();
    toggleTipoPropuesta();
});