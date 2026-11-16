@if (session('success'))
    <div class="customer-cart-notice" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="customer-cart-notice customer-cart-notice--error" role="alert" tabindex="-1">
        <strong>Vui lòng kiểm tra lại lựa chọn</strong>
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
