export function initializeMenuSearch() {
    const form = document.querySelector('[data-menu-search-form]');
    if (!form) {
        return;
    }

    const input = form.querySelector('input[name="search"]');
    const defaultMenu = document.querySelector('[data-menu-default]');
    const results = document.querySelector('[data-menu-search-results]');
    const status = document.querySelector('[data-menu-search-status]');
    const categoryNav = document.querySelector('[data-menu-category-nav]');
    let debounceTimer;
    let activeRequest;
    let requestVersion = 0;

    const setStatus = (message, state = '') => {
        status.textContent = message;
        status.dataset.state = state;
        status.hidden = message === '';
    };

    const updateUrl = (query) => {
        const url = new URL(form.action);
        const category = form.querySelector('[name="category"]')?.value;
        if (category) url.searchParams.set('category', category);
        if (query) {
            url.searchParams.set('search', query);
        }
        document.querySelectorAll('.public-menu-categories a').forEach((link) => {
            const target = new URL(link.href);
            if (query) target.searchParams.set('search', query);
            else target.searchParams.delete('search');
            link.href = target.toString();
        });
        window.history.replaceState(null, '', url);
    };

    const reset = () => {
        clearTimeout(debounceTimer);
        requestVersion++;
        activeRequest?.abort();
        activeRequest = undefined;
        if (new URL(window.location.href).searchParams.has('search')) {
            const url = new URL(form.action);
            const category = form.querySelector('[name="category"]')?.value;
            if (category) url.searchParams.set('category', category);
            window.location.assign(url);
            return;
        }
        defaultMenu.hidden = false;
        results.hidden = true;
        if (categoryNav) categoryNav.hidden = false;
        setStatus('');
        updateUrl('');
    };

    const search = async () => {
        const query = input.value.trim();
        if (!query) {
            reset();
            return;
        }

        const version = ++requestVersion;
        activeRequest?.abort();
        activeRequest = new AbortController();
        const requestUrl = new URL(form.dataset.searchEndpoint);
        requestUrl.searchParams.set('q', query);
        const category = form.querySelector('[name="category"]')?.value;
        if (category) requestUrl.searchParams.set('category', category);
        setStatus('Đang tìm món...', 'loading');

        try {
            const response = await fetch(requestUrl, {
                signal: activeRequest.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Menu search failed');
            const html = await response.text();
            if (version !== requestVersion || input.value.trim() !== query) return;

            results.hidden = false;
            results.innerHTML = html;
            defaultMenu.hidden = true;
            if (categoryNav) categoryNav.hidden = true;
            setStatus('');
            updateUrl(query);
        } catch (error) {
            if (error.name !== 'AbortError' && version === requestVersion) {
                setStatus('Không thể tìm kiếm lúc này. Vui lòng thử lại.', 'error');
            }
        } finally {
            if (version === requestVersion) activeRequest = undefined;
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        requestVersion++;
        activeRequest?.abort();
        activeRequest = undefined;

        if (!input.value.trim()) {
            reset();
            return;
        }

        setStatus('Đang tìm món...', 'loading');
        debounceTimer = setTimeout(search, 350);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        search();
    });
}
