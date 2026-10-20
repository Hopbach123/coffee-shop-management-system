<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Topping;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

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
     * Creates a pending order and all of its item snapshots as one POS workflow.
     * The POS controller supplies only variant IDs and quantities from its draft cart.
     *
     * @param  array<int, array{variant_id: mixed, quantity: mixed}>  $items
     */
    public function createWithItems(array $attributes, array $items, User $actor): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['cart' => 'Chưa có món trong đơn.']);
        }

        return DB::transaction(function () use ($attributes, $items, $actor): Order {
            foreach ($items as $item) {
                if (! is_array($item)
                    || ! isset($item['variant_id'], $item['quantity'])
                    || ! ctype_digit((string) $item['variant_id'])
                    || ! ctype_digit((string) $item['quantity'])
                    || (int) $item['variant_id'] < 1
                    || (int) $item['quantity'] < 1
                    || (int) $item['quantity'] > 2147483647) {
                    throw ValidationException::withMessages(['cart' => 'Giỏ hàng không hợp lệ.']);
                }
            }

            $order = $this->create($attributes, $actor);

            foreach ($items as $item) {
                $this->addItem($order, [
                    'variant_id' => (int) $item['variant_id'],
                    'quantity' => (int) $item['quantity'],
                ], $actor);
            }

            return Order::query()->findOrFail($order->getKey());
        }, 3);
    }

    /**
     * Cancel a pending order without deleting its historical item or topping snapshots.
     * Inventory is intentionally not restored here: only pending items are cancellable.
     */
    public function cancel(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            $this->assertActiveStaff($actor);

            $lockedOrder = Order::query()->lockForUpdate()->find($order->getKey());
            if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hủy đơn đang chờ xử lý.']);
            }

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->getKey())
                ->lockForUpdate()
                ->get(['id', 'status']);

            if ($items->contains(fn (OrderItem $item): bool => $item->status !== 'pending')) {
                throw ValidationException::withMessages(['order_item' => 'Không thể hủy đơn đã có món đang hoặc đã pha chế.']);
            }

            $lockedOrder->forceFill(['status' => 'cancelled']);
            $lockedOrder->saveOrFail();

            if ($lockedOrder->order_type === 'dine_in' && $lockedOrder->table_id !== null) {
                $table = CafeTable::withTrashed()->lockForUpdate()->find($lockedOrder->table_id);

                if ($table !== null && ! $table->trashed() && $table->status === 'occupied') {
                    $hasAnotherPendingOrder = Order::query()
                        ->where('table_id', $table->getKey())
                        ->where('order_type', 'dine_in')
                        ->where('status', 'pending')
                        ->where('id', '!=', $lockedOrder->getKey())
                        ->lockForUpdate()
                        ->exists();

                    if (! $hasAnotherPendingOrder) {
                        $table->forceFill(['status' => 'available'])->saveOrFail();
                    }
                }
            }

            return $lockedOrder;
        }, 3);
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

    public function updateItemQuantity(Order $order, OrderItem $item, array $attributes, User $actor): OrderItem
    {
        return DB::transaction(function () use ($order, $item, $attributes, $actor): OrderItem {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $quantity = (int) $attributes['quantity'];

            $lockedItem->forceFill([
                'quantity' => $quantity,
                'subtotal' => $this->lineSubtotal(
                    $lockedItem->getRawOriginal('unit_price'),
                    $quantity,
                    $lockedItem->getRawOriginal('topping_amount'),
                ),
            ]);
            $lockedItem->saveOrFail();

            $this->recalculateTotals($lockedOrder);

            return $lockedItem;
        }, 3);
    }

    public function removeItem(Order $order, OrderItem $item, User $actor): void
    {
        DB::transaction(function () use ($order, $item, $actor): void {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);

            $lockedItem->deleteOrFail();
            $this->recalculateTotals($lockedOrder);
        }, 3);
    }

    public function addItemTopping(Order $order, OrderItem $item, array $attributes, User $actor): OrderItemTopping
    {
        return DB::transaction(function () use ($order, $item, $attributes, $actor): OrderItemTopping {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);

            $topping = Topping::query()->lockForUpdate()->find($attributes['topping_id']);
            if ($topping === null || $topping->status !== 'available') {
                throw ValidationException::withMessages(['topping_id' => 'Topping không hợp lệ hoặc hiện không sẵn sàng.']);
            }

            $quantity = (int) $attributes['quantity'];
            $itemTopping = OrderItemTopping::query()
                ->where('order_item_id', $lockedItem->getKey())
                ->where('topping_id', $topping->getKey())
                ->lockForUpdate()
                ->first();

            if ($itemTopping === null) {
                $itemTopping = new OrderItemTopping();
                $itemTopping->forceFill([
                    'order_item_id' => $lockedItem->getKey(),
                    'topping_id' => $topping->getKey(),
                    'topping_name' => $topping->name,
                    'price' => $this->normalizeMoney($topping->getRawOriginal('price')),
                    'quantity' => $quantity,
                ]);
            } else {
                if ($quantity > 2147483647 - $itemTopping->quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Số lượng topping vượt quá giới hạn cho phép.']);
                }

                // Preserve the original name and price snapshot after a topping is sold.
                $itemTopping->quantity += $quantity;
            }

            $itemTopping->saveOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);

            return $itemTopping;
        }, 3);
    }

    public function removeItemTopping(Order $order, OrderItem $item, OrderItemTopping $itemTopping, User $actor): void
    {
        DB::transaction(function () use ($order, $item, $itemTopping, $actor): void {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $lockedItemTopping = OrderItemTopping::query()
                ->whereKey($itemTopping->getKey())
                ->where('order_item_id', $lockedItem->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedItemTopping === null) {
                throw ValidationException::withMessages(['order_item_topping' => 'Topping không thuộc món trong đơn hàng này.']);
            }

            $lockedItemTopping->deleteOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);
        }, 3);
    }

    public function updateItemToppingQuantity(Order $order, OrderItem $item, OrderItemTopping $itemTopping, array $attributes, User $actor): OrderItemTopping
    {
        return DB::transaction(function () use ($order, $item, $itemTopping, $attributes, $actor): OrderItemTopping {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $lockedItemTopping = OrderItemTopping::query()
                ->whereKey($itemTopping->getKey())
                ->where('order_item_id', $lockedItem->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedItemTopping === null) {
                throw ValidationException::withMessages(['order_item_topping' => 'Topping không thuộc món trong đơn hàng này.']);
            }

            $lockedItemTopping->forceFill(['quantity' => (int) $attributes['quantity']]);
            $lockedItemTopping->saveOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);

            return $lockedItemTopping;
        }, 3);
    }

    public function transitionItemStatus(Order $order, OrderItem $item, string $nextStatus, User $actor): OrderItem
    {
        return DB::transaction(function () use ($order, $item, $nextStatus, $actor): OrderItem {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedItemForStatus($lockedOrder, $item);

            if ($nextStatus === 'preparing') {
                if ($lockedItem->status !== 'pending') {
                    throw ValidationException::withMessages(['order_item' => 'Món phải ở trạng thái chờ trước khi bắt đầu pha chế.']);
                }

                $this->inventoryService->startPreparing($lockedItem, $actor);

                return OrderItem::query()->findOrFail($lockedItem->getKey());
            }

            $expectedCurrentStatus = match ($nextStatus) {
                'ready' => 'preparing',
                'served' => 'ready',
                default => null,
            };

            if ($expectedCurrentStatus === null || $lockedItem->status !== $expectedCurrentStatus) {
                throw ValidationException::withMessages(['status' => 'Chuyển trạng thái món không hợp lệ.']);
            }

            $lockedItem->forceFill(['status' => $nextStatus]);
            $lockedItem->saveOrFail();

            return $lockedItem;
        }, 3);
    }

    private function assertActiveStaff(User $actor): void
    {
        $currentActor = User::query()->lockForUpdate()->find($actor->getKey());
        if ($currentActor === null || $currentActor->role !== 'staff' || $currentActor->status !== 'active') {
            throw new DomainException('Chỉ nhân viên đang hoạt động mới được tạo đơn hàng.');
        }
    }

    private function lockedPendingOrder(Order $order): Order
    {
        $lockedOrder = Order::query()->lockForUpdate()->find($order->getKey());
        if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
            throw ValidationException::withMessages(['order' => 'Chỉ có thể chỉnh sửa món trong đơn đang chờ xử lý.']);
        }

        return $lockedOrder;
    }

    private function lockedPendingItem(Order $order, OrderItem $item): OrderItem
    {
        $lockedItem = OrderItem::query()
            ->whereKey($item->getKey())
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if ($lockedItem === null) {
            throw ValidationException::withMessages(['order_item' => 'Món không thuộc đơn hàng này.']);
        }

        if ($lockedItem->status !== 'pending') {
            throw ValidationException::withMessages(['order_item' => 'Chỉ có thể chỉnh sửa món đang chờ pha chế.']);
        }

        return $lockedItem;
    }

    private function lockedItemForStatus(Order $order, OrderItem $item): OrderItem
    {
        $lockedItem = OrderItem::query()
            ->whereKey($item->getKey())
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if ($lockedItem === null) {
            throw ValidationException::withMessages(['order_item' => 'Món không thuộc đơn hàng này.']);
        }

        return $lockedItem;
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

    private function recalculateItemAndOrderTotals(Order $order, OrderItem $item): void
    {
        $toppingAmountInCents = 0;
        $maximumToppingAmountInCents = 999999999999;

        OrderItemTopping::query()
            ->where('order_item_id', $item->getKey())
            ->lockForUpdate()
            ->get(['price', 'quantity'])
            ->each(function (OrderItemTopping $itemTopping) use (&$toppingAmountInCents, $maximumToppingAmountInCents): void {
                $lineAmountInCents = $this->moneyToCents($this->multiplyMoney(
                    $itemTopping->getRawOriginal('price'),
                    $itemTopping->quantity,
                ));

                if ($lineAmountInCents > $maximumToppingAmountInCents - $toppingAmountInCents) {
                    throw ValidationException::withMessages(['quantity' => 'Tổng tiền topping vượt quá giới hạn cho phép.']);
                }

                $toppingAmountInCents += $lineAmountInCents;
            });

        $toppingAmount = $this->moneyFromCents($toppingAmountInCents);
        $item->forceFill([
            'topping_amount' => $toppingAmount,
            'subtotal' => $this->lineSubtotal(
                $item->getRawOriginal('unit_price'),
                $item->quantity,
                $toppingAmount,
            ),
        ]);
        $item->saveOrFail();

        $this->recalculateTotals($order);
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

    private function lineSubtotal(string $unitPrice, int $quantity, string $toppingAmount): string
    {
        $itemAmountInCents = $this->moneyToCents($this->multiplyMoney($unitPrice, $quantity));
        $toppingAmountInCents = $this->moneyToCents($toppingAmount);
        $maximumOrderAmountInCents = 99999999999999999;

        if ($toppingAmountInCents > $maximumOrderAmountInCents - $itemAmountInCents) {
            throw ValidationException::withMessages(['quantity' => 'Tổng tiền món vượt quá giới hạn cho phép.']);
        }

        return $this->moneyFromCents($itemAmountInCents + $toppingAmountInCents);
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
