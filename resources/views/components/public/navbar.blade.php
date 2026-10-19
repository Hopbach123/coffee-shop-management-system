<header class="site-header" data-site-header>
    <div class="utility-bar">
        <div class="coffee-container utility-bar__inner">
            <p>✦ Cà phê thủ công, cảm hứng mỗi ngày</p>
            <span>MAISON DU CAFÉ · COFFEE & MORE</span>
            @auth('web')
                <a href="{{ auth('web')->user()->role === 'admin' ? route('admin.home') : route('staff.home') }}">
                    {{ auth('web')->user()->role === 'admin' ? 'Trang quản trị' : 'Khu vực nhân viên' }}
                </a>
                <form method="POST" action="{{ route('logout') }}" class="auth-logout">
                    @csrf
                    <button type="submit">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}">Đăng nhập nội bộ</a>
            @endauth
        </div>
    </div>
    <nav class="site-nav coffee-container" aria-label="Điều hướng chính">
        <x-public.brand />
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-nav-toggle>
            <span class="sr-only">Mở menu</span>
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="site-nav__menu" id="primary-menu" data-nav-menu>
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Trang chủ</a>
            <a href="{{ route('menu.index') }}" @if(request()->routeIs('menu.*')) aria-current="page" @endif>Thực đơn</a>
            <a href="{{ route('home') }}#specials">Ưu đãi</a>
            <a href="{{ route('home') }}#story">Về Maison</a>
            <a href="{{ route('home') }}#reservation">Đặt bàn</a>
        </div>
    </nav>
</header>
