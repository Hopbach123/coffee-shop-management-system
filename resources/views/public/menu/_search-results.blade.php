@if ($query === '')
    <div class="public-menu-empty" role="status">
        <p>Nhập tên món để tìm kiếm.</p>
    </div>
@elseif ($categories->isEmpty())
    <div class="public-menu-empty" role="status">
        <h2>Không tìm thấy món phù hợp.</h2>
        <p>Thử từ khóa khác hoặc xóa tìm kiếm để xem toàn bộ thực đơn.</p>
    </div>
@else
    @foreach ($categories as $category)
        <section class="public-menu-category" aria-labelledby="search-category-title-{{ $loop->iteration }}">
            <div class="public-menu-category__heading">
                <h2 id="search-category-title-{{ $loop->iteration }}">{{ $category->name }}</h2>
            </div>
            <div class="public-menu-grid">
                @foreach ($category->products as $product)
                    @include('public.menu._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endforeach
@endif

@if ($query !== '')
    <div class="category-pagination">{{ $products->links() }}</div>
@endif
