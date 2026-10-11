<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use DomainException;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /** Preview an eligible code without reserving quota or changing the order. */
    public function previewDiscount(Order $order, string $code): string
    {
        $order->refresh();
        $promotion = $this->resolve($code);
        $this->assertEligible($order, $promotion);

        return $this->calculateDiscount($promotion, $order->subtotal);
    }

    /** Apply one promotion atomically. Does not consume usage. */
    public function applyToOrder(Order $order, string $code): OrderPromotion
    {
        return DB::transaction(function () use ($order, $code): OrderPromotion {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $promotion = Promotion::query()->where('code', $this->normalizeCode($code))
                ->lockForUpdate()->first();
            if ($promotion === null) {
                throw new DomainException('Mã khuyến mãi không tồn tại.');
            }

            $this->assertEligible($lockedOrder, $promotion);
            $discount = $this->calculateDiscount($promotion, $lockedOrder->subtotal);
            $discountCents = $this->cents($discount);
            $totalCents = $this->cents($lockedOrder->subtotal) - $discountCents
                + $this->cents($lockedOrder->tax_amount);

            $snapshot = new OrderPromotion;
            $snapshot->order_id = $lockedOrder->getKey();
            $snapshot->promotion_id = $promotion->getKey();
            $snapshot->promotion_code = $promotion->code;
            $snapshot->discount_amount = $discount;
            $snapshot->save();

            $lockedOrder->discount_amount = $discount;
            $lockedOrder->total_amount = $this->money($totalCents);
            $lockedOrder->save();

            $order->refresh();

            return $snapshot;
        });
    }

    /** Call in the Payment transaction before changing Payment to paid. */
    public function lockPromotionForPayment(Order $order): void
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Cần khóa khuyến mãi trong transaction thanh toán.');
        }

        $snapshot = OrderPromotion::query()->where('order_id', $order->getKey())->first();
        if ($snapshot === null) {
            return;
        }

        $promotion = Promotion::withTrashed()->lockForUpdate()->findOrFail($snapshot->promotion_id);
        if ($promotion->usage_limit !== null && $promotion->used_count >= $promotion->usage_limit) {
            throw new DomainException('Khuyến mãi đã hết lượt sử dụng.');
        }
    }

    /**
     * Call after Payment becomes paid, inside the same transaction that first
     * called lockPromotionForPayment. Payment ownership stays with the caller.
     * Returns false when there is no promotion or this order was already counted.
     */
    public function consumeUsageAfterPaid(Order $order): bool
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Cần gọi cập nhật lượt dùng trong transaction thanh toán.');
        }

        $snapshot = OrderPromotion::query()->where('order_id', $order->getKey())->first();
        if ($snapshot === null) {
            return false;
        }

        $promotion = Promotion::withTrashed()->lockForUpdate()->findOrFail($snapshot->promotion_id);
        $paid = fn ($query) => $query->whereIn('status', ['paid', 'refunded'])->whereNotNull('paid_at');
        if (! $order->payments()->where($paid)->exists()) {
            throw new DomainException('Đơn chưa có thanh toán thành công.');
        }

        // Locking read sees committed paid attempts after the promotion lock.
        // Multiple attempts for one order still contribute only one use.
        $paidOrderCount = DB::table('payments')
            ->whereIn('order_id', fn ($query) => $query->select('order_id')
                ->from('order_promotions')->where('promotion_id', $promotion->getKey()))
            ->whereIn('status', ['paid', 'refunded'])
            ->whereNotNull('paid_at')
            ->lockForUpdate()
            ->pluck('order_id')->unique()->count();

        if ($paidOrderCount === $promotion->used_count) {
            return false;
        }
        if ($paidOrderCount !== $promotion->used_count + 1) {
            throw new DomainException('Lượt dùng khuyến mãi không khớp lịch sử thanh toán.');
        }
        if ($promotion->usage_limit !== null && $paidOrderCount > $promotion->usage_limit) {
            throw new DomainException('Khuyến mãi đã hết lượt sử dụng.');
        }

        $promotion->used_count = $paidOrderCount;
        $promotion->save();

        return true;
    }

    /** Calculate the capped discount in decimal currency units. */
    public function calculateDiscount(Promotion $promotion, string $subtotal): string
    {
        $subtotalCents = $this->cents($subtotal);
        if ($promotion->discount_type === 'fixed') {
            $discountCents = $this->cents($promotion->discount_value);
        } elseif ($promotion->discount_type === 'percentage') {
            $rateBasisPoints = $this->cents($promotion->discount_value);
            $discountCents = $rateBasisPoints >= 10000
                ? $subtotalCents
                : intdiv($subtotalCents, 10000) * $rateBasisPoints
                    + intdiv(($subtotalCents % 10000) * $rateBasisPoints + 5000, 10000);
            if ($promotion->max_discount !== null) {
                $discountCents = min($discountCents, $this->cents($promotion->max_discount));
            }
        } else {
            throw new DomainException('Cấu hình loại khuyến mãi không hợp lệ.');
        }

        return $this->money(min($discountCents, $subtotalCents));
    }

    private function resolve(string $code): Promotion
    {
        return Promotion::query()->where('code', $this->normalizeCode($code))->first()
            ?? throw new DomainException('Mã khuyến mãi không tồn tại.');
    }

    private function assertEligible(Order $order, Promotion $promotion): void
    {
        if ($order->status !== 'pending') {
            throw new DomainException('Đơn không còn ở trạng thái có thể áp dụng khuyến mãi.');
        }
        if ($order->orderPromotion()->exists()) {
            throw new DomainException('Đơn đã có khuyến mãi.');
        }
        if ($promotion->status !== 'active') {
            throw new DomainException('Khuyến mãi không hoạt động.');
        }
        if (now()->lt($promotion->start_at)) {
            throw new DomainException('Khuyến mãi chưa bắt đầu.');
        }
        if (now()->gt($promotion->end_at)) {
            throw new DomainException('Khuyến mãi đã hết hạn.');
        }
        if ($this->cents($order->subtotal) < $this->cents($promotion->minimum_order)) {
            throw new DomainException('Đơn chưa đạt giá trị tối thiểu.');
        }
        if ($promotion->usage_limit !== null && $promotion->used_count >= $promotion->usage_limit) {
            throw new DomainException('Khuyến mãi đã hết lượt sử dụng.');
        }
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new DomainException('Cấu hình số tiền khuyến mãi không hợp lệ.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($cents < 0) {
            throw new DomainException('Cấu hình số tiền khuyến mãi không hợp lệ.');
        }

        return $cents;
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
