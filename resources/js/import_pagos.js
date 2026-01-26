document.addEventListener('DOMContentLoaded', () => {

    const fileInput = document.getElementById('csvInput');
    const dropZone = document.getElementById('dropZone');
    const validationBox = document.getElementById('validationBox');
    const statusMsg = document.getElementById('statusMsg');
    const btnImport = document.getElementById('btnImport');
    const fileName = document.getElementById('fileName');

    // Seguridad: si esta vista no está presente, no revientes
    if (!fileInput || !dropZone || !validationBox || !statusMsg || !btnImport) return;

    // Columnas requeridas (Mayúsculas)
    const REQUIRED = ['DOCUMENTO', 'FECHA', 'MONTO'];

    // 1) Drag & Drop visual + soporte drop real
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');

        const files = e.dataTransfer?.files;
        if (files && files.length > 0) {
            // Asignar al input y disparar validación
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // 2) Validación de archivo
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) return resetUI();

        if (fileName) fileName.textContent = `Archivo: ${file.name}`;

        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result ?? '';
            const rows = String(text).split(/\r?\n/).filter(line => line.trim() !== '');

            if (rows.length < 2) {
                showError('El archivo está vacío o no contiene datos.');
                return;
            }

            // Detectar separador (coma o punto y coma)
            const firstLine = rows[0];
            const semi = (firstLine.match(/;/g) || []).length;
            const comma = (firstLine.match(/,/g) || []).length;
            const separator = semi > comma ? ';' : ',';

            // Obtener headers limpios
            const headers = firstLine
                .split(separator)
                .map(h => h.trim().toUpperCase().replace(/^"|"$/g, ''));

            // Verificar columnas críticas
            const missing = REQUIRED.filter(h => !headers.includes(h));

            if (missing.length > 0) {
                showError(`Faltan columnas obligatorias: ${missing.join(', ')}`);
            } else {
                showSuccess(`Archivo válido. ${rows.length - 1} registros listos.`);
            }
        };

        reader.readAsText(file);
    });

    function showError(msg) {
        validationBox.classList.remove('hidden');
        statusMsg.className = 'rounded-lg px-3 py-2 text-xs font-semibold flex items-center gap-2 bg-red-50 text-red-700 border border-red-100';
        statusMsg.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ${msg}
        `;
        btnImport.disabled = true;
    }

    function showSuccess(msg) {
        validationBox.classList.remove('hidden');
        statusMsg.className = 'rounded-lg px-3 py-2 text-xs font-semibold flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-100';
        statusMsg.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7"/>
            </svg>
            ${msg}
        `;
        btnImport.disabled = false;
    }

    function resetUI() {
        validationBox.classList.add('hidden');
        btnImport.disabled = true;
        if (fileName) fileName.textContent = '';
    }
});
