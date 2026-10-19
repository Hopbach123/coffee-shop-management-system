@if (in_array($reservation->status, \App\Services\ReservationCancellationService::CANCELLABLE_STATUSES, true) && auth('web')->user()->role === 'admin' && app()->environment(['local', 'testing']))
    <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu đặt bàn #{{ $reservation->id }}?');">
        @csrf
        @method('PATCH')
        <input type="hidden" name="expected_status" value="{{ $reservation->status }}">
        <x-public.button type="submit" variant="outline" class="category-delete" aria-label="Hủy đặt bàn {{ $reservation->id }}">Hủy đặt bàn</x-public.button>
    </form>
@endif
