document.addEventListener('DOMContentLoaded', () => {
    
    // --- Referencias Globales ---
    const chkAll = document.getElementById('chkAll');
    const chks = document.querySelectorAll('.chkOp');
    const btnPropuesta = document.getElementById('btnPropuesta');
    const btnCna = document.getElementById('btnSolicitarCna');
    const countSpans = document.querySelectorAll('.selection-count');
    
    // --- Referencias Modal Propuesta ---
    const opsResumen = document.getElementById('opsResumen');
    const opsHidden = document.getElementById('opsHidden');
    const cvTotal = document.getElementById('cvTotal');
    const cvNro = document.getElementById('cvNro');
    const cvFechaIni = document.getElementById('cvFechaIni');
    const cvGen = document.getElementById('cvGen');
    const tblCronoBody = document.getElementById('tblCronoBody');
    const cvSuma = document.getElementById('cvSuma');

    // --- Referencias Modal CNA [NUEVO] ---
    const cnaOpsHidden = document.getElementById('cnaOpsHidden');

    // --- Estado ---
    let selectedOps = [];

    // --- Funciones ---

    function updateSelection() {
        selectedOps = Array.from(chks)
            .filter(c => c.checked)
            .map(c => c.value);

        const count = selectedOps.length;
        
        // Actualizar contadores visuales
        countSpans.forEach(span => span.textContent = count);

        // Habilitar/Deshabilitar botones
        if (count > 0) {
            if(btnPropuesta) { btnPropuesta.classList.remove('btn-disabled'); btnPropuesta.disabled = false; }
            if(btnCna) { btnCna.classList.remove('btn-disabled'); btnCna.disabled = false; }
        } else {
            if(btnPropuesta) { btnPropuesta.classList.add('btn-disabled'); btnPropuesta.disabled = true; }
            if(btnCna) { btnCna.classList.add('btn-disabled'); btnCna.disabled = true; }
        }

        // Actualizar checkbox maestro
        if(chkAll) {
            chkAll.checked = (count === chks.length && count > 0);
            chkAll.indeterminate = (count > 0 && count < chks.length);
        }
    }

    // Renderiza inputs ocultos para el Modal de PROPUESTA
    function renderModalOpsPropuesta() {
        if(!opsResumen || !opsHidden) return;
        
        opsResumen.innerHTML = '';
        opsHidden.innerHTML = '';

        selectedOps.forEach(op => {
            // Badge visual
            const badge = document.createElement('span');
            badge.className = 'text-[10px] bg-white border border-slate-200 px-2 py-1 rounded text-slate-600 font-mono font-bold';
            badge.textContent = op;
            opsResumen.appendChild(badge);

            // Input hidden
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'operaciones[]';
            input.value = op;
            opsHidden.appendChild(input);
        });
    }

    // Renderiza inputs ocultos para el Modal de CNA [NUEVO]
    function renderModalOpsCna() {
        if(!cnaOpsHidden) return;
        
        cnaOpsHidden.innerHTML = ''; // Limpiar anteriores

        selectedOps.forEach(op => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'operaciones[]'; // Mismo nombre que espera el controlador
            input.value = op;
            cnaOpsHidden.appendChild(input);
        });
    }

    function generateCronograma() {
        if(!tblCronoBody) return;
        tblCronoBody.innerHTML = '';
        
        const total = parseFloat(cvTotal.value);
        const cuotas = parseInt(cvNro.value);
        const fechaStr = cvFechaIni.value;

        if (!total || !cuotas || !fechaStr) return;

        let montoBase = Math.floor((total / cuotas) * 100) / 100;
        let diff =  Math.round((total - (montoBase * cuotas)) * 100) / 100;
        let primerMonto = montoBase + diff;
        let currentDate = new Date(fechaStr + 'T00:00:00');

        let html = '';
        for (let i = 1; i <= cuotas; i++) {
            let monto = (i === 1) ? primerMonto : montoBase;
            
            let day = String(currentDate.getDate()).padStart(2, '0');
            let month = String(currentDate.getMonth() + 1).padStart(2, '0');
            let year = currentDate.getFullYear();
            let dateFmt = `${day}/${month}/${year}`;

            html += `
                <tr>
                    <td class="py-2 px-3 text-center border-b border-slate-100 text-slate-500">${i}</td>
                    <td class="py-2 px-3 text-left border-b border-slate-100 text-slate-700">${dateFmt}</td>
                    <td class="py-2 px-3 text-right border-b border-slate-100 font-mono font-medium">S/ ${monto.toFixed(2)}</td>
                </tr>
            `;
            currentDate.setMonth(currentDate.getMonth() + 1);
        }

        tblCronoBody.innerHTML = html;
        if(cvSuma) cvSuma.textContent = total.toFixed(2);
    }

    // --- Event Listeners ---

    if(chkAll) {
        chkAll.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            chks.forEach(c => {
                if(!c.disabled) c.checked = isChecked;
            });
            updateSelection();
        });
    }

    chks.forEach(c => {
        c.addEventListener('change', updateSelection);
    });

    // Lógica para abrir Modales
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    modalTriggers.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            
            if(modal) {
                // Si abrimos Propuesta -> Llenar datos de propuesta
                if (targetId === 'modalPropuesta') {
                    renderModalOpsPropuesta();
                    if(tblCronoBody) tblCronoBody.innerHTML = '';
                    if(cvSuma) cvSuma.textContent = '0.00';
                }
                
                // Si abrimos CNA -> Llenar datos de CNA [ESTO FALTABA]
                if (targetId === 'modalCna') {
                    renderModalOpsCna();
                }

                modal.classList.add('show');
            }
        });
    });

    // Botones Cerrar Modal
    const closeBtns = document.querySelectorAll('[data-modal-close]');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if(modal) modal.classList.remove('show');
        });
    });

    if(cvGen) {
        cvGen.addEventListener('click', generateCronograma);
    }

    // Copiar DNI
    const btnCopy = document.querySelector('[data-copy]');
    if(btnCopy){
        btnCopy.addEventListener('click', () => {
            const text = btnCopy.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(() => {
                const original = btnCopy.innerHTML;
                btnCopy.innerHTML = `<span class="text-brand text-xs font-bold">Copiado!</span>`;
                setTimeout(() => btnCopy.innerHTML = original, 1500);
            });
        });
    }
});