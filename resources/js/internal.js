const shell = document.querySelector('[data-sneat-layout]');
const toggle = document.querySelector('[data-sneat-toggle]');
const backdrop = document.querySelector('[data-sneat-backdrop]');
if (shell && toggle && backdrop) {
    const sidebar = document.getElementById('work-sidebar');
    const workspace = shell.querySelector('.sneat-workspace');
    const closeButton = shell.querySelector('[data-sneat-close]');
    const mobile = window.matchMedia('(max-width: 767px)');
    let collapsed = false;
    let drawerOpen = false;
    try { collapsed = localStorage.getItem('maison.sidebar.collapsed') === 'true'; } catch { /* Storage can be unavailable. */ }

    const render = () => {
        const open = mobile.matches ? drawerOpen : !collapsed;
        shell.classList.toggle('is-open', mobile.matches && drawerOpen);
        shell.classList.toggle('is-collapsed', !mobile.matches && collapsed);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Thu gọn menu' : 'Mở menu');
        if (sidebar) sidebar.inert = !open;
        if (workspace) workspace.inert = mobile.matches && drawerOpen;
        document.body.classList.toggle('sneat-drawer-open', mobile.matches && drawerOpen);
    };
    const close = () => {
        const restoreFocus = drawerOpen;
        drawerOpen = false;
        render();
        if (restoreFocus) toggle.focus();
    };
    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            drawerOpen = !drawerOpen;
        } else {
            collapsed = !collapsed;
            try { localStorage.setItem('maison.sidebar.collapsed', String(collapsed)); } catch { /* Keep working without persistence. */ }
        }
        render();
        if (mobile.matches && drawerOpen) closeButton?.focus();
    });
    backdrop.addEventListener('click', close);
    closeButton?.addEventListener('click', close);
    document.addEventListener('keydown', (event) => {
        if (!mobile.matches || !drawerOpen) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key === 'Tab' && sidebar) {
            const controls = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(el => el.getClientRects().length);
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    mobile.addEventListener('change', () => {
        const focusWasInSidebar = sidebar?.contains(document.activeElement);
        drawerOpen = false;
        render();
        if (focusWasInSidebar && sidebar.inert) toggle.focus();
    });
    render();
}
const orderType = document.querySelector('[data-order-type]');
const tableFields = document.querySelector('[data-pos-table]');
if (orderType && tableFields) {
    const tableSelect = tableFields.querySelector('select');
    const update = () => {
        const seated = orderType.value === 'dine_in';
        tableFields.hidden = !seated;
        tableSelect.disabled = !seated;
        tableSelect.required = seated;
    };
    orderType.addEventListener('change', update);
    update();
}
