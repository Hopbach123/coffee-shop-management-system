@extends('layouts.internal')
@section('title', 'Đặt bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Đặt bàn</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Đặt bàn</h1>
        <p class="cafe-page-subtitle">Tra cứu yêu cầu đặt bàn. Thời gian hiển thị theo UTC.</p>
    </div>
    @if (app()->environment(['local', 'testing']))
        <a href="{{ route('admin.reservations.create') }}" class="cafe-btn-primary">
            <x-internal.icon name="plus" size="16" /> Tạo yêu cầu đặt bàn
        </a>
    @endif
</div>

@if ($errors->any())
    <div class="cafe-notice cafe-notice--error" role="alert">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<div class="cafe-card">
    <form method="GET" action="{{ route('admin.reservations.index') }}" role="search" class="v5-toolbar">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:180px;">
                <label for="search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên hoặc số điện thoại khách</label>
                <input id="search" class="cafe-input" name="search" type="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" placeholder="Nhập tên hoặc SĐT…">
            </div>
            <div style="min-width:150px;">
                <label for="status" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Trạng thái</label>
                <select id="status" class="cafe-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:150px;">
                <label for="date" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Ngày bắt đầu (UTC)</label>
                <input id="date" class="cafe-input" name="date" type="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div style="min-width:150px;">
                <label for="area_id" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Khu vực</label>
                <select id="area_id" class="cafe-select" name="area_id">
                    <option value="">Tất cả khu vực</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) ($filters['area_id'] ?? '') === (string) $area->id)>{{ $area->name }}{{ $area->deleted_at ? ' (đã xóa)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:.5rem;padding-bottom:1px;">
                <button type="submit" class="cafe-btn-secondary">
                    <x-internal.icon name="filter" size="15" /> Lọc
                </button>
                <a href="{{ route('admin.reservations.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            </div>
        </div>
    </form>

    @if ($reservations->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="calendar" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">
                {{ count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) ? 'Không tìm thấy đặt bàn' : 'Chưa có đặt bàn' }}
            </p>
            <p style="font-size:.875rem;">Thử thay đổi bộ lọc hoặc quay lại sau.</p>
        </div>
    @else
        <div class="cafe-table-wrap">
            <table class="cafe-table">
                <caption class="sr-only">Danh sách đặt bàn, thời gian UTC</caption>
                <thead>
                    <tr>
                        @foreach (['Mã', 'Khách hàng', 'Điện thoại', 'Bàn / khu vực', 'Số khách', 'Bắt đầu (UTC)', 'Kết thúc (UTC)', 'Trạng thái', 'Chi tiết'] as $heading)
                            <th scope="col">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <th scope="row" data-label="Mã">#{{ $reservation->id }}</th>
                            <td data-label="Khách hàng">{{ $reservation->customer_name }}</td>
                            <td data-label="Điện thoại">{{ $reservation->customer_phone }}</td>
                            <td data-label="Bàn / khu vực">{{ $reservation->cafeTable?->table_code ?? 'Chưa gán bàn' }} / {{ $reservation->cafeTable?->area?->name ?? '—' }}</td>
                            <td data-label="Số khách">{{ $reservation->guest_count }}</td>
                            <td data-label="Bắt đầu (UTC)">{{ $reservation->reservation_start_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-label="Kết thúc (UTC)">{{ $reservation->reservation_end_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-label="Trạng thái">
                                <span class="cafe-badge cafe-badge--info">{{ $statuses[$reservation->status] ?? $reservation->status }}</span>
                            </td>
                            <td data-label="Chi tiết">
                                <div class="v5-actions">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="cafe-btn-secondary" aria-label="Chi tiết đặt bàn {{ $reservation->id }}">Xem</a>
                                    @include('admin.reservations._approve')
                                    @include('admin.reservations._cancel')
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($reservations->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $reservations->links() }}</div>
        @endif
    @endif
</div>
@endsection
