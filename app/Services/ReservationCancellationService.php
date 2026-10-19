<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationCancellationService
{
    public const CANCELLABLE_STATUSES = ['pending', 'confirmed'];

    public function cancel(Reservation $reservation, string $expectedStatus): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        DB::transaction(function () use ($reservation, $expectedStatus): void {
            // Same parent -> table -> reservation ordering as Create/Approve, without
            // availability restrictions: inactive/deleted masters do not prevent cancellation.
            $snapshot = CafeTable::withTrashed()->find($reservation->table_id);
            if ($snapshot !== null) {
                Area::withTrashed()->whereKey($snapshot->area_id)->lockForUpdate()->first(['id']);
                $table = CafeTable::withTrashed()->lockForUpdate()->find($snapshot->id);
                if ($table === null || (int) $table->area_id !== (int) $snapshot->area_id) {
                    throw ValidationException::withMessages(['cancellation' => 'Bàn đã thay đổi. Vui lòng tải lại trang.']);
                }
            }
            $current = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            if ($current->table_id !== $reservation->table_id) {
                throw ValidationException::withMessages(['cancellation' => 'Bàn của yêu cầu đã thay đổi. Vui lòng tải lại trang.']);
            }
            if (! in_array($current->status, self::CANCELLABLE_STATUSES, true) || $current->status !== $expectedStatus) {
                throw ValidationException::withMessages(['cancellation' => 'Yêu cầu không còn ở trạng thái có thể hủy hoặc đã thay đổi. Vui lòng tải lại trang.']);
            }
            if ($current->orders()->orderBy('id')->lockForUpdate()->first(['id']) !== null) {
                throw ValidationException::withMessages(['cancellation' => 'Không thể hủy đặt bàn đã có đơn hàng liên kết.']);
            }
            $current->status = 'cancelled';
            $current->saveOrFail();
        }, 3);
    }
}
