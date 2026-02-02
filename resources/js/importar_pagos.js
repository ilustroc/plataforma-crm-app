document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('fileInput');
    const btnSubmit = document.getElementById('btnSubmit');
    const fileNameDisplay = document.getElementById('fileName');
    const fileFeedback = document.getElementById('fileFeedback');

    if (!fileInput || !btnSubmit) return;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        
        if (file) {
            // Actualizar nombre y mostrar check
            if (fileNameDisplay) fileNameDisplay.textContent = file.name;
            if (fileFeedback) fileFeedback.style.display = 'flex';
            
            // Habilitar el botón para que el formulario se envíe normalmente
            btnSubmit.disabled = false;
        } else {
            btnSubmit.disabled = true;
            if (fileFeedback) fileFeedback.style.display = 'none';
        }
    });
});