<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $attributes, User $actor): Order
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $actor): Order {
                    $this->assertActiveStaff($actor);
                    $orderType = $attributes['order_type'];
                    $customerId = $attributes['customer_id'] ?? null;

                    if ($customerId !== null) {
                        $customer = Customer::query()->lockForUpdate()->find($customerId);
                        if ($customer === null || $customer->status !== 'active') {
                            throw ValidationException::withMessages(['customer_id' => 'Khách hàng không còn hợp lệ hoặc đã ngừng hoạt động.']);
                        }
                    }

                    $tableId = null;
                    if ($orderType === 'dine_in') {
                        $tableId = (int) $attributes['table_id'];
                        $tableSnapshot = CafeTable::query()->find($tableId);
                        if ($tableSnapshot === null) {
                            throw ValidationException::withMessages(['table_id' => 'Bàn không còn tồn tại.']);
                        }

                        // Follow the existing table-management lock order: area, then table.
                        $area = Area::query()->lockForUpdate()->find($tableSnapshot->area_id);
                        $table = CafeTable::query()->lockForUpdate()->find($tableId);
                        if ($area === null || $area->status !== 'active' || $table === null || $table->status !== 'available') {
                            throw ValidationException::withMessages(['table_id' => 'Bàn không còn sẵn sàng để tạo đơn tại chỗ.']);
                        }

                        $activeOrderExists = Order::query()
                            ->where('table_id', $table->getKey())
                            ->where('order_type', 'dine_in')
                            ->where('status', 'pending')
                            ->lockForUpdate()
                            ->exists();

                        if ($activeOrderExists) {
                            throw ValidationException::withMessages(['table_id' => 'Bàn này đã có đơn đang xử lý.']);
                        }

                        $table->status = 'occupied';
                        $table->saveOrFail();
                    }

                    $order = new Order();
                    $order->forceFill([
                        'order_code' => $this->newOrderCode(),
                        'table_id' => $tableId,
                        'customer_id' => $customerId,
                        'user_id' => $actor->getKey(),
                        'reservation_id' => null,
                        'order_type' => $orderType,
                        'status' => 'pending',
                        'subtotal' => '0.00',
                        'discount_amount' => '0.00',
                        'tax_amount' => '0.00',
                        'total_amount' => '0.00',
                        'note' => $attributes['note'] ?? null,
                        'ordered_at' => now(),
                        'completed_at' => null,
                    ]);
                    $order->saveOrFail();

                    return $order;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã đơn hàng duy nhất.');
    }

    /**
     * Add one sellable variant to a pending order and refresh its server-owned totals.
     * Toppings and item-status transitions are deliberately handled by later workflows.
     */
    public function addItem(Order $order, array $attributes, User $actor): OrderItem
    {
        return DB::transaction(function () use ($order, $attributes, $actor): OrderItem {
            $this->assertActiveStaff($actor);

            $lockedOrder = Order::query()->lockForUpdate()->find($order->getKey());
            if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể thêm món vào đơn đang chờ xử lý.']);
            }

            $variant = ProductVariant::query()->lockForUpdate()->find($attributes['variant_id']);
            $product = $variant === null
                ? null
                : Product::query()->lockForUpdate()->find($variant->product_id);

            if ($product === null || $product->status !== 'available') {
                throw ValidationException::withMessages(['variant_id' => 'Sản phẩm của biến thể này không còn sẵn sàng để bán.']);
            }

            if ($variant === null || $variant->status !== 'available') {
                throw ValidationException::withMessages(['variant_id' => 'Biến thể không hợp lệ hoặc không còn sẵn sàng để bán.']);
            }

            $quantity = (int) $attributes['quantity'];
            $unitPrice = $this->normalizeMoney($variant->getRawOriginal('price'));
            $lineSubtotal = $this->multiplyMoney($unitPrice, $quantity);

            $item = new OrderItem();
            $item->forceFill([
                'order_id' => $lockedOrder->getKey(),
                'product_id' => $product->getKey(),
                'variant_id' => $variant->getKey(),
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'topping_amount' => '0.00',
                'subtotal' => $lineSubtotal,
                'note' => $attributes['note'] ?? null,
                'status' => 'pending',
            ]);
            $item->saveOrFail();

            $this->recalculateTotals($lockedOrder);

            return $item;
        }, 3);
    }

    private function assertActiveStaff(User $actor): void
    {
        $currentActor = User::query()->lockForUpdate()->find($actor->getKey());
        if ($currentActor === null || $currentActor->role !== 'staff' || $currentActor->status !== 'active') {
            throw new DomainException('Chỉ nhân viên đang hoạt động mới được tạo đơn hàng.');
        }
    }

    private function newOrderCode(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotalInCents = 0;
        $maximumOrderAmountInCents = 99999999999999999;

        OrderItem::query()
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->get(['subtotal'])
            ->each(function (OrderItem $item) use (&$subtotalInCents, $maximumOrderAmountInCents): void {
                $itemSubtotalInCents = $this->moneyToCents($item->getRawOriginal('subtotal'));
                if ($itemSubtotalInCents > $maximumOrderAmountInCents - $subtotalInCents) {
                    throw ValidationException::withMessages(['quantity' => 'Tổng tiền đơn hàng vượt quá giới hạn cho phép.']);
                }

                $subtotalInCents += $itemSubtotalInCents;
            });

        $discountInCents = $this->moneyToCents($order->getRawOriginal('discount_amount'));
        $taxInCents = $this->moneyToCents($order->getRawOriginal('tax_amount'));
        if ($discountInCents > $subtotalInCents) {
            throw new DomainException('Giảm giá hiện tại không hợp lệ với tổng tiền món.');
        }

        $order->forceFill([
            'subtotal' => $this->moneyFromCents($subtotalInCents),
            'total_amount' => $this->moneyFromCents($subtotalInCents - $discountInCents + $taxInCents),
        ]);
        $order->saveOrFail();
    }

    private function multiplyMoney(string $amount, int $quantity): string
    {
        $unitPriceInCents = $this->moneyToCents($amount);
        $maximumOrderAmountInCents = 99999999999999999;

        if ($unitPriceInCents > intdiv($maximumOrderAmountInCents, $quantity)) {
            throw ValidationException::withMessages(['quantity' => 'Số lượng làm tổng tiền món vượt quá giới hạn cho phép.']);
        }

        return $this->moneyFromCents($unitPriceInCents * $quantity);
    }

    private function normalizeMoney(mixed $amount): string
    {
        $amount = trim((string) $amount);
        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new DomainException('Giá bán của biến thể không hợp lệ.');
        }

        return $matches[1].'.'.str_pad($matches[2] ?? '', 2, '0');
    }

    private function moneyToCents(mixed $amount): int
    {
        [$whole, $fraction] = explode('.', $this->normalizeMoney($amount));

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function moneyFromCents(int $amountInCents): string
    {
        if ($amountInCents < 0) {
            throw new DomainException('Tổng tiền không thể âm.');
        }

        return intdiv($amountInCents, 100).'.'.str_pad((string) ($amountInCents % 100), 2, '0', STR_PAD_LEFT);
    }
}
