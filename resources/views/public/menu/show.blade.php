@extends('layouts.public')

@section('title', $product->name . ' — Maison du Café')

@section('content')
    <section class="coffee-section public-menu-detail" id="home" aria-labelledby="product-title">
        <div class="coffee-container">
            <nav class="public-menu-breadcrumb" aria-label="Đường dẫn">
                <a href="{{ route('menu.index') }}">Thực đơn</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <div class="public-menu-detail__grid">
                <div class="public-menu-detail__media">
                    @if ($imageUrl = $product->imageUrl())
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                    @else
                        <img src="{{ asset('images/coffeehouse/artisan-espresso.png') }}" alt="Hình minh họa cà phê" loading="lazy">
                    @endif
                </div>
                <div class="public-menu-detail__copy">
                    <p class="section-heading__eyebrow">{{ $product->category->name }}</p>
                    <h1 id="product-title">{{ $product->name }}</h1>
                    <div class="public-menu-detail__badges">
                        @if ($product->is_featured)
                            <span class="public-menu-featured">Món nổi bật</span>
                        @endif
                        <span class="public-menu-availability {{ $product->status === 'available' ? 'public-menu-availability--available' : 'public-menu-availability--unavailable' }}">
                            {{ $product->status === 'available' ? 'Có sẵn' : 'Hết món' }}
                        </span>
                    </div>
                    @if ($product->description)
                        <p class="public-menu-detail__description">{{ $product->description }}</p>
                    @endif

                    <h2>Kích cỡ và giá</h2>
                    @if ($product->productVariants->isEmpty())
                        <p>Chưa có kích cỡ hoặc giá bán để hiển thị.</p>
                    @else
                        <ul class="public-menu-detail__variants">
                            @foreach ($product->productVariants as $variant)
                                <li>
                                    <span>{{ $variant->name }} @if($variant->status === 'unavailable') <small>· Tạm hết</small> @endif</span>
                                    <strong>{{ number_format((float) $variant->price, 2, ',', '.') }} ₫</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <x-public.button href="{{ route('menu.index') }}" variant="outline">Quay lại thực đơn</x-public.button>
                </div>
            </div>
        </div>
    </section>
@endsection
