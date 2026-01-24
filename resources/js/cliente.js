document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. MODALES VANILLA (Abrir/Cerrar) ---
    const modals = document.querySelectorAll('.modal-backdrop');
    
    function openModal(id) {
        const m = document.getElementById(id);
        if(m) {
            m.classList.add('show');
            document.body.style.overflow = 'hidden'; // Bloquear scroll body
        }
    }
    
    function closeModal(id) {
        const m = document.getElementById(id);
        if(m) {
            m.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // Botones que abren modales
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modalTarget));
    });

    // Botones cerrar y click fuera
    modals.forEach(m => {
        const closeBtns = m.querySelectorAll('[data-modal-close]');
        closeBtns.forEach(b => b.addEventListener('click', () => closeModal(m.id)));
        
        m.addEventListener('click', (e) => {
            if(e.target === m) closeModal(m.id);
        });
    });


    // --- 2. SELECCIÓN DE CUENTAS (Checkboxes) ---
    const chkAll = document.getElementById('chkAll');
    const chks = Array.from(document.querySelectorAll('.chkOp'));
    const btnProp = document.getElementById('btnPropuesta');
    const btnCna = document.getElementById('btnSolicitarCna');
    const counters = document.querySelectorAll('.selection-count'); // Para actualizar contadores

    function refreshSelection() {
        const selected = chks.filter(c => c.checked && !c.disabled).map(c => c.value);
        const count = selected.length;
        
        // Actualizar badges
        counters.forEach(el => el.textContent = count);
        
        // Habilitar botones
        if(btnProp) btnProp.disabled = count === 0;
        if(btnCna) btnCna.disabled = count === 0;

        return selected;
    }

    if(chkAll) {
        chkAll.addEventListener('change', () => {
            chks.forEach(c => { if(!c.disabled) c.checked = chkAll.checked; });
            refreshSelection();
        });
    }

    chks.forEach(c => c.addEventListener('change', () => {
        const enabled = chks.filter(x => !x.disabled).length;
        const checked = chks.filter(x => x.checked && !x.disabled).length;
        if (enabled && chkAll) chkAll.checked = (checked === enabled);
        refreshSelection();
    }));


    // --- 3. MODAL PROPUESTA (Lógica Cronograma) ---
    const modalProp = document.getElementById('modalPropuesta');
    
    // Solo si el modal existe (usuario autorizado)
    if(modalProp) {
        // Al abrir, llenar operaciones
        const openBtn = document.querySelector('[data-modal-target="modalPropuesta"]');
        if(openBtn) {
            openBtn.addEventListener('click', () => {
                const ops = refreshSelection();
                const container = document.getElementById('opsResumen');
                const hiddenContainer = document.getElementById('opsHidden');
                
                // Visual
                container.innerHTML = ops.length 
                    ? ops.map(op => `<span class="badge badge-neutral bg-slate-100 px-2 py-0.5 rounded text-xs border border-slate-200 mr-1">${op}</span>`).join('') 
                    : '<span class="text-slate-400 italic">Ninguna</span>';

                // Hidden Inputs
                hiddenContainer.innerHTML = '';
                ops.forEach(op => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'operaciones[]'; i.value = op;
                    hiddenContainer.appendChild(i);
                });

                // Disparar input para recalcular capital
                document.getElementById('cvTotal')?.dispatchEvent(new Event('input'));
            });
        }

        // Lógica Cronograma (Simplificada del original)
        const nro = document.getElementById('cvNro');
        const total = document.getElementById('cvTotal');
        const tblCrono = document.getElementById('tblCronoBody');
        const sumaLabel = document.getElementById('cvSuma');
        const genBtn = document.getElementById('cvGen');
        const fIni = document.getElementById('cvFechaIni');

        function renderRows(n) {
            if(!tblCrono) return;
            tblCrono.innerHTML = '';
            for(let i=1; i<=n; i++) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-slate-500 font-mono">${String(i).padStart(2,'0')}</td>
                    <td><input type="date" class="w-full rounded border-slate-200 text-sm py-1 px-2 cr-fecha"></td>
                    <td><input type="number" step="0.01" class="w-full rounded border-slate-200 text-sm py-1 px-2 cr-monto text-right"></td>
                `;
                tblCrono.appendChild(tr);
            }
        }

        function autoFill() {
            const n = parseInt(nro.value || 0);
            const t = parseFloat(total.value || 0);
            if(n <= 0) return;
            
            const amount = (t / n).toFixed(2);
            let startDate = fIni.value ? new Date(fIni.value + 'T00:00:00') : null;

            const inputs = tblCrono.querySelectorAll('.cr-monto');
            const dates = tblCrono.querySelectorAll('.cr-fecha');

            inputs.forEach((inp, idx) => {
                inp.value = amount;
                if(startDate) {
                    const d = new Date(startDate);
                    d.setMonth(d.getMonth() + idx);
                    dates[idx].valueAsDate = d;
                }
            });
            recalcTotal();
        }

        function recalcTotal() {
            const inputs = tblCrono.querySelectorAll('.cr-monto');
            let sum = 0;
            inputs.forEach(i => sum += parseFloat(i.value || 0));
            if(sumaLabel) {
                sumaLabel.textContent = sum.toFixed(2);
                const target = parseFloat(total.value || 0);
                const diff = Math.abs(sum - target);
                
                sumaLabel.className = diff < 0.1 ? 'font-bold text-emerald-600' : 'font-bold text-red-600';
            }
        }

        // Eventos Cronograma
        if(genBtn) genBtn.addEventListener('click', () => {
            renderRows(parseInt(nro.value || 1));
            autoFill();
            recalcTotal();
        });
        
        if(tblCrono) tblCrono.addEventListener('input', recalcTotal);
    }

    // --- 4. COPIAR AL PORTAPAPELES ---
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML = `<svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;
                setTimeout(() => btn.innerHTML = original, 1500);
            });
        });
    });

});