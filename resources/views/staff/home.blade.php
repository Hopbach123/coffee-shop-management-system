@extends('layouts.internal')
@section('title', 'Bán hàng / POS')
@section('content')
<div class="sneat-page-title"><div><p>Nhân viên · Quầy bán hàng</p><h1>Bán hàng / POS</h1><span>Chọn món → kiểm tra tổng → nhận đủ tiền → chuẩn bị món</span></div><span class="sneat-pill is-success">● Đang hoạt động</span></div>
<div class="pos-grid">
    <section class="pos-catalog"><div class="sneat-card"><form action="{{ route('staff.home') }}" method="GET" class="pos-search"><x-internal.icon name="search" size="18"/><input type="search" name="q" value="{{ $search }}" placeholder="Tìm món trong thực đơn..." maxlength="100"><button type="submit">Tìm</button></form></div>
    <div class="pos-product-grid">@forelse($products as $product)
        <article class="pos-product"><div class="pos-product-art">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">@else<x-internal.icon name="coffee" size="38"/>@endif</div><span class="pos-product-category">{{ $product->category?->name }}</span><h2>{{ $product->name }}</h2>
        @if($product->productVariants->isEmpty())<p class="sneat-muted">Tạm hết hàng</p>@else
        <form method="POST" action="{{ route('staff.pos.add') }}" class="pos-checkout">
            @csrf
            <label for="variant-{{ $product->id }}">Kích cỡ</label>
            <select id="variant-{{ $product->id }}" name="variant_id">
                @foreach($product->productVariants as $variant)<option value="{{ $variant->id }}">{{ $variant->name }} · {{ $priceLabels[$variant->id] }}</option>@endforeach
            </select>
            <label for="quantity-{{ $product->id }}">Số lượng món</label>
            <input id="quantity-{{ $product->id }}" type="number" name="quantity" min="1" max="2147483647" value="1" required>
            @if($toppings->isNotEmpty())
                <details><summary>Chọn topping</summary>
                    <p class="sneat-hint">Nhập tổng số phần topping cho dòng món này; 0 là không chọn.</p>
                    @foreach($toppings as $topping)
                        <label for="topping-{{ $product->id }}-{{ $topping->id }}">{{ $topping->name }} · {{ $toppingPriceLabels[$topping->id] }}</label>
                        <input id="topping-{{ $product->id }}-{{ $topping->id }}" type="number" name="toppings[{{ $topping->id }}]" min="0" max="2147483647" value="0">
                    @endforeach
                </details>
            @endif
            <label for="item-note-{{ $product->id }}">Ghi chú món</label>
            <input id="item-note-{{ $product->id }}" name="note" maxlength="500" placeholder="Ít đá, ít đường...">
            <button type="submit" class="sneat-primary">Thêm {{ $product->name }}</button>
        </form>
        @endif</article>
    @empty<div class="sneat-card sneat-empty">Không có món phù hợp. Hãy kiểm tra sản phẩm trong Admin.</div>@endforelse</div></section>
    <aside class="pos-cart"><div class="sneat-card"><div class="sneat-card-heading"><h2>Đơn hiện tại</h2><span class="sneat-pill is-info">Đang tạo</span></div>
    @forelse($quote['items'] as $line)
        <div class="pos-cart-line"><div>
            <strong>{{ $line['name'] }}</strong><small>{{ $line['variant_name'] }} · SL: {{ $line['quantity'] }}</small>
            @foreach($line['toppings'] as $topping)<small>+ {{ $topping['name'] }} × {{ $topping['quantity'] }} · {{ $topping['amount_label'] }}</small>@endforeach
            @if($line['note'])<small>{{ $line['note'] }}</small>@endif
            @if($line['available'])<strong>{{ $line['subtotal_label'] }}</strong>@else<p role="alert">{{ $line['error'] }}</p>@endif
        </div><form method="POST" action="{{ route('staff.pos.remove') }}">@csrf<input type="hidden" name="line_id" value="{{ $line['id'] }}"><button aria-label="Bỏ {{ $line['name'] }} khỏi đơn" title="Bỏ món">×</button></form></div>
    @empty<div class="pos-cart-empty"><x-internal.icon name="bag" size="38"/><p>Chưa có món nào</p><small>Chọn món từ thực đơn để bắt đầu</small></div>@endforelse
    <div class="pos-total"><span>Tổng cần nhận</span><strong>{{ $quote['subtotal_label'] ?? 'Cần kiểm tra lại món' }}</strong></div>
    <form method="POST" action="{{ route('staff.pos.checkout') }}" class="pos-checkout">
        @csrf<input type="hidden" name="checkout_key" value="{{ $attempt['key'] ?? '' }}">
        <label for="pos-order-type">Hình thức</label>
        <select id="pos-order-type" name="order_type" data-order-type><option value="takeaway" @selected(old('order_type') === 'takeaway')>Mang đi</option><option value="dine_in" @selected(old('order_type') === 'dine_in')>Tại bàn</option></select>
        <div data-pos-table><label for="pos-table">Bàn trống (chỉ dùng cho tại bàn)</label><select name="table_id" id="pos-table"><option value="">-- Chọn bàn --</option>@foreach($tables as $table)<option value="{{ $table->id }}" @selected((string) old('table_id') === (string) $table->id)>{{ $table->table_name ?: $table->table_code }} · {{ $table->capacity }} chỗ</option>@endforeach</select></div>
        <label for="pos-note">Ghi chú đơn</label><textarea id="pos-note" name="note" rows="2" maxlength="500">{{ old('note') }}</textarea>
        <label for="pos-payment-method">Phương thức thực nhận</label><select id="pos-payment-method" name="payment_method"><option value="cash" @selected(old('payment_method') === 'cash')>Tiền mặt</option><option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Chuyển khoản</option></select>
        <label for="pos-transaction-code">Mã giao dịch chuyển khoản (không bắt buộc)</label><input id="pos-transaction-code" name="transaction_code" maxlength="100" value="{{ old('transaction_code') }}">
        <label for="pos-received-amount">Tiền khách đưa khi trả tiền mặt (VND)</label><input id="pos-received-amount" name="received_amount" inputmode="decimal" value="{{ old('received_amount') }}" placeholder="Để trống khi nhận đúng tổng tiền">
        <label><input type="checkbox" name="received_confirmation" value="1" required> Tôi đã kiểm tra và nhận đủ {{ $quote['subtotal_label'] ?? 'tiền' }} thực tế.</label>
        <button class="sneat-primary" type="submit" @disabled(!$quote['items'] || $quote['subtotal'] === null)>Ghi nhận thu tiền &amp; chuẩn bị món</button>
    </form>
    <p class="sneat-hint">Nếu lưu đơn gặp lỗi sau khi đã nhận tiền, kiểm tra giao dịch trước khi thử lại cùng phiên; không thu tiền lần hai.</p></div></aside>
</div>
@endsection
