<article class="public-menu-card" data-menu-product>
    <div class="public-menu-card__media">
        @if ($imageUrl = $product->imageUrl())
            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy" class="public-menu-card__img">
        @else
            <span aria-hidden="true">M</span>
        @endif
        <div class="public-menu-card__badges">
            @if ($product->is_featured)
                <span class="public-menu-badge public-menu-badge--featured">
                    <svg viewBox="0 0 20 20" width="13" height="13" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span>Món nổi bật</span>
                </span>
            @endif
            <span class="public-menu-badge {{ $product->status === 'available' ? 'public-menu-badge--available' : 'public-menu-badge--unavailable' }}">
                <span class="badge-dot"></span>
                <span>{{ $product->status === 'available' ? 'Có sẵn' : 'Hết món' }}</span>
            </span>
        </div>
    </div>
    <div class="public-menu-card__body">
        <h3 class="public-menu-card__title">{{ $product->name }}</h3>
        @if ($product->description)
            <p class="public-menu-card__description">{{ $product->description }}</p>
        @endif

        <div class="public-menu-card__pricing">
            @if ($product->productVariants->isEmpty())
                <p class="public-menu-card__no-price">Chưa có giá bán</p>
            @else
                <ul class="public-menu-card__variants" aria-label="Kích cỡ và giá">
                    @foreach ($product->productVariants as $variant)
                        <li class="variant-item @if($loop->first) is-primary @endif">
                            <span class="variant-name">{{ $variant->name }} @if($variant->status === 'unavailable') <small>· Tạm hết</small> @endif</span>
                            <span class="variant-divider"></span>
                            <strong class="variant-price">{{ number_format((float) $variant->price, 2, ',', '.') }} ₫</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <a class="coffee-button coffee-button--primary storefront-card-button" href="{{ route('menu.show', ['product' => $product->slug]) }}">
            <span>{{ $product->status === 'available' ? 'Chọn món' : 'Xem chi tiết' }}</span>
            <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" class="btn-arrow">
                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>
</article>
