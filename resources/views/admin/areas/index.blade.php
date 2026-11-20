@extends('layouts.internal')
@section('title', 'Khu vực')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Khu vực</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Khu vực</h1>
        <p class="cafe-page-subtitle">Danh sách khu vực của quán.</p>
    </div>
    <a href="{{ route('admin.areas.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm khu vực
    </a>
</div>

@if (session('success'))
    <div class="cafe-notice cafe-notice--success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="cafe-notice cafe-notice--error" role="alert">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<div class="cafe-card v5-area-panel">
    <form method="GET" action="{{ route('admin.areas.index') }}" role="search" class="v5-toolbar">
        <div style="flex:1;min-width:200px;">
            <label for="area-search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tìm khu vực theo tên</label>
            <input id="area-search" class="cafe-input" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Nhập tên khu vực…"
                @error('search') aria-invalid="true" aria-describedby="area-search-error" @enderror>
            @error('search') <p style="font-size:.8rem;color:var(--cafe-danger);margin-top:.25rem;" id="area-search-error" role="alert">{{ $message }}</p> @enderror
        </div>
        <div class="v5-toolbar-actions">
            <button type="submit" class="cafe-btn-secondary">
                <x-internal.icon name="search" size="15" /> Tìm kiếm
            </button>
            @if ($search !== '')
                <a href="{{ route('admin.areas.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            @endif
        </div>
    </form>

    @if ($areas->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="grid" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">
                {{ $search !== '' ? 'Không tìm thấy khu vực' : 'Chưa có khu vực' }}
            </p>
            <p style="font-size:.875rem;">
                {{ $search !== '' ? 'Thử tên khác hoặc xóa bộ lọc.' : 'Thêm khu vực để lưu thông tin không gian quán.' }}
            </p>
            @if ($search !== '')
                <a href="{{ route('admin.areas.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
            @endif
        </div>
    @else
        <div class="v5-grid" aria-label="Danh sách khu vực">
            @foreach($areas as $area)
                <article class="v5-entity-card"><div class="v5-card-heading"><span class="v5-card-icon"><x-internal.icon name="layers" /></span><span class="cafe-badge {{ $area->status === 'active' ? 'cafe-badge--success' : 'cafe-badge--warning' }}">{{ $area->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></div><h2>{{ $area->name }}</h2><p>{{ $area->description ?? 'Chưa có mô tả khu vực.' }}</p><div class="v5-card-meta"><span>#{{ $area->id }}</span><span>Tạo {{ $area->created_at?->format('d/m/Y H:i') ?? '—' }}</span></div><a href="{{ route('admin.tables.index', ['area_id' => $area->id]) }}" class="cafe-btn-secondary">Xem bàn trong khu vực <x-internal.icon name="arrow" size="16" /></a><div class="v5-card-footer v5-actions">
                                    <a href="{{ route('admin.areas.edit', $area) }}" class="cafe-btn-icon" aria-label="Sửa khu vực {{ $area->id }}">
                                        <x-internal.icon name="edit" size="15" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.areas.status', $area) }}" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái khu vực này? Trạng thái bàn và giao dịch được giữ nguyên.');">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $area->status === 'active' ? 'inactive' : 'active' }}">
                                        <button type="submit" class="cafe-btn-secondary">
                                            {{ $area->status === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm khu vực này? Chỉ khu vực không có bàn liên kết mới được xóa.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" aria-label="Xóa khu vực {{ $area->id }}">
                                            <x-internal.icon name="trash" size="15" />
                                        </button>
                                    </form>
                                </div></article>
            @endforeach
        </div>
        @if ($areas->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $areas->links() }}</div>
        @endif
    @endif
</div>
@endsection
