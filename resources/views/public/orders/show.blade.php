@extends('layouts.public')
@section('title', 'Theo dõi yêu cầu — Maison du Café')
@section('content')
<section class="coffee-section customer-cart" aria-labelledby="tracking-title"><div class="coffee-container">
    <h1 id="tracking-title">Yêu cầu của bạn</h1><p>Mã: {{ $tracking['reference'] }}</p>
    @include('public.cart._notices')
    <div class="customer-cart-layout"><div class="customer-cart-list">
        <article class="customer-cart-item"><h2>{{ ['pending' => 'Chờ quán tiếp nhận', 'accepted' => 'Quán đã tiếp nhận', 'rejected' => 'Quán chưa thể tiếp nhận'][$tracking['status']] ?? 'Đang xử lý' }}</h2>
            <p>Thanh toán: <strong>{{ $tracking['paid'] ? 'Đã nhận đủ tiền' : 'Chưa thanh toán' }}</strong> · {{ $tracking['payment_option'] === 'cod' ? 'COD — thu khi khách nhận món' : 'Chuyển khoản trước' }}</p>
            <p>Giao món: {{ $tracking['delivered_at'] ? 'Đã giao thành công' : ($tracking['dispatched_at'] ? 'Đang giao đến bạn' : 'Chưa bắt đầu giao') }}</p>
            @if($tracking['order_status'] === 'cancelled')<p role="status">Đơn đã được quán hủy. Vui lòng liên hệ quán để được hỗ trợ.</p>@endif
            @foreach($tracking['item_statuses'] as $status => $count)<p>{{ ['pending' => 'Chờ chuẩn bị', 'preparing' => 'Đang chuẩn bị', 'ready' => 'Sẵn sàng giao', 'served' => 'Đã nhận món', 'cancelled' => 'Đã hủy'][$status] ?? $status }}: {{ $count }} món</p>@endforeach
            <a href="{{ route('customer-orders.show', $tracking['reference']) }}">Cập nhật trạng thái</a>
        </article>
        @foreach($tracking['items'] as $line)<article class="customer-cart-item"><h2>{{ $line['name'] }} · {{ $line['variant'] }}</h2><p>Số lượng: {{ $line['quantity'] }} · {{ $line['subtotal'] }} VND</p>@foreach($line['toppings'] as $row)<p>+ {{ $row['name'] }} × {{ $row['quantity'] }}</p>@endforeach @if($line['note'])<p>{{ $line['note'] }}</p>@endif</article>@endforeach
    </div><aside class="customer-cart-summary"><h2>Thông tin đã gửi</h2><p>{{ $tracking['customer_name'] }} · {{ $tracking['customer_phone'] }}</p><p>{{ $tracking['customer_address'] }}</p>@if($tracking['note'])<p>{{ $tracking['note'] }}</p>@endif<p>Tiền món: {{ $tracking['quoted_subtotal'] }} VND</p><p>Phí giao: {{ $tracking['delivery_fee'] }} VND</p><p class="customer-cart-summary__amount">Tổng: {{ $tracking['quoted_total'] }} VND</p><p>Chỉ trình duyệt đã gửi yêu cầu mới xem được trang này. Mất phiên hoặc quá 7 ngày, vui lòng liên hệ quán.</p><x-public.button href="{{ route('menu.index') }}" variant="outline">Về thực đơn</x-public.button></aside></div>
</div></section>
@endsection
