<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use UnexpectedValueException;

/** Read-only daily overview. Stored UTC timestamps are grouped into Vietnam business days. */
class PeriodStatisticsService
{
    public function summarize(int $days, bool $paymentsVerified, string $metric = 'paid'): ?array
    {
        $columns = Schema::getColumnListing('orders');
        if (array_diff(['ordered_at', 'completed_at', 'total_amount'], $columns)) {
            return null;
        }

        $days = $days === 30 ? 30 : 7;
        $end = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay()->addDay();
        $start = $end->subDays($days);
        $bounds = [$start->utc()->format('Y-m-d H:i:s'), $end->utc()->format('Y-m-d H:i:s')];
        $statuses = DB::table('orders')->where('ordered_at', '>=', $bounds[0])->where('ordered_at', '<', $bounds[1])
            ->select('status')->selectRaw('COUNT(*) AS count')->groupBy('status')->pluck('count', 'status')->map(fn ($v) => (int) $v)->all();
        $completed = $this->daily('orders', 'completed_at', 'total_amount', 'completed', $bounds);
        $paidColumns = Schema::getColumnListing('payments');
        $paidAvailable = $paymentsVerified && in_array('paid_at', $paidColumns, true)
            && ! DB::table('payments')->where('status', 'paid')->whereNull('paid_at')->exists();
        $paid = $paidAvailable ? $this->daily('payments', 'paid_at', 'amount', 'paid', $bounds) : collect();

        $completedCents = 0;
        $paidCents = 0;
        $completedCount = 0;
        $rows = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $key = $date->format('Y-m-d');
            $completion = $completed->get($key);
            $receipt = $paid->get($key);
            $value = $this->cents((string) ($completion->amount ?? '0'));
            $received = $this->cents((string) ($receipt->amount ?? '0'));
            $completedCents = $this->add($completedCents, $value);
            $paidCents = $this->add($paidCents, $received);
            $completedCount += (int) ($completion->count ?? 0);
            $rows[] = ['date' => $date->format('d/m'), 'full_date' => $date->format('d/m/Y'),
                'completed' => $this->decimal($value), 'paid' => $this->decimal($received),
                'count' => (int) ($completion->count ?? 0), 'completed_cents' => $value, 'paid_cents' => $received];
        }

        $metric = $metric === 'completed' ? 'completed' : 'paid';
        $max = max(array_column($rows, $metric.'_cents'));
        $ceiling = $max > 0 ? ceil($max / 4 / (10 ** floor(log10(max(1, $max / 4))))) * (10 ** floor(log10(max(1, $max / 4)))) * 4 : 10000;
        $points = [];
        foreach ($rows as $i => &$row) {
            $row['x'] = round(56 + $i * 688 / ($days - 1), 2);
            $row['y'] = round(224 - $row[$metric.'_cents'] / $ceiling * 192, 2);
            $row['show_label'] = $days === 7 || $i % 5 === 0 || $i === $days - 1;
            $points[] = $row['x'].','.$row['y'];
        }
        unset($row);
        $ticks = [];
        for ($i = 0; $i <= 4; $i++) {
            $ticks[] = ['y' => 224 - $i * 48, 'label' => $this->compact($ceiling * $i / 4 / 100)];
        }

        return ['days' => $days, 'has_order_details' => ! array_diff(['order_code', 'user_id', 'order_type'], $columns),
            'range' => $start->format('d/m/Y').' – '.$end->subDay()->format('d/m/Y'),
            'updated_at' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
            'metric' => $metric, 'paid_available' => $paidAvailable, 'rows' => $rows, 'ticks' => $ticks,
            'line' => implode(' ', $points), 'area' => '56,224 '.implode(' ', $points).' 744,224',
            'paid' => $this->decimal($paidCents), 'completed' => $this->decimal($completedCents),
            'completed_count' => $completedCount, 'orders_count' => array_sum($statuses), 'statuses' => $statuses,
            'unclassified' => array_sum(array_diff_key($statuses, OrderStatisticsService::STATUS_LABELS)),
            'average' => $completedCount ? $this->decimal(intdiv($completedCents, $completedCount)) : null,
            'has_chart_data' => $metric === 'paid' ? $paidCents > 0 : $completedCents > 0];
    }

    private function daily(string $table, string $date, string $amount, string $status, array $bounds)
    {
        // Identifiers are internal constants, never request input. Vietnam has no DST.
        $day = DB::connection()->getDriverName() === 'sqlite'
            ? "DATE($date, '+7 hours')" : "DATE(DATE_ADD($date, INTERVAL 7 HOUR))";

        return DB::table($table)->where('status', $status)->where($date, '>=', $bounds[0])->where($date, '<', $bounds[1])
            ->selectRaw("$day AS day, CAST(SUM($amount) AS CHAR) AS amount, COUNT(*) AS count")
            ->groupByRaw($day)->get()->keyBy('day');
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/\A(\d{1,15})(?:\.(\d{1,2}))?\z/', $amount, $parts)) {
            throw new UnexpectedValueException('Unverified dashboard amount.');
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    private function add(int $left, int $right): int
    {
        if ($left > PHP_INT_MAX - $right) {
            throw new UnexpectedValueException('Dashboard aggregate exceeds supported range.');
        }

        return $left + $right;
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function compact(float $amount): string
    {
        foreach ([1000000000 => 'tỷ', 1000000 => 'tr', 1000 => 'nghìn'] as $unit => $label) {
            if ($amount >= $unit) {
                return rtrim(rtrim(number_format($amount / $unit, 1, ',', ''), '0'), ',').' '.$label;
            }
        }

        return number_format($amount, 0, ',', '.');
    }
}
