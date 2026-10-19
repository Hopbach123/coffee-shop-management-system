const shell = document.querySelector('[data-sneat-layout]');
const toggle = document.querySelector('[data-sneat-toggle]');
const backdrop = document.querySelector('[data-sneat-backdrop]');
if (shell && toggle && backdrop) {
    const close = () => { shell.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => { const open = shell.classList.toggle('is-open'); toggle.setAttribute('aria-expanded', String(open)); });
    backdrop.addEventListener('click', close);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
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
