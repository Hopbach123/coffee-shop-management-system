@extends('layouts.internal')
@section('title', 'Yêu cầu đặt bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Tiếp nhận</span><span>Đặt bàn</span></nav>
        <h1 class="cafe-page-title">Yêu cầu đặt bàn</h1>
        <p class="cafe-page-subtitle">Theo dõi yêu cầu đặt bàn và lịch khách đến quán.</p>
    </div>
</div>

<div class="cafe-card">
    <div class="cafe-table-wrap">
        <table class="cafe-table">
            <caption class="sr-only">Danh sách yêu cầu đặt bàn</caption>
            <thead>
                <tr>
                    <th scope="col">Khách hàng</th>
                    <th scope="col">Điện thoại</th>
                    <th scope="col">Ngày giờ</th>
                    <th scope="col">Số khách</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $reservation)
                    <tr>
                        <td data-label="Khách hàng"><strong>{{ $reservation->customer_name }}</strong></td>
                        <td data-label="Điện thoại">{{ $reservation->customer_phone }}</td>
                        <td data-label="Ngày giờ">{{ $reservation->reservation_start_at?->format('d/m/Y H:i') }}</td>
                        <td data-label="Số khách">{{ $reservation->guest_count }}</td>
                        <td data-label="Trạng thái"><span class="cafe-badge cafe-badge--info">{{ ['pending' => 'Chờ duyệt', 'confirmed' => 'Đã duyệt', 'arrived' => 'Đã đến', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'no_show' => 'Không đến'][$reservation->status] ?? $reservation->status }}</span></td>
                        <td data-label="Ghi chú" style="color:var(--cafe-text-secondary);font-size:.875rem;">{{ $reservation->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--cafe-text-secondary);">Chưa có yêu cầu đặt bàn.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $reservations->links() }}</div>
</div>
@endsection
