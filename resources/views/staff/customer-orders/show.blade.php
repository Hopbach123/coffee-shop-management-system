@extends('layouts.internal')
@section('title', 'Yêu cầu giao món')
@section('content')
<section class="category-page order-detail">
    <header class="order-detail__header"><div><p>Yêu cầu web · {{ $customerRequest->status }}</p><h1>{{ $customerRequest->reference }}</h1></div><x-public.button href="{{ route('staff.customer-orders.index') }}" variant="outline">Danh sách yêu cầu</x-public.button></header>
    <section class="category-panel"><h2>Thông tin giao hàng</h2><p>{{ $customerRequest->customer_name }} · {{ $customerRequest->customer_phone }}</p><p>{{ $customerRequest->customer_address }}</p>@if($customerRequest->note)<p>{{ $customerRequest->note }}</p>@endif<p>Tiền món {{ $customerRequest->quoted_subtotal }} VND + phí giao {{ $customerRequest->delivery_fee }} VND = <strong>{{ $customerRequest->quoted_total }} VND</strong></p><p>{{ $customerRequest->payment_option === 'cod' ? 'COD — chưa thu khi tiếp nhận hoặc bắt đầu giao' : 'Chuyển khoản trước — phải xác minh thực nhận trước khi chuẩn bị' }}</p></section>
    <section class="category-panel"><h2>Món khách đã chốt</h2>@foreach($customerRequest->items as $item)<article><h3>{{ $item->product_name }} · {{ $item->variant_name }} × {{ $item->quantity }}</h3><p>{{ $item->subtotal }} VND @if($item->note) · {{ $item->note }} @endif</p>@foreach($item->toppings as $row)<p>+ {{ $row->topping_name }} × {{ $row->quantity }} · giá snapshot {{ $row->price }} VND</p>@endforeach</article>@endforeach</section>
    <section class="category-panel"><h2>Xử lý yêu cầu</h2>
        @if($customerRequest->status === 'pending')<p>Tiếp nhận sẽ kiểm tra lại món và giá, tạo một Order bằng tài khoản của bạn. Chưa ghi nhận tiền hoặc trừ kho.</p><form method="POST" action="{{ route('staff.customer-orders.accept', $customerRequest) }}">@csrf<x-public.button type="submit">Tiếp nhận yêu cầu</x-public.button></form>@endif
        @if($customerRequest->order)
            <p>Order: <a href="{{ route('staff.orders.show', $customerRequest->order) }}">{{ $customerRequest->order->order_code }}</a> · {{ $customerRequest->order->status }}</p>
            <p>Thanh toán: {{ $customerRequest->order->payments->contains('status', 'paid') ? 'Đã nhận đủ tiền' : 'Chưa thanh toán' }}</p>
            @if($customerRequest->payment_option === 'bank_transfer_prepaid')<x-public.button href="{{ route('staff.orders.payments.show', $customerRequest->order) }}" variant="outline">Xác nhận chuyển khoản / Lịch sử</x-public.button>@endif
            @if($canPrepare)<form method="POST" action="{{ route('staff.customer-orders.prepare', $customerRequest) }}">@csrf<x-public.button type="submit">Bắt đầu chuẩn bị COD</x-public.button></form>@endif
            <p>Chuyển món từ preparing sang ready tại <a href="{{ route('staff.orders.show', $customerRequest->order) }}">chi tiết Order</a> trước khi giao.</p>
        @endif
        @if($canDispatch)<form method="POST" action="{{ route('staff.customer-orders.dispatch', $customerRequest) }}">@csrf<x-public.button type="submit">Bắt đầu giao</x-public.button></form>@endif
        @if($customerRequest->dispatched_at)<p>Bắt đầu giao: {{ $customerRequest->dispatched_at->format('d/m/Y H:i') }}</p>@endif
        @if($customerRequest->delivered_at)<p>Giao thành công: {{ $customerRequest->delivered_at->format('d/m/Y H:i') }}</p>@endif
    </section>
    @if($canDeliver)<section class="category-panel"><h2>Xác nhận khách nhận món</h2><form method="POST" action="{{ route('staff.customer-orders.deliver', $customerRequest) }}" class="category-form">
        @csrf<p><label><input type="checkbox" name="delivery_confirmation" value="1" required> Khách đã nhận đủ món thực tế.</label></p>
        @if($customerRequest->payment_option === 'cod')
            <div class="category-field"><label for="cod-method">Phương thức thực nhận</label><select id="cod-method" name="payment_method"><option value="cash">Tiền mặt</option><option value="bank_transfer">Chuyển khoản khi nhận</option></select></div>
            <div class="category-field"><label for="cod-reference">Mã chuyển khoản (không bắt buộc)</label><input id="cod-reference" name="transaction_code" maxlength="100" value="{{ old('transaction_code') }}"></div>
            <p><label><input type="checkbox" name="received_confirmation" value="1" required> Đã thu đủ {{ $customerRequest->quoted_total }} VND từ khách.</label></p>
        @endif
        <x-public.button type="submit">Xác nhận giao thành công{{ $customerRequest->payment_option === 'cod' ? ' và thu COD' : '' }}</x-public.button>
    </form><p>Nếu ghi nhận lỗi sau khi đã nhận tiền, kiểm tra giao dịch trước khi thử lại; không thu lần hai.</p></section>@endif
</section>
@endsection
