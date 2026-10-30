@extends('layouts.public')

@section('title', 'Maison du Café — Cà phê ngon, khoảnh khắc đẹp')

@section('content')
    <section class="hero" id="home" aria-labelledby="hero-title">
        <video class="hero__image hero__video" autoplay muted loop playsinline preload="metadata" poster="{{ asset('assets/video/maison-coffee-poster.jpg') }}" aria-hidden="true">
            <source src="{{ asset('assets/video/maison-coffee-hero.mp4') }}" type="video/mp4">
        </video>
        <div class="hero__veil" aria-hidden="true"></div>
        <div class="coffee-container hero__content">
            <p class="hero__kicker">MAISON DU CAFÉ · COFFEE & MORE</p>
            <h1 id="hero-title">Một chút cà phê,<br><em>một ngày tuyệt vời.</em></h1>
            <p class="hero__intro">Từ hạt cà phê tuyển chọn đến ly nước trên tay bạn — từng khoảnh khắc đều xứng đáng được nâng niu.</p>
            <div class="hero__actions">
                <x-public.button href="{{ route('menu.index') }}">Khám phá thực đơn →</x-public.button>
                <x-public.button href="{{ route('reservation.create') }}" variant="ghost">Đặt bàn ngay</x-public.button>
            </div>
        </div>
        <p class="hero__note">ROASTED WITH CARE · SERVED WITH LOVE</p>
    </section>

    <section class="welcome-note" aria-label="Triết lý cà phê của Maison">
        <div class="coffee-container welcome-note__inner">
            <span aria-hidden="true">✦</span>
            <p>Mỗi tách cà phê được làm bằng sự tận tâm, từ giọt đầu tiên đến dư vị cuối cùng.</p>
            <span aria-hidden="true">✦</span>
        </div>
    </section>

    <section class="coffee-section story" id="story">
        <div class="coffee-container story__grid">
            <figure class="story__image-wrap">
                <img
                    src="{{ asset('images/coffeehouse/artisan-espresso.png') }}"
                    alt="Espresso thủ công, hạt cà phê rang và những nguyên liệu chọn lọc"
                    loading="lazy"
                >
                <figcaption>Điều giản dị, được chăm chút bằng cả trái tim.</figcaption>
            </figure>

            <div class="story__copy">
                <p class="accent-script">Một nhịp sống chậm hơn</p>
                <x-public.section-heading eyebrow="Câu chuyện Maison" title="Chút tỉ mỉ trong từng ly cà phê">
                    <p>Chúng tôi tin rằng quán cà phê đẹp nhất là nơi bạn muốn quay trở lại: có ánh sáng ấm, hương cà phê mới và những cuộc trò chuyện không vội vã.</p>
                </x-public.section-heading>
                <p>Từ những thức uống quen thuộc đến chút biến tấu mới, Maison luôn giữ trọn chất cà phê và sự chỉn chu ở từng công đoạn.</p>
                <a class="text-link" href="#menu">Khám phá hương vị hôm nay <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="coffee-section storefront-featured" id="menu">
        <div class="coffee-container">
            <div class="storefront-section-top"><div><p class="section-heading__eyebrow">BEST SELLERS</p><h2>Thức uống dành cho bạn</h2><p>Hương vị quen thuộc, được chăm chút từ những điều nhỏ nhất.</p></div><a class="text-link" href="{{ route('menu.index') }}">Xem thực đơn đầy đủ →</a></div>
            @if($featured->isNotEmpty())
            <div class="storefront-products">
                @foreach($featured as $product)
                    <article class="storefront-product"><a href="{{ route('menu.show', $product->slug) }}" class="storefront-product-image">
                        @if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">@else<img src="{{ asset('images/coffeehouse/artisan-espresso.png') }}" alt="Hình minh họa cà phê" loading="lazy">@endif
                    </a><span class="storefront-product-tag">SIGNATURE</span><h3>{{ $product->name }}</h3><div class="storefront-product-bottom"><span>@if($product->productVariants->isNotEmpty())Từ {{ number_format((float)$product->productVariants->min('price'),0,',','.') }}đ @else Xem sản phẩm @endif</span><a href="{{ route('menu.show',$product->slug) }}" aria-label="Xem {{ $product->name }}">Xem món ↗</a></div></article>
                @endforeach
            </div>
            @else
            <div class="storefront-featured-empty"><img src="{{ asset('images/coffeehouse/artisan-espresso.png') }}" alt="Cà phê Maison du Café" loading="lazy"><div><h3>Cùng khám phá thực đơn của chúng tôi</h3><p>Những thức uống được yêu thích sẽ xuất hiện tại đây khi cửa hàng đánh dấu sản phẩm nổi bật.</p><x-public.button href="{{ route('menu.index') }}">Khám phá thực đơn</x-public.button></div></div>
            @endif
        </div>
    </section>
    <section class="storefront-promo coffee-section" id="specials"><div class="coffee-container storefront-promo-grid"><div><p class="section-heading__eyebrow">MADE FOR YOUR MOMENTS</p><h2>Mang hương vị yêu thích đi cùng bạn.</h2><p>Chọn món trực tuyến, gửi yêu cầu nhận tại quán và để Maison chuẩn bị cho cuộc hẹn cà phê tiếp theo.</p><x-public.button href="{{ route('menu.index') }}">Chọn món yêu thích →</x-public.button></div><img src="{{ asset('images/coffeehouse/artisan-espresso.png') }}" loading="lazy" alt="Ly espresso thủ công và hạt cà phê"></div></section>

    <section class="coffee-section reservation" id="reservation">
        <div class="coffee-container reservation__panel">
            <div class="reservation__ornament" aria-hidden="true">M</div>
            <div>
                <p class="accent-script">Một chỗ ngồi, một câu chuyện</p>
                <h2>Một chiếc bàn dành cho bạn.</h2>
                <p>Hẹn một buổi sáng thư thái hay cuộc gặp gỡ cuối tuần. Gửi yêu cầu đặt bàn ngay hôm nay.</p>
            </div>
            <x-public.button href="{{ route('reservation.create') }}">Đặt bàn ngay</x-public.button>
        </div>
    </section>
@endsection