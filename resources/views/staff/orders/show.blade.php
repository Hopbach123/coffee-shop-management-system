@extends('layouts.internal')
@section('title', 'Chi tiết đơn hàng #' . $order->order_code)
@section('content')
<div class="sneat-page-title">
    <div>
        <p>Vận hành · Quản lý đơn hàng</p>
        <h1>Đơn hàng #{{ $order->order_code }}</h1>
        <span>Tạo lúc: {{ $order->ordered_at?->format('d/m/Y H:i') }} · Nhân viên: {{ $order->creator?->name ?? '—' }}</span>
    </div>
            <div class="order-detail__item-actions">
                @if ($order->order_type === 'dine_in' && $order->table_id)
                    <x-public.button :href="route('staff.table-checkout.show', [$order->table_id, $order->id])" variant="outline">Xem tạm tính</x-public.button>
                @endif
                @if ($order->status === 'pending' && $order->order_type === 'dine_in' && $order->table_id)
                    <x-public.button :href="route('staff.table-order.index', [$order->table_id, $order->id])" variant="outline">Gọi món tại bàn</x-public.button>
                @endif
                @if ($canCancel)
                    <form method="POST" action="{{ route('staff.orders.cancel', $order) }}" onsubmit="return confirm('Hủy đơn hàng này? Dữ liệu đơn sẽ được lưu lại.');">
                        @csrf
                        @method('PATCH')
                        <x-public.button type="submit" variant="outline">Hủy đơn</x-public.button>
                    </form>
                @endif
                <x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Danh sách đơn</x-public.button>
            </div>
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

