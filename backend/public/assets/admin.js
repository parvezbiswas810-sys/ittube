(function () {
    const sidebar = document.getElementById('sidebar');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
        btn.addEventListener('click', () => sidebar && sidebar.classList.toggle('open'));
    });
    if (window.innerWidth <= 820) {
        const toggle = document.querySelector('[data-sidebar-toggle]');
        if (toggle) toggle.style.display = 'grid';
    }

    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalOpen);
            if (modal) modal.classList.add('open');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.modal')?.classList.remove('open'));
    });
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
    });

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    const toast = document.getElementById('toast');
    window.showToast = function (message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3200);
    };

    document.querySelectorAll('input[type=file]').forEach(input => {
        input.addEventListener('change', () => {
            const hint = input.parentElement.querySelector('.file-name');
            if (hint && input.files.length) hint.textContent = input.files[0].name;
        });
    });
})();
