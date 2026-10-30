<footer class="site-footer" id="contact">
    <div class="coffee-container site-footer__grid">
        <section class="site-footer__brand" aria-labelledby="footer-brand-title">
            <p class="accent-script">Hẹn bạn tại Maison</p>
            <h2 id="footer-brand-title">Maison du Café</h2>
            <p>Một góc nhỏ ấm áp dành cho những cuộc trò chuyện, hương cà phê thơm và khoảnh khắc riêng của bạn.</p>
        </section>
        <section aria-labelledby="footer-visit-title">
            <h3 id="footer-visit-title">Ghé thăm</h3>
            <address>Liên hệ cửa hàng để xác nhận địa chỉ và thời gian hoạt động.</address>
        </section>
        <section aria-labelledby="footer-hours-title">
            <h3 id="footer-hours-title">Khám phá</h3>
            <dl class="opening-hours">
                <div><dt>Đặt bàn</dt><dd><a href="{{ route('reservation.create') }}">Gửi yêu cầu ↗</a></dd></div>
                <div><dt>Đặt món</dt><dd><a href="{{ route('menu.index') }}">Xem thực đơn ↗</a></dd></div>
            </dl>
        </section>
        <section aria-labelledby="footer-letter-title">
            <h3 id="footer-letter-title">Liên kết nhanh</h3>
            <p><a href="{{ route('menu.index') }}">Thực đơn</a></p>
            <p><a href="{{ route('cart.index') }}">Giỏ hàng</a></p>
            @guest('web')<p><a href="{{ route('login') }}">Đăng nhập nhân viên</a></p>@endguest
        </section>
    </div>
    <div class="coffee-container site-footer__bottom">
        <p>© {{ now()->year }} Maison du Café. Chậm một chút, trọn vị hơn.</p>
        <a href="#home">Về đầu trang ↑</a>
    </div>
</footer>