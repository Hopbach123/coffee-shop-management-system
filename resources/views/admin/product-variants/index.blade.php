@extends('layouts.internal')
@section('title', 'Biến thể sản phẩm')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <span>Biến thể sản phẩm</span>
                </div>
                <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Size &amp; công thức</h1>
                <p class="cafe-page-subtitle">Quản lý kích cỡ, SKU, giá bán và công thức định lượng của từng món.</p>
            </div>
            <div>
                <a href="{{ route('admin.product-variants.create') }}" class="cafe-btn-primary">
                    <x-internal.icon name="plus" size="18" /> Thêm biến thể
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="sneat-alert sneat-alert--success cafe-notice" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="sneat-alert sneat-alert--danger cafe-notice" role="alert">{{ session('error') }}</div>
        @endif

        <div class="v5-context"><x-internal.icon name="chef" /><div><strong>Công thức theo từng size</strong><p>Chọn Định lượng tại dòng biến thể để cấu hình nguyên liệu. Lưu công thức không tự trừ tồn kho.</p></div></div>
        <div class="cafe-card category-panel">
            <form method="GET" action="{{ route('admin.product-variants.index') }}" class="v5-toolbar category-search" role="search">
                <div class="v5-search-wrap">
                    <x-internal.icon name="search" size="16" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cafe-text-secondary);" />
                    <label class="sr-only" for="variant-search">Tìm size hoặc SKU</label><input id="variant-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Tìm kiếm theo tên biến thể hoặc mã SKU..." class="cafe-input" style="padding-left: 40px;">
                </div>
                <button type="submit" class="cafe-btn-secondary">Tìm kiếm</button>
                @if ($search !== '')
                    <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
                @endif
            </form>

            @if ($variants->isEmpty())
                <div class="category-empty" style="text-align: center; padding: 48px 20px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--cafe-cream); display: grid; place-items: center; margin: 0 auto 16px; color: var(--cafe-caramel);">
                        <x-internal.icon name="box" size="28" />
                    </div>
                    @if ($search !== '')
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Không tìm thấy biến thể</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thử tên hoặc SKU khác, hoặc xóa bộ lọc.</p>
                        <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
                    @else
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Chưa có biến thể</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thêm biến thể đầu tiên để thiết lập giá bán cho sản phẩm.</p>
                        <a href="{{ route('admin.product-variants.create') }}" class="cafe-btn-primary">
                            <x-internal.icon name="plus" size="16" /> Thêm biến thể
                        </a>
                    @endif
                </div>
            @else
                <div class="cafe-table-wrap category-table-wrap product-table-wrap" role="region" aria-label="Danh sách biến thể sản phẩm" tabindex="0">
                    <table class="cafe-table category-table product-table">
                        <caption class="sr-only">Danh sách biến thể sản phẩm</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px; text-align: center;">STT</th>
                                <th scope="col">Sản phẩm</th>
                                <th scope="col">Tên biến thể</th>
                                <th scope="col" style="width: 140px;">SKU</th>
                                <th scope="col" style="width: 130px; text-align: right;">Giá bán</th>
                                <th scope="col" style="width: 120px;">Trạng thái</th>
                                <th scope="col" style="width: 110px; text-align: center;">Công thức</th>
                                <th scope="col" class="v5-action-heading">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($variants as $variant)
                                <tr>
                                    <td style="text-align: center; color: var(--cafe-text-secondary);" data-label="ID">{{ $loop->iteration + ($variants->currentPage() - 1) * $variants->perPage() }}</td>
                                    <td data-label="Sản phẩm">
                                        <strong style="color: var(--cafe-espresso);">
                                            {{ $variant->product?->name ?? '—' }}
                                        </strong>
                                        @if ($variant->product?->trashed()) <span style="font-size: 0.75rem; color: var(--cafe-danger);">(đã xóa)</span> @endif
                                    </td>
                                    <td data-label="Tên biến thể" style="font-weight: 600;">
                                        <span class="cafe-badge cafe-badge--info">{{ $variant->name }}</span>
                                    </td>
                                    <td data-label="SKU" style="font-family: monospace; font-size: 0.8125rem; color: var(--cafe-text-secondary);">{{ $variant->sku ?? '—' }}</td>
                                    <td data-label="Giá bán" style="text-align: right; font-weight: 700; color: var(--cafe-espresso);">
                                        {{ number_format((float) $variant->price, 2, ',', '.') }} ₫
                                    </td>
                                    <td data-label="Trạng thái">
                                        <span class="cafe-badge cafe-badge--{{ $variant->status === 'available' ? 'success' : 'warning' }}">
                                            {{ $variant->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}
                                        </span>
                                    </td>
                                    <td data-label="Công thức" style="text-align: center;">
                                        <a href="{{ route('admin.product-recipes.index', $variant) }}" class="cafe-btn-secondary" title="Cấu hình công thức pha chế">
                                            <x-internal.icon name="chef" size="14" /> Định lượng
                                        </a>
                                    </td>
                                    <td data-label="Thao tác" style="text-align: right;">
                                        <div class="category-actions v5-actions">
                                            <a href="{{ route('admin.product-variants.edit', $variant) }}" class="cafe-btn-icon" title="Sửa biến thể">
                                                <x-internal.icon name="edit" size="15" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.product-variants.toggle-status', $variant) }}" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="cafe-btn-icon" title="{{ $variant->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}">
                                                    <x-internal.icon name="eye" size="15" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.product-variants.destroy', $variant) }}" onsubmit="return confirm('Bạn có chắc muốn xóa biến thể này?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" title="Xóa">
                                                    <x-internal.icon name="trash" size="15" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; font-size: 0.8125rem; color: var(--cafe-text-secondary); flex-wrap: wrap; gap: 12px;">
                    <div>
                        Hiển thị {{ $variants->firstItem() ?? 0 }}–{{ $variants->lastItem() ?? 0 }} / {{ $variants->total() }} kết quả
                    </div>
                    @if ($variants->hasPages())
                        <div class="category-pagination">{{ $variants->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
