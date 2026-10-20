<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Models\ProductRecipe;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function startPreparing(OrderItem $orderItem, User $actor): void
    {
        DB::transaction(function () use ($orderItem, $actor): void {
            $this->assertActiveStaff($actor);

            $lockedItem = OrderItem::query()->lockForUpdate()->find($orderItem->getKey());
            if ($lockedItem === null || $lockedItem->status !== 'pending') {
                throw ValidationException::withMessages(['order_item' => 'Chỉ có thể bắt đầu pha chế món đang chờ xử lý.']);
            }

            $requiredByIngredient = $this->requiredQuantities($lockedItem);
            $ingredients = Ingredient::query()
                ->whereIn('id', array_keys($requiredByIngredient))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requiredByIngredient as $ingredientId => $requiredQuantity) {
                $ingredient = $ingredients->get($ingredientId);
                if ($ingredient === null || $ingredient->status !== 'active') {
                    throw ValidationException::withMessages(['ingredient' => 'Nguyên liệu không hợp lệ hoặc đã ngừng sử dụng.']);
                }

                if ($this->decimalToMilli($ingredient->getRawOriginal('current_stock')) < $requiredQuantity) {
                    throw ValidationException::withMessages(['ingredient' => 'Không đủ tồn kho để bắt đầu pha chế.']);
                }
            }

            foreach ($requiredByIngredient as $ingredientId => $requiredQuantity) {
                /** @var Ingredient $ingredient */
                $ingredient = $ingredients->get($ingredientId);
                $beforeQuantity = $this->decimalToMilli($ingredient->getRawOriginal('current_stock'));
                $afterQuantity = $beforeQuantity - $requiredQuantity;

                $ingredient->forceFill(['current_stock' => $this->milliToDecimal($afterQuantity)]);
                $ingredient->saveOrFail();

                $transaction = new InventoryTransaction();
                $transaction->forceFill([
                    'ingredient_id' => $ingredient->getKey(),
                    'type' => 'consume',
                    'quantity' => $this->milliToDecimal($requiredQuantity),
                    'before_quantity' => $this->milliToDecimal($beforeQuantity),
                    'after_quantity' => $this->milliToDecimal($afterQuantity),
                    'reference_type' => 'order_item',
                    'reference_id' => $lockedItem->getKey(),
                    'note' => null,
                    'created_by' => $actor->getKey(),
                ]);
                $transaction->saveOrFail();
            }

            $lockedItem->forceFill(['status' => 'preparing']);
            $lockedItem->saveOrFail();
        }, 3);
    }

    /**
     * @return array<int, int> Ingredient ID => required quantity in thousandths.
     */
    private function requiredQuantities(OrderItem $item): array
    {
        $itemQuantity = (int) $item->quantity;
        if ($itemQuantity < 1) {
            throw new DomainException('Số lượng món không hợp lệ để trừ kho.');
        }

        $maximumMilli = 999999999999;
        $requiredByIngredient = [];

        ProductRecipe::query()
            ->where('variant_id', $item->variant_id)
            ->orderBy('ingredient_id')
            ->get(['ingredient_id', 'quantity'])
            ->each(function (ProductRecipe $recipe) use (&$requiredByIngredient, $itemQuantity, $maximumMilli): void {
                $recipeQuantity = $this->decimalToMilli($recipe->getRawOriginal('quantity'));
                if ($recipeQuantity > intdiv($maximumMilli, $itemQuantity)) {
                    throw ValidationException::withMessages(['quantity' => 'Lượng nguyên liệu cần dùng vượt quá giới hạn cho phép.']);
                }

                $requiredQuantity = $recipeQuantity * $itemQuantity;
                $ingredientId = (int) $recipe->ingredient_id;
                $currentRequirement = $requiredByIngredient[$ingredientId] ?? 0;
                if ($requiredQuantity > $maximumMilli - $currentRequirement) {
                    throw ValidationException::withMessages(['quantity' => 'Lượng nguyên liệu cần dùng vượt quá giới hạn cho phép.']);
                }

                $requiredByIngredient[$ingredientId] = $currentRequirement + $requiredQuantity;
            });

        ksort($requiredByIngredient, SORT_NUMERIC);

        return $requiredByIngredient;
    }

    private function assertActiveStaff(User $actor): void
    {
        $currentActor = User::query()->find($actor->getKey());
        if ($currentActor === null || $currentActor->role !== 'staff' || $currentActor->status !== 'active') {
            throw new DomainException('Chỉ nhân viên đang hoạt động mới được bắt đầu pha chế.');
        }
    }

    private function decimalToMilli(mixed $quantity): int
    {
        $quantity = trim((string) $quantity);
        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,3}))?$/', $quantity, $matches)) {
            throw new DomainException('Số lượng tồn kho không hợp lệ.');
        }

        return ((int) $matches[1] * 1000) + (int) str_pad($matches[2] ?? '', 3, '0');
    }

    private function milliToDecimal(int $quantity): string
    {
        if ($quantity < 0) {
            throw new DomainException('Tồn kho không thể âm.');
        }

        return intdiv($quantity, 1000).'.'.str_pad((string) ($quantity % 1000), 3, '0', STR_PAD_LEFT);
    }
}
