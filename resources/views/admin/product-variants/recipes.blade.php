@extends('layouts.internal')
@section('title', 'Công thức biến thể: ' . $productVariant->name)
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.product-variants.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Biến thể</a>
            <span>Định lượng</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Công thức: {{ $productVariant->product?->name }} ({{ $productVariant->name }})</h1>
        <p class="cafe-page-subtitle">Định mức nguyên liệu tiêu hao cho biến thể này khi quầy Barista thực hiện pha chế.</p>
    </div>
    <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">
        ← Về danh sách biến thể
    </a>
</div>

<div class="cafe-card" style="padding:1.5rem;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--cafe-border);">
        <div>
            <span style="color:var(--cafe-text-secondary);font-size:.8rem;display:block;margin-bottom:.2rem;">Sản phẩm cha</span>
            <strong>{{ $productVariant->product?->name ?? '—' }}</strong>
        </div>
        <div>
            <span style="color:var(--cafe-text-secondary);font-size:.8rem;display:block;margin-bottom:.2rem;">Tên biến thể / Size</span>
            <strong>{{ $productVariant->name }}</strong>
        </div>
        <div>
            <span style="color:var(--cafe-text-secondary);font-size:.8rem;display:block;margin-bottom:.2rem;">Mã SKU</span>
            <code>{{ $productVariant->sku ?? '—' }}</code>
        </div>
        <div>
            <span style="color:var(--cafe-text-secondary);font-size:.8rem;display:block;margin-bottom:.2rem;">Giá bán</span>
            <strong style="color:var(--cafe-espresso);">{{ number_format((float) $productVariant->price, 0, ',', '.') }} ₫</strong>
        </div>
    </div>

    <h2 style="font-size:1.1rem;font-weight:700;color:var(--cafe-text-primary);margin-bottom:1rem;">Thành phần nguyên liệu tiêu hao ({{ $productVariant->productRecipes->count() }} thành phần)</h2>

    @if ($productVariant->productRecipes->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="recipe" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">Chưa thiết lập công thức</p>
            <p style="font-size:.875rem;">Biến thể này chưa có công thức định lượng nguyên liệu trong cơ sở dữ liệu.</p>
        </div>
    @else
        <div class="cafe-table-wrap">
            <table class="cafe-table">
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
                                <strong style="color:var(--cafe-espresso);">{{ number_format((float) $recipe->quantity, 3, ',', '.') }}</strong>
                            </td>
                            <td>{{ $recipe->ingredient?->unit ?? '—' }}</td>
                            <td>
                                @if($recipe->ingredient)
                                    @php
                                        $stockClass = (float)$recipe->ingredient->current_stock <= (float)$recipe->ingredient->minimum_stock ? 'cafe-badge--warning' : 'cafe-badge--success';
                                    @endphp
                                    <span class="cafe-badge {{ $stockClass }}">
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

    <div class="cafe-notice cafe-notice--info" style="margin-top:1.5rem;margin-bottom:0;">
        <strong>Ghi chú tích hợp kho:</strong> Dữ liệu công thức được đọc từ bảng <code>product_recipes</code> liên kết trực tiếp với <code>ingredients</code>. Việc cập nhật công thức hoặc tự động trừ kho nguyên liệu (consume transaction) yêu cầu <code>InventoryService</code> và audit log, hiện tại màn hình hiển thị ở chế độ xem an toàn (Read-only).
    </div>
</div>
@endsection
