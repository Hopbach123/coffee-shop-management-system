@extends('layouts.internal')
@section('title', 'Chi tiết đơn hàng #' . $order->order_code)
@section('content')
<div class="sneat-page-title">
    <div>
        <p>Vận hành · Quản lý đơn hàng</p>
        <h1>Đơn hàng #{{ $order->order_code }}</h1>
        <span>Tạo lúc: {{ $order->ordered_at?->format('d/m/Y H:i') }} · Nhân viên: {{ $order->creator?->name ?? '—' }}</span>
    </div>
    <a href="{{ route('staff.orders.index') }}" class="sneat-primary">← Danh sách đơn</a>
</div>

<div class="sneat-chart-grid" style="margin-bottom: 1.5rem;">
    <section class="sneat-card">
        <div class="sneat-card-heading">
            <h2>Thông tin đơn hàng</h2>
            <span class="sneat-pill {{ $order->status === 'completed' ? 'is-success' : ($order->status === 'cancelled' ? 'is-danger' : 'is-warning') }}">
                {{ ['pending'=>'Chờ xử lý','preparing'=>'Đang chuẩn bị','ready'=>'Sẵn sàng','completed'=>'Hoàn thành','cancelled'=>'Đã hủy'][$order->status] ?? $order->status }}
            </span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
            <div>
                <small style="color: var(--coffee-text-muted); display: block;">Hình thức phục vụ</small>
                <strong>{{ $order->order_type === 'takeaway' ? 'Mang đi (Takeaway)' : 'Tại bàn (Dine-in)' }}</strong>
            </div>
            <div>
                <small style="color: var(--coffee-text-muted); display: block;">Vị trí bàn</small>
                <strong>{{ $order->cafeTable ? ($order->cafeTable->table_name ?: $order->cafeTable->table_code) . ' · ' . ($order->cafeTable->area?->name ?? 'Khu vực') : '—' }}</strong>
            </div>
            <div>
                <small style="color: var(--coffee-text-muted); display: block;">Khách hàng</small>
                <strong>{{ $order->customer?->name ?? ($order->reservation?->customer_name ?? 'Khách vãng lai') }}</strong>
            </div>
            <div>
                <small style="color: var(--coffee-text-muted); display: block;">Thời gian hoàn thành</small>
                <strong>{{ $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : 'Chưa hoàn thành' }}</strong>
            </div>
        </div>
        @if($order->note)
            <div style="margin-top: 1rem; padding: 0.75rem; background: var(--coffee-bg); border-radius: var(--radius-sm); border: 1px solid var(--coffee-border);">
                <small style="color: var(--coffee-text-muted); font-weight: 600; display: block;">Ghi chú đơn:</small>
                <span>{{ $order->note }}</span>
            </div>
        @endif
    </section>

    <section class="sneat-card sneat-chart-summary">
        <div>
            <span class="sneat-eyebrow">TỔNG KẾT TÀI CHÍNH</span>
            <h2>{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</h2>
        </div>
        <div style="width: 100%; border-top: 1px solid var(--coffee-border); margin: 0.75rem 0; padding-top: 0.75rem; text-align: left; display: grid; gap: 0.5rem;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--coffee-text-muted);">Tạm tính:</span>
                <strong>{{ number_format((float) $order->subtotal, 0, ',', '.') }} ₫</strong>
            </div>
            @if((float) $order->discount_amount > 0)
                <div style="display: flex; justify-content: space-between; color: var(--coffee-success);">
                    <span>Giảm giá {{ $order->orderPromotion?->promotion ? '(' . $order->orderPromotion->promotion->name . ')' : '' }}:</span>
                    <strong>-{{ number_format((float) $order->discount_amount, 0, ',', '.') }} ₫</strong>
                </div>
            @endif
            @if((float) $order->tax_amount > 0)
                <div style="display: flex; justify-content: space-between; color: var(--coffee-text-muted);">
                    <span>Thuế:</span>
                    <span>+{{ number_format((float) $order->tax_amount, 0, ',', '.') }} ₫</span>
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; border-top: 1px dashed var(--coffee-border); padding-top: 0.5rem;">
                <span style="font-weight: 700;">Tổng thanh toán:</span>
                <strong style="color: var(--coffee-espresso);">{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</strong>
            </div>
        </div>
    </section>
</div>

<section class="sneat-card" style="margin-bottom: 1.5rem;">
    <div class="sneat-card-heading">
        <h2>Danh sách món đã gọi ({{ $order->orderItems->count() }} món)</h2>
    </div>
    <div class="sneat-responsive">
        <table class="sneat-datatable">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên món</th>
                    <th>Kích cỡ / Biến thể</th>
                    <th>Món thêm (Topping)</th>
                    <th style="text-align: right;">Đơn giá</th>
                    <th style="text-align: center;">Số lượng</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_name ?: ($item->product?->name ?? '—') }}</strong>
                            @if($item->note)
                                <small style="display: block; color: var(--coffee-text-muted);">Ghi chú: {{ $item->note }}</small>
                            @endif
                        </td>
                        <td>{{ $item->variant_name ?: ($item->productVariant?->name ?? 'Mặc định') }}</td>
                        <td>
                            @if($item->orderItemToppings->isNotEmpty())
                                <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.9rem;">
                                    @foreach($item->orderItemToppings as $itemTopping)
                                        <li>{{ $itemTopping->topping_name ?: ($itemTopping->topping?->name ?? 'Topping') }} (+{{ number_format((float)$itemTopping->price, 0, ',', '.') }}₫)</li>
                                    @endforeach
                                </ul>
                            @else
                                <span style="color: var(--coffee-text-muted);">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;">{{ number_format((float) $item->unit_price, 0, ',', '.') }} ₫</td>
                        <td style="text-align: center;"><strong>{{ $item->quantity }}</strong></td>
                        <td style="text-align: right;"><strong>{{ number_format((float) $item->subtotal, 0, ',', '.') }} ₫</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="sneat-empty">Không có món nào trong đơn này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="sneat-card">
    <div class="sneat-card-heading">
        <h2>Lịch sử thanh toán thực tế (Payment Records)</h2>
        <span class="sneat-pill is-info">Dữ liệu thực tế</span>
    </div>
    @if($order->payments->isNotEmpty())
        <div class="sneat-responsive">
            <table class="sneat-datatable">
                <thead>
                    <tr>
                        <th>Mã thanh toán</th>
                        <th>Phương thức</th>
                        <th>Số tiền</th>
                        <th>Mã giao dịch ngân hàng</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Người ghi nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->payments as $payment)
                        <tr>
                            <td><strong>{{ $payment->payment_code }}</strong></td>
                            <td>
                                @if($payment->payment_method === 'cash')
                                    💵 Tiền mặt
                                @elseif($payment->payment_method === 'bank_transfer')
                                    🏦 Chuyển khoản ngân hàng
                                @else
                                    {{ $payment->payment_method }}
                                @endif
                            </td>
                            <td><strong>{{ number_format((float) $payment->amount, 0, ',', '.') }} ₫</strong></td>
                            <td>{{ $payment->transaction_code ?: '—' }}</td>
                            <td>
                                <span class="sneat-pill {{ $payment->status === 'paid' ? 'is-success' : ($payment->status === 'failed' ? 'is-danger' : 'is-warning') }}">
                                    {{ ['pending'=>'Chờ thu tiền','paid'=>'Đã thanh toán','failed'=>'Thất bại','refunded'=>'Hoàn tiền'][$payment->status] ?? $payment->status }}
                                </span>
                            </td>
                            <td>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : ($payment->created_at?->format('d/m/Y H:i') ?? '—') }}</td>
                            <td>{{ $payment->creator?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="padding: 1.5rem; text-align: center; color: var(--coffee-text-muted);">
            <p style="margin: 0 0 0.5rem 0;">Chưa có bản ghi thanh toán nào được ghi nhận cho đơn hàng này.</p>
            <small>Đơn hàng được tạo ở trạng thái pending; chưa phát sinh giao dịch thu tiền.</small>
        </div>
    @endif
    <p class="sneat-hint" style="margin-top: 1rem;">
        <strong>Quy định vận hành:</strong> Dữ liệu thanh toán được đọc trung thực từ bảng <code>payments</code> trong cơ sở dữ liệu. Hệ thống hiện không tự ý kết luận đơn đã thanh toán khi chưa có giao dịch hợp lệ; chức năng xác nhận thu tiền / cổng thanh toán chưa có backend command xử lý và đang ở chế độ xem (Read-only).
    </p>
</section>
@endsection
