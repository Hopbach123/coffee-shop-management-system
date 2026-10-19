<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class OrderStatisticsService
{
    public const STATUS_LABELS = [
        'pending' => 'Đang xử lý',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];

    /** All recorded orders, including cancelled; no date or relationship filters. */
    public function summarize(): array
    {
        $counts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
        $groups = DB::table('orders')->select('status')->selectRaw('COUNT(*) AS order_count')->groupBy('status')->get();
        foreach ($groups as $group) {
            // Preserve unexpected groups rather than silently understating the total.
            $counts[$group->status ?? ''] = (int) $group->order_count;
        }

        return ['total' => array_sum($counts), 'by_status' => $counts];
    }
}
