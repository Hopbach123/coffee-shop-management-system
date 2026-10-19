@extends('layouts.internal')
@section('title', 'Chi tiết đặt bàn #'.$reservation->id)
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Quản trị</p><h1 id="page-title">Đặt bàn #{{ $reservation->id }}</h1><p>Thời gian hiển thị theo UTC.</p></div><x-public.button href="{{ route('admin.reservations.index') }}" variant="outline">Về danh sách</x-public.button></div>
    <div class="category-panel">
        @if (session('success'))<p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>@endif
        @if ($errors->any())<div class="category-notice category-notice--error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @include('admin.reservations._approve')
        @include('admin.reservations._cancel')
        <h2>Thông tin đặt bàn</h2>
        <dl class="category-fields">
            <div class="category-field"><dt>Trạng thái</dt><dd>{{ $statuses[$reservation->status] ?? $reservation->status }}</dd></div>
            <div class="category-field"><dt>Số khách</dt><dd>{{ $reservation->guest_count }}</dd></div>
            <div class="category-field"><dt>Bắt đầu (UTC)</dt><dd>{{ $reservation->reservation_start_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="category-field"><dt>Kết thúc (UTC)</dt><dd>{{ $reservation->reservation_end_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="category-field"><dt>Tên khách khi đặt</dt><dd>{{ $reservation->customer_name }}</dd></div>
            <div class="category-field"><dt>Điện thoại khi đặt</dt><dd>{{ $reservation->customer_phone }}</dd></div>
            <div class="category-field"><dt>Hồ sơ khách hàng liên kết</dt><dd>{{ $reservation->customer ? '#'.$reservation->customer->id.' · '.($reservation->customer->name ?? 'Khách hàng') : 'Không có hồ sơ khách hàng liên kết' }}</dd></div>
            <div class="category-field"><dt>Bàn</dt><dd>{{ $reservation->cafeTable?->table_code ?? 'Chưa gán bàn' }} · {{ $reservation->cafeTable?->table_name ?? '—' }}</dd></div>
            <div class="category-field"><dt>Sức chứa bàn</dt><dd>{{ $reservation->cafeTable?->capacity ?? '—' }}</dd></div>
            <div class="category-field"><dt>Khu vực</dt><dd>{{ $reservation->cafeTable?->area?->name ?? '—' }}</dd></div>
            <div class="category-field"><dt>Người tạo</dt><dd>{{ $reservation->creator?->name ?? 'Không ghi nhận người tạo' }}</dd></div>
            <div class="category-field"><dt>Ngày tạo (UTC)</dt><dd>{{ $reservation->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="category-field"><dt>Cập nhật (UTC)</dt><dd>{{ $reservation->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="category-field"><dt>Ghi chú</dt><dd class="category-description">{{ $reservation->note ?? '—' }}</dd></div>
        </dl>
    </div>
</section>
@endsection
