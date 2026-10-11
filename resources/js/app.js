import { initializeMenuSearch } from './public/menu-search.js';

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
