@extends('layouts.internal')
@section('title', 'Dashboard')
@section('content')
@php
    try {
        $hasOrders = \Illuminate\Support\Facades\Schema::hasTable('orders');
        $dailyTotals = $hasOrders ? \App\Models\Order::query()->where('status', 'completed')
            ->whereDate('completed_at', '>=', today()->subDays(6))->get(['completed_at', 'total_amount'])
            ->groupBy(fn ($order) => $order->completed_at?->format('Y-m-d'))
            ->map(fn ($orders) => $orders->sum('total_amount')) : collect();
        $lastSevenDays = collect(range(6, 0))->map(function ($daysAgo) use ($dailyTotals) {
            $day = today()->subDays($daysAgo);
            return ['date' => $day->format('d/m'), 'amount' => (float) ($dailyTotals[$day->format('Y-m-d')] ?? 0)];
        });
        $maximum = max(1, (float) $lastSevenDays->max('amount'));
        $revenueChart = $lastSevenDays->map(fn ($day) => [...$day, 'height' => $day['amount'] > 0 ? max(3, round($day['amount'] * 100 / $maximum)) : 0]);
        $weeklyRevenue = $lastSevenDays->sum('amount');
        $revenue = $hasOrders ? \App\Models\Order::query()->where('status', 'completed')->whereDate('completed_at', today())->sum('total_amount') : 0;
        $todayOrders = $hasOrders ? \App\Models\Order::query()->whereDate('ordered_at', today())->count() : 0;
        $pendingOrders = $hasOrders ? \App\Models\Order::query()->where('status', 'pending')->count() : 0;
        $productsCount = \Illuminate\Support\Facades\Schema::hasTable('products') ? \App\Models\Product::count() : 0;
        $categoryCount = \Illuminate\Support\Facades\Schema::hasTable('categories') ? \App\Models\Category::count() : 0;
        $promotionCount = \Illuminate\Support\Facades\Schema::hasTable('promotions') ? \App\Models\Promotion::count() : 0;
        $tableCount = \Illuminate\Support\Facades\Schema::hasTable('cafe_tables') ? \App\Models\CafeTable::count() : 0;
        $customerCount = \Illuminate\Support\Facades\Schema::hasTable('customers') ? \App\Models\Customer::count() : 0;
        $recentOrders = $hasOrders ? \App\Models\Order::with('creator')->orderByDesc('id')->limit(6)->get() : collect();
    } catch (\Throwable $e) {
        $lastSevenDays = collect(range(6, 0))->map(fn ($d) => ['date' => today()->subDays($d)->format('d/m'), 'amount' => 0, 'height' => 0]);
        $revenueChart = $lastSevenDays;
        $weeklyRevenue = 0;
        $revenue = 0;
        $todayOrders = 0;
        $pendingOrders = 0;
        $productsCount = 0;
        $categoryCount = 0;
        $promotionCount = 0;
        $tableCount = 0;
        $customerCount = 0;
        $recentOrders = collect();
    }
@endphp

<div class="sneat-page-title">
    <div>
        <p>Xin chào, {{ auth('web')->user()->name }} 👋</p>
        <h1>Tổng quan cửa hàng</h1>
        <span>Hôm nay, {{ now()->format('d/m/Y') }} · Dữ liệu từ hệ thống của bạn</span>
    </div>
    <a href="{{ route('admin.products.create') }}" class="sneat-primary">+ Thêm sản phẩm</a>
</div>

<div class="sneat-overview-grid">
    <section class="sneat-welcome">
        <div>
            <span class="sneat-eyebrow">MAISON DU CAFÉ / ADMIN</span>
            <h2>Chúc một ngày<br>kinh doanh hiệu quả!</h2>
            <p>Quản lý menu, đơn hàng và vận hành quán trong một không gian trực quan phong cách Sneat.</p>
            <a href="{{ route('staff.home') }}">Mở màn hình POS →</a>
        </div>
        <div class="sneat-welcome-art">☕<span>✦</span></div>
    </section>
    <div class="sneat-metric">
        <span class="sneat-metric-icon">↗</span>
        <small>Doanh thu hôm nay</small>
        <strong>{{ number_format((float) $revenue, 0, ',', '.') }} ₫</strong>
        <span>Đơn đã hoàn thành</span>
    </div>
    <div class="sneat-metric">
        <span class="sneat-metric-icon second">◫</span>
        <small>Đơn hôm nay</small>
        <strong>{{ $todayOrders }}</strong>
        <span>Đơn phát sinh hôm nay</span>
    </div>
