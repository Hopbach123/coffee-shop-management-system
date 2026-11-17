<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Đăng nhập & Đăng ký — Maison du Café')</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
        @if (is_file(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="{{ asset('assets/css/app.bundle.css') }}">
            <script src="{{ asset('assets/js/app.bundle.js') }}" defer></script>
        @endif
    </head>
    <body class="coffee-page auth-universe">
        <a class="skip-link" href="#main-content">Đến nội dung chính</a>

        <!-- Atmospheric Golden Sky & Animated Birds Background -->
        <div class="auth-bg-layer" aria-hidden="true">
            <div class="auth-bg-gradient-overlay"></div>
            <div class="auth-birds-container">
                <svg class="auth-bird bird-1" viewBox="0 0 50 30" fill="currentColor">
                    <path d="M 0 15 Q 12 5, 25 15 Q 38 5, 50 15 Q 35 22, 25 15 Q 15 22, 0 15 Z" />
                </svg>
                <svg class="auth-bird bird-2" viewBox="0 0 50 30" fill="currentColor">
                    <path d="M 0 15 Q 12 5, 25 15 Q 38 5, 50 15 Q 35 22, 25 15 Q 15 22, 0 15 Z" />
                </svg>
                <svg class="auth-bird bird-3" viewBox="0 0 50 30" fill="currentColor">
                    <path d="M 0 15 Q 12 5, 25 15 Q 38 5, 50 15 Q 35 22, 25 15 Q 15 22, 0 15 Z" />
                </svg>
            </div>
        </div>

        <!-- Top Navigation matching reference format -->
        <header class="auth-topbar">
            <div class="auth-topbar__inner">
                <a href="{{ url('/') }}" class="auth-brand" aria-label="Maison du Café Trang chủ">
                    <span class="auth-brand__symbol">☕</span>
                    <span class="auth-brand__text">Maison du Café</span>
                </a>

                <nav class="auth-nav-links" aria-label="Điều hướng trang đăng nhập">
                    <a href="{{ route('home') }}" class="auth-nav-item">TRANG CHỦ</a>
                    <a href="{{ route('home') }}#story" class="auth-nav-item">VỀ MAISON</a>
                    <a href="{{ route('menu.index') }}" class="auth-nav-item">THỰC ĐƠN</a>
                    <a href="{{ Route::has('reservation.create') ? route('reservation.create') : route('home').'#reservation' }}" class="auth-nav-item">ĐẶT BÀN</a>
                    <a href="{{ route('login') }}" class="auth-nav-item auth-nav-item--active">ĐĂNG NHẬP</a>
                </nav>
            </div>
        </header>

        <main id="main-content" class="auth-main-wrapper">
            @yield('content')
        </main>
    </body>
</html>