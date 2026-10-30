@extends('layouts.internal')
@section('title', 'Công thức biến thể: ' . $productVariant->name)
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Định lượng pha chế</p>
                <h1 id="page-title">Công thức: {{ $productVariant->product?->name }} ({{ $productVariant->name }})</h1>
                <p>Định mức nguyên liệu tiêu hao cho biến thể này khi quầy Barista thực hiện pha chế.</p>
            </div>
            <x-public.button href="{{ route('admin.product-variants.index') }}" variant="outline">← Về danh sách biến thể</x-public.button>
        </div>

        <div class="category-panel">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--coffee-border);">
                <div>
                    <span style="color: var(--coffee-text-muted); font-size: var(--text-small); display: block;">Sản phẩm cha</span>
                    <strong>{{ $productVariant->product?->name ?? '—' }}</strong>
                </div>
                <div>
                    <span style="color: var(--coffee-text-muted); font-size: var(--text-small); display: block;">Tên biến thể / Size</span>
                    <strong>{{ $productVariant->name }}</strong>
                </div>
                <div>
                    <span style="color: var(--coffee-text-muted); font-size: var(--text-small); display: block;">Mã SKU</span>
                    <code>{{ $productVariant->sku ?? '—' }}</code>
                </div>
                <div>
                    <span style="color: var(--coffee-text-muted); font-size: var(--text-small); display: block;">Giá bán</span>
                    <strong style="color: var(--coffee-espresso);">{{ number_format((float) $productVariant->price, 0, ',', '.') }} ₫</strong>
                </div>
            </div>

            <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Thành phần nguyên liệu tiêu hao ({{ $productVariant->productRecipes->count() }} thành phần)</h2>

            @if ($productVariant->productRecipes->isEmpty())
                <div class="category-empty">
                    <h2>Chưa thiết lập công thức</h2>
                    <p>Biến thể này chưa có công thức định lượng nguyên liệu trong cơ sở dữ liệu.</p>
                </div>
            @else
                <div class="category-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách nguyên liệu trong công thức</caption>
                        <thead>
                            <tr>
                                <th scope="col">STT</th>
                                <th scope="col">Tên nguyên liệu</th>
                                <th scope="col">Định lượng tiêu hao</th>
                                <th scope="col">Đơn vị tính</th>
                                <th scope="col">Tồn kho hiện tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productVariant->productRecipes as $index => $recipe)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <th scope="row">
                                        <strong>{{ $recipe->ingredient?->name ?? 'Nguyên liệu #' . $recipe->ingredient_id }}</strong>
                                    </th>
                                    <td>
                                        <strong style="color: var(--coffee-espresso);">{{ number_format((float) $recipe->quantity, 3, ',', '.') }}</strong>
                                    </td>
                                    <td>{{ $recipe->ingredient?->unit ?? '—' }}</td>
                                    <td>
                                        @if($recipe->ingredient)
                                            <span class="sneat-pill {{ (float)$recipe->ingredient->current_stock <= (float)$recipe->ingredient->minimum_stock ? 'is-warning' : 'is-success' }}">
                                                {{ number_format((float)$recipe->ingredient->current_stock, 2, ',', '.') }} {{ $recipe->ingredient->unit }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p class="sneat-hint" style="margin-top: 1.5rem;">
                <strong>Ghi chú tích hợp kho:</strong> Dữ liệu công thức được đọc từ bảng <code>product_recipes</code> liên kết trực tiếp với <code>ingredients</code>. Việc cập nhật công thức hoặc tự động trừ kho nguyên liệu (consume transaction) yêu cầu <code>InventoryService</code> và audit log, hiện tại màn hình hiển thị ở chế độ xem an toàn (Read-only).
            </p>
        </div>
    </section>
@endsection
