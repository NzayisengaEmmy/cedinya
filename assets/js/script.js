document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-open');
    });
});