<footer class="site-footer" id="contact">
    <div class="coffee-container site-footer__grid">
        <section class="site-footer__brand" aria-labelledby="footer-brand-title">
            <p class="accent-script">Stay awhile</p>
            <h2 id="footer-brand-title">Maison du Café</h2>
            <p>A quiet corner for considered coffee, unhurried conversations and the comfort of familiar rituals.</p>
        </section>
        <section aria-labelledby="footer-visit-title">
            <h3 id="footer-visit-title">Visit</h3>
            <address>24 Rue du Matin<br>District One, Saigon<br><a href="tel:+84000000000">+84 000 000 000</a></address>
        </section>
        <section aria-labelledby="footer-hours-title">
            <h3 id="footer-hours-title">Opening hours</h3>
            <dl class="opening-hours">
                <div><dt>Mon–Fri</dt><dd>07:00–21:00</dd></div>
                <div><dt>Sat–Sun</dt><dd>08:00–22:00</dd></div>
            </dl>
        </section>
        <section aria-labelledby="footer-letter-title">
            <h3 id="footer-letter-title">Coffee letters</h3>
            <p>Seasonal notes and quiet invitations, delivered occasionally.</p>
            <form class="newsletter-form" action="#" method="get">
                <label class="sr-only" for="newsletter-email">Email address</label>
                <input id="newsletter-email" type="email" name="email" placeholder="Your email address">
                <button type="submit" aria-label="Join coffee letters">Join</button>
            </form>
        </section>
    </div>
    <div class="coffee-container site-footer__bottom">
        <p>© {{ now()->year }} Maison du Café. Crafted slowly.</p>
        <a href="#home">Back to top ↑</a>
    </div>
</footer>