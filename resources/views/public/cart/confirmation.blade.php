@extends('layouts.public')

@section('title', 'Đã nhận yêu cầu — Maison du Café')

@section('content')
<section class="coffee-section commerce-page" style="padding: 60px 0;">
    <div class="coffee-container">
        <div class="commerce-confirm" style="max-width: 620px; margin: 0 auto; background: #FFFDF9; border: 1px solid var(--coffee-border); border-radius: 20px; padding: 48px 36px; text-align: center; box-shadow: 0 10px 30px rgba(44, 27, 20, 0.06);">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #F0F5ED; border: 2px solid #C8DFC4; color: #2B5433; display: grid; place-items: center; margin: 0 auto 20px; font-size: 2rem;">
                ✓
            </div>
            
            <p class="section-heading__eyebrow" style="color: var(--coffee-caramel); font-weight: 700; text-transform: uppercase; font-size: 0.8125rem; letter-spacing: 0.1em; margin-bottom: 8px;">
                THANK YOU!
            </p>
            <h1 style="font-family: var(--font-display); font-size: 2rem; color: var(--coffee-espresso); margin: 0 0 16px;">
                Đã nhận yêu cầu của bạn
            </h1>

            <div style="background: var(--coffee-bg); border: 1px dashed var(--coffee-border); border-radius: 12px; padding: 18px; margin-bottom: 24px;">
                <p style="margin: 0 0 6px; font-size: 0.875rem; color: var(--coffee-text-muted);">Mã tra cứu yêu cầu:</p>
                <code style="font-size: 1.5rem; font-weight: 700; color: var(--coffee-caramel); font-family: monospace; letter-spacing: 0.05em;">
                    {{ $orderRequest->reference }}
                </code>
            </div>

            <p style="font-size: 1.05rem; color: var(--coffee-text); margin-bottom: 12px; line-height: 1.5;">
                Cảm ơn <strong>{{ $orderRequest->customer_name }}</strong>! Dự kiến nhận món lúc <strong style="color: var(--coffee-espresso);">{{ $orderRequest->pickup_at->format('H:i d/m/Y') }}</strong>.
            </p>

            <div style="background: #FDF6EB; border: 1px solid #F3DCBA; border-radius: 10px; padding: 14px 18px; margin-bottom: 32px; font-size: 0.875rem; color: #8F5C1D; line-height: 1.5; text-align: left;">
                <strong style="display: block; margin-bottom: 4px;">✧ Lưu ý quan trọng:</strong>
                Yêu cầu đang chờ nhân viên xác nhận, chưa phải đơn thanh toán hoặc cam kết đã chuẩn bị món. Nhân viên quán sẽ kiểm tra nguyên liệu và tiếp nhận ngay.
            </div>

            <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                @if(\Illuminate\Support\Facades\Route::has('orders.track'))
                    <a href="{{ route('orders.track', ['code' => $orderRequest->reference]) }}" class="coffee-button coffee-button--primary" style="background: #AD764C; color: #ffffff; border-radius: 9999px; padding: 11px 26px; text-decoration: none; font-weight: 600;">
                        Tra cứu đơn hàng ↗
                    </a>
                @endif
                <a href="{{ route('home') }}" class="coffee-button coffee-button--ghost" style="border: 1px solid var(--coffee-border); border-radius: 9999px; padding: 11px 26px; text-decoration: none; font-weight: 600; color: var(--coffee-espresso); background: #ffffff;">
                    Trở về trang chủ →
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
