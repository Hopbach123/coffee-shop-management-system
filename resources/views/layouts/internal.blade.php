@php
$isAdmin = request()->routeIs('admin.*');
$menus = [
['label' => 'WORKSPACE', 'links' => [
['route' => 'staff.home', 'text' => 'Bán hàng / POS', 'icon' => 'bag'],
['route' => 'staff.orders.index', 'text' => 'Đơn hàng', 'icon' => 'receipt'],
['route' => 'staff.online-requests.index', 'text' => 'Online & hẹn', 'icon' => 'bag'],
['route' => 'staff.brewing.index', 'text' => 'Pha chế', 'icon' => 'coffee'],
['route' => 'staff.tables.index', 'text' => 'Bàn phục vụ', 'icon' => 'table'],
['route' => 'staff.reservations.index', 'text' => 'Đặt bàn', 'icon' => 'calendar'],
]],
];
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f5f9">
    <title>@yield('title', 'Bảng điều khiển') — Maison du Café</title>
    @if (is_file(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/css/internal/sneat.css', 'resources/js/app.js'])
    @else
    <link rel="stylesheet" href="{{ asset('assets/css/app.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.bundle.css') }}">
    <script src="{{ asset('assets/js/app.bundle.js') }}" defer></script>
    @endif
</head>

<body class="sneat-app {{ $isAdmin ? 'sneat-admin' : 'sneat-staff' }}">
    <a class="skip-link" href="#main-content">Đến nội dung chính</a>
    <div class="sneat-layout" data-sneat-layout>
        <aside class="sneat-sidebar" id="work-sidebar">
            <a href="{{ $isAdmin ? route('admin.home') : route('staff.home') }}" class="sneat-logo">
                <span class="sneat-logo-mark"><x-internal.icon name="coffee" size="24" /></span>
                <span>Maison <strong>du Café</strong><small>{{ $isAdmin ? 'Khu vực Admin' : 'STAFF WORKSPACE' }}</small></span>
            </a>
            <nav aria-label="Điều hướng nội bộ" class="sneat-nav">
                @if ($isAdmin)
                    <x-internal.admin-sidebar />
                @else
                @foreach ($menus as $group)
                <p class="sneat-nav-section">{{ $group['label'] }}</p>
                @foreach($group['links'] as $entry)
                <a href="{{ route($entry['route']) }}" class="sneat-nav-link {{ request()->routeIs(str_replace('.index', '.*', $entry['route'])) || request()->routeIs($entry['route']) ? 'is-active' : '' }}" @if(request()->routeIs(str_replace('.index', '.*', $entry['route'])) || request()->routeIs($entry['route'])) aria-current="page" @endif>
                    <x-internal.icon :name="$entry['icon']" size="19" /><span>{{ $entry['text'] }}</span>
                </a>
                @endforeach
                @endforeach
                @endif
            </nav>
            <div class="sneat-sidebar-footer">
                <a href="{{ url('/') }}"><x-internal.icon name="arrow" size="17" /> Xem website khách</a>
            </div>
        </aside>
        <div class="sneat-workspace">
            <header class="sneat-topbar">
                <div class="sneat-topbar-left">
                    <button class="sneat-menu-toggle" type="button" data-sneat-toggle aria-controls="work-sidebar" aria-expanded="false" aria-label="Mở menu"><x-internal.icon name="menu" /></button>
                    <span class="sneat-breadcrumb">Maison du Café <span>/</span> @yield('title', 'Tổng quan')</span>
                </div>
                <div class="sneat-topbar-right">
                    <span class="sneat-role">{{ $isAdmin ? 'Quản trị viên' : 'Nhân viên' }}</span>
                    <span class="sneat-avatar" title="{{ auth('web')->user()->name }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth('web')->user()->name, 0, 1)) }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="sneat-logout" type="submit" title="Đăng xuất"><x-internal.icon name="logout" size="19" /></button>
                    </form>
                </div>
            </header>
            <main class="sneat-content" id="main-content">
                @if(session('success'))<div class="sneat-alert sneat-alert--success" role="status">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="sneat-alert sneat-alert--danger" role="alert">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="sneat-alert sneat-alert--danger" role="alert"><strong>Vui lòng kiểm tra lại thông tin:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>@endif
                @yield('content')
            </main>
            <footer class="sneat-footer">
                © {{ date('Y') }} Maison du Café <span>@if(! $isAdmin && auth('web')->user()->role === 'admin')<a href="{{ route('admin.home') }}">Trang quản trị ↗</a> · @endif Thiết kế theo phong cách Sneat · Coffee Management</span>
            </footer>
        </div>
        <button type="button" class="sneat-backdrop" data-sneat-backdrop aria-label="Đóng menu"></button>
    </div>
</body>

</html>
