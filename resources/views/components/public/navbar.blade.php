<header class="site-header" data-site-header>
    <div class="utility-bar">
        <div class="coffee-container utility-bar__inner">
            <p>Roasted with patience · Served with warmth</p>
            <a href="#contact">Open daily · 07:00–21:00</a>
            @auth('web')
                @if (auth('web')->user()->role === 'admin')
                    <a href="{{ route('admin.home') }}">Trang quản trị</a>
                @elseif (auth('web')->user()->role === 'staff')
                    <a href="{{ route('staff.home') }}">Khu vực nhân viên</a>
                @endif
                <form class="auth-logout" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}">Đăng nhập nội bộ</a>
            @endauth
        </div>
    </div>

    <nav class="site-nav coffee-container" aria-label="Primary navigation">
        <x-public.brand />

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-nav-toggle>
            <span class="sr-only">Toggle navigation</span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>

        <div class="site-nav__menu" id="primary-menu" data-nav-menu>
            <a href="{{ url('/') }}#home">Home</a>
            <a href="{{ url('/') }}#story">About</a>
            <a href="{{ route('menu.index') }}" @if(request()->routeIs('menu.*')) aria-current="page" @endif>Thực đơn</a>
            <a href="{{ url('/') }}#specials">Specials</a>
            <a href="{{ url('/') }}#reservation">Reservation</a>
            <a href="#contact">Contact</a>
        </div>
    </nav>
</header>
