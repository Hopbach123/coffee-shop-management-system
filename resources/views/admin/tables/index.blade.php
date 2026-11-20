@extends('layouts.internal')
@section('title', 'Quản lý bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Bàn</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Quản lý bàn</h1>
        <p class="cafe-page-subtitle">Danh sách bàn và khu vực của quán.</p>
    </div>
    <a href="{{ route('admin.tables.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm bàn
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

<div class="cafe-card">
    <form method="GET" action="{{ route('admin.tables.index') }}" role="search" class="v5-toolbar">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:160px;">
                <label for="search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mã bàn</label>
                <input id="search" class="cafe-input" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="20" placeholder="Tìm theo mã bàn…"
                    @error('search') aria-invalid="true" aria-describedby="search-error" @enderror>
                @error('search') <p id="search-error" style="font-size:.8rem;color:var(--cafe-danger);margin-top:.25rem;">{{ $message }}</p> @enderror
            </div>
            <div style="min-width:160px;">
                <label for="area-filter" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Khu vực</label>
                <select id="area-filter" class="cafe-select" name="area_id" @error('area_id') aria-invalid="true" aria-describedby="area-filter-error" @enderror>
                    <option value="">Tất cả khu vực</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) ($filters['area_id'] ?? '') === (string) $area->id)>{{ $area->name }}{{ $area->trashed() ? ' (đã xóa mềm)' : '' }}</option>
                    @endforeach
                </select>
                @error('area_id') <p id="area-filter-error" style="font-size:.8rem;color:var(--cafe-danger);margin-top:.25rem;">{{ $message }}</p> @enderror
            </div>
            <div style="min-width:160px;">
                <label for="status-filter" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Trạng thái</label>
                <select id="status-filter" class="cafe-select" name="status" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p id="status-error" style="font-size:.8rem;color:var(--cafe-danger);margin-top:.25rem;">{{ $message }}</p> @enderror
            </div>
            <div style="display:flex;gap:.5rem;padding-bottom:1px;">
                <button type="submit" class="cafe-btn-secondary">
                    <x-internal.icon name="search" size="15" /> Lọc
                </button>
                <a href="{{ route('admin.tables.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            </div>
        </div>
    </form>

    @if ($tables->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="table" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">
                @if (($filters['search'] ?? '') !== '' || !empty($filters['area_id']) || !empty($filters['status']))
                    Không tìm thấy bàn
                @else
                    Chưa có bàn
                @endif
            </p>
            <p style="font-size:.875rem;">
                @if (($filters['search'] ?? '') !== '' || !empty($filters['area_id']) || !empty($filters['status']))
                    Thử mã bàn hoặc bộ lọc khác.
                @else
                    Thêm bàn để bắt đầu quản lý.
                @endif
            </p>
        </div>
    @else
        @foreach($tables->getCollection()->groupBy('area_id') as $areaTables)
            <section class="v5-table-area" aria-labelledby="table-area-{{ $loop->index }}"><header><h2 id="table-area-{{ $loop->index }}">{{ $areaTables->first()->area?->name ?? 'Không tìm thấy khu vực' }}</h2><small>{{ $areaTables->count() }} bàn trên trang này</small></header><div class="v5-grid">
                @foreach($areaTables as $table)
                    <article class="v5-entity-card v5-table-card v5-table-card--{{ $table->status }}"><div class="v5-card-heading"><span class="v5-card-icon"><x-internal.icon name="table" /></span><span class="cafe-badge {{ $table->status === 'available' ? 'cafe-badge--success' : ($table->status === 'occupied' ? 'cafe-badge--warning' : 'cafe-badge--danger') }}">{{ $statuses[$table->status] ?? $table->status }}</span></div><h2>{{ $table->table_code }}</h2><p>{{ $table->table_name ?? 'Chưa có tên bàn' }}</p><div class="v5-card-meta"><span>{{ $table->capacity }} chỗ</span><span>#{{ $table->id }} · {{ $table->created_at?->format('d/m/Y H:i') ?? '—' }}</span></div><div class="v5-card-footer"><details class="v5-row-actions"><summary>Thao tác với bàn</summary><div class="v5-row-actions__content">
                                    @foreach ($transitions[$table->status] ?? [] as $target)
                                        <form method="POST" action="{{ route('admin.tables.status', $table) }}" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái bàn này? Hệ thống sẽ kiểm tra đơn hàng và đặt bàn trước khi lưu.');">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="expected_status" value="{{ $table->status }}">
                                            <input type="hidden" name="status" value="{{ $target }}">
                                            <button type="submit" class="cafe-btn-secondary" aria-label="Chuyển bàn {{ $table->table_code }} sang {{ $statuses[$target] }}">
                                                {{ $target === 'maintenance' ? 'Bảo trì' : ($table->status === 'occupied' ? 'Giải phóng bàn' : 'Mở lại bàn') }}
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('admin.tables.edit', $table) }}" class="cafe-btn-icon" aria-label="Sửa bàn {{ $table->table_code }}">
                                        <x-internal.icon name="edit" size="15" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm bàn này? Chỉ xóa khi không có khách, đơn pending hoặc đặt bàn còn hiệu lực. Lịch sử được giữ nguyên.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" aria-label="Xóa bàn {{ $table->table_code }}">
                                            <x-internal.icon name="trash" size="15" />
                                        </button>
                                    </form>
                                </div></details></div></article>
                @endforeach
            </div></section>
        @endforeach
        @if ($tables->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $tables->links() }}</div>
        @endif
    @endif
</div>
@endsection
