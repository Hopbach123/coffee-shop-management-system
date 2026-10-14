<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use DomainException;
use Illuminate\Support\Facades\DB;

class CafeTableManagementService
{
    public const STATUS_TRANSITIONS = [
        'available' => ['maintenance'],
        'maintenance' => ['available'],
        'occupied' => ['available'],
    ];

    public function setStatus(CafeTable $table, string $expected, string $target): void
    {
        DB::transaction(function () use ($table, $expected, $target): void {
            $area = Area::withTrashed()->lockForUpdate()->find($table->area_id);
            $current = CafeTable::query()->lockForUpdate()->findOrFail($table->getKey());
            if ((int) $current->area_id !== (int) $table->area_id || $current->status !== $expected) {
                throw new DomainException('Bàn đã thay đổi trạng thái hoặc khu vực. Vui lòng tải lại danh sách.');
            }
            if (! in_array($target, self::STATUS_TRANSITIONS[$current->status] ?? [], true)) {
                throw new DomainException('Chuyển trạng thái này chưa được phép trong quản lý bàn.');
            }
            if ($target === 'available' && ($area === null || $area->trashed() || $area->status !== 'active')) {
                throw new DomainException('Chỉ giải phóng hoặc mở lại bàn trong khu vực đang hoạt động và chưa xóa.');
            }
            $reservation = $current->reservations()->whereIn('status', ['pending', 'confirmed', 'arrived'])
                ->where('reservation_end_at', '>', now())->orderBy('id')->lockForUpdate()->first(['id']);
            $order = $current->orders()->where('status', 'pending')->orderBy('id')->lockForUpdate()->first(['id']);
            if ($reservation !== null || $order !== null) {
                throw new DomainException('Không thể đổi trạng thái khi có đơn pending hoặc đặt bàn còn hiệu lực.');
            }
            $current->status = $target;
            if (! $current->save()) {
                throw new DomainException('Không thể lưu trạng thái bàn.');
            }
        }, 3);
    }

    public function update(CafeTable $table, array $attributes): void
    {
        DB::transaction(function () use ($table, $attributes): void {
            $areas = Area::query()->whereIn('id', [$table->area_id, $attributes['area_id']])->orderBy('id')->lockForUpdate()->get();
            $current = CafeTable::query()->lockForUpdate()->findOrFail($table->getKey());
            if ((int) $current->area_id !== (int) $table->area_id) {
                throw new DomainException('Bàn đã được chuyển khu vực bởi thao tác khác. Vui lòng tải lại form.');
            }
            $destination = $areas->firstWhere('id', (int) $attributes['area_id']);
            $moving = (int) $attributes['area_id'] !== (int) $current->area_id;
            if ($destination === null || ($moving && $destination->status !== 'active')) {
                throw new DomainException('Khu vực không hợp lệ hoặc không còn hoạt động. Vui lòng chọn lại.');
            }
            $reservations = $current->reservations()->whereIn('status', ['pending', 'confirmed', 'arrived'])
                ->where('reservation_end_at', '>', now())->orderBy('id')->lockForUpdate()->get();
            $pendingOrder = $current->orders()->where('status', 'pending')->orderBy('id')->lockForUpdate()->first(['id']);
            if ($moving && ($current->status === 'occupied' || $pendingOrder !== null || $reservations->isNotEmpty())) {
                throw new DomainException('Không thể chuyển khu vực khi bàn đang có khách, có đơn pending hoặc đặt bàn còn hiệu lực.');
            }
            if ((int) $attributes['capacity'] < (int) $current->capacity
                && (int) $attributes['capacity'] < (int) ($reservations->max('guest_count') ?? 0)) {
                throw new DomainException('Không thể giảm sức chứa dưới số khách của đặt bàn còn hiệu lực.');
            }
            $current->fill(array_intersect_key($attributes, array_flip(['area_id', 'table_code', 'table_name', 'capacity'])))->save();
        }, 3);
    }

    public function delete(CafeTable $table): void
    {
        DB::transaction(function () use ($table): void {
            // Parent before child, consistent with Area deletion and table creation/update.
            Area::withTrashed()->whereKey($table->area_id)->lockForUpdate()->first(['id']);
            $current = CafeTable::query()->lockForUpdate()->findOrFail($table->getKey());
            if (! in_array($current->status, ['available', 'maintenance'], true)) {
                throw new DomainException('Không thể xóa bàn đang có khách hoặc có trạng thái không cho phép.');
            }
            $reservation = $current->reservations()->whereIn('status', ['pending', 'confirmed', 'arrived'])
                ->where('reservation_end_at', '>', now())->orderBy('id')->lockForUpdate()->first(['id']);
            $order = $current->orders()->where('status', 'pending')->orderBy('id')->lockForUpdate()->first(['id']);
            if ($reservation !== null || $order !== null) {
                throw new DomainException('Không thể xóa bàn có đơn pending hoặc đặt bàn còn hiệu lực.');
            }
            if (! $current->delete()) {
                throw new DomainException('Không thể xóa bàn.');
            }
        }, 3);
    }

    public function create(array $attributes): CafeTable
    {
        return DB::transaction(function () use ($attributes): CafeTable {
            // Same parent-first locking protocol as Area deletion.
            $area = Area::query()->lockForUpdate()->find($attributes['area_id']);
            if ($area === null || $area->status !== 'active') {
                throw new DomainException('Khu vực không còn hoạt động hoặc đã bị xóa. Vui lòng chọn lại.');
            }

            return CafeTable::create(array_intersect_key($attributes, array_flip(['area_id', 'table_code', 'table_name', 'capacity'])));
        }, 3);
    }
}
