@extends('layouts.internal')
@section('title', 'Đơn hàng')
@section('content')
<div class="cafe-page-header">
    <div>
        <div class="cafe-breadcrumbs">
            <span>Bán hàng & Vận hành</span>
            <span>›</span>
            <span>Danh sách đơn hàng</span>
        </div>
        <h1 class="cafe-page-title">Đơn hàng</h1>
        <p class="cafe-page-subtitle">Quản lý danh sách đơn hàng bán tại quầy và nhận trực tuyến.</p>
    </div>
    <div>
        <a href="{{ route('staff.home') }}" class="cafe-btn-primary">
            <x-internal.icon name="plus" size="18" /> Tạo đơn tại quầy
        </a>
    </div>
</div>

<div class="cafe-card">
    <div class="cafe-table-wrap">
        <table class="cafe-table cafe-orders-table">
            <thead>
                <tr>
                    <th scope="col">Mã đơn</th>
                    <th scope="col" style="width: 120px;">Thời gian</th>
                    <th scope="col" style="width: 130px;">Kênh / Loại</th>
                    <th scope="col" style="width: 100px; text-align: center;">Bàn</th>
                    <th scope="col">Nhân viên tạo</th>
                    <th scope="col" style="width: 90px; text-align: center;">Số món</th>
                    <th scope="col" style="width: 130px; text-align: right;">Tổng tiền</th>
                    <th scope="col" style="width: 130px; text-align: center;">Trạng thái</th>
                    <th scope="col" style="width: 110px; text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('staff.orders.show', $order) }}" style="color: var(--cafe-caramel); font-weight: 700; text-decoration: underline; font-family: monospace; font-size: 0.9375rem;">
                                #{{ $order->order_code }}
                            </a>
                        </td>
                        <td style="color: var(--cafe-text-secondary); font-size: 0.8125rem;">
                            {{ $order->ordered_at?->format('d/m H:i') }}
                        </td>
                        <td>
                            <span class="cafe-badge cafe-badge--info">
                                {{ $order->order_type === 'takeaway' ? 'Mang đi' : ($order->order_type === 'dine_in' ? 'Tại bàn' : $order->order_type) }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            @if($order->cafeTable)
                                <strong style="color: var(--cafe-espresso);">{{ $order->cafeTable->table_name ?: $order->cafeTable->table_code }}</strong>
                            @else
                                <span style="color: var(--cafe-text-secondary);">—</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color: var(--cafe-espresso);">{{ $order->creator?->name ?? '—' }}</strong>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            {{ $order->orderItems->sum('quantity') }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--cafe-espresso);">
                            {{ number_format((float)$order->total_amount, 0, ',', '.') }} ₫
                        </td>
                        <td style="text-align: center;">
                            @php
                                $statusBadge = match($order->status) {
                                    'completed' => 'success',
                                    'preparing' => 'info',
                                    'ready' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <span class="sneat-pill cafe-badge cafe-badge--{{ $statusBadge }} {{ $order->status === 'completed' ? 'is-success' : 'is-warning' }}">
                                {{ ['pending' => 'Chờ xử lý', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'][$order->status] ?? $order->status }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('staff.orders.show', $order) }}" class="cafe-btn-secondary" style="padding: 5px 10px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;">
                                Chi tiết →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="sneat-empty" style="text-align: center; padding: 48px 20px;">
                            <x-internal.icon name="receipt" size="36" style="color: var(--cafe-caramel); margin-bottom: 8px;" />
                            <p style="margin: 0; color: var(--cafe-text-secondary); font-weight: 600;">Chưa có đơn hàng nào.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="sneat-pagination" style="margin-top: 18px; display: flex; justify-content: flex-end;">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
