<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') — Maison du Café</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="coffee-page">
        <a class="skip-link" href="#main-content">Đến nội dung chính</a>
        <div class="internal-shell">
            <aside class="internal-sidebar">
                <x-public.brand />
                <nav class="internal-nav" aria-label="Khu vực nội bộ">
                    @if (auth('web')->user()->role === 'admin')
                        <a href="{{ route('admin.home') }}" @if(request()->routeIs('admin.home')) aria-current="page" @endif>Khu vực Admin</a>
                        <a href="{{ route('admin.categories.index') }}" @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif>Danh mục</a>
                    @endif
                    <a href="{{ route('staff.home') }}" @if(request()->routeIs('staff.*')) aria-current="page" @endif>Khu vực Staff</a>
                    <a href="{{ url('/') }}">Trang công khai</a>
                </nav>
            </aside>
            <div class="internal-workspace">
                <header class="internal-header">
                    <div>
                        <strong>{{ auth('web')->user()->name }}</strong>
                        <p>{{ auth('web')->user()->role === 'admin' ? 'Quản trị viên' : 'Nhân viên' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-public.button type="submit" variant="outline">Đăng xuất</x-public.button>
                    </form>
                </header>
                <main class="internal-content" id="main-content">
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
