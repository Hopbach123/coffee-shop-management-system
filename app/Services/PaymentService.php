<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Adapted from Finance e1e16fc2: settlement only; no Sales/Inventory commands. */
class PaymentService
{
    public function __construct(private readonly PromotionService $promotions) {}

    public function recordPaid(Order $order, array $attributes, User $actor): Payment
    {
        if (! Schema::hasColumn('payments', 'operation_key')) {
            throw ValidationException::withMessages(['payment' => 'Chưa cập nhật schema receipt. Chưa thể ghi nhận thu tiền.']);
        }
        $method = $attributes['payment_method'] ?? '';
        $key = $attributes['operation_key'] ?? '';
        if (! in_array($method, ['cash', 'bank_transfer'], true) || ! is_string($key) || ! preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $key)
            || ! in_array($attributes['received_confirmation'] ?? null, [true, 1, '1', 'on'], true)) {
            throw ValidationException::withMessages(['payment' => 'Cần phương thức, mã thao tác và xác nhận đã thực nhận tiền.']);
        }
        $received = $this->cents($attributes['received_amount'] ?? '');
        $reference = $method === 'bank_transfer' ? strtoupper(trim((string) ($attributes['transaction_code'] ?? ''))) : null;
        if ($method === 'bank_transfer' && ! preg_match('/^[A-Z0-9][A-Z0-9._:\/-]{2,99}$/', $reference)) {
            throw ValidationException::withMessages(['transaction_code' => 'Nhập mã giao dịch ngân hàng hợp lệ (3–100 ký tự).']);
        }
        $hash = hash('sha256', json_encode([$order->id, $actor->id, $method, $this->money($received), $reference], JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($order, $actor, $method, $key, $received, $reference, $hash): Payment {
                $user = User::lockForUpdate()->find($actor->id);
                if (! $user?->canOperateSales()) {
                    throw ValidationException::withMessages(['payment' => 'Chỉ Staff/Admin đang hoạt động được xác nhận thu tiền.']);
                }
                $locked = Order::lockForUpdate()->findOrFail($order->id);
                $prior = Payment::where('operation_key', $key)->lockForUpdate()->first();
                if ($prior) {
                    if (! hash_equals((string) $prior->payload_hash, $hash)) {
                        throw ValidationException::withMessages(['operation_key' => 'Mã thao tác đã dùng với nội dung khác.']);
                    }

                    return $prior;
                }
                if ($locked->status !== 'pending') {
                    throw ValidationException::withMessages(['payment' => 'Đơn không còn hợp lệ để thu tiền.']);
                }
                $items = $locked->orderItems()->orderBy('id')->lockForUpdate()->get();
                if ($items->isEmpty() || $items->contains(fn ($item) => ! in_array($item->status, ['pending', 'preparing', 'ready', 'served'], true))) {
                    throw ValidationException::withMessages(['payment' => 'Đơn chưa có món hợp lệ để thanh toán.']);
                }
                if ($locked->payments()->whereIn('status', ['paid', 'refunded'])->exists()) {
                    throw ValidationException::withMessages(['payment' => 'Đơn đã có chứng từ thu tiền, không thu lần hai.']);
                }
                $total = $this->cents((string) $locked->total_amount);
                if ($total <= 0 || $received < $total || ($method === 'bank_transfer' && $received !== $total)) {
                    throw ValidationException::withMessages(['received_amount' => 'Tiền mặt phải đủ tổng; chuyển khoản phải khớp chính xác tổng đơn.']);
                }
                $bankKey = $reference === null ? null : hash('sha256', $reference);
                if ($bankKey && Payment::where('bank_reference_key', $bankKey)->exists()) {
                    throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch ngân hàng đã được sử dụng.']);
                }
                $hasPromotion = Schema::hasTable('order_promotions');
                if ($hasPromotion) {
                    $this->promotions->lockPromotionForPayment($locked);
                }
                $payment = new Payment;
                $payment->forceFill([
                    'order_id' => $locked->id, 'payment_code' => 'PAY-'.Str::upper(Str::random(24)),
                    'payment_method' => $method, 'amount' => $this->money($total),
                    'received_amount' => $this->money($received), 'change_amount' => $this->money($received - $total),
                    'transaction_code' => $reference, 'bank_reference_key' => $bankKey,
                    'operation_key' => $key, 'payload_hash' => $hash,
                    'status' => 'paid', 'paid_at' => now(), 'created_by' => $user->id,
                ])->saveOrFail();
                if ($hasPromotion) {
                    $this->promotions->consumeUsageAfterPaid($locked);
                }

                return $payment;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $prior = Payment::where('operation_key', $key)->first();
            if ($prior && hash_equals((string) $prior->payload_hash, $hash)) {
                return $prior;
            }
            throw ValidationException::withMessages(['payment' => 'Thao tác, mã ngân hàng hoặc chứng từ đã được ghi nhận. Hãy kiểm tra receipt trước khi thử lại.']);
        }
    }

    /** Read under the caller's Order lock for lifecycle decisions. */
    public function settlementForOrder(Order $order): array
    {
        $query = $order->payments()->where('status', 'paid');
        // Lifecycle may already have a read snapshot while waiting for table locks.
        // A locking read must see the latest committed settlement in that transaction.
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }
        $rows = $query->get();
        $total = $this->cents((string) $order->total_amount);
        $paid = 0;
        foreach ($rows as $row) {
            $paid += $this->cents((string) $row->amount);
        }
        $receipt = $rows->first();

        return ['order_id' => $order->id, 'payment_status' => $total > 0 && $paid >= $total ? 'paid' : 'unpaid',
            'total_amount' => $this->money($total), 'paid_amount' => $this->money($paid),
            'remaining_amount' => $this->money(max(0, $total - $paid)), 'payment_method' => $receipt?->payment_method,
            'payment_reference' => $receipt?->transaction_code ?? $receipt?->payment_code,
            'payment_id' => $receipt?->id, 'paid_at' => $receipt?->paid_at];
    }

    private function cents(mixed $value): int
    {
        if (! is_string($value) || ! preg_match('/^(0|[1-9]\d{0,12})(?:\.(\d{1,2}))?$/', $value, $match)) {
            throw ValidationException::withMessages(['received_amount' => 'Nhập số tiền không âm, tối đa 13 chữ số và 2 số thập phân.']);
        }

        return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
