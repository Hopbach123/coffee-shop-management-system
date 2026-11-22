@extends('layouts.public')

@section('title', 'Ưu đãi — Maison du Café')

@section('content')
<section class="coffee-section public-promotions" aria-labelledby="promotion-title">
    <div class="coffee-container">
        <p class="section-heading__eyebrow">Maison du Café · Ưu đãi</p>
        <h1 id="promotion-title">Mã ưu đãi cho lần ghé quán tiếp theo.</h1>
        <p class="public-promotions__intro">Lưu hoặc sao chép mã trước khi đặt món. Quán luôn kiểm tra lại điều kiện và tổng tiền ở bước xác nhận.</p>
        @include('public.cart._notices')

        <div class="public-promotions__grid">
            @forelse ($promotions as $promotion)
                <article class="public-promotion-card">
                    @if ($promotion->imageUrl())
                        <img src="{{ $promotion->imageUrl() }}" alt="{{ $promotion->name }}" loading="lazy">
                    @endif
                    <div class="public-promotion-card__body">
                        <p class="section-heading__eyebrow">{{ $promotion->discount_type === 'percentage' ? 'Giảm theo phần trăm' : 'Giảm trực tiếp' }}</p>
                        <h2>{{ $promotion->name }}</h2>
                        @if ($promotion->description)<p>{{ $promotion->description }}</p>@endif
                        <dl>
                            <div><dt>Mã</dt><dd><code>{{ $promotion->code }}</code></dd></div>
                            <div><dt>Điều kiện</dt><dd>Đơn từ {{ number_format((float) $promotion->minimum_order, 0, ',', '.') }}₫</dd></div>
                            <div><dt>Hạn dùng</dt><dd>{{ $promotion->end_at->format('d/m/Y H:i') }}</dd></div>
                            @if($promotion->usage_limit !== null)<div><dt>Lượt còn lại</dt><dd>{{ max(0, $promotion->usage_limit - $promotion->used_count) }}</dd></div>@endif
                        </dl>
                        <div class="public-promotion-card__actions">
                            <button type="button" class="coffee-button coffee-button--outline" data-copy-code="{{ $promotion->code }}">Sao chép mã</button>
                            <form method="POST" action="{{ route('promotions.save', $promotion) }}">@csrf<x-public.button type="submit" variant="outline">{{ in_array($promotion->code, $savedCodes, true) ? 'Đã lưu' : 'Lưu mã' }}</x-public.button></form>
                            <a class="coffee-button coffee-button--primary" href="{{ route('customer-orders.create', ['promotion' => $promotion->code]) }}">Dùng khi đặt món</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="public-menu-empty" role="status"><h2>Chưa có ưu đãi phù hợp</h2><p>Quán sẽ cập nhật chương trình mới sớm nhất.</p></div>
            @endforelse
        </div>
        {{ $promotions->links() }}
    </div>
</section>
<script>
document.querySelectorAll('[data-copy-code]').forEach((button) => button.addEventListener('click', async () => {
    const code = button.dataset.copyCode;
    try {
        await navigator.clipboard.writeText(code);
        button.textContent = 'Đã sao chép';
    } catch (_) {
        window.prompt('Sao chép mã ưu đãi:', code);
    }
    window.setTimeout(() => { button.textContent = 'Sao chép mã'; }, 1800);
}));
</script>
@endsection
