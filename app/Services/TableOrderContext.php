<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TableOrderContext
{
    public function candidates(CafeTable $table): HasMany
    {
        return $table->orders()->where('order_type', 'dine_in')->where('status', 'pending');
    }

    public function read(int $tableId, int $orderId): array
    {
        $table = CafeTable::query()->with('area')->findOrFail($tableId);
        $order = Order::query()->where('table_id', $tableId)->where('order_type', 'dine_in')->findOrFail($orderId);
        $this->assertUsable($table, $order);

        return [$table, $order];
    }

    private function assertUsable(CafeTable $table, Order $order): void
    {
        abort_unless($table->area !== null && ! $table->area->trashed() && $table->area->status === 'active'
            && in_array($table->status, ['available', 'occupied'], true), 409, 'Bàn hoặc khu vực không còn cho phép gọi món.');
        abort_unless($order->status === 'pending' && $this->candidates($table)->count() === 1,
            409, 'Đơn đã đóng hoặc bàn có nhiều đơn đang xử lý. Vui lòng kiểm tra lại.');
    }

    public function save(int $tableId, int $orderId, array $data, User $actor): array
    {
        return DB::transaction(function () use ($tableId, $orderId, $data, $actor): array {
            $currentActor = User::query()->lockForUpdate()->findOrFail($actor->id);
            abort_unless(in_array($currentActor->role, ['admin', 'staff'], true) && $currentActor->status === 'active', 403);
            $snapshot = CafeTable::query()->findOrFail($tableId);
            $area = Area::query()->lockForUpdate()->find($snapshot->area_id);
            $table = CafeTable::query()->lockForUpdate()->findOrFail($tableId);
            abort_unless($area !== null && $table->area_id === $area->id, 409, 'Khu vực của bàn đã thay đổi.');
            $table->setRelation('area', $area);
            $order = Order::query()->where('table_id', $tableId)->where('order_type', 'dine_in')->lockForUpdate()->findOrFail($orderId);
            $this->candidates($table)->lockForUpdate()->get(['id']);
            $this->assertUsable($table, $order);
            $item = app(OrderService::class)->addItem($order, $data, $currentActor);

            return ['saved_item_id' => $item->id, 'snapshot' => $this->summary($order->fresh())];
        }, 3);
    }

    public function summary(Order $order): array
    {
        return [
            'order_id' => $order->id, 'status' => $order->status,
            'subtotal' => $order->subtotal, 'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount, 'total_amount' => $order->total_amount,
            'items' => $order->orderItems()->orderBy('id')->get([
                'id', 'product_name', 'variant_name', 'quantity', 'unit_price', 'topping_amount', 'subtotal', 'note', 'status',
            ])->toArray(),
        ];
    }
}
