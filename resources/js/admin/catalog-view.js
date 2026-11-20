export function initializeCatalogView(scope = document, storage = null) {
    const root = scope.querySelector('[data-catalog-view]');
    if (!root) return;
    const controls = root.querySelector('[data-catalog-controls]');
    if (!controls) return;
    const buttons = [...root.querySelectorAll('[data-catalog-mode]')];
    const panels = [...root.querySelectorAll('[data-catalog-panel]')];
    if (panels.length !== 2) return;
    const apply = mode => {
        if (!['grid', 'table'].includes(mode)) mode = 'grid';
        panels.forEach(panel => { panel.hidden = panel.dataset.catalogPanel !== mode; });
        buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.catalogMode === mode)));
    };
    let initial = 'grid';
    try { initial = storage.getItem('cafe_product_view') || 'grid'; } catch { /* Usable without storage. */ }
    apply(initial);
    buttons.forEach(button => button.addEventListener('click', () => {
        apply(button.dataset.catalogMode);
        try { storage.setItem('cafe_product_view', button.dataset.catalogMode); } catch { /* Presentation only. */ }
    }));
    controls.hidden = false;
}
