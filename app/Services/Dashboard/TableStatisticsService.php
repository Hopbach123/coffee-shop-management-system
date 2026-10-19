<?php

namespace App\Services\Dashboard;

use App\Models\CafeTable;

class TableStatisticsService
{
    public const STATUS_LABELS = ['available' => 'Bàn trống', 'occupied' => 'Đang có khách', 'maintenance' => 'Bảo trì'];

    public function summarize(): array
    {
        $counts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
        $unclassified = 0;
        $groups = CafeTable::query()->select('status')->selectRaw('COUNT(*) AS table_count')->groupBy('status')->toBase()->get();
        foreach ($groups as $group) {
            if (array_key_exists($group->status ?? '', $counts)) {
                $counts[$group->status] = (int) $group->table_count;
            } else {
                $unclassified += (int) $group->table_count;
            }
        }

        return ['total' => array_sum($counts) + $unclassified, 'by_status' => $counts, 'unclassified' => $unclassified];
    }
}
