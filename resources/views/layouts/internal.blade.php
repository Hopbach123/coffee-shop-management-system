@php
    $isAdmin = request()->routeIs('admin.*');
    $accountIsAdmin = auth('web')->user()?->role === 'admin';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2c1b14">
    <title>@yield('title','Bảng điều khiển') — Maison du Café</title>
    @if (is_file(public_path('build/manifest.json')))
        @vite(['resources/css/app.css','resources/css/internal/sneat.css','resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/app.bundle.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/admin.bundle.css') }}">
        <script src="{{ asset('assets/js/app.bundle.js') }}" defer></script>
    @endif
</head>
<body class="sneat-app {{ $isAdmin ? 'sneat-admin' : 'sneat-staff' }} {{ $isAdmin && !request()->routeIs('admin.menus.*') ? 'admin-v5' : '' }}">
    <a class="skip-link" href="#main-content">Đến nội dung chính</a>
    @php
        $isAdmin = request()->routeIs('admin.*');
        $staffMenus = [
            ['label' => 'BÁN HÀNG & VẬN HÀNH', 'links' => [
                ['route'=>'staff.home','text'=>'POS / Bán tại quầy','icon'=>'bag'],
                ['route'=>'staff.orders.index','text'=>'Danh sách đơn','icon'=>'receipt'],
                ['route'=>'staff.customer-orders.index','text'=>'Đơn giao từ web','icon'=>'receipt'],
                ['route'=>'staff.online-requests.index','text'=>'Yêu cầu online / Đặt hẹn','icon'=>'box'],
                ['route'=>'staff.brewing.index','text'=>'Pha chế','icon'=>'chef'],
                ['route'=>'staff.tables.index','text'=>'Sơ đồ bàn','icon'=>'table']
            ]],
        ];
    @endphp
    <div class="sneat-layout" data-sneat-layout>
        <aside class="sneat-sidebar" id="work-sidebar">
            <button type="button" class="sneat-sidebar-close" data-sneat-close aria-label="Đóng menu"><x-internal.icon name="close" size="20" /></button>
            <a href="{{ $isAdmin ? route('admin.home') : route('staff.home') }}" class="sneat-logo">
                <span class="sneat-logo-mark"><x-internal.icon name="coffee" size="24" /></span>
                <span>Maison <strong>du Café</strong><small>{{ $isAdmin ? 'KHU VỰC QUẢN TRỊ · ADMIN' : 'KHU VỰC NHÂN VIÊN · STAFF' }}</small></span>
            </a>
            <nav aria-label="Điều hướng nội bộ" class="sneat-nav">
                @if ($isAdmin)
                    <x-internal.fixed-admin-sidebar />

                @else
                    @foreach ($staffMenus as $group)
                        <p class="sneat-nav-section">{{ $group['label'] }}</p>
                        @foreach($group['links'] as $entry)
                            <a href="{{ route($entry['route']) }}" class="sneat-nav-link {{ request()->routeIs(str_replace('.index', '.*', $entry['route'])) || request()->routeIs($entry['route']) ? 'is-active' : '' }}" @if(request()->routeIs(str_replace('.index', '.*', $entry['route'])) || request()->routeIs($entry['route'])) aria-current="page" @endif>
                                <x-internal.icon :name="$entry['icon']" size="19"/><span>{{ $entry['text'] }}</span>
                            </a>
                        @endforeach
                    @endforeach
                @endif
            </nav>
            <div class="sneat-sidebar-footer"><a href="{{ route('home') }}"><x-internal.icon name="arrow" size="17"/> Xem website khách</a></div>
        </aside>
        <div class="sneat-workspace">
            <header class="sneat-topbar">
                <div class="sneat-topbar-left">
                    <button class="sneat-menu-toggle" type="button" data-sneat-toggle aria-controls="work-sidebar" aria-expanded="true" aria-label="Thu gọn menu"><x-internal.icon name="menu" /></button>
                    <div class="sneat-workspace-heading">
                        <span class="sneat-workspace-label">{{ $isAdmin ? 'Quản trị cửa hàng' : 'Khu vực Staff · Bán hàng / POS' }}</span>
                        <span class="sneat-breadcrumb">@yield('title','Tổng quan')</span>
                    </div>
                </div>
                <div class="sneat-topbar-right">
                    @if ($accountIsAdmin)
                        <a href="{{ $isAdmin ? route('staff.home') : route('admin.home') }}" class="cafe-btn-secondary sneat-workspace-switch">
                            <x-internal.icon :name="$isAdmin ? 'bag' : 'grid'" size="18" />
                            <span>{{ $isAdmin ? 'Mở POS' : 'Về Quản trị' }}</span>
                        </a>
                    @endif
                    <div class="sneat-user-pill">
                        <span class="sneat-avatar" title="{{ auth('web')->user()?->name }}">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth('web')->user()?->name ?? 'U', 0, 2)) }}</span>
                        <div class="sneat-user-meta">
                            <span class="sneat-user-name">{{ auth('web')->user()?->name ?? 'Người dùng' }}</span>
                            <span class="sneat-role-badge">{{ $accountIsAdmin ? 'Admin' : 'Staff' }}</span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="sneat-logout" type="submit" title="Đăng xuất" aria-label="Đăng xuất"><x-internal.icon name="logout" size="18" /></button></form>
                </div>
            </header>
            <main class="sneat-content" id="main-content">
                @if(session('success'))<div class="sneat-alert sneat-alert--success" role="status">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="sneat-alert sneat-alert--danger" role="alert">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="sneat-alert sneat-alert--danger" role="alert"><strong>Vui lòng kiểm tra lại thông tin:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
            <footer class="sneat-footer">© {{ date('Y') }} Maison du Café <span>@if(! $isAdmin && auth('web')->user()->role === 'admin')<a href="{{ route('admin.home') }}">Trang quản trị ↗</a> · @endif Hệ thống quản trị &amp; vận hành · Warm Editorial Coffee System</span></footer>
        </div>
        <button type="button" class="sneat-backdrop" data-sneat-backdrop aria-label="Đóng menu"></button>
    </div>
</body>
</html>
