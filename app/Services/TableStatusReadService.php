<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TableStatusReadService
{
    public const LABELS = [
        'available' => 'Trống', 'occupied' => 'Có khách', 'reserved' => 'Đã đặt',
        'maintenance' => 'Bảo trì', 'conflict' => 'Cần kiểm tra', 'unknown' => 'Chưa xác định',
    ];

    public const ICONS = [
        'available' => 'table', 'occupied' => 'users', 'reserved' => 'calendar',
        'maintenance' => 'clock', 'conflict' => 'bell', 'unknown' => 'table',
    ];

    public function query(CarbonImmutable $observedAt): Builder
    {
        $states = ['pending', 'confirmed', 'arrived'];
        $base = CafeTable::query()->select('cafe_tables.*')->addSelect([
            'active_area_count' => Area::query()->selectRaw('COUNT(*)')->whereColumn('areas.id', 'cafe_tables.area_id')->where('status', 'active'),
            'pending_payment_count' => Payment::query()->selectRaw('COUNT(*)')->where('status', 'pending')
                ->whereHas('order', fn ($query) => $query->whereColumn('orders.table_id', 'cafe_tables.id')->where('order_type', 'dine_in')->where('status', 'pending')),
            'paid_payment_count' => Payment::query()->selectRaw('COUNT(*)')->where('status', 'paid')
                ->whereHas('order', fn ($query) => $query->whereColumn('orders.table_id', 'cafe_tables.id')->where('order_type', 'dine_in')->where('status', 'pending')),
        ])->withCount([
            'orders as pending_order_count' => fn ($query) => $query->where('order_type', 'dine_in')->where('status', 'pending'),
            'reservations as current_reservation_count' => fn ($query) => $query->whereIn('status', $states)
                ->where('reservation_start_at', '<=', $observedAt)->where('reservation_end_at', '>', $observedAt),
            'reservations as future_reservation_count' => fn ($query) => $query->whereIn('status', $states)->where('reservation_start_at', '>', $observedAt),
        ]);
        // One SQL classification is shared by cards, detail, filters and all counts.
        $classified = DB::query()->fromSub($base->toBase(), 'signals')->select('signals.*')->selectRaw("CASE
            WHEN active_area_count <> 1 OR status IS NULL OR status NOT IN ('available','occupied','maintenance') THEN 'unknown'
            WHEN pending_order_count > 1
                OR (status = 'available' AND pending_order_count > 0)
                OR (status = 'occupied' AND pending_order_count = 0)
                OR (status = 'maintenance' AND (pending_order_count > 0 OR current_reservation_count > 0))
                OR (pending_order_count > 0 AND current_reservation_count > 0) THEN 'conflict'
            WHEN status = 'available' AND current_reservation_count > 0 THEN 'reserved'
            ELSE status END AS display_status");

        return CafeTable::query()->fromSub($classified, 'cafe_tables')->select('cafe_tables.*');
    }

    public function areas(string $search, string $status, CarbonImmutable $observedAt): Builder
    {
        $counts = DB::query()->fromSub($this->query($observedAt)->toBase(), 'table_states')
            ->select('area_id')->selectRaw('COUNT(*) AS tables_count')->groupBy('area_id');
        foreach (array_keys(self::LABELS) as $key) {
            $counts->selectRaw("SUM(CASE WHEN display_status = ? THEN 1 ELSE 0 END) AS {$key}_count", [$key]);
        }
        $areas = Area::query()->matchingFilters($search, $status)->leftJoinSub($counts, 'state_counts', 'state_counts.area_id', '=', 'areas.id')
            ->select('areas.*')->selectRaw('COALESCE(state_counts.tables_count, 0) AS tables_count');
        foreach (array_keys(self::LABELS) as $key) {
            $areas->selectRaw("COALESCE(state_counts.{$key}_count, 0) AS {$key}_count");
        }

        return $areas;
    }
}
