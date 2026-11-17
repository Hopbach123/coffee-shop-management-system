export function initializeStatusRefresh(scope = document, events = window, reload = () => window.location.reload()) {
    const panel = scope.querySelector('[data-status-refresh-scope]');
    if (!panel) return;
    const link = panel.querySelector('[data-status-refresh]');
    const message = panel.querySelector('[data-status-refresh-message]');
    const markLoading = () => { panel.setAttribute('aria-busy', 'true'); if (message) message.textContent = 'Đang tải trạng thái mới từ hệ thống…'; };
    link?.addEventListener('click', markLoading);
    let requested = false;
    events.addEventListener('pageshow', (event) => {
        if (event.persisted && !requested) {
            requested = true;
            markLoading();
            reload();
        }
    });
}
