@extends('layouts.internal')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code)

@section('content')
<div class="v5-actions">
    @if($order->order_type === 'dine_in' && $order->table_id)
        <a class="cafe-btn-secondary" href="{{ route('staff.table-checkout.show', [$order->table_id, $order->id]) }}">Xem tạm tính</a>
        @if($canEdit)<a class="cafe-btn-secondary" href="{{ route('staff.table-order.index', [$order->table_id, $order->id]) }}">Gọi món tại bàn</a>@endif
    @endif
    <a class="cafe-btn-secondary" href="{{ route('staff.orders.payments.show', $order) }}">Thanh toán / Lịch sử</a>
    @if($isWebDelivery)<a class="cafe-btn-secondary" href="{{ route('staff.customer-orders.show', $order->customerRequest) }}">Giao món / Thu COD</a>@endif
</div>
@if($order->onlineOrderRequest?->pickup_at)
    <div style="background: #fef5e7; border: 1px solid #f9e79f; border-left: 4px solid var(--cafe-caramel, #ad754b); border-radius: 9px; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 0.95rem; color: #7d4e07; font-weight: 600;">
            ⏰ Giờ hẹn khách đến lấy: <strong>{{ $order->onlineOrderRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</strong> (giờ Việt Nam)
        </span>
        <span class="kitchen-channel-badge channel-scheduled">Đơn đặt hẹn trước</span>
    </div>
@endif

<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Breadcrumb">
            <span>Vận hành</span>
            <span class="cafe-breadcrumbs__separator" aria-hidden="true">/</span>
            <span>Quản lý đơn hàng</span>
        </nav>
        <div style="display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;">
            <h1 class="cafe-page-title" style="margin: 0; white-space: nowrap;">Đơn hàng #{{ $order->order_code }}</h1>
            <span class="cafe-badge {{ $order->status === 'completed' ? 'cafe-badge--success' : ($order->status === 'cancelled' ? 'cafe-badge--danger' : 'cafe-badge--warning') }}">
                {{ ['pending'=>'Chờ xử lý','preparing'=>'Đang chuẩn bị','ready'=>'Sẵn sàng','completed'=>'Hoàn thành','cancelled'=>'Đã hủy'][$order->status] ?? $order->status }}
            </span>
        </div>
        <p class="cafe-page-subtitle">Tạo lúc: {{ $order->ordered_at?->format('d/m/Y H:i') }} · Nhân viên: {{ $order->creator?->name ?? '—' }}</p>
    </div>
    <a href="{{ route('staff.orders.index') }}" class="cafe-btn cafe-btn-secondary">
        ← Danh sách đơn
    </a>
</div>

<!-- Order Overview & Financial Summary Grid -->
<div style="display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr); gap: 20px; margin-bottom: 24px;">
    <!-- Order Info Card -->
    <section class="cafe-card" style="padding: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Thông tin đơn hàng</h2>
            <div>
                @php $pt = $order->purchase_type ?? 'counter'; @endphp
                @if($pt === 'scheduled')
                    <span class="kitchen-channel-badge channel-scheduled">⏰ Hẹn đến lấy</span>
                @elseif($pt === 'online')
                    <span class="kitchen-channel-badge channel-online">🌐 Online lấy sớm</span>
                @else
                    <span class="kitchen-channel-badge channel-counter">🏪 Tại quầy</span>
                @endif
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
            <div>
                <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Hình thức phục vụ</small>
                <strong style="color: var(--cafe-espresso, #3b281f);">{{ $order->order_type === 'takeaway' ? 'Mang đi (Takeaway)' : 'Tại bàn (Dine-in)' }}</strong>
            </div>
            <div>
                <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Vị trí bàn</small>
                <strong>{{ $order->cafeTable ? ($order->cafeTable->table_name ?: $order->cafeTable->table_code) . ' · ' . ($order->cafeTable->area?->name ?? 'Khu vực') : '—' }}</strong>
            </div>
            <div>
                <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Khách hàng</small>
                <strong>{{ $order->customer?->name ?? 'Khách vãng lai' }}</strong>
            </div>
            <div>
                <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Thời gian hoàn tất</small>
                <strong>{{ $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : 'Chưa hoàn thành' }}</strong>
            </div>
            @if($order->online_request_id)
                <div>
                    <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Yêu cầu trực tuyến</small>
                    <a href="{{ route('staff.online-requests.show', $order->online_request_id) }}" style="color: var(--cafe-caramel, #ad754b); font-weight: 600; text-decoration: underline;">
                        Xem yêu cầu gốc →
                    </a>
                </div>
            @endif
        </div>

        @if($order->note)
            <div style="margin-top: 16px; padding: 10px 14px; background: var(--cafe-surface-alt, #fcf8f2); border-radius: 6px; border: 1px solid var(--cafe-border, #e9e0d6);">
                <small style="color: var(--cafe-text-muted, #7c716a); font-weight: 700; display: block; margin-bottom: 2px;">Ghi chú đơn:</small>
                <span>{{ $order->note }}</span>
            </div>
        @endif

        <!-- Actions Bar -->
        @if($order->status === 'pending' && auth('web')->user()?->canOperateSales())
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--cafe-border, #e9e0d6); display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                @if($order->isPaidInFull() && $order->allItemsServed())
                    <form method="POST" action="{{ route('staff.orders.complete', $order) }}" onsubmit="return confirm('Xác nhận hoàn tất đơn hàng này?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="cafe-btn cafe-btn-primary" style="background: var(--cafe-success, #2c7550); border-color: var(--cafe-success, #2c7550);">
                            ✓ Hoàn tất đơn hàng (Complete)
                        </button>
                    </form>
                @else
                    <div style="font-size: 0.85rem; color: var(--cafe-text-muted, #7c716a); background: var(--cafe-surface-alt, #fcf8f2); padding: 8px 14px; border-radius: 6px; border: 1px solid var(--cafe-border, #e9e0d6);">
                        @if(! $order->allItemsServed())
                            <div>⚠️ <strong>Chưa thể hoàn tất:</strong> Tất cả các món phải đạt trạng thái "Đã phục vụ (Served)".</div>
                        @endif
                        @if(! $order->isPaidInFull())
                            <div>⚠️ <strong>Chưa thể hoàn tất:</strong> Đơn hàng chưa thu đủ tiền theo quy định tài chính.</div>
                        @endif
                    </div>
                @endif

                @if($canCancel)
                    <form method="POST" action="{{ route('staff.orders.cancel', $order) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');" style="margin-left: auto;">
                        @csrf @method('PATCH')
                        <button type="submit" class="cafe-btn cafe-btn-danger">
                            ✕ Hủy đơn hàng
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </section>

    <!-- Financial Summary Card -->
    <section class="cafe-card" style="padding: 22px; display: flex; flex-direction: column;">
        <div>
            <span style="font-size: 11px; font-weight: 800; letter-spacing: 1.2px; color: var(--cafe-caramel, #ad754b); text-transform: uppercase;">TỔNG KẾT TÀI CHÍNH</span>
            <div style="font-size: 2rem; font-weight: 800; color: var(--cafe-espresso, #3b281f); font-variant-numeric: tabular-nums; margin: 8px 0 0; white-space: nowrap;">
                {{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫
            </div>
        </div>
        <div style="width: 100%; border-top: 1px solid var(--cafe-border, #e9e0d6); margin-top: 16px; padding-top: 14px; display: grid; gap: 8px; font-size: 0.95rem;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--cafe-text-muted, #7c716a);">Tạm tính:</span>
                <strong style="font-variant-numeric: tabular-nums;">{{ number_format((float) $order->subtotal, 0, ',', '.') }} ₫</strong>
            </div>
            @if((float) $order->discount_amount > 0)
                <div style="display: flex; justify-content: space-between; color: var(--cafe-success, #2c7550);">
                    <span>Giảm giá {{ $order->orderPromotion?->promotion ? '(' . $order->orderPromotion->promotion->name . ')' : '' }}:</span>
                    <strong style="font-variant-numeric: tabular-nums;">-{{ number_format((float) $order->discount_amount, 0, ',', '.') }} ₫</strong>
                </div>
            @endif
            @if((float) $order->tax_amount > 0)
                <div style="display: flex; justify-content: space-between; color: var(--cafe-text-muted, #7c716a);">
                    <span>Thuế:</span>
                    <span style="font-variant-numeric: tabular-nums;">+{{ number_format((float) $order->tax_amount, 0, ',', '.') }} ₫</span>
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; font-size: 1.15rem; border-top: 1px dashed var(--cafe-border, #e9e0d6); padding-top: 10px; margin-top: 4px;">
                <span style="font-weight: 700;">Tổng thanh toán:</span>
                <strong style="color: var(--cafe-espresso, #3b281f); font-variant-numeric: tabular-nums;">{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</strong>
            </div>
        </div>
    </section>
</div>

<!-- Order Items List -->
<section class="cafe-card" style="margin-bottom: 24px; padding: 0; overflow: hidden;">
    <div style="padding: 18px 22px; border-bottom: 1px solid var(--cafe-border, #e9e0d6); display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Danh sách món đã gọi ({{ $order->orderItems->count() }} món)</h2>
    </div>
    <div style="overflow-x: auto;">
        <table class="cafe-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center; white-space: nowrap;">STT</th>
                    <th style="white-space: nowrap;">Tên món</th>
                    <th style="white-space: nowrap;">Size / Biến thể</th>
                    <th style="white-space: nowrap;">Món thêm (Topping)</th>
                    <th style="text-align: right; white-space: nowrap;">Đơn giá</th>
                    <th style="text-align: center; white-space: nowrap;">Số lượng</th>
                    <th style="text-align: right; white-space: nowrap;">Thành tiền</th>
                    <th style="text-align: center; white-space: nowrap;">Trạng thái pha / phục vụ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderItems as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>
                            <strong style="color: var(--cafe-espresso, #3b281f); font-size: 0.95rem;">{{ $item->product_name ?: ($item->product?->name ?? '—') }}</strong>
                            @if($item->note)
                                <small style="display: block; color: var(--cafe-text-muted, #7c716a); margin-top: 2px;">Ghi chú: {{ $item->note }}</small>
                            @endif
                        </td>
                        <td>{{ $item->variant_name ?: ($item->productVariant?->name ?? 'Mặc định') }}</td>
                        <td>
                            @if($item->orderItemToppings->isNotEmpty())
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach($item->orderItemToppings as $itemTopping)
                                        <span class="kitchen-toppings-pill" style="font-size: 0.78rem;">
                                            + {{ $itemTopping->topping_name ?: ($itemTopping->topping?->name ?? 'Topping') }} ({{ number_format((float)$itemTopping->price, 0, ',', '.') }}₫)
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--cafe-text-muted, #7c716a);">—</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;">{{ number_format((float) $item->unit_price, 0, ',', '.') }} ₫</td>
                        <td style="text-align: center;"><strong>{{ $item->quantity }}</strong></td>
                        <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;"><strong style="color: var(--cafe-caramel, #ad754b);">{{ number_format((float) $item->subtotal, 0, ',', '.') }} ₫</strong></td>
                        <td style="text-align: center;">
                            <span class="cafe-badge {{ $item->status === 'served' ? 'cafe-badge--success' : ($item->status === 'ready' ? 'cafe-badge--info' : ($item->status === 'preparing' ? 'cafe-badge--warning' : ($item->status === 'cancelled' ? 'cafe-badge--danger' : ''))) }}">
                                {{ ['pending'=>'Chờ pha','preparing'=>'Đang pha','ready'=>'Sẵn sàng','served'=>'Đã phục vụ','cancelled'=>'Đã hủy'][$item->status] ?? $item->status }}
                            </span>
                            @if($order->status === 'pending' && auth('web')->user()?->canOperateSales())
                                <div style="margin-top: 6px; display: flex; gap: 4px; justify-content: center; align-items: center; flex-wrap: wrap;">
                                    @if($canEdit && $item->status === 'pending')
                                        <form method="POST" action="{{ route('staff.orders.items.update', [$order, $item]) }}">
                                            @csrf @method('PATCH')
                                            <label for="item-quantity-{{ $item->id }}">Số lượng</label>
                                            <input id="item-quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="2147483647" value="{{ $item->quantity }}" required>
                                            <button type="submit" class="cafe-btn-secondary">Cập nhật</button>
                                        </form>
                                        <form method="POST" action="{{ route('staff.orders.items.destroy', [$order, $item]) }}" onsubmit="return confirm('Xóa món chưa pha khỏi đơn?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="cafe-btn-danger">Xóa món</button>
                                        </form>
                                    @endif
                                    @if($item->status === 'pending' && $canStartPreparing)
                                        <form method="POST" action="{{ route('staff.orders.items.status.update', [$order, $item]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="preparing">
                                            <button type="submit" class="cafe-btn cafe-btn-primary" style="min-height: 28px; padding: 0 10px; font-size: 0.75rem;">Bắt đầu pha chế</button>
                                        </form>
                                    @elseif($item->status === 'preparing')
                                        <form method="POST" action="{{ route('staff.orders.items.status.update', [$order, $item]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="ready">
                                            <button type="submit" class="cafe-btn cafe-btn-primary" style="min-height: 28px; padding: 0 10px; font-size: 0.75rem; background: var(--cafe-success, #2c7550); border-color: var(--cafe-success, #2c7550);">Xong</button>
                                        </form>
                                    @elseif($item->status === 'ready' && !$isWebDelivery)
                                        <form method="POST" action="{{ route('staff.orders.items.status.update', [$order, $item]) }}" style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="served">
                                            @if($order->onlineOrderRequest)
                                                <label style="font-size: 0.75rem;">Mã nhận: <input class="handover-code-input" name="pickup_code" required maxlength="50" autocomplete="off" style="width: 80px; min-height: 28px; padding: 2px 6px; font-size: 0.75rem; display: inline-block;"></label>
                                            @endif
                                            <button type="submit" class="cafe-btn cafe-btn-secondary" style="min-height: 28px; padding: 0 10px; font-size: 0.75rem;" @disabled(! $order->isPaidInFull())>Giao</button>
                                            @unless($order->isPaidInFull())<span style="font-size: 0.72rem; color: #b54640;">Thu đủ tiền trước khi giao.</span>@endunless
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: var(--cafe-text-muted, #7c716a);">Không có món nào trong đơn này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- Payment Records & Settlement -->
<section class="cafe-card" style="padding: 22px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Lịch sử thanh toán thực tế (Payment Records)</h2>
        <span class="cafe-badge cafe-badge--info">Dữ liệu thực tế</span>
    </div>

    @if($order->payments->isNotEmpty())
        <div style="overflow-x: auto; margin-top: 14px;">
            <table class="cafe-table">
                <thead>
                    <tr>
                        <th style="white-space: nowrap;">Mã thanh toán</th>
                        <th style="white-space: nowrap;">Phương thức</th>
                        <th style="text-align: right; white-space: nowrap;">Số tiền</th>
                        <th style="white-space: nowrap;">Mã GD ngân hàng</th>
                        <th style="text-align: center; white-space: nowrap;">Trạng thái</th>
                        <th style="white-space: nowrap;">Thời gian</th>
                        <th style="white-space: nowrap;">Người ghi nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->payments as $payment)
                        <tr>
                            <td><strong style="white-space: nowrap;">{{ $payment->payment_code }}</strong></td>
                            <td>
                                @if($payment->payment_method === 'cash')
                                    💵 Tiền mặt
                                @elseif($payment->payment_method === 'bank_transfer')
                                    🏦 Chuyển khoản ngân hàng
                                @else
                                    {{ $payment->payment_method }}
                                @endif
                            </td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;"><strong style="color: var(--cafe-caramel, #ad754b);">{{ number_format((float) $payment->amount, 0, ',', '.') }} ₫</strong></td>
                            <td>{{ $payment->transaction_code ?: '—' }}</td>
                            <td style="text-align: center;">
                                <span class="cafe-badge {{ $payment->status === 'paid' ? 'cafe-badge--success' : ($payment->status === 'failed' ? 'cafe-badge--danger' : 'cafe-badge--warning') }}">
                                    {{ ['pending'=>'Chờ thu tiền','paid'=>'Đã thanh toán','failed'=>'Thất bại','refunded'=>'Hoàn tiền'][$payment->status] ?? $payment->status }}
                                </span>
                            </td>
                            <td style="white-space: nowrap;">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : ($payment->created_at?->format('d/m/Y H:i') ?? '—') }}</td>
                            <td>{{ $payment->creator?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="padding: 24px; text-align: center; color: var(--cafe-text-muted, #7c716a);">
            <p style="margin: 0 0 4px; font-weight: 600;">Chưa có bản ghi thanh toán nào được ghi nhận cho đơn hàng này.</p>
            <small>Đơn hàng được tạo ở trạng thái pending; chưa phát sinh giao dịch thu tiền.</small>
        </div>
    @endif

    @if(!$isWebDelivery && $order->status === 'pending' && ! $order->isPaidInFull() && auth('web')->user()?->canOperateSales())
        <div style="margin-top: 18px; padding: 18px; background: var(--cafe-surface-alt, #fcf8f2); border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 9px;">
            <h3 style="margin-top: 0; font-size: 1.05rem; color: var(--cafe-espresso, #3b281f);">💵 Ghi nhận thu tiền tại quầy / Đối soát ngân hàng</h3>
            @if($paymentSchemaReady)
            <form method="POST" action="{{ route('staff.orders.payments.store', $order) }}" style="display: grid; gap: 12px; max-width: 500px;">
                @csrf
                <input type="hidden" name="operation_key" value="{{ old('operation_key', $paymentOperation) }}">
                <div>
                    <label for="receipt-method" style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px;">Phương thức</label>
                    <select name="payment_method" id="receipt-method" class="cafe-select" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;">
                        <option value="cash" @selected(old('payment_method') === 'cash')>Tiền mặt</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Chuyển khoản</option>
                    </select>
                </div>
                <div>
                    <label for="receipt-received" style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px;">Tiền khách đưa / chuyển khoản thực nhận (VND)</label>
                    <input id="receipt-received" name="received_amount" type="text" inputmode="decimal" required value="{{ old('received_amount') }}" aria-describedby="receipt-help" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;">
                    <p id="receipt-help" style="margin: 4px 0 0; font-size: 0.78rem; color: var(--cafe-text-muted, #7c716a);">Tiền mặt được trả lại tiền thừa; chuyển khoản phải khớp tổng đơn. Backend kiểm tra tổng tiền từ đơn.</p>
                </div>
                <div>
                    <label for="receipt-reference" style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px;">Mã giao dịch ngân hàng (bắt buộc khi chuyển khoản)</label>
                    <input id="receipt-reference" name="transaction_code" maxlength="100" value="{{ old('transaction_code') }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: flex; gap: 8px; align-items: center; font-size: 0.85rem; cursor: pointer;">
                        <input type="checkbox" name="received_confirmation" value="1" required @checked(old('received_confirmation'))>
                        <span>Tôi đã kiểm đếm tiền mặt hoặc xác minh ngân hàng thực nhận.</span>
                    </label>
                </div>
                <button type="submit" class="cafe-btn cafe-btn-primary" style="justify-content: center;">
                    Xác nhận thu tiền
                </button>
                <p style="margin: 0; font-size: 0.78rem; color: var(--cafe-text-muted, #7c716a);">Chỉ bấm khi đã thực nhận. Nếu mạng lỗi, gửi lại cùng thao tác để nhận receipt cũ.</p>
            </form>
            @else
                <p role="status">Schema receipt chưa sẵn sàng. Chưa cho phép thu tiền trước khi migration được kiểm tra và áp dụng.</p>
            @endif
        </div>
    @endif

    <p style="margin-top: 18px; line-height: 1.4; font-size: 0.82rem; color: var(--cafe-text-muted, #7c716a);">
        <strong>Quy định vận hành:</strong> Dữ liệu thanh toán được đọc trung thực từ bảng <code>payments</code> trong cơ sở dữ liệu. Thu đủ tiền là điều kiện bắt buộc cùng với phục vụ đủ món để hoàn tất đơn hàng.
    </p>
</section>

        @if ($canEdit && $order->orderItems->isNotEmpty())
            <section class="category-panel order-detail__toppings" aria-labelledby="toppings-title">
                <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Tùy chọn món</p><h2 id="toppings-title">Topping</h2></div><span>Thêm hoặc xóa theo từng món</span></div>
                <div class="order-detail__topping-grid">@foreach ($order->orderItems as $item)@if ($item->status === 'pending')<article class="order-detail__topping-card">
                    <header><h3>{{ $item->product_name }}</h3><p>{{ $item->variant_name ?: 'Phiên bản tiêu chuẩn' }}</p></header>
                    <p>Tổng topping: <x-public.money :amount="$item->topping_amount" /></p>
                    @if ($item->orderItemToppings->isEmpty())<p class="order-detail__empty-topping">Chưa có topping.</p>@else<ul class="order-detail__topping-list">@foreach ($item->orderItemToppings as $itemTopping)<li><span>{{ $itemTopping->topping_name }} <small><x-public.money :amount="$itemTopping->price" /> / phần</small></span><div><strong>{{ $itemTopping->quantity }} phần</strong><form method="POST" action="{{ route('staff.orders.items.toppings.update', [$order, $item, $itemTopping]) }}">@csrf @method('PATCH')<label class="sr-only" for="topping-line-quantity-{{ $itemTopping->id }}">Số lượng {{ $itemTopping->topping_name }}</label><input id="topping-line-quantity-{{ $itemTopping->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ $itemTopping->quantity }}" required><button type="submit">Lưu</button></form><form method="POST" action="{{ route('staff.orders.items.toppings.destroy', [$order, $item, $itemTopping]) }}">@csrf @method('DELETE')<button type="submit">Xóa</button></form></div></li>@endforeach</ul>@endif
                    @if ($toppings->isNotEmpty())<form method="POST" action="{{ route('staff.orders.items.toppings.store', [$order, $item]) }}" class="order-detail__topping-form">@csrf<label class="sr-only" for="topping_id-{{ $item->id }}">Chọn topping cho {{ $item->product_name }}</label><select id="topping_id-{{ $item->id }}" name="topping_id" required><option value="">Chọn topping</option>@foreach ($toppings as $topping)<option value="{{ $topping->id }}">{{ $topping->name }} · <x-public.money :amount="$topping->price" /></option>@endforeach</select><label class="sr-only" for="topping-quantity-{{ $item->id }}">Số lượng topping</label><input id="topping-quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="1" required><x-public.button type="submit" variant="outline">Thêm</x-public.button></form>@else<p class="category-notice category-notice--error" role="alert">Chưa có topping đang bán.</p>@endif
                </article>@endif @endforeach</div>
            </section>
        @endif

        @if ($canEdit)
            <section class="category-panel order-detail__add-item" aria-labelledby="add-item-title">
                <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Thao tác nhanh</p><h2 id="add-item-title">Thêm món vào đơn</h2></div></div>
                @if ($variants->isEmpty())<p class="category-notice category-notice--error" role="alert">Chưa có biến thể sản phẩm đang bán.</p>@else<form method="POST" action="{{ route('staff.orders.items.store', $order) }}" class="order-detail__add-form">@csrf
                    <div class="category-field"><label for="variant_id">Sản phẩm và biến thể</label><select id="variant_id" name="variant_id" required><option value="">Chọn món</option>@foreach ($variants as $variant)<option value="{{ $variant->id }}" @selected((string) old('variant_id') === (string) $variant->id)>{{ $variant->product?->name }} · {{ $variant->name }} · <x-public.money :amount="$variant->price" /></option>@endforeach</select></div>
                    <div class="category-field"><label for="quantity">Số lượng</label><input id="quantity" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ old('quantity', 1) }}" required></div>
                    <div class="category-field"><label for="item_note">Ghi chú món</label><input id="item_note" name="note" type="text" maxlength="500" value="{{ old('note') }}"></div>
                    <div class="order-detail__add-action"><x-public.button type="submit">Thêm món</x-public.button></div>
                </form>@endif
            </section>
        @endif

@endsection
