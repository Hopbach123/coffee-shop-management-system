@extends('layouts.internal')
@section('title', 'Nguyên liệu')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <span>Kho & Nguyên liệu</span>
                </div>
                <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Nguyên liệu</h1>
                <p class="cafe-page-subtitle">Quản lý nguyên liệu, đơn vị tính, giá vốn và ngưỡng tồn an toàn.</p>
            </div>
            <div>
                <a href="{{ route('admin.ingredients.create') }}" class="cafe-btn-primary">
                    <x-internal.icon name="plus" size="18" /> Thêm nguyên liệu
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="sneat-alert sneat-alert--success cafe-notice" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="sneat-alert sneat-alert--danger cafe-notice" role="alert">{{ session('error') }}</div>
        @endif

        <div class="v5-context"><x-internal.icon name="box" /><div><strong>Nhập kho · Kiểm kê · Lịch sử</strong><p>Chọn thao tác tại từng nguyên liệu. Tồn kho chỉ thay đổi qua phiếu nghiệp vụ; chỉnh sửa thông tin không thay thế nhập hoặc điều chỉnh kho.</p></div></div>
        <div class="cafe-card category-panel">
            <form method="GET" action="{{ route('admin.ingredients.index') }}" class="v5-toolbar category-search" role="search">
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; min-width: 240px;">
                        <x-internal.icon name="search" size="16" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cafe-text-secondary);" />
                        <label for="ingredient-search" class="sr-only">Tìm nguyên liệu theo tên</label>
                        <input id="ingredient-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên nguyên liệu..." class="cafe-input" style="padding-left: 40px;">
                    </div>
                    <div style="min-width: 180px;">
                        <label for="stock-filter" class="sr-only">Tình trạng tồn kho</label>
                        <select id="stock-filter" name="stock" class="cafe-select">
                            <option value="">Tất cả tình trạng</option>
                            <option value="out" @selected($stock === 'out')>Hết tồn</option>
                            <option value="low" @selected($stock === 'low')>Dưới ngưỡng</option>
                            <option value="sufficient" @selected($stock === 'sufficient')>Đủ ngưỡng</option>
                        </select>
                    </div>
                    <button type="submit" class="cafe-btn-secondary">Tìm kiếm</button>
                    @if ($search !== '' || $stock !== '')
                        <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
                    @endif
                </div>
            </form>
            <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin-bottom: 20px; line-height: 1.5; background: var(--cafe-cream); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--cafe-border);">
                Hết tồn: bằng 0. Dưới ngưỡng: lớn hơn 0 và thấp hơn tồn tối thiểu. Bộ lọc tồn chỉ gồm nguyên liệu đang dùng; bằng ngưỡng vẫn đủ. Cảnh báo không tự ngừng bán món hoặc giữ nguyên liệu cho đơn.
            </p>

            @if ($ingredients->isEmpty())
                <div class="category-empty" style="text-align: center; padding: 48px 20px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--cafe-cream); display: grid; place-items: center; margin: 0 auto 16px; color: var(--cafe-caramel);">
                        <x-internal.icon name="box" size="28" />
                    </div>
                    @if ($search !== '' || $stock !== '')
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Không tìm thấy nguyên liệu</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thử tên hoặc tình trạng tồn khác, hoặc xóa bộ lọc.</p>
                        <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
                    @else
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Chưa có nguyên liệu</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thêm nguyên liệu đầu tiên để bắt đầu quản lý.</p>
                        <a href="{{ route('admin.ingredients.create') }}" class="cafe-btn-primary">
                            <x-internal.icon name="plus" size="16" /> Thêm nguyên liệu
                        </a>
                    @endif
                </div>
            @else
                <div class="cafe-table-wrap category-table-wrap" role="region" aria-label="Danh sách nguyên liệu" tabindex="0">
                    <table class="cafe-table category-table">
                        <caption class="sr-only">Danh sách nguyên liệu</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 50px; text-align: center;">ID</th>
                                <th scope="col">Tên nguyên liệu</th>
                                <th scope="col" style="width: 100px;">Đơn vị tính</th>
                                <th scope="col" style="width: 110px; text-align: right;">Tồn hiện tại</th>
                                <th scope="col" style="width: 110px; text-align: right;">Tồn tối thiểu</th>
                                <th scope="col" style="width: 150px;">Cảnh báo tồn</th>
                                <th scope="col" style="width: 120px; text-align: right;">Giá vốn</th>
                                <th scope="col" style="width: 120px;">Trạng thái</th>
                                <th scope="col" style="width: 240px; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ingredients as $ingredient)
                                <tr>
                                    <td data-label="ID" style="text-align: center; color: var(--cafe-text-secondary);">{{ $ingredient->id }}</td>
                                    <th scope="row" data-label="Tên" style="font-weight: 600; color: var(--cafe-espresso);">
                                        {{ $ingredient->name }}
                                    </th>
                                    <td data-label="Đơn vị tính">
                                        <span class="cafe-badge cafe-badge--info">{{ $ingredient->unit }}</span>
                                    </td>
                                    <td data-label="Tồn hiện tại" style="text-align: right; font-weight: 700; color: var(--cafe-espresso);">
                                        {{ $ingredient->current_stock }}
                                    </td>
                                    <td data-label="Tồn tối thiểu" style="text-align: right; color: var(--cafe-text-secondary);">
                                        {{ $ingredient->minimum_stock }}
                                    </td>
                                    <td data-label="Cảnh báo tồn">
                                        <span class="sneat-pill cafe-badge cafe-badge--{{ $ingredient->stock_level === 'sufficient' ? 'success' : ($ingredient->stock_level === 'low' ? 'warning' : ($ingredient->stock_level === 'out' ? 'danger' : 'info')) }} {{ in_array($ingredient->stock_level, ['low', 'out'], true) ? 'is-warning' : '' }}">
                                            {{ ['out' => 'Hết tồn', 'low' => 'Dưới ngưỡng', 'sufficient' => 'Đủ ngưỡng', 'inactive' => 'Ngừng dùng — không cảnh báo'][$ingredient->stock_level] }}
                                        </span>
                                    </td>
                                    <td data-label="Giá vốn" style="text-align: right; font-weight: 600;">
                                        {{ number_format((float) $ingredient->cost_price, 2, ',', '.') }} ₫
                                    </td>
                                    <td data-label="Trạng thái">
                                        <span class="cafe-badge cafe-badge--{{ $ingredient->status === 'active' ? 'success' : 'warning' }} category-status category-status--{{ $ingredient->status === 'active' ? 'active' : 'inactive' }}">
                                            {{ $ingredient->status === 'active' ? 'Đang dùng' : 'Ngừng dùng' }}
                                        </span>
                                    </td>
                                    <td data-label="Thao tác" style="text-align: right;">
                                        <details class="v5-row-actions"><summary>Thao tác kho</summary><div class="v5-row-actions__content">
                                            @if ($ingredient->status === 'active')
                                                <a href="{{ route('admin.ingredients.import.create', $ingredient) }}" class="cafe-btn-secondary" title="Nhập hàng vào kho">
                                                    Nhập kho
                                                </a>
                                                <a href="{{ route('admin.ingredients.adjustment.create', $ingredient) }}" class="cafe-btn-secondary" title="Kiểm kê điều chỉnh kho">
                                                    Điều chỉnh / kiểm kê
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.ingredients.history', $ingredient) }}" class="cafe-btn-secondary" title="Lịch sử biến động">
                                                Lịch sử kho
                                            </a>
                                            <a href="{{ route('admin.ingredients.edit', $ingredient) }}" class="cafe-btn-icon" title="Sửa">
                                                <x-internal.icon name="edit" size="14" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.ingredients.toggle-status', $ingredient) }}" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="cafe-btn-icon" title="{{ $ingredient->status === 'active' ? 'Ngừng sử dụng' : 'Sử dụng lại' }}">
                                                    <x-internal.icon name="eye" size="14" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.ingredients.destroy', $ingredient) }}" onsubmit="return confirm('Bạn có chắc muốn xóa nguyên liệu này?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger category-delete" title="Xóa">
                                                    <x-internal.icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        </div></details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; font-size: 0.8125rem; color: var(--cafe-text-secondary); flex-wrap: wrap; gap: 12px;">
                    <div>
                        Hiển thị {{ $ingredients->firstItem() ?? 0 }}–{{ $ingredients->lastItem() ?? 0 }} / {{ $ingredients->total() }} kết quả
                    </div>
                    @if ($ingredients->hasPages())
                        <div class="category-pagination">{{ $ingredients->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