</div>

<div class="sneat-chart-grid">
    <section class="sneat-card">
        <div class="sneat-card-heading">
            <h2>Doanh thu 7 ngày gần nhất</h2>
            <span>Dữ liệu đơn đã hoàn thành</span>
        </div>
        <div class="sneat-chart-bars" role="img" aria-label="Biểu đồ doanh thu từng ngày trong 7 ngày gần nhất">
            @foreach($revenueChart as $bar)
                <div class="sneat-chart-column">
                    <div class="sneat-chart-track" title="{{ $bar['date'] }}: {{ number_format($bar['amount'],0,',','.') }}đ">
                        <div class="sneat-chart-bar" style="height: {{ $bar['height'] }}%"></div>
                    </div>
                    <span>{{ $bar['date'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
    <section class="sneat-card sneat-chart-summary">
        <div>
            <span class="sneat-eyebrow">TỔNG DOANH THU</span>
            <h2>7 ngày vừa qua</h2>
        </div>
        <div class="sneat-revenue-ring">
            <span>{{ $todayOrders }}<small>Đơn hôm nay</small></span>
        </div>
        <strong>{{ number_format($weeklyRevenue, 0, ',', '.') }} ₫</strong>
        <p>Doanh thu được cộng dồn từ các hóa đơn hoàn thành trong tuần.</p>
    </section>
</div>

<div class="sneat-stats-grid">
    <section class="sneat-card">
        <div class="sneat-card-heading">
            <h2>Tình hình vận hành</h2>
            <span>Thời điểm hiện tại</span>
        </div>
        <div class="sneat-quick-stats">
            <div>
                <span>Đơn chờ xử lý</span>
                <strong>{{ $pendingOrders }}</strong>
                <div class="sneat-progress"><i style="width:{{ min(100, $pendingOrders * 10) }}%"></i></div>
            </div>
            <div>
                <span>Bàn phục vụ</span>
                <strong>{{ $tableCount }}</strong>
                <div class="sneat-progress"><i style="width:{{ min(100, $tableCount * 5) }}%"></i></div>
            </div>
            <div>
                <span>Sản phẩm đang quản lý</span>
                <strong>{{ $productsCount }}</strong>
                <div class="sneat-progress"><i style="width:{{ min(100, $productsCount * 2) }}%"></i></div>
            </div>
            <div>
                <span>Danh mục</span>
                <strong>{{ $categoryCount }}</strong>
                <div class="sneat-progress"><i style="width:{{ min(100, $categoryCount * 8) }}%"></i></div>
            </div>
        </div>
    </section>
    <section class="sneat-card sneat-card--highlight">
        <span class="sneat-eyebrow">QUẢN LÝ NHANH</span>
        <h2>Mọi thứ trong tầm tay</h2>
        <p>Chỉnh sửa thực đơn, cập nhật ưu đãi và theo dõi mặt bằng quán nhanh chóng.</p>
        <div class="sneat-shortcuts">
            <a href="{{ route('admin.categories.index') }}">Danh mục ↗</a>
            <a href="{{ route('admin.products.index') }}">Sản phẩm ↗</a>
            <a href="{{ route('admin.promotions.index') }}">Khuyến mãi ({{ $promotionCount }}) ↗</a>
            <a href="{{ route('admin.tables.index') }}">Bàn cà phê ↗</a>
        </div>
    </section>
</div>

<section class="sneat-card sneat-recent">
    <div class="sneat-card-heading">
        <h2>Đơn hàng gần đây</h2>
        <a href="{{ route('staff.home') }}">Vào màn hình POS →</a>
    </div>
    <div class="sneat-responsive">
        <table class="sneat-datatable">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Thời gian</th>
                    <th>Nhân viên</th>
                    <th>Hình thức</th>
                    <th>Trạng thái</th>
                    <th>Tổng tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td><strong>{{ $order->order_code }}</strong></td>
                        <td>{{ $order->ordered_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $order->creator?->name ?? '—' }}</td>
                        <td>{{ $order->order_type === 'takeaway' ? 'Mang đi' : 'Tại bàn' }}</td>
                        <td>
                            <span class="sneat-pill {{ $order->status === 'completed' ? 'is-success' : 'is-warning' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ number_format((float)$order->total_amount, 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="sneat-empty">Chưa có đơn hàng nào. Số liệu sẽ hiển thị khi có giao dịch thật.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
