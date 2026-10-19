<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class RevenueStatisticsService
{
    /** Current paid value only, not gross historical receipts or net revenue. */
    public function summarize(): array
    {
        $paidOrders = DB::table('payments')->where('status', 'paid')->groupBy('order_id')
            ->select('order_id')->selectRaw('SUM(amount) AS paid_amount, COUNT(*) AS paid_count');
        // One statement keeps the duplicate guard and total on the same DB snapshot.
        $result = DB::query()->fromSub($paidOrders, 'paid_orders')->selectRaw(
            'CAST(COALESCE(SUM(paid_amount), 0) AS CHAR) AS amount, COALESCE(MAX(paid_count), 0) AS max_paid_count'
        )->first();
        if ((int) $result->max_paid_count > 1) {
            return ['available' => false, 'amount' => null, 'reason' => 'multiple_paid_per_order'];
        }
        $amount = (string) $result->amount;
        if (! preg_match('/\A(\d+)(?:\.(\d{1,2}))?\z/', $amount, $parts)) {
            // Never display a rounded/scientific/imprecise value as a verified amount.
            return ['available' => false, 'amount' => null, 'reason' => 'invalid_aggregate'];
        }
        $whole = ltrim($parts[1], '0');

        return ['available' => true, 'amount' => ($whole === '' ? '0' : $whole).'.'.str_pad($parts[2] ?? '', 2, '0'), 'reason' => null];
    }
}
