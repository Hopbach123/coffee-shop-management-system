@if ($reservation->status === 'pending' && auth('web')->user()->role === 'admin' && app()->environment(['local', 'testing']))
    <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}" onsubmit="return confirm('Xác nhận duyệt yêu cầu đặt bàn #{{ $reservation->id }}?');">
        @csrf
        @method('PATCH')
        <button type="submit" class="cafe-btn-secondary" aria-label="Duyệt đặt bàn {{ $reservation->id }}">Duyệt đặt bàn</button>
    </form>
@endif