<div class="order-detail">
        <section class="category-panel order-detail__items" aria-labelledby="items-title">
            <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Danh sách phục vụ</p><h2 id="items-title">Món trong đơn</h2></div><span>{{ $order->orderItems->count() }} món</span></div>
            @if ($order->orderItems->isEmpty())
                <div class="category-empty"><h3>Chưa có món nào</h3><p>Chọn món từ biểu mẫu bên dưới để bắt đầu đơn hàng.</p></div>
            @else
                <div class="category-table-wrap product-table-wrap"><table class="category-table order-detail__table"><caption class="sr-only">Món trong đơn hàng</caption>
                    <thead><tr><th scope="col">Món</th><th scope="col">Đơn giá</th><th scope="col">Số lượng</th><th scope="col">Tạm tính</th><th scope="col">Trạng thái</th>@if ($order->status === 'pending')<th scope="col">Thao tác</th>@endif</tr></thead>
                    <tbody>@foreach ($order->orderItems as $item)<tr>
                        <th scope="row" data-label="Món">
                            <span class="order-detail__item-name">{{ $item->product_name }}</span>
                            @if ($item->variant_name)<small>{{ $item->variant_name }}</small>@endif
                            @if ($item->note)<em>{{ $item->note }}</em>@endif
                            @foreach ($item->orderItemToppings as $itemTopping)
                                <small>{{ $itemTopping->topping_name }} × {{ $itemTopping->quantity }} (+{{ number_format((float) $itemTopping->price, 0, ',', '.') }} ₫ / phần)</small>
                            @endforeach
                        </th>
                        <td data-label="Đơn giá">{{ number_format((float) $item->unit_price, 0, ',', '.') }} đ</td><td data-label="Số lượng">{{ $item->quantity }}</td><td data-label="Tạm tính"><strong>{{ number_format((float) $item->subtotal, 0, ',', '.') }} đ</strong></td><td data-label="Trạng thái"><span class="category-status">{{ $item->status }}</span></td>
                        @if ($order->status === 'pending')<td data-label="Thao tác">@if ($item->status === 'pending')<div class="order-detail__item-actions"><form method="POST" action="{{ route('staff.orders.items.update', [$order, $item]) }}">@csrf @method('PATCH')<label class="sr-only" for="quantity-{{ $item->id }}">Số lượng {{ $item->product_name }}</label><input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ $item->quantity }}" required><x-public.button type="submit" variant="outline">Cập nhật</x-public.button></form><form method="POST" action="{{ route('staff.orders.items.destroy', [$order, $item]) }}">@csrf @method('DELETE')<x-public.button type="submit" variant="outline">Xóa món</x-public.button></form></div>@endif @if (in_array($item->status, ['pending', 'preparing', 'ready'], true))<form method="POST" action="{{ route('staff.orders.items.status.update', [$order, $item]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $item->status === 'pending' ? 'preparing' : ($item->status === 'preparing' ? 'ready' : 'served') }}"><x-public.button type="submit" variant="outline">{{ $item->status === 'pending' ? 'Bắt đầu pha chế' : ($item->status === 'preparing' ? 'Sẵn sàng' : 'Đã phục vụ') }}</x-public.button></form>@else<span class="order-detail__locked">Đã khóa</span>@endif</td>@endif
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </section>

        @if ($order->status === 'pending' && $order->orderItems->isNotEmpty())
            <section class="category-panel order-detail__toppings" aria-labelledby="toppings-title">
                <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Tùy chọn món</p><h2 id="toppings-title">Topping</h2></div><span>Thêm hoặc xóa theo từng món</span></div>
                <div class="order-detail__topping-grid">@foreach ($order->orderItems as $item)@if ($item->status === 'pending')<article class="order-detail__topping-card">
                    <header><h3>{{ $item->product_name }}</h3><p>{{ $item->variant_name ?: 'Phiên bản tiêu chuẩn' }}</p></header>
                    @if ($item->orderItemToppings->isEmpty())<p class="order-detail__empty-topping">Chưa có topping.</p>@else<ul class="order-detail__topping-list">@foreach ($item->orderItemToppings as $itemTopping)<li><span>{{ $itemTopping->topping_name }} <small>{{ number_format((float) $itemTopping->price, 0, ',', '.') }} đ / phần</small></span><div><strong>{{ number_format((float) $itemTopping->price * $itemTopping->quantity, 0, ',', '.') }} đ</strong><form method="POST" action="{{ route('staff.orders.items.toppings.update', [$order, $item, $itemTopping]) }}">@csrf @method('PATCH')<label class="sr-only" for="topping-line-quantity-{{ $itemTopping->id }}">Số lượng {{ $itemTopping->topping_name }}</label><input id="topping-line-quantity-{{ $itemTopping->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ $itemTopping->quantity }}" required><button type="submit">Lưu</button></form><form method="POST" action="{{ route('staff.orders.items.toppings.destroy', [$order, $item, $itemTopping]) }}">@csrf @method('DELETE')<button type="submit">Xóa</button></form></div></li>@endforeach</ul>@endif
                    @if ($toppings->isNotEmpty())<form method="POST" action="{{ route('staff.orders.items.toppings.store', [$order, $item]) }}" class="order-detail__topping-form">@csrf<label class="sr-only" for="topping_id-{{ $item->id }}">Chọn topping cho {{ $item->product_name }}</label><select id="topping_id-{{ $item->id }}" name="topping_id" required><option value="">Chọn topping</option>@foreach ($toppings as $topping)<option value="{{ $topping->id }}">{{ $topping->name }} · {{ number_format((float) $topping->price, 0, ',', '.') }} đ</option>@endforeach</select><label class="sr-only" for="topping-quantity-{{ $item->id }}">Số lượng topping</label><input id="topping-quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="1" required><x-public.button type="submit" variant="outline">Thêm</x-public.button></form>@else<p class="category-notice category-notice--error" role="alert">Chưa có topping đang bán.</p>@endif
                </article>@endif @endforeach</div>
            </section>
        @endif

        @if ($order->status === 'pending')
            <section class="category-panel order-detail__add-item" aria-labelledby="add-item-title">
                <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Thao tác nhanh</p><h2 id="add-item-title">Thêm món vào đơn</h2></div></div>
                @if ($variants->isEmpty())<p class="category-notice category-notice--error" role="alert">Chưa có biến thể sản phẩm đang bán.</p>@else<form method="POST" action="{{ route('staff.orders.items.store', $order) }}" class="order-detail__add-form">@csrf
                    <div class="category-field"><label for="variant_id">Sản phẩm và biến thể</label><select id="variant_id" name="variant_id" required><option value="">Chọn món</option>@foreach ($variants as $variant)<option value="{{ $variant->id }}" @selected((string) old('variant_id') === (string) $variant->id)>{{ $variant->product?->name }} · {{ $variant->name }} · {{ number_format((float) $variant->price, 0, ',', '.') }} đ</option>@endforeach</select></div>
                    <div class="category-field"><label for="quantity">Số lượng</label><input id="quantity" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ old('quantity', 1) }}" required></div>
                    <div class="category-field"><label for="item_note">Ghi chú món</label><input id="item_note" name="note" type="text" maxlength="500" value="{{ old('note') }}"></div>
                    <div class="order-detail__add-action"><x-public.button type="submit">Thêm món</x-public.button></div>
                </form>@endif
            </section>
        @endif

</div>

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
