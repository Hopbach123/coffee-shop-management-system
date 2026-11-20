@extends('layouts.internal')
@section('title', 'Topping')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <span>Topping</span>
                </div>
                <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Topping & Món kèm</h1>
                <p class="cafe-page-subtitle">Quản lý topping dùng chung, giá bán bổ sung và trạng thái sẵn có.</p>
            </div>
            <div>
                <a href="{{ route('admin.toppings.create') }}" class="cafe-btn-primary">
                    <x-internal.icon name="plus" size="18" /> Thêm topping
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
            <form method="GET" action="{{ route('admin.toppings.index') }}" class="v5-toolbar category-search" role="search">
                <div class="v5-search-wrap">
                    <x-internal.icon name="search" size="16" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cafe-text-secondary);" />
                    <label class="sr-only" for="topping-search">Tìm topping</label><input id="topping-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Tìm kiếm topping theo tên..." class="cafe-input" style="padding-left: 40px;">
                </div>
                <button type="submit" class="cafe-btn-secondary">Tìm kiếm</button>
                @if ($search !== '')
                    <a href="{{ route('admin.toppings.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
                @endif
            </form>

            @if ($toppings->isEmpty())
                <div class="category-empty" style="text-align: center; padding: 48px 20px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--cafe-cream); display: grid; place-items: center; margin: 0 auto 16px; color: var(--cafe-caramel);">
                        <x-internal.icon name="tag" size="28" />
                    </div>
                    @if ($search !== '')
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Không tìm thấy topping</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thử tên khác hoặc xóa bộ lọc.</p>
                        <a href="{{ route('admin.toppings.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
                    @else
                        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin-bottom: 6px;">Chưa có topping</h2>
                        <p style="color: var(--cafe-text-secondary); margin-bottom: 16px;">Thêm topping đầu tiên để phục vụ bán hàng.</p>
                        <a href="{{ route('admin.toppings.create') }}" class="cafe-btn-primary">
                            <x-internal.icon name="plus" size="16" /> Thêm topping
                        </a>
                    @endif
                </div>
            @else
                <div class="cafe-table-wrap category-table-wrap" role="region" aria-label="Danh sách topping" tabindex="0">
                    <table class="cafe-table category-table">
                        <caption class="sr-only">Danh sách topping</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px; text-align: center;">STT</th>
                                <th scope="col">Tên topping</th>
                                <th scope="col" style="width: 150px; text-align: right;">Giá bán</th>
                                <th scope="col" style="width: 140px;">Trạng thái</th>
                                <th scope="col" class="v5-action-heading">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($toppings as $topping)
                                <tr>
                                    <td style="text-align: center; color: var(--cafe-text-secondary);" data-label="ID">{{ $loop->iteration + ($toppings->currentPage() - 1) * $toppings->perPage() }}</td>
                                    <th scope="row" data-label="Tên" style="font-weight: 600; color: var(--cafe-espresso);">
                                        {{ $topping->name }}
                                    </th>
                                    <td data-label="Giá bán" style="text-align: right; font-weight: 700; color: var(--cafe-espresso);">
                                        {{ number_format((float) $topping->price, 2, ',', '.') }} ₫
                                    </td>
                                    <td data-label="Trạng thái">
                                        <span class="cafe-badge cafe-badge--{{ $topping->status === 'available' ? 'success' : 'warning' }}">
                                            {{ $topping->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}
                                        </span>
                                    </td>
                                    <td data-label="Thao tác" style="text-align: right;">
                                        <div class="category-actions v5-actions">
                                            <a href="{{ route('admin.toppings.edit', $topping) }}" class="cafe-btn-icon" title="Sửa topping">
                                                <x-internal.icon name="edit" size="15" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.toppings.toggle-status', $topping) }}" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="cafe-btn-icon" title="{{ $topping->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}">
                                                    <x-internal.icon name="eye" size="15" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.toppings.destroy', $topping) }}" onsubmit="return confirm('Bạn có chắc muốn xóa topping này?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger category-delete" title="Xóa">
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
                        Hiển thị {{ $toppings->firstItem() ?? 0 }}–{{ $toppings->lastItem() ?? 0 }} / {{ $toppings->total() }} kết quả
                    </div>
                    @if ($toppings->hasPages())
                        <div class="category-pagination">{{ $toppings->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
