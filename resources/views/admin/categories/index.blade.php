@extends('layouts.internal')
@section('title', 'Danh mục')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <span>Danh mục</span>
                </div>
                <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Danh mục</h1>
                <p class="cafe-page-subtitle">Quản lý danh mục sản phẩm và trạng thái hiển thị.</p>
            </div>
            <div>
                <a href="{{ route('admin.categories.create') }}" class="cafe-btn-primary">
                    <x-internal.icon name="plus" size="18" /> Thêm danh mục
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="sneat-alert sneat-alert--success cafe-notice" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="sneat-alert sneat-alert--danger cafe-notice" role="alert">{{ session('error') }}</div>
        @endif

        <div class="cafe-card category-panel">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="v5-toolbar category-search" role="search">
                <div class="v5-search-wrap">
                    <x-internal.icon name="search" size="16" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cafe-text-secondary);" />
                    <label class="sr-only" for="category-search">Tìm danh mục</label><input id="category-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Tìm kiếm danh mục theo tên..." class="cafe-input" style="padding-left: 40px;">
                </div>
                <button type="submit" class="cafe-btn-secondary">Tìm kiếm</button>
                @if ($search !== '')
                    <a href="{{ route('admin.categories.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
                @endif
            </form>

            @if ($categories->isEmpty())
                <div class="category-empty" style="text-align: center; padding: 48px 20px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--cafe-cream); display: grid; place-items: center; margin: 0 auto 16px; color: var(--cafe-caramel);">
                        <x-internal.icon name="coffee" size="28" />
                    </div>
                    @if ($search !== '')
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Không tìm thấy danh mục</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thử một tên khác hoặc xóa bộ lọc để xem toàn bộ danh mục.</p>
                        <a href="{{ route('admin.categories.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
                    @else
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Chưa có danh mục</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thêm danh mục đầu tiên để bắt đầu quản lý thực đơn.</p>
                        <a href="{{ route('admin.categories.create') }}" class="cafe-btn-primary">
                            <x-internal.icon name="plus" size="16" /> Thêm danh mục
                        </a>
                    @endif
                </div>
            @else
                <div class="cafe-table-wrap category-table-wrap">
                    <table class="cafe-table category-table">
                        <caption class="sr-only">Danh sách danh mục</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 65px; text-align: center;">STT</th>
                                <th scope="col" style="width: 70px;">Ảnh</th>
                                <th scope="col">Tên danh mục</th>
                                <th scope="col">Mô tả</th>
                                <th scope="col" style="width: 90px; text-align: center;">Thứ tự</th>
                                <th scope="col" style="width: 140px;">Trạng thái</th>
                                <th scope="col" class="v5-action-heading">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td style="text-align: center; color: var(--cafe-text-secondary);">{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                    <td>
                                        <div class="cafe-thumbnail">
                                            @if ($category->image)
                                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                                            @else
                                                <x-internal.icon name="coffee" size="20" style="color: var(--cafe-caramel);" />
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--cafe-espresso); font-weight: 600;">{{ $category->name }}</strong>
                                    </td>
                                    <td style="color: var(--cafe-text-secondary); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $category->description !== null && $category->description !== '' ? $category->description : '—' }}
                                    </td>
                                    <td style="text-align: center;">{{ $category->display_order }}</td>
                                    <td>
                                        <span class="cafe-badge cafe-badge--{{ $category->status === 'active' ? 'success' : 'warning' }}">
                                            {{ $category->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}
                                        </span>
                                    </td>
                                    <td data-label="Thao tác" style="text-align: right;">
                                        <div class="v5-actions">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="cafe-btn-icon" title="Chỉnh sửa">
                                                <x-internal.icon name="edit" size="15" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.categories.toggle-status', $category) }}" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="cafe-btn-icon" title="{{ $category->status === 'active' ? 'Ngừng hoạt động' : 'Kích hoạt' }}">
                                                    <x-internal.icon name="eye" size="15" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');" style="display: inline;">
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
                        Hiển thị {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} / {{ $categories->total() }} kết quả
                    </div>
                    @if ($categories->hasPages())
                        <div class="category-pagination">{{ $categories->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
