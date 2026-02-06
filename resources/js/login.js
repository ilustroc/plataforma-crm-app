import './bootstrap';

document.addEventListener("DOMContentLoaded", () => {
    
    // 1. TOGGLE PASSWORD (VISUALIZAR CONTRASEÑA)
    const tgl = document.getElementById("togglePwd");
    const pwd = document.getElementById("password");
    const eye = document.getElementById("eyeIcon");

    if (tgl && pwd && eye) {
        tgl.addEventListener("click", () => {
            const isPassword = pwd.type === "password";
            
            // Cambiar el tipo de input
            pwd.type = isPassword ? "text" : "password";
            
            // Estilo visual del botón
            tgl.classList.toggle("text-brand", isPassword);
            tgl.classList.toggle("text-slate-400", !isPassword);
            
            // Cambiar el Icono SVG (Ojo abierto / Ojo con línea)
            eye.innerHTML = isPassword
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>`;
            
            // Accesibilidad
            tgl.setAttribute("aria-label", isPassword ? "Ocultar contraseña" : "Mostrar contraseña");
        });
    }

    // 2. SUBMIT SPINNER (BOTÓN DE CARGA)
    const form = document.getElementById("login-form");
    const btn = document.getElementById("submitBtn");
    if (form && btn) {
        form.addEventListener("submit", (e) => {
            if (form.checkValidity()) {
                btn.disabled = true;
                btn.querySelector(".spinner")?.classList.remove("hidden");
                btn.querySelector(".btn-text").textContent = "Ingresando...";
            }
        });
    }

    // 3. CAPS LOCK (DETECTAR MAYÚSCULAS)
    const caps = document.getElementById("capsHint");
    if (pwd && caps) {
        const checkCaps = (e) => {
            if (e.getModifierState("CapsLock")) {
                caps.classList.remove("hidden");
            } else {
                caps.classList.add("hidden");
            }
        };
        pwd.addEventListener("keyup", checkCaps);
        pwd.addEventListener("keydown", checkCaps);
    }

    // 4. INICIALIZAR TOASTS
    initToasts();
});

function initToasts() {
    document.querySelectorAll('.toast[data-autoclose]').forEach(t => {
        const time = parseInt(t.getAttribute('data-autoclose')) || 5000;
        setTimeout(() => closeToast(t), time);
    });

    document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('.toast-close');
        if (closeBtn) closeToast(closeBtn.closest('.toast'));
    });
}

function closeToast(el) {
    if (!el) return;
    el.style.opacity = '0';
    el.style.transform = 'translateX(100%)';
    setTimeout(() => el.remove(), 400);
}