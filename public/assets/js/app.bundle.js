// Prebuilt bundle from local source (no third-party javascript).
function initializeMenuSearch() {
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
        if (query) {
            url.searchParams.set('search', query);
        }
        window.history.replaceState(null, '', url);
    };

    const reset = () => {
        clearTimeout(debounceTimer);
        requestVersion++;
        activeRequest?.abort();
        activeRequest = undefined;
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



const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');

if (navToggle && navMenu) {
    const closeMenu = () => {
        navToggle.setAttribute('aria-expanded', 'false');
        navMenu.classList.remove('is-open');
        document.body.classList.remove('nav-open');
    };

    navToggle.addEventListener('click', () => {
        const willOpen = navToggle.getAttribute('aria-expanded') !== 'true';

        navToggle.setAttribute('aria-expanded', String(willOpen));
        navMenu.classList.toggle('is-open', willOpen);
        document.body.classList.toggle('nav-open', willOpen);
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            navToggle.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 48.01rem)').matches) {
            closeMenu();
        }
    });
}

document.querySelectorAll('[data-menu-category]').forEach((category) => {
    const products = Array.from(category.querySelectorAll('[data-menu-product]'));
    const loadMore = category.querySelector('[data-menu-load-more]');

    if (!loadMore || products.length <= 3) {
        return;
    }

    let visibleCount = 3;
    products.slice(visibleCount).forEach((product) => { product.hidden = true; });
    loadMore.hidden = false;

    loadMore.addEventListener('click', () => {
        const nextCount = Math.min(visibleCount + 3, products.length);
        products.slice(visibleCount, nextCount).forEach((product) => { product.hidden = false; });
        visibleCount = nextCount;
        loadMore.hidden = visibleCount >= products.length;
    });
});

initializeMenuSearch();

const promotionType = document.querySelector('[data-promotion-discount-type]');
const promotionMaximum = document.querySelector('[data-promotion-max-discount]');

if (promotionType && promotionMaximum) {
    const maximumInput = promotionMaximum.querySelector('input');
    const updateMaximum = () => {
        const isPercentage = promotionType.value === 'percentage';
        promotionMaximum.hidden = !isPercentage;
        maximumInput.disabled = !isPercentage;
    };

    promotionType.addEventListener('change', updateMaximum);
    updateMaximum();
}



// Respect accessibility preferences for motion-heavy hero media.
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.querySelectorAll('.hero__video').forEach(video => video.pause());
}

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
