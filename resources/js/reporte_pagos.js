document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('filterForm');
    const tableContainer = document.getElementById('tableContainer');
    const exportBtn = document.getElementById('btnExport');

    // 1. Cargar datos (AJAX)
    async function loadData(url = null) {
        // Mostrar esqueleto o loader
        tableContainer.innerHTML = `
            <div class="p-12 text-center text-slate-400 animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-1/3 mx-auto mb-3"></div>
                <div class="h-4 bg-slate-100 rounded w-2/3 mx-auto"></div>
            </div>
        `;

        // Construir URL
        const targetUrl = new URL(url || form.action, window.location.origin);
        if (!url) { // Si no es paginación, agregar filtros del form
            const formData = new FormData(form);
            for (const [key, value] of formData) {
                if(value) targetUrl.searchParams.append(key, value);
            }
        }
        targetUrl.searchParams.append('partial', '1'); // Indicar que queremos solo la tabla

        // Fetch
        try {
            const res = await fetch(targetUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            tableContainer.innerHTML = html;
            
            // Actualizar URL del botón exportar con los filtros actuales
            const exportUrl = new URL(form.dataset.exportUrl, window.location.origin);
            targetUrl.searchParams.forEach((v, k) => {
                if(k !== 'partial' && k !== 'page') exportUrl.searchParams.append(k, v);
            });
            exportBtn.href = exportUrl.toString();

        } catch (error) {
            tableContainer.innerHTML = `<div class="p-6 text-center text-red-500">Error al cargar datos.</div>`;
            console.error(error);
        }
    }

    // 2. Eventos
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadData();
    });

    // Detectar clicks en paginación (delegación)
    tableContainer.addEventListener('click', (e) => {
        const link = e.target.closest('.page-link');
        if (link && !link.parentElement.classList.contains('disabled') && !link.parentElement.classList.contains('active')) {
            e.preventDefault();
            loadData(link.href);
        }
    });

    // Carga inicial
    loadData();
});