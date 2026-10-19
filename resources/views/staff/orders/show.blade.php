@extends('layouts.internal')
@section('title', 'Chi tiết đơn hàng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Nhân viên</p><h1 id="page-title">{{ $order->order_code }}</h1><p>Đơn {{ $order->order_type === 'dine_in' ? 'tại chỗ' : 'mang đi' }} đang ở trạng thái {{ $order->status }}.</p></div><x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Danh sách đơn</x-public.button></div>
        @if (session('success'))<p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>@endif
        <div class="category-panel"><dl class="detail-list">
            <dt>Nhân viên tạo</dt><dd>{{ $order->creator?->name }}</dd><dt>Bàn</dt><dd>{{ $order->cafeTable?->table_code ?? '—' }}</dd><dt>Khách hàng</dt><dd>{{ $order->customer?->name ?? 'Khách vãng lai' }}</dd><dt>Thời gian tạo</dt><dd>{{ $order->ordered_at?->format('d/m/Y H:i') }}</dd><dt>Ghi chú</dt><dd>{{ $order->note ?: '—' }}</dd>
        </dl><p>Chức năng thêm món sẽ được triển khai ở task Order Item.</p></div>
    </section>
@endsection
