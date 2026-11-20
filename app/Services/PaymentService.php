<?php

namespace App\Services;

use App\Data\Payments\SettlementResult;
use App\Data\Payments\SettlementSnapshot;
use App\Models\CustomerOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Support\DecimalMoney;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PromotionService $promotions,
    ) {}

    /** Finance writer for Sales orchestration. Completion and preparation belong to the caller. */
    public function confirmCash(int $orderId, string $operationKey, string $receivedAmount, User $actor): SettlementResult
    {
        return $this->confirmSettlement($orderId, $operationKey, 'cash', $receivedAmount, null, $actor);
    }

    public function confirmBankTransfer(int $orderId, string $operationKey, string $bankReference, string $verifiedAmount, User $actor): SettlementResult
    {
        if (trim($bankReference) === '' || mb_strlen(trim($bankReference)) > 100) {
            throw ValidationException::withMessages(['transaction_code' => 'API Finance yêu cầu mã giao dịch ngân hàng tối đa 100 ký tự.']);
        }

        return $this->confirmSettlement($orderId, $operationKey, 'bank_transfer', $verifiedAmount, trim($bankReference), $actor);
    }

    private function settlementSnapshot(int $orderId): SettlementSnapshot
    {
        return DB::transaction(function () use ($orderId): SettlementSnapshot {
            $order = Order::lockForUpdate()->findOrFail($orderId);
            $payments = Payment::where('order_id', $orderId)->where('status', 'paid')->orderBy('id')->lockForUpdate()->get();
            $confirmed = 0;
            foreach ($payments as $payment) {
                $confirmed += DecimalMoney::cents($payment->amount);
            }

            return new SettlementSnapshot($orderId, $order->total_amount, DecimalMoney::format($confirmed),
                $payments->count() === 1 && $payments->first()->paid_at !== null
                    && $confirmed > 0 && $confirmed === DecimalMoney::cents($order->total_amount),
                $payments->pluck('payment_code')->all());
        });
    }

    private function confirmSettlement(int $orderId, string $key, string $method, string $received, ?string $reference, User $actor): SettlementResult
    {
        if (trim($key) === '' || strlen($key) > 200) {
            throw ValidationException::withMessages(['operation_key' => 'Khóa thao tác bắt buộc, tối đa 200 ký tự.']);
        }
        $received = DecimalMoney::format(DecimalMoney::cents($received));
        $keyHash = hash('sha256', $key);
        $payloadHash = hash('sha256', json_encode([$orderId, $actor->getKey(), $method, $received, $reference], JSON_THROW_ON_ERROR));

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($orderId, $actor, $method, $received, $reference, $keyHash, $payloadHash): SettlementResult {
                    $currentActor = User::lockForUpdate()->find($actor->getKey());
                    if ($currentActor === null || ! $currentActor->canOperateSales()) {
                        throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được ghi nhận thanh toán.');
                    }
                    $order = Order::lockForUpdate()->findOrFail($orderId);
                    $existing = Payment::where('operation_key_hash', $keyHash)->lockForUpdate()->first();
                    if ($existing !== null) {
                        if (! hash_equals($existing->operation_payload_hash ?? '', $payloadHash)) {
                            throw ValidationException::withMessages(['operation_key' => 'Khóa thao tác đã được dùng cho nội dung hoặc người thao tác khác.']);
                        }

                        return $this->settlementResult($existing, true);
                    }
                    $items = OrderItem::where('order_id', $orderId)->orderBy('id')->lockForUpdate()->get();
                    if ($order->status !== 'pending' || ! $items->contains(fn (OrderItem $item): bool => $item->status !== 'cancelled')) {
                        throw ValidationException::withMessages(['order' => 'Đơn phải đang xử lý và có món hợp lệ để thu tiền.']);
                    }
                    if (Payment::where('order_id', $orderId)->whereIn('status', ['paid', 'refunded'])->lockForUpdate()->exists()) {
                        throw ValidationException::withMessages(['payment' => 'Đơn đã có khoản thu. Vui lòng kiểm tra lịch sử thanh toán.']);
                    }
                    $request = CustomerOrderRequest::where('order_id', $orderId)->lockForUpdate()->first();
                    if ($request !== null && ($request->status !== 'accepted'
                        || ($request->payment_option === 'cod' && $request->delivered_at === null)
                        || ($request->payment_option === 'bank_transfer_prepaid' && $method !== 'bank_transfer'))) {
                        throw ValidationException::withMessages(['payment' => 'Chưa đủ điều kiện xác nhận thu tiền cho yêu cầu web.']);
                    }
                    $this->orders->refreshTotalsForPayment($order);
                    $expected = DecimalMoney::cents($order->total_amount);
                    $receivedCents = DecimalMoney::cents($received);
                    if ($expected === 0 || $receivedCents < $expected || ($method === 'bank_transfer' && $receivedCents !== $expected)) {
                        throw ValidationException::withMessages(['received_amount' => 'Tiền mặt phải đủ tổng đơn. Chuyển khoản phải xác nhận đúng số tiền của đơn.']);
                    }
                    $this->promotions->lockPromotionForPayment($order);
                    $payment = $this->createPaidPayment($order, ['payment_method' => $method, 'transaction_code' => $reference], $currentActor, [
                        'operation_key_hash' => $keyHash, 'operation_payload_hash' => $payloadHash,
                        'received_amount' => $received, 'change_amount' => DecimalMoney::format($receivedCents - $expected),
                    ]);
                    $this->promotions->consumeUsageAfterPaid($order);

                    return $this->settlementResult($payment, false);
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($this->isUniqueCollision($exception, 'bank_reference_hash')) {
                    throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch đã được sử dụng cho khoản thu khác.']);
                }
                if ($attempt === 2 || (! $this->isUniqueCollision($exception, 'operation_key_hash')
                    && ! $this->isUniqueCollision($exception, 'payment_code'))) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Chưa thể ghi nhận thanh toán. Hãy kiểm tra bằng khóa thao tác đã dùng.');
    }

    private function settlementResult(Payment $payment, bool $replay): SettlementResult
    {
        return new SettlementResult($payment->id, $payment->payment_code, $payment->order_id, $payment->amount,
            $payment->payment_method, $payment->paid_at->toIso8601String(), $replay,
            $payment->received_amount ?? $payment->amount, $payment->change_amount ?? '0.00');
    }

    private function isUniqueCollision(UniqueConstraintViolationException $exception, string $column): bool
    {
        $details = (string) ($exception->errorInfo[2] ?? '');

        return str_contains($details, 'payments.'.$column)
            || str_contains($details, 'payments_'.$column.'_unique');
    }

    private function createPaidPayment(Order $order, array $attributes, User $actor, array $receipt = []): Payment
    {
        $reference = $attributes['payment_method'] === 'bank_transfer' ? (trim($attributes['transaction_code'] ?? '') ?: null) : null;
        $referenceHash = null;
        if ($reference !== null) {
            $normalized = mb_strtoupper($reference);
            $referenceHash = hash('sha256', $normalized);
            if (Payment::whereIn('status', ['paid', 'refunded'])->whereRaw('UPPER(TRIM(transaction_code)) = ?', [$normalized])
                ->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch đã được sử dụng cho khoản thu khác.']);
            }
        }
        $payment = new Payment;
        if (Schema::hasColumn('payments', 'bank_reference_key')) {
            $payment->forceFill(['bank_reference_key' => $referenceHash]);
        }
        $received = $attributes['payment_method'] === 'cash' && isset($attributes['received_amount'])
            ? $attributes['received_amount'] : $order->total_amount;
        $receivedCents = DecimalMoney::cents($received);
        $expectedCents = DecimalMoney::cents($order->total_amount);
        if ($receivedCents < $expectedCents) {
            throw ValidationException::withMessages(['received_amount' => 'Tiền khách đưa chưa đủ tổng thanh toán.']);
        }
        $payment->forceFill(array_merge([
            'order_id' => $order->getKey(), 'payment_code' => 'PAY-'.Str::upper(Str::random(24)),
            'payment_method' => $attributes['payment_method'], 'amount' => $order->total_amount,
            'transaction_code' => $reference, 'bank_reference_hash' => $referenceHash,
            'received_amount' => DecimalMoney::format($receivedCents), 'change_amount' => DecimalMoney::format($receivedCents - $expectedCents),
            'status' => 'paid', 'paid_at' => now(), 'created_by' => $actor->getKey(),
        ], $receipt));
        try {
            $payment->saveOrFail();
        } catch (UniqueConstraintViolationException $exception) {
            if ($this->isUniqueCollision($exception, 'bank_reference_hash')) {
                throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch đã được sử dụng cho khoản thu khác.']);
            }
            throw $exception;
        }

        return $payment;
    }

    private function recordServedPayment(Order $order, array $attributes, User $actor): Payment
    {
        if (! in_array($attributes['payment_method'] ?? null, ['cash', 'bank_transfer'], true)
            || ! in_array($attributes['received_confirmation'] ?? null, [true, 1, '1', 'true', 'yes', 'on'], true)) {
            throw ValidationException::withMessages(['payment' => 'Phải chọn phương thức và xác nhận đã nhận đủ tiền.']);
        }
        $reference = $attributes['transaction_code'] ?? null;
        if ($reference !== null && (! is_string($reference) || mb_strlen(trim($reference)) > 100)) {
            throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch tối đa 100 ký tự.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $attributes, $actor, $reference): Payment {
                    $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
                    $currentActor = User::query()->find($actor->getKey());
                    if ($currentActor === null || $currentActor->status !== 'active'
                        || ! in_array($currentActor->role, ['admin', 'staff'], true)) {
                        throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được ghi nhận thanh toán.');
                    }
                    $items = OrderItem::query()->where('order_id', $lockedOrder->getKey())
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($lockedOrder->status !== 'pending'
                        || ! $items->contains('status', 'served')
                        || $items->contains(fn (OrderItem $item): bool => ! in_array($item->status, ['served', 'cancelled'], true))) {
                        throw ValidationException::withMessages(['order' => 'Đơn phải đang xử lý và mọi món chưa hủy phải được phục vụ trước khi thanh toán.']);
                    }
                    $paidExists = Payment::query()->where('order_id', $lockedOrder->getKey())
                        ->where('status', 'paid')->lockForUpdate()->exists();
                    if ($paidExists) {
                        throw ValidationException::withMessages(['payment' => 'Đơn đã có thanh toán thành công.']);
                    }

                    $this->orders->refreshTotalsForPayment($lockedOrder);
                    $amount = $lockedOrder->total_amount;
                    if (! preg_match('/^\d{1,13}\.\d{2}$/', $amount) || $amount === '0.00') {
                        throw ValidationException::withMessages(['order' => 'Tổng thanh toán phải lớn hơn 0 và đúng giới hạn dữ liệu.']);
                    }

                    $this->promotions->lockPromotionForPayment($lockedOrder);
                    $payment = $this->createPaidPayment($lockedOrder, $attributes, $currentActor);
                    $this->promotions->consumeUsageAfterPaid($lockedOrder);

                    $this->orders->completePaidOrderIfAllServed($lockedOrder);

                    return $payment;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                // Only payment-code collisions justify regenerating a code.
                if ($attempt === 2 || ! $this->isUniqueCollision($exception, 'payment_code')) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã thanh toán duy nhất.');
    }

    /**
     * Called only by the POS checkout transaction after Order and items are created.
     * A failed payment or preparation must also roll back that newly created Order.
     */
    public function recordPosPrepaymentAndStartPreparing(Order $order, array $attributes, User $actor): Payment
    {
        return $this->recordPrepaymentAndStartPreparing($order, $attributes, $actor);
    }

    public function confirmCustomerBankTransfer(Order $order, array $attributes, User $actor): Payment
    {
        return DB::transaction(function () use ($order, $attributes, $actor): Payment {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            $request = CustomerOrderRequest::where('order_id', $lockedOrder->id)->lockForUpdate()->first();
            if ($request === null || $request->status !== 'accepted' || $request->payment_option !== 'bank_transfer_prepaid'
                || ($attributes['payment_method'] ?? null) !== 'bank_transfer') {
                throw ValidationException::withMessages(['payment' => 'Chỉ xác nhận chuyển khoản cho đúng yêu cầu web đã tiếp nhận.']);
            }

            return $this->recordPrepaymentAndStartPreparing($lockedOrder, $attributes, $actor);
        }, 3);
    }

    /** COD collection is part of the delivery-confirmation transaction. */
    public function recordCustomerCodReceipt(Order $order, array $attributes, User $actor): Payment
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Thu COD phải nằm trong transaction xác nhận giao hàng.');
        }
        $request = CustomerOrderRequest::where('order_id', $order->id)->lockForUpdate()->first();
        if ($request === null || $request->status !== 'accepted' || $request->payment_option !== 'cod' || $request->delivered_at === null) {
            throw ValidationException::withMessages(['payment' => 'COD chỉ được ghi nhận khi xác nhận khách đã nhận món và trả đủ tiền.']);
        }

        return $this->recordPaid($order, $attributes, $actor);
    }

    private function recordPrepaymentAndStartPreparing(Order $order, array $attributes, User $actor): Payment
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('POS phải tạo đơn và thanh toán trong cùng transaction.');
        }
        if (! in_array($attributes['payment_method'] ?? null, ['cash', 'bank_transfer'], true)
            || ! in_array($attributes['received_confirmation'] ?? null, [true, 1, '1', 'true', 'yes', 'on'], true)) {
            throw ValidationException::withMessages(['payment' => 'Phải chọn phương thức và xác nhận đã nhận đủ tiền.']);
        }
        $reference = $attributes['transaction_code'] ?? null;
        if ($reference !== null && (! is_string($reference) || mb_strlen(trim($reference)) > 100)) {
            throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch tối đa 100 ký tự.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $attributes, $actor, $reference): Payment {
                    $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
                    $currentActor = User::query()->find($actor->getKey());
                    if ($currentActor === null || $currentActor->status !== 'active'
                        || ! in_array($currentActor->role, ['admin', 'staff'], true)) {
                        throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được ghi nhận thanh toán.');
                    }
                    $items = OrderItem::query()->where('order_id', $lockedOrder->getKey())
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($lockedOrder->status !== 'pending' || $items->isEmpty()
                        || $items->contains(fn (OrderItem $item): bool => $item->status !== 'pending')) {
                        throw ValidationException::withMessages(['order' => 'POS chỉ thu trước cho đơn đang xử lý với toàn bộ món chờ pha chế.']);
                    }
                    if (Payment::query()->where('order_id', $lockedOrder->getKey())
                        ->where('status', 'paid')->lockForUpdate()->exists()) {
                        throw ValidationException::withMessages(['payment' => 'Đơn đã có thanh toán thành công.']);
                    }

                    $this->orders->refreshTotalsForPayment($lockedOrder);
                    $amount = $lockedOrder->total_amount;
                    if (! preg_match('/^\d{1,13}\.\d{2}$/', $amount) || $amount === '0.00') {
                        throw ValidationException::withMessages(['order' => 'Tổng thanh toán phải lớn hơn 0 và đúng giới hạn dữ liệu.']);
                    }

                    $this->promotions->lockPromotionForPayment($lockedOrder);
                    $payment = $this->createPaidPayment($lockedOrder, $attributes, $currentActor);
                    $this->promotions->consumeUsageAfterPaid($lockedOrder);

                    foreach ($items as $item) {
                        $this->orders->transitionItemStatus($lockedOrder, $item, 'preparing', $currentActor);
                    }

                    return $payment;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2 || ! $this->isUniqueCollision($exception, 'payment_code')) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã thanh toán duy nhất.');
    }

    /** Compatibility at the Finance boundary: old Sales receipt forms and Finance channel commands. */
    public function recordPaid(Order $order, array $attributes, User $actor): Payment
    {
        return array_key_exists('operation_key', $attributes)
            ? $this->recordSalesReceipt($order, $attributes, $actor)
            : $this->recordServedPayment($order, $attributes, $actor);
    }

    public function settlementForOrder(Order|int $order): array|SettlementSnapshot
    {
        return $order instanceof Order ? $this->salesSettlement($order) : $this->settlementSnapshot($order);
    }
    private function recordSalesReceipt(Order $order, array $attributes, User $actor): Payment
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
                if (Schema::hasTable('customer_order_requests') && $locked->customerRequest !== null) {
                    throw ValidationException::withMessages(['payment' => 'Đơn giao web phải xác nhận thanh toán qua luồng giao hàng.']);
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
                if ($bankKey && Payment::whereRaw('UPPER(TRIM(transaction_code)) = ?', [$reference])->whereIn('status', ['paid', 'refunded'])->exists()) {
                    throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch ngân hàng đã được sử dụng.']);
                }
                $hasPromotion = Schema::hasTable('order_promotions');
                if ($hasPromotion) {
                    $this->promotions->lockPromotionForPayment($locked);
                }
                $payment = new Payment;
                if (Schema::hasColumn('payments', 'bank_reference_hash')) {
                    $payment->forceFill(['bank_reference_hash' => $bankKey]);
                }
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
    private function salesSettlement(Order $order): array
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

        return ['order_id' => $order->id, 'payment_status' => $total > 0 && $rows->count() === 1 && $paid === $total ? 'paid' : 'unpaid',
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
