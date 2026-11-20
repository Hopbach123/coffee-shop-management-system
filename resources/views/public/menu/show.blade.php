@extends('layouts.public')

@section('title', $product->name . ' — Maison du Café')

@section('content')
    <section class="coffee-section public-menu-detail" id="home" aria-labelledby="product-title" style="padding-top: 32px; padding-bottom: 64px;">
        <div class="coffee-container">
            <nav class="public-menu-breadcrumb" aria-label="Đường dẫn" style="margin-bottom: 24px; font-size: 0.875rem;">
                <a href="{{ route('menu.index') }}" style="color: var(--coffee-caramel); font-weight: 500;">Thực đơn</a>
                <span aria-hidden="true" style="margin: 0 8px; color: var(--coffee-text-muted);">/</span>
                <span aria-current="page" style="color: var(--coffee-espresso); font-weight: 600;">{{ $product->name }}</span>
            </nav>

            <div class="public-menu-detail__grid" style="display: grid; grid-template-columns: minmax(300px, 440px) 1fr; gap: 48px; align-items: start;">
                {{-- Media Left Column --}}
                <div class="public-menu-detail__media" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--coffee-border); box-shadow: 0 6px 20px rgba(44, 27, 20, 0.08); aspect-ratio: 1/1; background: #FFFDF9; display: grid; place-items: center;">
                    @if ($imageUrl = $product->imageUrl())
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span aria-hidden="true">M</span>
                    @endif
                </div>

                {{-- Product Information & Ordering Right Column --}}
                <div class="public-menu-detail__copy" style="background: #FFFDF9; border: 1px solid var(--coffee-border); border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px rgba(44, 27, 20, 0.04);">
                    <p class="section-heading__eyebrow" style="color: var(--coffee-caramel); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.08em; margin-bottom: 6px;">{{ $product->category->name }}</p>
                    <h1 id="product-title" style="font-family: var(--font-display); font-size: clamp(1.75rem, 3vw, 2.25rem); color: var(--coffee-espresso); margin: 0 0 12px; font-weight: 700;">{{ $product->name }}</h1>
                    
                    <div class="public-menu-detail__badges" style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px;">
                        @if ($product->is_featured)
                            <span class="public-menu-featured" style="background: var(--coffee-surface-deep); color: var(--coffee-cocoa); padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Món nổi bật</span>
                        @endif
                        <span class="public-menu-availability {{ $product->status === 'available' ? 'public-menu-availability--available' : 'public-menu-availability--unavailable' }}" style="padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                            {{ $product->status === 'available' ? 'Có sẵn' : 'Hết món' }}
                        </span>
                    </div>

                    @if ($product->description)
                        <p class="public-menu-detail__description" style="color: var(--coffee-text-muted); font-size: 0.9375rem; line-height: 1.6; margin-bottom: 24px; border-bottom: 1px solid var(--coffee-border-subtle); padding-bottom: 16px;">
                            {{ $product->description }}
                        </p>
                    @endif

                    <h2 style="font-size: 1.05rem; font-weight: 600; color: var(--coffee-espresso); margin: 0 0 12px;">Kích cỡ và giá</h2>
                    @if ($product->productVariants->isEmpty())
                        <p style="color: var(--coffee-text-muted); font-style: italic;">Chưa có kích cỡ hoặc giá bán để hiển thị.</p>
                    @else
                        <ul class="public-menu-detail__variants" style="margin: 0 0 24px; padding: 0; display: grid; gap: 8px;">
                            @foreach ($product->productVariants as $variant)
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: var(--coffee-bg); border-radius: 8px; border: 1px solid var(--coffee-border-subtle);">
                                    <span style="font-weight: 600; color: var(--coffee-text);">
                                        {{ $variant->name }} @if($variant->status === 'unavailable') <small style="color: var(--coffee-danger); font-weight: normal;">· Tạm hết</small> @endif
                                    </span>
                                    <strong style="color: var(--coffee-espresso); font-size: 1rem;">{{ number_format((float) $variant->price, 2, ',', '.') }} ₫</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if(\Illuminate\Support\Facades\Route::has('cart.add') && $product->status === 'available' && $product->productVariants->contains('status', 'available'))
                        <form class="storefront-add-form" action="{{ route('cart.add') }}" method="POST" style="margin-top: 1.5rem; display: grid; gap: 1.25rem; background: var(--coffee-surface-alt); padding: 20px; border-radius: 12px; border: 1px solid var(--coffee-border);">
                            @csrf
                            <div>
                                <label for="choose-variant" style="font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--coffee-espresso);">Chọn kích cỡ</label>
                                <select name="variant_id" id="choose-variant" required style="width: 100%; min-height: 42px; padding: 0.6rem 0.8rem; border: 1px solid var(--coffee-border); border-radius: 8px; background: #ffffff; font-family: inherit; font-size: 0.9375rem; color: var(--coffee-text);">
                                    @foreach($product->productVariants->where('status', 'available') as $variant)
                                        <option value="{{ $variant->id }}">{{ $variant->name }} — {{ number_format((float)$variant->price, 0, ',', '.') }} ₫</option>
                                    @endforeach
                                </select>
                            </div>

                            @if(isset($toppings) && $toppings->isNotEmpty())
                                <div>
                                    <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: var(--coffee-espresso);">Topping thêm (tùy chọn):</label>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px;">
                                        @foreach($toppings as $topping)
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8125rem; background: #ffffff; padding: 8px 10px; border: 1px solid var(--coffee-border); border-radius: 8px; cursor: pointer; transition: border-color 0.15s ease;">
                                                <input type="checkbox" name="toppings[]" value="{{ $topping->id }}">
                                                <span>{{ $topping->name }} <strong style="color: var(--coffee-caramel);">(+{{ number_format((float)$topping->price, 0, ',', '.') }}đ)</strong></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label for="choose-note" style="font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--coffee-espresso);">Ghi chú món (không bắt buộc):</label>
                                <input type="text" id="choose-note" name="note" maxlength="255" placeholder="VD: ít đường, nhiều đá, sữa yến mạch..." style="width: 100%; min-height: 40px; padding: 0.5rem 0.75rem; border: 1px solid var(--coffee-border); border-radius: 8px; background: #ffffff; font-family: inherit;">
                            </div>

                            <div style="display: flex; gap: 12px; align-items: flex-end; margin-top: 6px;">
                                <div style="width: 110px;">
                                    <label for="choose-quantity" style="font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--coffee-espresso);">Số lượng</label>
                                    <input type="number" id="choose-quantity" name="quantity" min="1" max="20" value="1" required style="width: 100%; min-height: 44px; padding: 0.6rem; border: 1px solid var(--coffee-border); border-radius: 8px; background: #ffffff; text-align: center; font-weight: 600; font-size: 1rem;">
                                </div>
                                <button class="coffee-button coffee-button--primary" type="submit" style="flex: 1; min-height: 44px; padding: 0.7rem 1.5rem; font-weight: 600; cursor: pointer; border-radius: 8px; background: #AD764C; color: #ffffff; border: none; font-size: 0.9375rem; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(173, 118, 76, 0.25);">
                                    <x-internal.icon name="bag" size="18" /> Thêm vào giỏ hàng
                                </button>
                            </div>
                        </form>
                    @endif

                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--coffee-border-subtle);">
                        <x-public.button href="{{ route('menu.index') }}" variant="outline">← Quay lại thực đơn</x-public.button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
