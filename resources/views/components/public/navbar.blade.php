<header class="site-header" data-site-header>
  <div class="utility-bar">
    <div class="coffee-container utility-bar__inner">
      <p>✧ Cà phê thủ công, cảm hứng mỗi ngày</p>
      <span>MAISON DU CAFÉ · COFFEE & MORE</span>
      <a href="{{ route('reservation.create') }}">Đặt bàn trước ↗</a>
      @auth('web')
        <a href="{{ auth('web')->user()->role === 'admin' ? route('admin.home') : route('staff.home') }}">{{ auth('web')->user()->role === 'admin' ? 'Trang quản trị' : 'Khu vực nhân viên' }}</a>
        <form method="POST" action="{{ route('logout') }}" class="auth-logout">@csrf<button type="submit">Đăng xuất</button></form>
      @endauth
    </div>
  </div>
  <nav class="site-nav coffee-container" aria-label="Điều hướng chính">
    <x-public.brand />
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-nav-toggle>
      <span class="sr-only">Mở menu</span>
      <span></span><span></span><span></span>
    </button>
    <div class="site-nav__menu" id="primary-menu" data-nav-menu>
      <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Trang chủ</a>
      <a href="{{ route('menu.index') }}" @if(request()->routeIs('menu.*')) aria-current="page" @endif>Thực đơn</a>
      <a href="{{ route('home') }}#specials">Ưu đãi</a>
      <a href="{{ route('home') }}#story">Về Maison</a>
      <a href="{{ route('reservation.create') }}" @if(request()->routeIs('reservation.*')) aria-current="page" @endif>Đặt bàn</a>
      <a href="{{ route('cart.index') }}" class="nav-cart" @if(request()->routeIs('cart.*')) aria-current="page" @endif>
        Giỏ hàng <span class="nav-cart-count">{{ array_sum(session('customer_cart', [])) }}</span>
      </a>
      @guest('web')
        <a href="{{ route('login') }}" class="nav-account-btn" title="Đăng nhập hoặc Đăng ký tài khoản">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
          </svg>
          <span>Đăng nhập</span>
        </a>
      @endguest
    </div>
  </nav>
</header>