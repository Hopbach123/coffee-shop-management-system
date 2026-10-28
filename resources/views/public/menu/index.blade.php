@extends('layouts.public')

@section('title', 'Thực đơn — Maison du Café')

@section('content')
    <!-- Menu Hero Header -->
    <section class="public-menu-intro coffee-section" id="home" aria-labelledby="menu-title">
        <div class="coffee-container">
            <div class="menu-intro-badge">
                <span class="badge-sparkle">✦</span>
                <span>MAISON DU CAFÉ · THỰC ĐƠN ĐẶC TRƯNG</span>
            </div>
            <h1 id="menu-title" class="menu-intro-title">Một tách cà phê cho từng khoảnh khắc.</h1>
            <p class="menu-intro-desc">Khám phá các dòng cà phê thủ công hảo hạng, trà thanh mát và những thức uống được chế tác công phu tại xưởng rang Maison.</p>

            <!-- Category Pills Navigation (Matching Reference Aesthetics) -->
            @if ($categories->isNotEmpty())
                <nav class="public-menu-categories" aria-label="Danh mục thực đơn" data-menu-category-nav @if ($search !== '') hidden @endif>
                    <a href="#all" class="category-pill is-active" data-category-filter="all">
                        <span class="pill-icon">✨</span>
                        <span>Tất cả món</span>
                    </a>
                    @foreach ($categories as $category)
                        <a href="#category-{{ $loop->iteration }}" class="category-pill" data-category-filter="category-{{ $loop->iteration }}">
                            @if (stripos($category->name, 'trà') !== false || stripos($category->name, 'matcha') !== false)
                                <span class="pill-icon">🍵</span>
                            @else
                                <span class="pill-icon">☕</span>
                            @endif
                            <span>{{ $category->name }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            <!-- Luxury Search Form -->
            <form class="public-menu-search" method="GET" action="{{ route('menu.index') }}" data-menu-search-form data-search-endpoint="{{ route('menu.search') }}" role="search">
                <label for="menu-search" class="sr-only">Tìm món</label>
                <div class="menu-search-box">
                    <span class="menu-search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input id="menu-search" name="search" type="search" value="{{ $search }}" placeholder="Tìm kiếm món yêu thích của bạn (cà phê sữa, bạc xỉu, trà đào...)" maxlength="100" autocomplete="off">
                    @if ($search !== '')
                        <a href="{{ route('menu.index') }}" class="menu-search-clear" aria-label="Xóa tìm kiếm">✕</a>
                    @endif
                </div>
            </form>
            <p class="public-menu-search-status" role="status" aria-live="polite" data-menu-search-status hidden></p>
        </div>
    </section>

    <!-- Main Menu Content -->
    <div class="coffee-container public-menu-content">
        <div data-menu-default @if ($search !== '') hidden @endif>
            @if ($categories->isEmpty())
                <div class="public-menu-empty" role="status">
                    <span class="empty-icon">☕</span>
                    <h2>Thực đơn đang được cập nhật</h2>
                    <p>Hiện chưa có danh mục để hiển thị. Quý khách vui lòng quay lại sau.</p>
                </div>
            @elseif (! $hasProducts)
                <div class="public-menu-empty" role="status">
                    <span class="empty-icon">☕</span>
                    <h2>Chưa có món trong thực đơn</h2>
                    <p>Các món mới sẽ được cập nhật sớm nhất tại xưởng rang.</p>
                </div>
            @endif

            @foreach ($categories as $category)
                <section class="public-menu-category" id="category-{{ $loop->iteration }}" aria-labelledby="category-title-{{ $loop->iteration }}" data-menu-category>
                    <div class="public-menu-category__heading">
                        <div class="category-heading-card">
                            <div class="category-meta-row">
                                <span class="category-counter">DANH MỤC {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="category-ornament-line"></span>
                            </div>
                            <h2 id="category-title-{{ $loop->iteration }}" class="category-main-title">{{ $category->name }}</h2>
                            @if ($category->description)
                                <p class="category-desc">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($category->products->isEmpty())
                        <div class="public-menu-category__empty-card">
                            <p>Danh mục này hiện chưa có món để hiển thị.</p>
                        </div>
                    @else
                        <div class="public-menu-grid" id="category-products-{{ $loop->iteration }}">
                            @foreach ($category->products as $product)
                                @include('public.menu._product-card', ['product' => $product])
                            @endforeach
                        </div>
                        @if ($category->products->count() > 3)
                            <x-public.button variant="outline" type="button" class="public-menu-load-more" data-menu-load-more aria-controls="category-products-{{ $loop->iteration }}" aria-label="Xem thêm món trong danh mục {{ $category->name }}" hidden>Xem thêm món</x-public.button>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>

        <!-- Live Search Results Container -->
        <div data-menu-search-results aria-live="polite" @if ($search === '') hidden @endif>
            @if ($search !== '')
                <div class="search-result-header">
                    <h2>Kết quả tìm kiếm cho: "<em>{{ $search }}</em>"</h2>
                    <a href="{{ route('menu.index') }}" class="search-reset-link">← Quay lại toàn bộ thực đơn</a>
                </div>
                <div class="public-menu-grid">
                    @foreach ($categories as $category)
                        @foreach ($category->products as $product)
                            @include('public.menu._product-card', ['product' => $product])
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection