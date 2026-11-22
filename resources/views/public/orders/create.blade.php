@extends('layouts.public')
@section('title', 'Gửi yêu cầu giao món — Maison du Café')
@section('content')
<section class="coffee-section customer-cart" aria-labelledby="checkout-title"><div class="coffee-container">
    <a href="{{ route('cart.index') }}">← Giỏ hàng</a>
    <h1 id="checkout-title">Thông tin giao món</h1>
    <p>Không cần tài khoản. Quán sẽ tiếp nhận yêu cầu và liên hệ qua thông tin bạn cung cấp.</p>
    @include('public.cart._notices')
    <section class="customer-cart-promotion" aria-labelledby="checkout-promotion-title">
        <div>
            <p class="coffee-eyebrow">Ưu đãi</p>
            <h2 id="checkout-promotion-title">Mã khuyến mãi</h2>
            <p>Chỉ áp dụng một mã. Giá và điều kiện sẽ được quán kiểm tra lại trước khi nhận đơn.</p>
        </div>
        @if($quote['promotion_code'])
            <div class="customer-cart-promotion__active">
                <strong>{{ $quote['promotion_code'] }}</strong>
                <span>Giảm {{ $quote['promotion_discount_label'] }}</span>
                <form method="POST" action="{{ route('customer-orders.promotion.remove') }}">@csrf @method('DELETE')
                    <x-public.button type="submit" variant="secondary">Bỏ mã</x-public.button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('customer-orders.promotion.apply') }}" class="customer-cart-promotion__form">
                @csrf
                <label for="promotion-code">Nhập mã</label>
                <input id="promotion-code" name="promotion_code" maxlength="50" value="{{ old('promotion_code') }}" placeholder="Ví dụ: CAFE10" required>
                <x-public.button type="submit" variant="secondary">Áp dụng</x-public.button>
            </form>
            @if($savedPromotionCodes !== [])
                <p class="customer-cart-promotion__saved">Mã đã lưu:
                    @foreach($savedPromotionCodes as $code)
                        <a href="{{ route('customer-orders.create', ['promotion' => $code]) }}">{{ $code }}</a>@if(! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        @endif
    </section>
    <div class="customer-cart-layout">
        <form method="POST" action="{{ route('customer-orders.store') }}" class="customer-cart-form">
            @csrf<input type="hidden" name="submission_key" value="{{ $submissionKey }}">
            <div class="customer-cart-field"><label for="customer-name">Họ tên</label><input id="customer-name" name="customer_name" autocomplete="name" maxlength="255" value="{{ old('customer_name') }}" required></div>
            <div class="customer-cart-field"><label for="customer-phone">Số điện thoại</label><input id="customer-phone" name="customer_phone" type="tel" autocomplete="tel" maxlength="20" value="{{ old('customer_phone') }}" required></div>
            <div class="customer-cart-field"><label for="customer-address">Địa chỉ giao đầy đủ</label><textarea id="customer-address" name="customer_address" autocomplete="street-address" maxlength="500" rows="3" required>{{ old('customer_address') }}</textarea></div>
            <div class="customer-cart-field"><label for="payment-option">Cách thanh toán</label><select id="payment-option" name="payment_option" required><option value="cod" @selected(old('payment_option') === 'cod')>COD — trả khi nhận món</option><option value="bank_transfer_prepaid" @selected(old('payment_option') === 'bank_transfer_prepaid')>Chuyển khoản trước — quán xác minh tiền rồi chuẩn bị</option></select></div>
            <p class="customer-cart-hint">Chọn chuyển khoản chưa phải đã trả tiền. Sau khi tiếp nhận, quán liên hệ để hướng dẫn và xác minh thực nhận.</p>
            <div class="customer-cart-field"><label for="request-note">Ghi chú giao hàng</label><textarea id="request-note" name="note" maxlength="500" rows="2">{{ old('note') }}</textarea></div>
            <x-public.button type="submit" :disabled="$quote['total'] === null">Gửi yêu cầu giao món</x-public.button>
        </form>
        <aside class="customer-cart-summary"><h2>Kiểm tra yêu cầu</h2>
            @if($quote['promotion_code'])<p>Giảm giá ({{ $quote['promotion_code'] }}): −{{ $quote['promotion_discount_label'] }}</p>@endif
            @foreach($quote['items'] as $line)<p><strong>{{ $line['name'] }} · {{ $line['variant_name'] }} × {{ $line['quantity'] }}</strong><br>{{ $line['subtotal_label'] ?? $line['error'] }}</p>@foreach($line['toppings'] as $row)<p>+ {{ $row['name'] }} × {{ $row['quantity'] }} · {{ $row['amount_label'] }}</p>@endforeach @endforeach
            <p>Tiền món: {{ $quote['subtotal_label'] ?? 'Cần kiểm tra lại món' }}</p><p>Phí giao: {{ $quote['delivery_fee_label'] }}</p>
            <p class="customer-cart-summary__amount">Tổng: {{ $quote['total_label'] ?? 'Chưa thể gửi' }}</p>
            <p>Giữ trang xác nhận trong trình duyệt này để theo dõi trong 7 ngày. Không có tra cứu bằng số điện thoại.</p>
        </aside>
    </div>
</div></section>
@endsection
