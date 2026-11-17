@extends('layouts.internal')
@section('title', 'Gọi món tại bàn')
@section('content')
<section class="table-menu category-page" aria-labelledby="page-title" data-table-menu data-read-url="{{ route('staff.table-order.index', [$table->id, $order->id]) }}" data-save-url="{{ route('staff.table-order.store', [$table->id, $order->id]) }}" data-csrf="{{ csrf_token() }}">
    <header class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Phục vụ tại bàn</p><h1 id="page-title">Gọi món · {{ $table->table_code }}</h1><p>{{ $table->area->name }} · {{ $table->status }} · Đơn {{ $order->order_code }} (#{{ $order->id }})</p></div>
        <x-public.button :href="route('staff.table-checkout.show', [$table->id, $order->id])" variant="outline">Xem tạm tính</x-public.button>
        <x-public.button :href="route('staff.orders.show', $order->id)" variant="outline">Chi tiết đơn / sửa món đã lưu</x-public.button>
        @if (auth('web')->user()->role === 'admin')<x-public.button :href="route('admin.tables.index', ['view' => 'floor', 'area_id' => $table->area_id])" variant="outline">Sơ đồ bàn</x-public.button>@else<x-public.button :href="route('staff.tables.index')" variant="outline">Danh sách bàn</x-public.button>@endif
    </header>
    <noscript><p role="alert">Bật JavaScript để soạn draft tại trang này. Bạn có thể thêm món qua trang Chi tiết đơn.</p></noscript>
    <p role="status" aria-live="polite" data-menu-message></p>
    <nav class="table-menu-jumps" aria-label="Chuyển nhanh trong đơn">
        <a class="coffee-button coffee-button--outline" href="#menu-catalog">Chọn món</a>
        <a class="coffee-button coffee-button--outline" href="#draft-title" data-cart-jump>Món chưa lưu <span data-draft-count>0</span></a>
        <a class="coffee-button coffee-button--outline" href="#saved-title">Món đã lưu</a>
    </nav>
    <div class="table-menu-layout">
        <section class="sneat-card" id="menu-catalog" tabindex="-1" aria-label="Chọn món">
            <form method="GET" class="table-menu-filters">
                <div class="category-field"><label for="menu-search">Tên sản phẩm hoặc SKU</label><input id="menu-search" type="search" name="search" maxlength="100" value="{{ $filters['search'] ?? '' }}"></div>
                <div class="category-field"><label for="menu-category">Danh mục</label><select name="category_id" id="menu-category"><option value="">Tất cả danh mục</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <x-public.button type="submit">Lọc món</x-public.button><x-public.button :href="route('staff.table-order.index', [$table->id, $order->id])" variant="outline">Xóa lọc</x-public.button>
            </form>
            <div class="table-menu-products">
            @forelse ($variants as $variant)
                <article class="table-menu-product">
                    @if ($image = $variant->product->imageUrl())<img src="{{ $image }}" alt="" loading="lazy" width="240" height="160">@endif
                    <h2>{{ $variant->product->name }}</h2><p>{{ $variant->product->category?->name ?? 'Chưa có danh mục' }} · {{ $variant->name }}</p><p class="table-menu-price">{{ $variant->price }} đ</p>
                    <x-public.button type="button" aria-label="Thêm {{ $variant->product->name }} · {{ $variant->name }} vào draft" data-draft-add data-variant="{{ $variant->id }}" data-name="{{ $variant->product->name }} · {{ $variant->name }}" data-price="{{ $variant->price }}">Thêm vào draft</x-public.button>
                </article>
            @empty <p>Không có sản phẩm phù hợp đang bán.</p>@endforelse
            </div>
            <div class="category-pagination">{{ $variants->links() }}</div>
        </section>
        <details class="sneat-card table-menu-cart" data-cart-panel open>
            <summary id="draft-title"><h2>Món chưa lưu</h2><span>Thu / mở giỏ</span></summary>
            <p>Draft chỉ ở trang này; đổi trang sẽ mất draft sau khi bạn xác nhận rời trang.</p>
            <div data-draft-lines></div><p class="table-menu-estimate" data-draft-estimate>Tạm tính draft: 0.00 đ</p>
            <div class="table-menu-cart-actions"><x-public.button type="button" data-draft-save disabled>Lưu từng món</x-public.button><button type="button" class="coffee-button coffee-button--outline" data-order-refresh>Đọc lại đơn</button></div>
            <p>Khi kết quả lưu chưa xác định, kiểm tra đơn trước khi rời trang hoặc soạn lại.</p>
        </details>
    </div>
    <section class="sneat-card table-menu-saved" aria-labelledby="saved-title"><h2 id="saved-title" tabindex="-1">Món đã lưu</h2><div data-saved-lines>
        @forelse ($snapshot['items'] as $item)<p>{{ $item['product_name'] }} · {{ $item['variant_name'] }} × {{ $item['quantity'] }} · Đơn giá {{ $item['unit_price'] }} · Thành tiền {{ $item['subtotal'] }} đ · {{ $item['status'] }} @if ($item['note'])— {{ $item['note'] }}@endif</p>@empty<p>Đơn chưa có món đã lưu.</p>@endforelse
    </div><p data-order-total>Tổng đơn theo backend: {{ $snapshot['total_amount'] }} đ (giảm {{ $snapshot['discount_amount'] }}, thuế {{ $snapshot['tax_amount'] }})</p></section>
</section>
@endsection
