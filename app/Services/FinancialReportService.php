<?php

namespace App\Services;

use App\Models\Payment;
use App\Support\DecimalMoney;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialReportService
{
    public function report(?string $from, ?string $to): array
    {
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh');
        $from ??= $today->format('Y-m-d');
        $to ??= $today->format('Y-m-d');
        if ($from > $to) {
            throw ValidationException::withMessages(['to' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.']);
        }
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $from, 'Asia/Ho_Chi_Minh')->utc();
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $to, 'Asia/Ho_Chi_Minh')->addDay()->utc();

        return DB::transaction(function () use ($from, $to, $start, $end): array {
            $query = Payment::query()->where('status', 'paid')->where('paid_at', '>=', $start)->where('paid_at', '<', $end);
            // A second paid receipt outside the selected period also makes an order inconsistent.
            $duplicate = Payment::query()->select('order_id')->where('status', 'paid')
                ->whereIn('order_id', (clone $query)->select('order_id'))
                ->groupBy('order_id')->havingRaw('COUNT(*) > 1')->exists();
            $totals = ['cash' => 0, 'bank_transfer' => 0];
            $count = 0;
            foreach ((clone $query)->select(['id', 'payment_method', 'amount'])->orderBy('id')->cursor() as $payment) {
                $cents = DecimalMoney::cents($payment->amount);
                if (! array_key_exists($payment->payment_method, $totals)
                    || $cents > PHP_INT_MAX - $totals[$payment->payment_method]) {
                    throw new DomainException('Dữ liệu thanh toán vượt giới hạn báo cáo.');
                }
                $totals[$payment->payment_method] += $cents;
                $count++;
            }
            if ($totals['cash'] > PHP_INT_MAX - $totals['bank_transfer']) {
                throw new DomainException('Tổng tiền vượt giới hạn báo cáo.');
            }

            return [
                'from' => $from, 'to' => $to, 'available' => ! $duplicate,
                'total' => $duplicate ? null : DecimalMoney::format($totals['cash'] + $totals['bank_transfer']),
                'cash' => $duplicate ? null : DecimalMoney::format($totals['cash']),
                'bank_transfer' => $duplicate ? null : DecimalMoney::format($totals['bank_transfer']),
                'paid_orders' => $duplicate ? null : $count,
                'undated_paid' => Payment::where('status', 'paid')->whereNull('paid_at')->count(),
                'receipts' => (clone $query)->with(['order', 'creator'])->orderByDesc('paid_at')->orderByDesc('id')
                    ->paginate(25)->withQueryString(),
            ];
        });
    }
}
