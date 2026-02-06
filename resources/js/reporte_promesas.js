document.addEventListener('DOMContentLoaded', () => {
    const $form = document.getElementById('filtros');
    const $tabla = document.getElementById('tablaPdp');
    const $btnExport = document.getElementById('btnExport');
    const $summary = document.getElementById('summary');
    const $btnLimpiar = document.getElementById('btnLimpiar');

    // URLs obtenidas del DOM
    const baseUrl = $form.getAttribute('action');
    const exportBaseUrl = $btnExport.getAttribute('href').split('?')[0];

    // 1. Helper para obtener los parámetros del formulario
    function getQueryParams(extra = {}) {
        const formData = new FormData($form);
        const params = new URLSearchParams(formData);
        
        // Limpiar parámetros vacíos
        for (const [key, value] of [...params.entries()]) {
            if (!value) params.delete(key);
        }

        Object.keys(extra).forEach(key => params.set(key, extra[key]));
        return params.toString();
    }

    // 2. Función principal de carga AJAX
    async function loadData(url = null) {
        $tabla.classList.add('loading');

        try {
            const query = getQueryParams({ partial: '1' });
            const fetchUrl = url ? `${url}&partial=1` : `${baseUrl}?${query}`;

            const response = await fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Error en la carga');

            const html = await response.text();
            
            // Inyectar el fragmento recibido
            $tabla.innerHTML = html;
            
            // Actualizar UI, URL del navegador y botón exportar
            updateUI();
            
        } catch (error) {
            console.error(error);
            $tabla.innerHTML = '<div class="alert alert-danger m-3">Error al cargar los datos.</div>';
        } finally {
            $tabla.classList.remove('loading');
        }
    }

    // 3. Actualizar elementos externos a la tabla
    function updateUI() {
        const query = getQueryParams();
        
        // Actualizar URL de exportación con los filtros actuales
        $btnExport.href = `${exportBaseUrl}?${query}`;

        // Actualizar resumen de resultados
        const $meta = document.getElementById('pagMeta');
        if ($meta && $summary) {
            $summary.textContent = `Página ${$meta.dataset.page} · ${$meta.dataset.total} resultados`;
        }

        // Re-vincular eventos de paginación
        $tabla.querySelectorAll('.pagination a').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                loadData(a.href);
            });
        });

        // Actualizar barra de direcciones sin recargar
        window.history.replaceState(null, '', `${baseUrl}?${query}`);
    }

    // 4. Event Listeners
    $form.addEventListener('submit', e => {
        e.preventDefault();
        loadData();
    });

    $btnLimpiar.addEventListener('click', () => {
        $form.reset();
        loadData();
    });

    // Inicializar UI al cargar
    updateUI();
});