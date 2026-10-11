@extends('layouts.public')

@section('title', 'Thực đơn — Maison du Café')

@section('content')
    <section class="public-menu-intro coffee-section" id="home" aria-labelledby="menu-title">
        <div class="coffee-container">
            <p class="section-heading__eyebrow">Maison du Café · Thực đơn</p>
            <h1 id="menu-title">Một tách cà phê cho từng khoảnh khắc.</h1>
            <p>Khám phá các món, kích cỡ, giá bán và tình trạng hiện tại.</p>

            @if ($categories->isNotEmpty())
                <nav class="public-menu-categories" aria-label="Danh mục thực đơn">
                    @foreach ($categories as $category)
                        <a href="#category-{{ $loop->iteration }}">{{ $category->name }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>

    <div class="coffee-container public-menu-content">
        @if ($categories->isEmpty())
            <div class="public-menu-empty" role="status">
                <h2>Thực đơn đang được cập nhật</h2>
                <p>Hiện chưa có danh mục để hiển thị. Hãy quay lại sau.</p>
            </div>
        @elseif (! $hasProducts)
            <div class="public-menu-empty" role="status">
                <h2>Chưa có món trong thực đơn</h2>
                <p>Các món sẽ được cập nhật sớm.</p>
            </div>
        @endif

        @foreach ($categories as $category)
            <section class="public-menu-category" id="category-{{ $loop->iteration }}" aria-labelledby="category-title-{{ $loop->iteration }}" data-menu-category>
                <div class="public-menu-category__heading">
                    <div>
                        <p class="section-heading__eyebrow">Danh mục {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h2 id="category-title-{{ $loop->iteration }}">{{ $category->name }}</h2>
                        @if ($category->description)
                            <p>{{ $category->description }}</p>
                        @endif
                    </div>
                </div>

                @if ($category->products->isEmpty())
                    <p class="public-menu-category__empty">Danh mục này chưa có món để hiển thị.</p>
                @else
                    <div class="public-menu-grid" id="category-products-{{ $loop->iteration }}">
                        @foreach ($category->products as $product)
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
                        @endforeach
                    </div>
                    @if ($category->products->count() > 3)
                        <x-public.button variant="outline" type="button" class="public-menu-load-more" data-menu-load-more aria-controls="category-products-{{ $loop->iteration }}" aria-label="Xem thêm món trong danh mục {{ $category->name }}" hidden>Xem thêm</x-public.button>
                    @endif
                @endif
            </section>
        @endforeach
    </div>
@endsection
