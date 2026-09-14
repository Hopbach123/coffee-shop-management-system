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