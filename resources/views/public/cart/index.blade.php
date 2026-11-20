@extends('layouts.public')

@section('title', 'Giỏ hàng — Maison du Café')

@section('content')
    <section class="coffee-section customer-cart" aria-labelledby="cart-title">
        <div class="coffee-container">
            <nav class="public-menu-breadcrumb" aria-label="Đường dẫn">
                <a href="{{ route('menu.index') }}">Thực đơn</a>
                <span aria-hidden="true">/</span><span aria-current="page">Giỏ hàng</span>
            </nav>
            <p class="section-heading__eyebrow">Maison du Café · Lựa chọn của bạn</p>
            <h1 id="cart-title">Giỏ hàng</h1>
            <p class="customer-cart-intro">Kiểm tra từng món và topping trước khi tiếp tục.</p>
            @include('public.cart._notices')

            @if ($cart['items'] === [])
                <div class="public-menu-empty">
                    <h2>Giỏ hàng đang trống</h2>
                    <p>Chọn một món trong thực đơn để bắt đầu.</p>
                    <x-public.button href="{{ route('menu.index') }}">Khám phá thực đơn</x-public.button>
                </div>
            @else
                <div class="customer-cart-layout">
                    <div class="customer-cart-list">
                        @foreach ($cart['items'] as $item)
                            <article class="customer-cart-item" aria-labelledby="item-title-{{ $item['id'] }}">
                                <div class="customer-cart-item__heading">
                                    <div>
                                        <h2 id="item-title-{{ $item['id'] }}">{{ $item['name'] }}</h2>
                                        <p>{{ $item['variant_name'] }}</p>
                                    </div>
                                    <span class="public-menu-availability public-menu-availability--{{ $item['available'] ? 'available' : 'unavailable' }}">{{ $item['available'] ? 'Có thể chọn' : 'Cần kiểm tra' }}</span>
                                </div>
                                @if ($item['note'])
                                    <p class="customer-cart-item__note">Ghi chú: {{ $item['note'] }}</p>
                                @endif
                                @if ($item['toppings'] !== [])
                                    <ul class="customer-cart-toppings" aria-label="Topping đã chọn">
                                        @foreach ($item['toppings'] as $topping)
                                            <li><span>{{ $topping['name'] }} × {{ $topping['quantity'] }} <small>({{ $topping['price_label'] }} / phần)</small></span><strong>{{ $topping['amount_label'] }}</strong></li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($item['error'])
                                    <p class="customer-cart-item__error" role="status">{{ $item['error'] }}</p>
                                @else
                                    <dl class="customer-cart-line-totals">
                                        <div><dt>Đơn giá món</dt><dd>{{ $item['unit_price_label'] }}</dd></div>
                                        <div><dt>Tổng topping</dt><dd>{{ $item['topping_amount_label'] }}</dd></div>
                                        <div><dt>Thành tiền dòng</dt><dd><strong>{{ $item['subtotal_label'] }}</strong></dd></div>
                                    </dl>
                                @endif
                                <div class="customer-cart-item__actions">
                                    @if ($item['available'])
                                        <form class="customer-cart-quantity" action="{{ route('cart.items.update', ['line' => $item['id']]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="cart_line" value="{{ $item['id'] }}">
                                            <label for="quantity-{{ $item['id'] }}">Số lượng món</label>
                                            <input id="quantity-{{ $item['id'] }}" type="number" name="quantity" min="1" max="2147483647" step="1" value="{{ old('cart_line') === $item['id'] ? old('quantity', $item['quantity']) : $item['quantity'] }}" required>
                                            <x-public.button type="submit" variant="outline">Cập nhật</x-public.button>
                                        </form>
                                    @else
                                        <p>Số lượng món: {{ $item['quantity'] }}</p>
                                    @endif
                                    <form action="{{ route('cart.items.destroy', ['line' => $item['id']]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-public.button type="submit" variant="outline" class="customer-cart-remove" aria-label="Xóa {{ $item['name'] }} khỏi giỏ">Xóa món</x-public.button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <aside class="customer-cart-summary" aria-labelledby="cart-summary-title">
                        <p class="section-heading__eyebrow">Kiểm tra giỏ hàng</p>
                        <h2 id="cart-summary-title">Tạm tính</h2>
                        @if ($cart['subtotal_label'] !== null)
                            <p class="customer-cart-summary__amount">{{ $cart['subtotal_label'] }}</p>
                        @else
                            <p class="customer-cart-item__error">{{ $cart['total_error'] ? 'Tổng giỏ hàng vượt giới hạn dữ liệu. Hãy giảm số lượng hoặc xóa bớt món.' : 'Chưa thể tính tổng. Hãy xóa hoặc chọn lại những món cần kiểm tra.' }}</p>
                        @endif
                        <p>Đã gồm topping đã chọn. Giá được cập nhật từ thực đơn khi bạn xem hoặc sửa giỏ.</p>
                        <p class="customer-cart-summary__status">Giỏ hàng chưa được gửi đến quán. Phí giao cố định 20.000đ được cộng ở bước kiểm tra thông tin. Gửi yêu cầu không có nghĩa đã thanh toán.</p>
                        @if ($cart['subtotal_label'] !== null)
                            <x-public.button href="{{ route('customer-orders.create') }}">Thông tin giao hàng</x-public.button>
                        @endif
                        <x-public.button href="{{ route('menu.index') }}" variant="outline">Chọn thêm món</x-public.button>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
