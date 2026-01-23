import './bootstrap';

document.addEventListener("DOMContentLoaded", () => {
    
    // 1. SIDEBAR TOGGLE (Solo existe en Dashboard)
    const toggleBtn = document.getElementById('sidebarToggle'); // Asegúrate de poner este ID al botón en el HTML
    
    // Opcional: Lógica para cerrar sidebar al redimensionar a desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) { 
            document.getElementById('sidebar')?.classList.remove('-translate-x-full'); 
            document.getElementById('sidebarBackdrop')?.classList.add('hidden');
        }
    });

    // 2. TOASTS
    initToasts();

    // --- LOGICA DEL BUSCADOR GLOBAL (SIDEBAR) ---
    const searchForm = document.getElementById('globalSearchForm');
    const searchInput = document.getElementById('globalSearchInput');

    if (searchForm && searchInput) {
        const baseUrl = searchForm.dataset.url;

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Limpiar input: solo números, máximo 12 caracteres
            const dni = (searchInput.value || '').replace(/\D/g, '').slice(0, 12);

            if (!dni) {
                searchInput.focus();
                // Pequeña animación de error (borde rojo temporal)
                searchInput.classList.add('border-red-400', 'ring-1', 'ring-red-400');
                setTimeout(() => {
                    searchInput.classList.remove('border-red-400', 'ring-1', 'ring-red-400');
                }, 1000);
                return;
            }

            // Redirigir a la ruta del cliente
            window.location.assign(baseUrl.replace('__DNI__', encodeURIComponent(dni)));
        });
    }
});

// Función de Toasts (Puedes extraerla a un utils.js si prefieres no repetir, pero por simplicidad la dejo aquí)
function initToasts() {
    document.querySelectorAll('.toast[data-autoclose]').forEach(t => {
        setTimeout(() => closeToast(t), t.getAttribute('data-autoclose') || 5000);
    });
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.toast-close');
        if (btn) closeToast(btn.closest('.toast'));
    });
}

function closeToast(el) {
    if (!el) return;
    el.style.transition = 'all 0.4s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(100%)';
    setTimeout(() => el.remove(), 400);
}