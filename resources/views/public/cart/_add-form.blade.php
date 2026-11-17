@if ($product->status === 'available' && $cartVariants->isNotEmpty())
    <form class="customer-cart-form" method="POST" action="{{ route('cart.items.store') }}" aria-labelledby="cart-add-title">
        @csrf
        <h2 id="cart-add-title">Chọn món của bạn</h2>
        <div class="customer-cart-form__choices">
            <div class="customer-cart-field">
                <label for="cart-variant">Kích cỡ</label>
                <select id="cart-variant" name="variant_id" required>
                    @foreach ($cartVariants as $variant)
                        <option value="{{ $variant['id'] }}" @selected((string) old('variant_id', $cartVariants->first()['id']) === (string) $variant['id'])>{{ $variant['name'] }} · {{ $variant['price_label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="customer-cart-field">
                <label for="cart-quantity">Số lượng món</label>
                <input id="cart-quantity" type="number" name="quantity" min="1" max="2147483647" step="1" value="{{ old('quantity', 1) }}" required>
            </div>
        </div>

        @if ($cartToppings->isNotEmpty())
            <fieldset class="customer-cart-topping-picker" aria-describedby="topping-help">
                <legend>Thêm topping <span>(tùy chọn)</span></legend>
                <p id="topping-help">Nhập 0 nếu không chọn. Số lượng topping dành cho cả dòng món, không tự nhân theo số ly.</p>
                @foreach ($cartToppings as $topping)
                    <div class="customer-cart-topping-picker__row">
                        <label for="cart-topping-{{ $topping['id'] }}">{{ $topping['name'] }} <span>{{ $topping['price_label'] }} / phần</span></label>
                        <input id="cart-topping-{{ $topping['id'] }}" type="number" name="toppings[{{ $topping['id'] }}]" min="0" max="2147483647" step="1" value="{{ old('toppings.'.$topping['id'], 0) }}" aria-label="Số phần {{ $topping['name'] }}" required>
                    </div>
                @endforeach
            </fieldset>
        @endif

        <div class="customer-cart-field">
            <label for="cart-note">Ghi chú cho món <span>(tùy chọn)</span></label>
            <textarea id="cart-note" name="note" rows="3" maxlength="500" placeholder="Ví dụ: ít đá, ít ngọt">{{ old('note') }}</textarea>
        </div>
        <x-public.button type="submit">Thêm vào giỏ hàng</x-public.button>
        <p class="customer-cart-hint">Mỗi lần thêm được giữ thành một dòng riêng để bạn dễ kiểm tra các lựa chọn.</p>
    </form>
@else
    <p class="customer-cart-notice">Món hiện chưa thể thêm vào giỏ. Bạn có thể chọn món khác trong thực đơn.</p>
@endif
