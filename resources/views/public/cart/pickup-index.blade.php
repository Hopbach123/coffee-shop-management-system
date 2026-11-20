@extends('layouts.public')

@section('title', 'Giỏ hàng — Maison du Café')

@section('content')
<section class="coffee-section commerce-page">
    <div class="coffee-container">
        <header class="commerce-hero">
            <p class="section-heading__eyebrow">YOUR ORDER</p>
            <h1>Giỏ hàng của bạn</h1>
            <p>Kiểm tra món yêu thích, tùy biến hương vị trước khi gửi yêu cầu nhận đồ tại quán.</p>
        </header>

        @if(session('success'))
            <div class="commerce-alert" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if(isset($invalidLines))
            <div class="commerce-alert commerce-alert--error" role="alert">
                <p><strong>Thông báo thay đổi:</strong> {{ $cartIssue }}</p>
                <p>Giỏ được giữ nguyên. Hãy xóa hoặc điều chỉnh dòng món không còn phù hợp để nhận báo giá mới nhất từ quán.</p>
            </div>
            <div style="margin-bottom: 24px; display: grid; gap: 10px;">
                @foreach($invalidLines as $lineKey => $draftLine)
                    <form action="{{ route('cart.update') }}" method="POST" class="commerce-quantity" style="background: #fff; padding: 12px 18px; border-radius: 8px; border: 1px solid var(--coffee-border);">
                        @csrf @method('PATCH')
                        <input type="hidden" name="line_id" value="{{ $lineKey }}">
                        <input type="hidden" name="quantity" value="0">
                        <span style="font-size: 0.9rem; color: var(--coffee-text-muted);">Biến thể #{{ is_array($draftLine) ? $draftLine['variant_id'] : $lineKey }} · {{ is_array($draftLine) ? $draftLine['quantity'] : $draftLine }} phần</span>
                        <button type="submit" class="coffee-button coffee-button--ghost" style="padding: 4px 12px; margin-left: auto; color: var(--coffee-danger); border-color: currentColor;">Xóa dòng món</button>
                    </form>
                @endforeach
            </div>
        @endif

        @if($errors->any())
            <div class="commerce-alert commerce-alert--error" role="alert">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if($cart['items'])
            <div class="commerce-layout">
                <div class="cart-items-wrapper">
                    @foreach($cart['items'] as $item)
                        <article class="cart-item-card">
                            <div class="cart-item-thumb">
                                @if($item['image'])
                                    <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}">
                                @else
                                    <x-internal.icon name="coffee" size="38"/>
                                @endif
                            </div>

                            <div class="cart-item-info">
                                <div class="cart-item-title-row">
                                    <h2 class="cart-item-name">{{ $item['product_name'] }}</h2>
                                    <span class="cart-variant-tag">{{ $item['variant_name'] }}</span>
                                </div>

                                <div class="cart-item-pricing">
                                    <span class="cart-unit-price">{{ number_format((float)$item['unit_price'], 0, ',', '.') }}đ / phần</span>
                                </div>

                                @if(!empty($item['toppings']))
                                    <div class="cart-toppings-list">
                                        @foreach($item['toppings'] as $top)
                                            <span class="cart-topping-badge">
                                                <span>+</span> {{ $top['name'] }}
                                                @if((float)($top['price'] ?? 0) > 0)
                                                    <small>({{ number_format((float)$top['price'], 0, ',', '.') }}đ)</small>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if(!empty($item['note']))
                                    <div class="cart-item-note">
                                        <span>Ghi chú:</span> {{ $item['note'] }}
                                    </div>
                                @endif

                                <form action="{{ route('cart.update') }}" method="POST" class="cart-qty-form" style="margin-top: 6px;">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="line_id" value="{{ $item['line_id'] ?? '' }}">
                                    <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                                    <label class="sr-only" for="qty-{{ $item['line_id'] ?? $item['variant_id'] }}">Số lượng</label>
                                    <input id="qty-{{ $item['line_id'] ?? $item['variant_id'] }}" name="quantity" type="number" min="0" max="20" value="{{ $item['quantity'] }}" class="cart-qty-input">
                                    <button type="submit" class="cart-update-btn">Cập nhật</button>
                                    <small style="color: var(--coffee-text-muted); font-size: 0.78rem;">(Đặt 0 để xóa)</small>
                                </form>
                            </div>

                            <div class="cart-item-subtotal">
                                <span style="font-size: 0.8rem; color: var(--coffee-text-muted);">Thành tiền</span>
                                <strong class="cart-subtotal-val">{{ number_format((float)$item['subtotal'], 0, ',', '.') }}đ</strong>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="commerce-summary">
                    <h2>Tóm tắt đơn</h2>
                    <div>
                        <span>Tạm tính</span>
                        <strong>{{ number_format($cart['total_cents'] / 100, 0, ',', '.') }}đ</strong>
                    </div>
                    <p style="margin: 14px 0; line-height: 1.5; color: var(--coffee-text-muted);">
                        Quán chuẩn bị sau khi xác nhận đơn. Thanh toán tại quầy hoặc chuyển khoản khi nhận món.
                    </p>
                    <a href="{{ route('cart.checkout') }}" class="coffee-button coffee-button--primary commerce-wide">
                        Tiếp tục đặt món →
                    </a>
                    <a href="{{ route('menu.index') }}" class="commerce-secondary">
                        ← Chọn thêm món
                    </a>
                </aside>
            </div>
        @elseif(!isset($invalidLines))
            <div class="commerce-empty">
                <x-internal.icon name="bag" size="48"/>
                <h2>Giỏ hàng đang trống</h2>
                <p>Một ly cà phê tuyệt vời đang chờ bạn khám phá tại Maison du Café.</p>
                <x-public.button href="{{ route('menu.index') }}">Xem thực đơn →</x-public.button>
            </div>
        @endif
    </div>
</section>
@endsection
