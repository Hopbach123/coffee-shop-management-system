@extends('layouts.public')

@section('title', 'Thực đơn — Maison du Café')

@section('content')
    <section class="public-menu-intro coffee-section" id="home" aria-labelledby="menu-title">
        <div class="coffee-container">
            <p class="section-heading__eyebrow">Maison du Café · Thực đơn</p>
            <h1 id="menu-title">Một tách cà phê cho từng khoảnh khắc.</h1>
            <p>Khám phá các món, kích cỡ, giá bán và tình trạng hiện tại.</p>

            @if ($categories->isNotEmpty())
                <nav class="public-menu-categories" aria-label="Danh mục thực đơn" data-menu-category-nav @if ($search !== '') hidden @endif>
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}">{{ $category->name }}</a>
                    @endforeach
                </nav>
            @endif

            <form class="public-menu-search" method="GET" action="{{ route('menu.index') }}" data-menu-search-form data-search-endpoint="{{ route('menu.search') }}" role="search">
                <label for="menu-search">Tìm món</label>
                <input id="menu-search" name="search" type="search" value="{{ $search }}" placeholder="Tìm món..." maxlength="100" autocomplete="off">
            </form>
            <p class="public-menu-search-status" role="status" aria-live="polite" data-menu-search-status hidden></p>
        </div>
    </section>

    <div class="coffee-container public-menu-content">
        <div data-menu-default @if ($search !== '') hidden @endif>
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
                <section class="public-menu-category" id="category-{{ $category->id }}" aria-labelledby="category-title-{{ $category->id }}" data-menu-category>
                    <div class="public-menu-category__heading">
                        <div>
                            <p class="section-heading__eyebrow">Danh mục {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                            <h2 id="category-title-{{ $category->id }}">{{ $category->name }}</h2>
                            @if ($category->description)
                                <p>{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($category->products->isEmpty())
                        <p class="public-menu-category__empty">Danh mục này chưa có món để hiển thị.</p>
                    @else
                        <div class="public-menu-grid" id="category-products-{{ $category->id }}">
                            @foreach ($category->products as $product)
                                @include('public.menu._product-card', ['product' => $product])
                            @endforeach
                        </div>
                        @if ($category->products->count() > 3)
                            <x-public.button variant="outline" type="button" class="public-menu-load-more" data-menu-load-more aria-controls="category-products-{{ $category->id }}" aria-label="Xem thêm món trong danh mục {{ $category->name }}" hidden>Xem thêm</x-public.button>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>
        <div data-menu-search-results aria-live="polite" @if ($search === '') hidden @endif>
            @if ($search !== '')
                @include('public.menu._search-results', ['categories' => $searchCategories, 'query' => $search])
            @endif
        </div>
    </div>
@endsection
