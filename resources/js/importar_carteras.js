document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('fileInput');
    const feedback = document.getElementById('fileFeedback');
    const fileName = document.getElementById('fileName');
    const btn = document.getElementById('btnSubmit');

    if(input) {
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                feedback.classList.remove('hidden');
                btn.disabled = false;
            } else {
                feedback.classList.add('hidden');
                btn.disabled = true;
            }
        });
    }
});