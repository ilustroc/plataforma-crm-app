document.addEventListener('DOMContentLoaded', () => {
    // Modal Toggle Logic
    const toggles = document.querySelectorAll('[data-modal-target]');
    const closers = document.querySelectorAll('[data-modal-close]');
    const backdrops = document.querySelectorAll('.modal-backdrop');

    function toggleModal(id, show) {
        const el = document.getElementById(id);
        if(el) {
            el.classList.toggle('show', show);
            document.body.style.overflow = show ? 'hidden' : '';
        }
    }

    toggles.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleModal(btn.dataset.modalTarget, true);
        });
    });

    closers.forEach(btn => {
        btn.addEventListener('click', () => toggleModal(btn.closest('.modal-backdrop').id, false));
    });

    backdrops.forEach(bd => {
        bd.addEventListener('click', (e) => {
            if(e.target === bd) toggleModal(bd.id, false);
        });
    });
});