<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class RevenueStatisticsService
{
    /** Order completion value is deliberately separate from paid receipt value. */
    public function dailyCompletedOrders(int $days = 7): ?array
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('orders');
        if (! in_array('total_amount', $columns, true) || ! in_array('completed_at', $columns, true)) {
            return null;
        }
        $days = $days === 30 ? 30 : 7;
        $start = today()->subDays($days - 1);
        $end = today()->addDay();
        $orders = DB::table('orders')->where('status', 'completed')
            ->where('completed_at', '>=', $start)->where('completed_at', '<', $end)
            ->selectRaw('DATE(completed_at) AS day, SUM(total_amount) AS amount, COUNT(*) AS count')
            ->groupByRaw('DATE(completed_at)')->get()->keyBy('day');
        $rows = collect(range(0, $days - 1))->map(function ($offset) use ($start, $orders) {
            $date = $start->copy()->addDays($offset);
            $row = $orders->get($date->format('Y-m-d'));
            return ['date' => $date->format('d/m'), 'amount' => (float) ($row->amount ?? 0), 'count' => (int) ($row->count ?? 0)];
        });
        $max = max(1, $rows->max('amount'));
        return ['days' => $days, 'rows' => $rows->map(fn ($row) => [...$row, 'height' => $row['amount'] > 0 ? max(2, $row['amount'] / $max * 100) : 0]), 'total' => $rows->sum('amount'), 'count' => $rows->sum('count')];
    }

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
