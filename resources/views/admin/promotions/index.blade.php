@extends('layouts.internal')
@section('title', 'Khuyến mãi')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Khuyến mãi</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Khuyến mãi</h1>
        <p class="cafe-page-subtitle">Quản lý mã giảm giá, thời gian hiệu lực và số lượt sử dụng.</p>
    </div>
    <a href="{{ route('admin.promotions.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm khuyến mãi
    </a>
</div>

@if (session('success'))
    <div class="cafe-notice cafe-notice--success" role="status">{{ session('success') }}</div>
@endif

<div class="cafe-card v5-promo-panel">
    <form method="GET" action="{{ route('admin.promotions.index') }}" role="search" class="v5-toolbar">
        <div style="flex:1;min-width:200px;">
            <label for="promotion-search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tìm theo mã hoặc tên</label>
            <input id="promotion-search" class="cafe-input" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập mã hoặc tên…">
        </div>
        <div class="v5-toolbar-actions">
            <button type="submit" class="cafe-btn-secondary">
                <x-internal.icon name="search" size="15" /> Tìm kiếm
            </button>
            @if ($search !== '')
                <a href="{{ route('admin.promotions.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            @endif
        </div>
    </form>

    @if ($promotions->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="tag" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">{{ $search !== '' ? 'Không tìm thấy khuyến mãi' : 'Chưa có khuyến mãi' }}</p>
            <p style="font-size:.875rem;">{{ $search !== '' ? 'Thử mã hoặc tên khác.' : 'Thêm khuyến mãi đầu tiên để bắt đầu.' }}</p>
        </div>
    @else
        <div class="v5-grid" aria-label="Danh sách khuyến mãi">
            @foreach($promotions as $promotion)
                <article class="v5-entity-card"><div class="v5-promo-banner {{ $promotion->imageUrl() ? 'v5-promo-banner--with-image' : '' }}">@if ($promotion->imageUrl())<img class="v5-promo-banner__image" src="{{ $promotion->imageUrl() }}" alt="" aria-hidden="true">@endif<div class="v5-promo-banner__content"><p>{{ $promotion->code }}</p><h2>{{ $promotion->name }}</h2><div class="v5-promo-value">{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim($promotion->discount_value, '0'), '.') . '%' : number_format((float) $promotion->discount_value, 2, ',', '.') . ' ₫' }}</div></div></div><span class="cafe-badge {{ $promotion->status === 'active' ? 'cafe-badge--success' : 'cafe-badge--warning' }}">{{ $promotion->status === 'active' ? 'Hoạt động' : 'Tạm dừng' }}</span><dl class="v5-promo-terms"><div><dt>Đơn tối thiểu</dt><dd>{{ number_format((float) $promotion->minimum_order, 2, ',', '.') }} ₫</dd></div><div><dt>Giảm tối đa</dt><dd>{{ $promotion->max_discount === null ? '—' : number_format((float) $promotion->max_discount, 2, ',', '.') . ' ₫' }}</dd></div><div><dt>Bắt đầu (UTC)</dt><dd>{{ $promotion->start_at->format('d/m/Y H:i') }}</dd></div><div><dt>Kết thúc (UTC)</dt><dd>{{ $promotion->end_at->format('d/m/Y H:i') }}</dd></div><div><dt>Lượt dùng</dt><dd>{{ $promotion->used_count }} / {{ $promotion->usage_limit ?? '∞' }}</dd></div></dl><div class="v5-card-footer v5-actions">
                                    <a href="{{ route('admin.promotions.edit', $promotion) }}" class="cafe-btn-icon" aria-label="Sửa {{ $promotion->code }}">
                                        <x-internal.icon name="edit" size="15" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.promotions.toggle-status', $promotion) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="cafe-btn-secondary">
                                            {{ $promotion->status === 'active' ? 'Tạm dừng' : 'Kích hoạt' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('Bạn có chắc muốn xóa khuyến mãi này?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" aria-label="Xóa {{ $promotion->code }}">
                                            <x-internal.icon name="trash" size="15" />
                                        </button>
                                    </form>
                                </div></article>
            @endforeach
        </div>
        @if ($promotions->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $promotions->links() }}</div>
        @endif
    @endif
</div>
@endsection
