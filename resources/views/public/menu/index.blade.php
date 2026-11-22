@extends('layouts.public')

@section('title', 'Thực đơn — Maison du Café')

@section('content')
    {{-- Warm Editorial Hero Banner (matching design mockup) --}}
    <section class="public-menu-hero" style="background: linear-gradient(135deg, #2C1B14 0%, #4B3024 60%, #30241E 100%); color: #ffffff; padding: 48px 0 40px; margin-bottom: 24px; position: relative; overflow: hidden;">
        <div style="position: absolute; right: -40px; top: -40px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(173, 117, 71, 0.25) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
        <div class="coffee-container public-menu-hero__inner" style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; flex-wrap: wrap; gap: 24px;">
            <div class="public-menu-hero__copy" style="max-width: 580px;">
                <span style="display: inline-block; padding: 4px 12px; background: rgba(173, 117, 71, 0.3); border: 1px solid rgba(173, 117, 71, 0.5); border-radius: 9999px; font-size: 0.8125rem; font-weight: 600; color: #E7D8BF; margin-bottom: 12px; letter-spacing: 0.05em; text-transform: uppercase;">
                    Maison du Café · Premium Coffee Boutique
                </span>
                <h1 style="font-family: var(--font-display, Georgia, serif); font-size: clamp(2rem, 4vw, 2.75rem); line-height: 1.15; color: #FFFDF9; margin: 0 0 10px; font-weight: 700;">
                    Good Coffee &bull; Good Mood
                </h1>
                <p style="font-size: 1rem; color: #D8C7AE; margin: 0 0 20px; line-height: 1.5;">
                    Thưởng thức những tách cà phê đặc sản được pha chế thủ công từ hạt nguyên chất tuyển chọn, đánh thức mọi giác quan của bạn.
                </p>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="#menu-catalog" class="coffee-button coffee-button--primary" style="background: #AD764C; color: #ffffff; border-radius: 9999px; padding: 10px 24px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Khám phá thực đơn</span> &rarr;
                    </a>
                </div>
            </div>
            <div class="public-menu-hero__mark" style="width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); display: grid; place-items: center; color: #E7D8BF; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <x-internal.icon name="coffee" size="64" />
            </div>
        </div>
    </section>

    <section id="menu-catalog" class="public-menu-intro coffee-section" aria-labelledby="menu-title" style="padding-top: 10px; border-bottom: none;">
        <div class="coffee-container">
            <p class="section-heading__eyebrow">Maison du Café · Thực đơn</p>
            <h1 id="menu-title">Một tách cà phê cho từng khoảnh khắc.</h1>
            <p>Khám phá các món, kích cỡ, giá bán và tình trạng hiện tại.</p>

            @if ($categories->isNotEmpty())
                <nav class="public-menu-categories" aria-label="Danh mục thực đơn" data-menu-category-nav @if ($search !== '') hidden @endif style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;">
                    <a href="#menu-catalog" style="padding: 8px 18px; border-radius: 9999px; background: #2F1E18; color: #ffffff; font-weight: 600; text-decoration: none; font-size: 0.875rem;">
                        Tất cả
                    </a>
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}" style="padding: 8px 18px; border-radius: 9999px; background: #ffffff; border: 1px solid #E6DDD2; color: #30241E; font-weight: 600; text-decoration: none; font-size: 0.875rem; transition: all 0.2s ease;">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <form class="public-menu-search" method="GET" action="{{ route('menu.index') }}" data-menu-search-form data-search-endpoint="{{ route('menu.search') }}" role="search" style="margin-top: 24px;">
                <label for="menu-search">Tìm món</label>
                <div style="position: relative;">
                    <input id="menu-search" name="search" type="search" value="{{ $search }}" placeholder="Tìm kiếm theo tên món, hương vị..." maxlength="100" autocomplete="off" style="border-radius: 12px; border: 1px solid #E6DDD2; padding: 12px 18px; background: #FFFDF9;">
                </div>
            </form>
            <p class="public-menu-search-status" role="status" aria-live="polite" data-menu-search-status hidden></p>
        </div>
    </section>

    <div class="coffee-container public-menu-content">
        <div data-menu-default @if ($search !== '') hidden @endif>
            @if ($categories->isEmpty())
                <div class="public-menu-empty" role="status" style="text-align: center; padding: 60px 20px; background: #FFFDF9; border-radius: 14px; border: 1px solid #E6DDD2;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #2F1E18; margin-bottom: 8px;">Thực đơn đang được cập nhật</h2>
                    <p style="color: #786B61;">Hiện chưa có danh mục để hiển thị. Hãy quay lại sau.</p>
                </div>
            @elseif (! $hasProducts)
                <div class="public-menu-empty" role="status" style="text-align: center; padding: 60px 20px; background: #FFFDF9; border-radius: 14px; border: 1px solid #E6DDD2;">
                    <h2 style="font-size: 1.25rem; font-weight: 600; color: #2F1E18; margin-bottom: 8px;">Chưa có món trong thực đơn</h2>
                    <p style="color: #786B61;">Các món sẽ được cập nhật sớm.</p>
                </div>
            @endif

            @foreach ($categories as $category)
                <section class="public-menu-category" id="category-{{ $category->id }}" aria-labelledby="category-title-{{ $category->id }}" data-menu-category style="padding-top: 40px;">
                    <div class="public-menu-category__heading" style="margin-bottom: 24px; border-bottom: 1px solid #E6DDD2; padding-bottom: 14px;">
                        <div>
                            <p class="section-heading__eyebrow" style="color: #AD764C; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.08em; margin-bottom: 4px;">Danh mục {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                            <h2 id="category-title-{{ $category->id }}" style="font-family: var(--font-display); font-size: 1.75rem; color: #2F1E18; margin: 0 0 6px;">{{ $category->name }}</h2>
                            @if ($category->description)
                                <p style="color: #786B61; margin: 0; font-size: 0.9375rem;">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($category->products->isEmpty())
                        <p class="public-menu-category__empty" style="color: #786B61; font-style: italic;">Danh mục này chưa có món để hiển thị.</p>
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
