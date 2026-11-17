@if ($product->status === 'available' && $cartVariants->isNotEmpty())
    <details class="customer-cart-form">
        <summary>Đặt trước, nhận tại quán</summary>
        <form class="storefront-add-form" method="POST" action="{{ route('cart.add') }}">
            @csrf
            <label for="pickup-variant">Kích cỡ nhận tại quán</label>
            <select id="pickup-variant" name="variant_id" required>
                @foreach ($cartVariants as $variant)
                    <option value="{{ $variant['id'] }}">{{ $variant['name'] }} · {{ $variant['price_label'] }}</option>
                @endforeach
            </select>
            <label for="pickup-quantity">Số lượng nhận tại quán</label>
            <input id="pickup-quantity" name="quantity" type="number" min="1" max="20" value="1" required>
            <x-public.button type="submit" variant="outline">Thêm vào giỏ nhận tại quán</x-public.button>
        </form>
        <a href="{{ route('pickup.cart.index') }}">Xem giỏ nhận tại quán</a>
    </details>
@endif
