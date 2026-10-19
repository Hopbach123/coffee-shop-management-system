@extends('layouts.internal')
@section('title', 'Chi tiết đơn hàng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Nhân viên</p><h1 id="page-title">{{ $order->order_code }}</h1><p>Đơn {{ $order->order_type === 'dine_in' ? 'tại chỗ' : 'mang đi' }} đang ở trạng thái {{ $order->status }}.</p></div><x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Danh sách đơn</x-public.button></div>
        @if (session('success'))<p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>@endif
        <div class="category-panel"><dl class="detail-list">
            <dt>Nhân viên tạo</dt><dd>{{ $order->creator?->name }}</dd><dt>Bàn</dt><dd>{{ $order->cafeTable?->table_code ?? '—' }}</dd><dt>Khách hàng</dt><dd>{{ $order->customer?->name ?? 'Khách vãng lai' }}</dd><dt>Thời gian tạo</dt><dd>{{ $order->ordered_at?->format('d/m/Y H:i') }}</dd><dt>Ghi chú</dt><dd>{{ $order->note ?: '—' }}</dd>
        </dl></div>

        <div class="category-panel">
            <h2>Món trong đơn</h2>
            @if ($order->orderItems->isEmpty())
                <p>Chưa có món nào trong đơn.</p>
            @else
                <div class="category-table-wrap product-table-wrap"><table class="category-table"><caption class="sr-only">Món trong đơn</caption>
                    <thead><tr><th scope="col">Món</th><th scope="col">Số lượng</th><th scope="col">Đơn giá</th><th scope="col">Thành tiền</th><th scope="col">Trạng thái</th>@if ($order->status === 'pending')<th scope="col">Thao tác</th>@endif</tr></thead>
                    <tbody>@foreach ($order->orderItems as $item)<tr>
                        <th scope="row" data-label="Món">{{ $item->product_name }}{{ $item->variant_name ? ' · '.$item->variant_name : '' }}</th><td data-label="Số lượng">{{ $item->quantity }}</td><td data-label="Đơn giá">{{ number_format((float) $item->unit_price, 0, ',', '.') }} đ</td><td data-label="Thành tiền">{{ number_format((float) $item->subtotal, 0, ',', '.') }} đ</td><td data-label="Trạng thái"><span class="category-status">{{ $item->status }}</span></td>
                        @if ($order->status === 'pending')<td data-label="Thao tác">@if ($item->status === 'pending')<form method="POST" action="{{ route('staff.orders.items.update', [$order, $item]) }}" class="category-form">@csrf @method('PATCH')<label class="sr-only" for="quantity-{{ $item->id }}">Số lượng {{ $item->product_name }}</label><input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ $item->quantity }}" required><x-public.button type="submit" variant="outline">Cập nhật</x-public.button></form><form method="POST" action="{{ route('staff.orders.items.destroy', [$order, $item]) }}" class="category-form">@csrf @method('DELETE')<x-public.button type="submit" variant="outline">Xóa</x-public.button></form>@else<em>Không thể sửa</em>@endif</td>@endif
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
            <p><strong>Tạm tính: {{ number_format((float) $order->subtotal, 0, ',', '.') }} đ</strong></p>
            <p><strong>Tổng thanh toán: {{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</strong></p>
        </div>

        @if ($order->status === 'pending')
            <div class="category-panel"><h2>Thêm món</h2>
                @if ($variants->isEmpty())<p class="category-notice category-notice--error" role="alert">Chưa có biến thể sản phẩm đang bán.</p>
                @else
                    <form method="POST" action="{{ route('staff.orders.items.store', $order) }}" class="category-form">@csrf
                        <div class="category-fields">
                            <div class="category-field"><label for="variant_id">Sản phẩm và biến thể</label><select id="variant_id" name="variant_id" required @error('variant_id') aria-invalid="true" @enderror><option value="">Chọn món</option>@foreach ($variants as $variant)<option value="{{ $variant->id }}" @selected((string) old('variant_id') === (string) $variant->id)>{{ $variant->product?->name }} · {{ $variant->name }} · {{ number_format((float) $variant->price, 0, ',', '.') }} đ</option>@endforeach</select>@error('variant_id')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                            <div class="category-field"><label for="quantity">Số lượng</label><input id="quantity" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ old('quantity', 1) }}" required @error('quantity') aria-invalid="true" @enderror>@error('quantity')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                            <div class="category-field"><label for="item_note">Ghi chú món</label><textarea id="item_note" name="note" rows="3" maxlength="500" @error('note') aria-invalid="true" @enderror>{{ old('note') }}</textarea>@error('note')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                        </div>
                        @error('order')<p class="category-notice category-notice--error" role="alert">{{ $message }}</p>@enderror
                        <div class="category-form-actions"><x-public.button type="submit">Thêm món</x-public.button></div>
                    </form>
                @endif
            </div>
        @endif
    </section>
@endsection
