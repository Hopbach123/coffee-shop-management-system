document.querySelectorAll('[data-admin-sidebar] [data-sidebar-group]').forEach(button => {
    const content = document.getElementById(button.getAttribute('aria-controls'));
    if (!content) return;
    const setOpen = open => {
        button.setAttribute('aria-expanded', String(open));
        content.hidden = !open;
    };
    setOpen(button.dataset.defaultOpen === '1');
    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
});
