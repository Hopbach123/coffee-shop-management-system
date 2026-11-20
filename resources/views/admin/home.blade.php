@extends('layouts.internal')
@section('title', 'Tổng quan cửa hàng')
@section('content')
<div class="bank-overview">
    <header class="overview-heading">
        <div><p class="overview-eyebrow">MAISON DU CAFÉ / QUẢN TRỊ</p><h1>Tổng quan cửa hàng</h1><p>Xin chào, {{ auth('web')->user()->name }}. Đây là tình hình kinh doanh của quán.</p></div>
        <a href="{{ route('staff.home') }}" class="cafe-btn-primary"><x-internal.icon name="bag" size="18" /> Mở POS bán hàng</a>
    </header>
    <form class="overview-period" method="GET" action="{{ route('admin.home') }}">
        <div><span class="overview-eyebrow">KỲ THỐNG KÊ</span><strong>{{ $period['range'] ?? 'Chưa có dữ liệu theo kỳ' }}</strong><small>Ngày Việt Nam · UTC+7 @if($period) · Cập nhật {{ $period['updated_at'] }} @endif</small></div>
        <div class="overview-period__controls"><label for="overview-days">Khoảng thời gian</label><select id="overview-days" name="days"><option value="7" @selected(($period['days'] ?? 7) === 7)>7 ngày gần nhất</option><option value="30" @selected(($period['days'] ?? 7) === 30)>30 ngày gần nhất</option></select><input type="hidden" name="metric" value="{{ $period['metric'] ?? 'paid' }}"><button class="cafe-btn-secondary" type="submit">Áp dụng</button></div>
    </form>
    @if($period)
    <div class="overview-kpis" aria-label="Chỉ số trong kỳ đã chọn">
        <section class="overview-kpi overview-kpi--primary"><div class="overview-kpi__label"><span>Tiền đã thu trong kỳ</span><x-internal.icon name="wallet" /></div>
            @if($period['paid_available'])<p class="overview-kpi__value">@include('admin.dashboard._vnd', ['amount' => $period['paid']])</p><small>Thanh toán paid · theo ngày nhận tiền</small>@else<p class="overview-kpi__unavailable">Cần đối soát dữ liệu</p><small>Thiếu ngày thu hoặc dữ liệu thanh toán chưa xác minh.</small>@endif
        </section>
        <section class="overview-kpi"><div class="overview-kpi__label"><span>Giá trị đơn hoàn thành</span><x-internal.icon name="chart" /></div><p class="overview-kpi__value">@include('admin.dashboard._vnd', ['amount' => $period['completed']])</p><small>{{ $period['completed_count'] }} đơn · theo ngày hoàn thành</small></section>
        <section class="overview-kpi"><div class="overview-kpi__label"><span>Đơn phát sinh trong kỳ</span><x-internal.icon name="receipt" /></div><p class="overview-kpi__value">{{ number_format($period['orders_count'], 0, ',', '.') }} <span>đơn</span></p><small>Bao gồm đơn đang xử lý, hoàn tất và đã hủy.</small></section>
        <section class="overview-kpi"><div class="overview-kpi__label"><span>Giá trị trung bình / đơn</span><x-internal.icon name="bag" /></div><p class="overview-kpi__value">@if($period['average'] !== null) @include('admin.dashboard._vnd', ['amount' => $period['average']]) @else — @endif</p><small>Chỉ tính đơn hoàn thành trong kỳ</small></section>
    </div>
    <div class="overview-main-grid">
        <section class="overview-panel overview-chart" aria-labelledby="overview-chart-title">
            <div class="overview-panel__heading"><div><p class="overview-eyebrow">DIỄN BIẾN THEO NGÀY</p><h2 id="overview-chart-title">{{ $period['metric'] === 'paid' ? 'Tiền đã thu' : 'Giá trị đơn hoàn thành' }}</h2></div><span class="overview-unit">Đơn vị: VNĐ</span></div>
            <nav class="overview-tabs" aria-label="Chọn chỉ số biểu đồ"><a href="{{ route('admin.home', ['days' => $period['days'], 'metric' => 'paid']) }}" @if($period['metric'] === 'paid') aria-current="page" @endif>Tiền đã thu</a><a href="{{ route('admin.home', ['days' => $period['days'], 'metric' => 'completed']) }}" @if($period['metric'] === 'completed') aria-current="page" @endif>Đơn hoàn thành</a></nav>
            @if($period['metric'] === 'paid' && ! $period['paid_available'])
                <div class="overview-empty" role="status"><x-internal.icon name="wallet" size="32" /><h3>Chưa thể xác nhận số liệu thu tiền</h3><p>Kiểm tra ngày thu và các khoản thanh toán trùng trước khi sử dụng biểu đồ.</p></div>
            @else
                <div class="overview-chart__scroll" tabindex="0" role="region" aria-label="Biểu đồ theo ngày, có thể cuộn ngang trên màn hình nhỏ">
                    <svg class="overview-chart__svg" viewBox="0 0 800 270" aria-labelledby="daily-svg-title daily-svg-desc" role="img">
                        <title id="daily-svg-title">{{ $period['metric'] === 'paid' ? 'Tiền đã thu' : 'Giá trị đơn hoàn thành' }} theo ngày</title><desc id="daily-svg-desc">Từ {{ $period['range'] }}. Bảng số liệu đầy đủ ở bên dưới. Di chuột hoặc dùng Tab tới điểm để xem giá trị.</desc>
                        @foreach($period['ticks'] as $tick)<g class="overview-chart__grid"><line x1="56" x2="744" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" /><text x="46" y="{{ $tick['y'] + 4 }}" text-anchor="end">{{ $tick['label'] }}</text></g>@endforeach
                        <polygon class="overview-chart__area" points="{{ $period['area'] }}" /><polyline class="overview-chart__line" points="{{ $period['line'] }}" />
                        @foreach($period['rows'] as $point)
                            @if($point['show_label'])<text class="overview-chart__date" x="{{ $point['x'] }}" y="250" text-anchor="middle">{{ $point['date'] }}</text>@endif
                            <g class="overview-chart__point" tabindex="0" role="img" aria-label="{{ $point['full_date'] }}: {{ $point[$period['metric']] }} VNĐ"><title>{{ $point['full_date'] }}: {{ $point[$period['metric']] }} VNĐ</title><circle class="overview-chart__hit" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="11" /><circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" /><g class="overview-chart__tooltip" transform="translate({{ max(56, min(600, $point['x'] - 72)) }},{{ max(0, $point['y'] - 52) }})"><rect width="144" height="42" rx="6" /><text x="10" y="16">{{ $point['date'] }}</text><text x="10" y="32">@include('admin.dashboard._vnd', ['amount' => $point[$period['metric']]])</text></g></g>
                        @endforeach
                    </svg>
                </div>
                @if(! $period['has_chart_data'])<p class="overview-chart__empty" role="status">Chưa phát sinh số liệu cho chỉ số này trong kỳ đã chọn.</p>@endif
            @endif
            <p class="overview-note">Tiền đã thu và đơn hoàn thành ghi nhận ở hai thời điểm khác nhau; không phải doanh thu thuần hay lợi nhuận. Khoản đã hoàn tiền không tính vào tiền paid hiện tại.</p>
            <details class="overview-data"><summary>Xem số liệu từng ngày</summary><div class="cafe-table-wrap"><table class="cafe-table"><caption class="overview-sr-only">Số liệu theo ngày Việt Nam trong kỳ {{ $period['range'] }}</caption><thead><tr><th scope="col">Ngày</th><th scope="col">Tiền đã thu</th><th scope="col">Đơn hoàn thành</th><th scope="col">Giá trị hoàn thành</th></tr></thead><tbody>@foreach($period['rows'] as $row)<tr><th scope="row">{{ $row['full_date'] }}</th><td>@if($period['paid_available']) @include('admin.dashboard._vnd', ['amount' => $row['paid']]) @else Chưa xác định @endif</td><td>{{ $row['count'] }}</td><td>@include('admin.dashboard._vnd', ['amount' => $row['completed']])</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <aside class="overview-side">
            <section class="overview-panel"><div class="overview-panel__heading"><div><p class="overview-eyebrow">TRONG KỲ ĐÃ CHỌN</p><h2>Trạng thái đơn phát sinh</h2></div></div><div class="overview-statuses">@foreach($orderStatusLabels as $status => $label)<div class="overview-status"><div><span>{{ $label }}</span><strong>{{ $period['statuses'][$status] ?? 0 }}</strong></div><meter min="0" max="{{ max(1, $period['orders_count']) }}" value="{{ $period['statuses'][$status] ?? 0 }}" aria-label="{{ $label }} trên {{ $period['orders_count'] }} đơn">{{ $period['statuses'][$status] ?? 0 }}</meter></div>@endforeach @if($period['unclassified'] > 0)<p class="overview-note">Chưa phân loại: {{ $period['unclassified'] }} đơn cần kiểm tra.</p>@endif</div><a class="overview-text-link" href="{{ route('staff.orders.index') }}">Xem danh sách đơn <x-internal.icon name="arrow" size="16" /></a></section>
            <section class="overview-panel overview-operations"><p class="overview-eyebrow">VẬN HÀNH HIỆN TẠI</p><h2>Bàn &amp; đơn chờ xử lý</h2><dl><div><dt>Đơn đang xử lý</dt><dd>{{ $orderStatistics['by_status']['pending'] }}</dd></div><div><dt>Bàn trống</dt><dd>{{ $tableStatistics['by_status']['available'] }}</dd></div><div><dt>Bàn đang có khách</dt><dd>{{ $tableStatistics['by_status']['occupied'] }}</dd></div></dl><a class="overview-text-link" href="{{ route('staff.tables.index') }}">Mở sơ đồ bàn <x-internal.icon name="arrow" size="16" /></a></section>
        </aside>
    </div>
    @else
        <section class="overview-panel overview-empty" role="status"><x-internal.icon name="chart" size="32" /><h2>Chưa thể hiển thị thống kê theo kỳ</h2><p>Dữ liệu thiếu trường ngày hoặc giá trị đơn. Các thống kê toàn bộ thời gian vẫn hiển thị bên dưới.</p></section>
    @endif
    <section class="overview-panel overview-recent" aria-labelledby="recent-orders-title"><div class="overview-panel__heading"><div><p class="overview-eyebrow">GIAO DỊCH &amp; VẬN HÀNH</p><h2 id="recent-orders-title">Đơn hàng gần đây</h2></div><a class="overview-text-link" href="{{ route('staff.orders.index') }}">Xem tất cả <x-internal.icon name="arrow" size="16" /></a></div><div class="cafe-table-wrap"><table class="cafe-table"><thead><tr><th scope="col">Mã đơn</th><th scope="col">Thời gian (VN)</th><th scope="col">Nhân viên</th><th scope="col">Hình thức</th><th scope="col">Trạng thái</th><th scope="col">Tổng tiền</th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><th scope="row"><a href="{{ route('staff.orders.show', $order) }}">{{ $order->order_code }}</a></th><td>{{ $order->ordered_at?->copy()->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td><td>{{ $order->creator?->name ?? '—' }}</td><td>{{ $order->order_type === 'takeaway' ? 'Mang đi' : 'Tại bàn' }}</td><td><span class="overview-status-label overview-status-label--{{ $order->status }}">{{ $orderStatusLabels[$order->status] ?? 'Chưa phân loại' }}</span></td><td>@include('admin.dashboard._vnd', ['amount' => (string) $order->total_amount])</td></tr>@empty<tr><td colspan="6" class="overview-table-empty">Chưa có đơn hàng để hiển thị.</td></tr>@endforelse</tbody></table></div></section>
    <details class="overview-panel overview-all-time"><summary>Thống kê toàn bộ thời gian <span>Đơn hàng · Thanh toán · Bàn</span></summary><p class="overview-note">Các số liệu sau không giới hạn ngày, độc lập với bộ lọc ở đầu trang.</p>@include('admin.dashboard._all-time')</details>
</div>
@endsection
