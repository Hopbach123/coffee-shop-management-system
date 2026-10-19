<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationApprovalService
{
    public function __construct(private ReservationAvailabilityService $availability) {}

    public function approve(Reservation $reservation): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        DB::transaction(function () use ($reservation): void {
            $tableId = (int) $reservation->table_id;
            $this->availability->lockTable($tableId);
            $current = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            if ($current->status !== 'pending') {
                throw ValidationException::withMessages(['approval' => 'Chỉ yêu cầu đang chờ duyệt mới được xác nhận. Vui lòng tải lại trang.']);
            }
            if ((int) $current->table_id !== $tableId) {
                throw ValidationException::withMessages(['approval' => 'Bàn của yêu cầu đã thay đổi. Vui lòng tải lại trang.']);
            }
            $times = [];
            foreach (['reservation_start_at', 'reservation_end_at'] as $field) {
                $raw = $current->getRawOriginal($field);
                try {
                    $time = is_string($raw) ? CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $raw, config('app.timezone')) : null;
                } catch (\InvalidArgumentException $exception) {
                    $time = null;
                }
                if ($time === null || $time === false || $time->format('Y-m-d H:i:s') !== $raw) {
                    throw ValidationException::withMessages(['approval' => 'Thời gian đặt bàn không hợp lệ.']);
                }
                $times[$field] = $time;
            }
            $this->availability->assertAvailable($tableId, (int) $current->guest_count, $times['reservation_start_at'], $times['reservation_end_at'], $current);
            $current->status = 'confirmed';
            $current->saveOrFail();
        }, 3);
    }
}
