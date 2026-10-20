@extends('layouts.internal')

@section('title', 'Chi tiết đơn hàng')

@section('content')
    <section class="category-page order-detail" aria-labelledby="page-title">
        <header class="order-detail__header">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Nhân viên</p>
                <div class="order-detail__title-row"><h1 id="page-title">{{ $order->order_code }}</h1><span class="order-detail__status">{{ $order->status }}</span></div>
                <p class="order-detail__intro">{{ $order->order_type === 'dine_in' ? 'Đơn tại chỗ' : 'Đơn mang đi' }} · tạo lúc {{ $order->ordered_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div class="order-detail__item-actions">
                @if ($canCancel)
                    <form method="POST" action="{{ route('staff.orders.cancel', $order) }}" onsubmit="return confirm('Hủy đơn hàng này? Dữ liệu đơn sẽ được lưu lại.');">
                        @csrf
                        @method('PATCH')
                        <x-public.button type="submit" variant="outline">Hủy đơn</x-public.button>
                    </form>
                @endif
                <x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Danh sách đơn</x-public.button>
            </div>
        </header>

        <div class="order-detail__overview">
            <section class="category-panel order-detail__facts" aria-label="Thông tin đơn hàng">
                <div class="order-detail__fact"><span>Khách hàng</span><strong>{{ $order->customer?->name ?? 'Khách vãng lai' }}</strong>@if ($order->customer?->phone)<small>{{ $order->customer->phone }}</small>@endif</div>
                <div class="order-detail__fact"><span>Bàn phục vụ</span><strong>{{ $order->cafeTable?->table_code ?? '—' }}</strong><small>{{ $order->order_type === 'dine_in' ? 'Phục vụ tại quán' : 'Không áp dụng' }}</small></div>
                <div class="order-detail__fact"><span>Nhân viên tạo</span><strong>{{ $order->creator?->name ?? '—' }}</strong><small>{{ $order->note ?: 'Không có ghi chú' }}</small></div>
            </section>
            <aside class="category-panel order-detail__total" aria-label="Tổng thanh toán">
                <span>Tạm tính</span><strong>{{ number_format((float) $order->subtotal, 0, ',', '.') }} đ</strong>
                @if ($order->discount_amount !== '0.00')<small>Giảm giá: {{ number_format((float) $order->discount_amount, 0, ',', '.') }} đ</small>@endif
                @if ($order->tax_amount !== '0.00')<small>Thuế: {{ number_format((float) $order->tax_amount, 0, ',', '.') }} đ</small>@endif
                <div><span>Tổng thanh toán</span><b>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</b></div>
            </aside>
        </div>

        <section class="category-panel order-detail__items" aria-labelledby="items-title">
            <div class="order-detail__section-heading"><div><p class="section-heading__eyebrow">Danh sách phục vụ</p><h2 id="items-title">Món trong đơn</h2></div><span>{{ $order->orderItems->count() }} món</span></div>
            @if ($order->orderItems->isEmpty())
                <div class="category-empty"><h3>Chưa có món nào</h3><p>Chọn món từ biểu mẫu bên dưới để bắt đầu đơn hàng.</p></div>
            @else
                <div class="category-table-wrap product-table-wrap"><table class="category-table order-detail__table"><caption class="sr-only">Món trong đơn hàng</caption>
                    <thead><tr><th scope="col">Món</th><th scope="col">Đơn giá</th><th scope="col">Số lượng</th><th scope="col">Tạm tính</th><th scope="col">Trạng thái</th>@if ($order->status === 'pending')<th scope="col">Thao tác</th>@endif</tr></thead>
                    <tbody>@foreach ($order->orderItems as $item)<tr>
                        <th scope="row" data-label="Món"><span class="order-detail__item-name">{{ $item->product_name }}</span>@if ($item->variant_name)<small>{{ $item->variant_name }}</small>@endif @if ($item->note)<em>{{ $item->note }}</em>@endif</th>
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
    </section>
@endsection
