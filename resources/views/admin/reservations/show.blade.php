@extends('layouts.internal')
@section('title', 'Chi tiết đặt bàn #'.$reservation->id)
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.reservations.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Đặt bàn</a>
            <span>#{{ $reservation->id }}</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Đặt bàn #{{ $reservation->id }}</h1>
        <p class="cafe-page-subtitle">Thời gian hiển thị theo UTC.</p>
    </div>
    <a href="{{ route('admin.reservations.index') }}" class="cafe-btn-secondary">
        ← Về danh sách
    </a>
</div>

<div class="cafe-card" style="max-width:800px;padding:1.5rem;">
    @if (session('success'))
        <div class="cafe-notice cafe-notice--success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="cafe-notice cafe-notice--error" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div style="display:flex;gap:.5rem;margin-bottom:1.25rem;">
        @include('admin.reservations._approve')
        @include('admin.reservations._cancel')
    </div>

    <h2 style="font-size:1.05rem;font-weight:700;color:var(--cafe-text-primary);margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid var(--cafe-border);">Thông tin đặt bàn</h2>
    <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;margin:0;">
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Trạng thái</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;"><span class="cafe-badge cafe-badge--info">{{ $statuses[$reservation->status] ?? $reservation->status }}</span></dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Số khách</dt>
            <dd style="font-size:.95rem;font-weight:700;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->guest_count }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Bắt đầu (UTC)</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->reservation_start_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Kết thúc (UTC)</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->reservation_end_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Tên khách khi đặt</dt>
            <dd style="font-size:.95rem;font-weight:600;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->customer_name }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Điện thoại khi đặt</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->customer_phone }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Hồ sơ khách hàng liên kết</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->customer ? '#'.$reservation->customer->id.' · '.($reservation->customer->name ?? 'Khách hàng') : 'Không có hồ sơ khách hàng liên kết' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Bàn</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->cafeTable?->table_code ?? 'Chưa gán bàn' }} · {{ $reservation->cafeTable?->table_name ?? '—' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Khu vực</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->cafeTable?->area?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Người tạo</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->creator?->name ?? 'Không ghi nhận người tạo' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Ngày tạo (UTC)</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-primary);margin:.2rem 0 0;">{{ $reservation->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);">Ghi chú</dt>
            <dd style="font-size:.95rem;color:var(--cafe-text-secondary);font-style:italic;margin:.2rem 0 0;">{{ $reservation->note ?? '—' }}</dd>
        </div>
    </dl>
</div>
@endsection
