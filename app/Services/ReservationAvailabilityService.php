<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ReservationAvailabilityService
{
    public const BLOCKING_STATUSES = ['pending', 'confirmed', 'arrived'];

    /**
     * Caller must keep this transaction open through the schedule write.
     * Approval must also lock Area -> CafeTable before locking/updating Reservation.
     * The optional context excludes only the reservation being checked for approval.
     */
    public function assertAvailable(int $tableId, int $guests, CarbonInterface $start, CarbonInterface $end, ?Reservation $context = null): CafeTable
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Availability requires an enclosing write transaction.');
        }
        if ($guests < 1) {
            throw ValidationException::withMessages(['guest_count' => 'Số khách phải là số nguyên dương.']);
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['reservation_end_at' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
        }
        $table = $this->lockTable($tableId);
        if ($guests > (int) $table->capacity) {
            throw ValidationException::withMessages(['guest_count' => 'Số khách không được vượt sức chứa bàn.']);
        }
        $conflict = Reservation::query()->where('table_id', $tableId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('reservation_start_at', '<', $end)
            ->where('reservation_end_at', '>', $start)
            ->when($context?->exists, fn ($query) => $query->whereKeyNot($context->getKey()))
            // Current read after waiting for the table lock, including under MySQL REPEATABLE READ.
            ->orderBy('id')->lockForUpdate()->first(['id']);
        if ($conflict !== null) {
            throw ValidationException::withMessages(['table_id' => 'Bàn đã có lịch đặt trùng thời gian. Vui lòng chọn bàn hoặc thời gian khác.']);
        }
        if ($start->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['reservation_start_at' => 'Thời gian bắt đầu phải ở tương lai.']);
        }

        return $table;
    }

    public function lockTable(int $tableId): CafeTable
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Table locking requires an enclosing write transaction.');
        }
        // Initial lookup determines lock order only, never availability.
        $snapshot = CafeTable::query()->find($tableId, ['id', 'area_id']);
        $area = $snapshot ? Area::query()->lockForUpdate()->find($snapshot->area_id) : null;
        $table = CafeTable::query()->lockForUpdate()->find($tableId);
        if ($area === null || $area->status !== 'active' || $table === null
            || (int) $table->area_id !== (int) $area->id
            || ! in_array($table->status, ['available', 'occupied'], true)) {
            throw ValidationException::withMessages(['table_id' => 'Bàn hoặc khu vực không còn nhận yêu cầu. Vui lòng chọn lại.']);
        }

        return $table;
    }
}
