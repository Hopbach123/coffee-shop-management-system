<article class="public-menu-card" data-menu-product>
    <div class="public-menu-card__media">
        @if ($imageUrl = $product->imageUrl())
            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span aria-hidden="true">M</span>
        @endif
    </div>
    <div class="public-menu-card__body">
        <div class="public-menu-card__meta">
            @if ($product->is_featured)
                <span class="public-menu-featured">Món nổi bật</span>
            @endif
            <span class="public-menu-availability {{ $product->status === 'available' ? 'public-menu-availability--available' : 'public-menu-availability--unavailable' }}">
                {{ $product->status === 'available' ? 'Có sẵn' : 'Hết món' }}
            </span>
        </div>
        <h3>{{ $product->name }}</h3>
        @if ($product->description)
            <p class="public-menu-card__description">{{ $product->description }}</p>
        @endif
        @if ($product->productVariants->isEmpty())
            <p class="public-menu-card__no-price">Chưa có giá bán</p>
        @else
            <ul class="public-menu-card__variants" aria-label="Kích cỡ và giá">
                @foreach ($product->productVariants as $variant)
                    <li>
                        <span>{{ $variant->name }} @if($variant->status === 'unavailable') · Tạm hết @endif</span>
                        <strong>{{ number_format((float) $variant->price, 2, ',', '.') }} ₫</strong>
                    </li>
                @endforeach
            </ul>
        @endif
        <a class="text-link" href="{{ route('menu.show', ['product' => $product->slug]) }}">Xem chi tiết <span aria-hidden="true">→</span></a>
    </div>
</article>
