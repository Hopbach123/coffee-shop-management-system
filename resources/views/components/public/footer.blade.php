<footer class="site-footer" id="contact">
    <div class="coffee-container site-footer__grid">
        <section class="site-footer__brand" aria-labelledby="footer-brand-title">
            <p class="accent-script">Hẹn bạn tại Maison</p>
            <h2 id="footer-brand-title">Maison du Café</h2>
            <p>Một góc nhỏ ấm áp dành cho những cuộc trò chuyện, hương cà phê thơm và khoảnh khắc riêng của bạn.</p>
        </section>
        <section aria-labelledby="footer-visit-title">
            <h3 id="footer-visit-title">Ghé thăm</h3>
            <x-public.shop-location />
        </section>
        <section aria-labelledby="footer-hours-title">
            <h3 id="footer-hours-title">Khám phá</h3>
            <dl class="opening-hours">
                <div><dt>Thực đơn</dt><dd><a href="{{ route('menu.index') }}">Xem danh mục ↗</a></dd></div>
                <div><dt>Giờ mở cửa</dt><dd>07:00 – 22:00 hằng ngày</dd></div>
            </dl>
        </section>
        <section aria-labelledby="footer-letter-title">
            <h3 id="footer-letter-title">Liên kết nhanh</h3>
            <p><a href="{{ route('menu.index') }}">Thực đơn quán</a></p>
            <p><a href="{{ route('home') }}#story">Câu chuyện Maison</a></p>
            @guest('web')<p><a href="{{ route('login') }}">Đăng nhập nội bộ</a></p>@endguest
        </section>
    </div>
    <div class="coffee-container site-footer__bottom">
        <p>© {{ now()->year }} Maison du Café. Chậm một chút, trọn vị hơn.</p>
        <a href="#home">Về đầu trang ↑</a>
    </div>
</footer>
