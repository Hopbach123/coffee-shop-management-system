@extends('layouts.internal')
@section('title', 'Dashboard · Tổng quan')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="category-heading"><div>
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Dashboard · Tổng quan</h1>
        <p>Đơn hàng và thanh toán trên toàn bộ dữ liệu, không giới hạn ngày. Trạng thái bàn tại thời điểm tải trang.</p>
    </div></div>
    <div class="dashboard-orders dashboard-orders--kpis">
        <section class="category-panel dashboard-orders__kpi" aria-labelledby="total-orders-label" aria-describedby="total-orders-definition">
            <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 4h10v17l-3-2-2 2-2-2-3 2V4Z"/><path d="M9 4V2h6v2M10 9h4M10 13h4"/></svg>
            <h2 id="total-orders-label">Tổng đơn hàng</h2>
            <p class="dashboard-orders__total">{{ number_format($orderStatistics['total'], 0, ',', '.') }}</p>
            <p id="total-orders-definition">Bao gồm đơn đang xử lý, hoàn tất và đã hủy.</p>
        </section>
        <section class="category-panel dashboard-orders__kpi" aria-labelledby="paid-value-label" aria-describedby="paid-value-definition">
            <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
            <h2 id="paid-value-label">Tổng thanh toán thành công</h2>
            @if ($revenueStatistics['available'])
                <p class="dashboard-orders__total">@include('admin.dashboard._vnd', ['amount' => $revenueStatistics['amount']])</p>
            @else
                <p class="category-notice category-notice--error" role="status">Chưa xác định — cần kiểm tra dữ liệu thanh toán.</p>
            @endif
            <p id="paid-value-definition">Tổng giá trị giao dịch đang ở trạng thái đã thanh toán (paid), trên toàn bộ thời gian. Không bao gồm giao dịch chờ xử lý, thất bại hoặc đã hoàn tiền. Đây không phải doanh thu thuần.</p>
        </section>
        <section class="category-panel dashboard-orders__kpi" aria-labelledby="total-tables-label" aria-describedby="total-tables-definition">
            <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 8h18v5H3zM6 13v7M18 13v7M8 8V4M16 8V4"/></svg>
            <h2 id="total-tables-label">Tổng số bàn</h2>
            <p class="dashboard-orders__total">{{ number_format($tableStatistics['total'], 0, ',', '.') }}</p>
            <p id="total-tables-definition">Bàn chưa xóa mềm, bao gồm bàn bảo trì và bàn thuộc khu vực ngừng hoạt động.</p>
        </section>
    </div>
    <div class="dashboard-orders dashboard-orders--details">
        <section class="category-panel" aria-labelledby="order-status-title">
            <h2 id="order-status-title">Phân bố trạng thái</h2>
            <dl class="dashboard-orders__statuses">
                @foreach ($orderStatistics['by_status'] as $status => $count)
                    <div><dt>{{ $orderStatusLabels[$status] ?? 'Trạng thái chưa xác định' }}</dt><dd>{{ number_format($count, 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
            @if ($orderStatistics['total'] === 0)<p>Chưa có đơn hàng được ghi nhận.</p>@endif
        </section>
        <section class="category-panel" aria-labelledby="table-status-title">
            <h2 id="table-status-title">Trạng thái bàn hiện tại</h2>
            <p>Không xác nhận khả dụng cho thời điểm đặt bàn trong tương lai.</p>
            <dl class="dashboard-orders__statuses">
                @foreach ($tableStatistics['by_status'] as $status => $count)
                    <div><dt class="dashboard-table-status dashboard-table-status--{{ $status }}">{{ $tableStatusLabels[$status] }}</dt><dd>{{ number_format($count, 0, ',', '.') }}</dd></div>
                @endforeach
                @if ($tableStatistics['unclassified'] > 0)
                    <div><dt>Chưa phân loại — cần kiểm tra dữ liệu</dt><dd>{{ number_format($tableStatistics['unclassified'], 0, ',', '.') }}</dd></div>
                @endif
            </dl>
            @if ($tableStatistics['total'] === 0)<p>Chưa có bàn được ghi nhận.</p>@endif
        </section>
    </div>
</section>
@endsection
