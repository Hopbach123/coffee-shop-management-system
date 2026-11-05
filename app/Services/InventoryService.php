<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Models\ProductRecipe;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

                $transaction = new InventoryTransaction;
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
        if ($currentActor === null || ! $currentActor->canOperateSales()) {
            throw new DomainException('Chỉ tài khoản nội bộ đang hoạt động mới được bắt đầu pha chế.');
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

    /**
     * Record a stock import and its immutable audit row atomically.
     */
    public function recordImport(Ingredient $ingredient, User $actor, string $quantity, ?string $note = null, ?string $operationKey = null, ?string $expectedUnit = null): InventoryTransaction
    {
        $quantityInMilliunits = $this->importMilliunits($quantity);
        if ($operationKey !== null && ! preg_match('/\A[1-9]\d{0,17}\z/', $operationKey)) {
            throw new DomainException('Mã phiếu nhập không hợp lệ.');
        }
        if ($note !== null && mb_strlen($note) > 1000) {
            throw new DomainException('Ghi chú nhập kho tối đa 1000 ký tự.');
        }

        return DB::transaction(function () use ($ingredient, $actor, $quantityInMilliunits, $note, $operationKey, $expectedUnit): InventoryTransaction {
            $currentActor = User::query()->lockForUpdate()->find($actor->getKey());
            if ($currentActor === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$actor->getKey()]);
            }
            if ($currentActor->role !== 'admin' || $currentActor->status !== 'active') {
                throw new DomainException('Chỉ Admin đang hoạt động mới được nhập kho.');
            }

            $currentIngredient = Ingredient::query()->lockForUpdate()->find($ingredient->getKey());
            if ($currentIngredient === null) {
                throw (new ModelNotFoundException)->setModel(Ingredient::class, [$ingredient->getKey()]);
            }

            if ($operationKey !== null) {
                $existing = InventoryTransaction::query()->where('ingredient_id', $currentIngredient->id)
                    ->where('reference_type', 'stock_import')->where('reference_id', $operationKey)->first();
                if ($existing !== null) {
                    if ($existing->type !== 'import' || $existing->created_by !== $currentActor->id
                        || $this->importMilliunits($existing->quantity) !== $quantityInMilliunits || $existing->note !== $note) {
                        throw new DomainException('Phiếu đã ghi nhận với dữ liệu khác. Hãy mở phiếu mới nếu cần nhập thêm.');
                    }

                    return $existing;
                }
            }
            if ($currentIngredient->status !== 'active') {
                throw new DomainException('Nguyên liệu đã ngừng dùng. Hãy kích hoạt trước khi nhập kho.');
            }
            if ($expectedUnit !== null && $expectedUnit !== $currentIngredient->unit) {
                throw new DomainException('Đơn vị nguyên liệu đã thay đổi. Hãy mở lại form nhập kho.');
            }

            $beforeInMilliunits = $this->importMilliunits($currentIngredient->current_stock, false);
            $afterInMilliunits = $beforeInMilliunits + $quantityInMilliunits;
            if ($afterInMilliunits > self::MAX_MILLIUNITS) {
                throw new DomainException('Lượng tồn sau nhập vượt giới hạn schema.');
            }

            $currentIngredient->current_stock = $this->importDecimal($afterInMilliunits);
            if (! $currentIngredient->saveOrFail()) {
                throw new \RuntimeException('Stock could not be saved.');
            }

            $transaction = new InventoryTransaction;
            $transaction->ingredient_id = $currentIngredient->getKey();
            $transaction->type = 'import';
            $transaction->quantity = $this->importDecimal($quantityInMilliunits);
            $transaction->before_quantity = $this->importDecimal($beforeInMilliunits);
            $transaction->after_quantity = $this->importDecimal($afterInMilliunits);
            $transaction->reference_type = $operationKey === null ? null : 'stock_import';
            $transaction->reference_id = $operationKey;
            $transaction->note = $note;
            $transaction->created_by = $currentActor->getKey();
            if (! $transaction->saveOrFail()) {
                throw new \RuntimeException('Stock ledger could not be saved.');
            }

            return $transaction;
        }, 3);
    }

    /**
     * Return immutable inventory history for one ingredient, newest first.
     *
     * @return Collection<int, InventoryTransaction>
     */
    public function historyFor(Ingredient $ingredient): Collection
    {
        return InventoryTransaction::query()
            ->where('ingredient_id', $ingredient->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private const MAX_MILLIUNITS = 999_999_999_999;

    public function recordAdjustment(Ingredient $ingredient, User $actor, string $mode, string $quantity, string $note, array $operation): InventoryTransaction
    {
        if (! in_array($mode, ['increase', 'decrease', 'waste', 'stocktake'], true)
            || trim($note) === '' || mb_strlen($note) > 1000
            || ! is_string($operation['key'] ?? null)
            || ! preg_match('/\A[1-9]\d{0,17}\z/', $operation['key'])
            || ($operation['ingredient'] ?? null) !== $ingredient->id
            || ($operation['actor'] ?? null) !== $actor->id
            || ! is_string($operation['unit'] ?? null)) {
            throw new DomainException('Thông tin phiếu điều chỉnh không hợp lệ.');
        }
        $amount = $this->importMilliunits($quantity, $mode !== 'stocktake');

        return DB::transaction(function () use ($ingredient, $actor, $mode, $amount, $note, $operation): InventoryTransaction {
            $currentActor = User::query()->lockForUpdate()->findOrFail($actor->id);
            if ($currentActor->role !== 'admin' || $currentActor->status !== 'active') {
                throw new DomainException('Chỉ Admin đang hoạt động mới được điều chỉnh kho.');
            }
            $current = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);
            $reference = 'stock_'.$mode;
            $existing = InventoryTransaction::query()->where('ingredient_id', $current->id)
                ->where('reference_id', $operation['key'])
                ->whereIn('reference_type', ['stock_increase', 'stock_decrease', 'stock_waste', 'stock_stocktake'])->first();
            if ($existing !== null) {
                $originalAmount = $mode === 'stocktake' ? $existing->after_quantity : $existing->quantity;
                if ($existing->reference_type !== $reference || $existing->created_by !== $currentActor->id
                    || $this->importMilliunits($originalAmount, false) !== $amount || $existing->note !== $note) {
                    throw new DomainException('Phiếu đã ghi với dữ liệu khác. Hãy mở phiếu mới.');
                }

                return $existing;
            }
            if ($current->status !== 'active' || $current->unit !== $operation['unit']) {
                throw new DomainException('Nguyên liệu ngừng dùng hoặc đã đổi đơn vị. Hãy mở lại phiếu.');
            }
            $before = $this->importMilliunits($current->current_stock, false);
            if ($mode === 'stocktake') {
                $latestId = (int) InventoryTransaction::query()->where('ingredient_id', $current->id)->max('id');
                if (($operation['stock'] ?? null) !== $current->current_stock || ($operation['ledger'] ?? null) !== $latestId) {
                    throw new DomainException('Tồn kho đã thay đổi sau khi mở phiếu. Hãy kiểm kê và mở phiếu mới.');
                }
            }
            $after = match ($mode) {
                'increase' => $before + $amount,
                'stocktake' => $amount,
                default => $before - $amount,
            };
            if ($after < 0 || $after > self::MAX_MILLIUNITS) {
                throw new DomainException('Tồn sau điều chỉnh phải từ 0 đến 999999999.999.');
            }
            if ($before === $after) {
                throw new DomainException('Tồn thực tế khớp hệ thống; không cần tạo phiếu điều chỉnh.');
            }
            $current->current_stock = $this->importDecimal($after);
            if (! $current->saveOrFail()) {
                throw new \RuntimeException('Stock could not be saved.');
            }
            $movement = new InventoryTransaction;
            $movement->forceFill([
                'ingredient_id' => $current->id, 'created_by' => $currentActor->id,
                'type' => $mode === 'waste' ? 'waste' : 'adjustment',
                'quantity' => $this->importDecimal(abs($after - $before)),
                'before_quantity' => $this->importDecimal($before), 'after_quantity' => $this->importDecimal($after),
                'reference_type' => $reference, 'reference_id' => $operation['key'], 'note' => $note,
            ]);
            if (! $movement->saveOrFail()) {
                throw new \RuntimeException('Stock ledger could not be saved.');
            }

            return $movement;
        }, 3);
    }

    private function importMilliunits(string $amount, bool $mustBePositive = true): int
    {
        if (! preg_match('/\A\d{1,9}(?:\.\d{1,3})?\z/', $amount)) {
            throw new DomainException('Số lượng phải là số dương, có tối đa ba chữ số thập phân.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $milliunits = (int) $whole * 1000 + (int) str_pad($fraction, 3, '0');
        if (($mustBePositive && $milliunits <= 0) || $milliunits > self::MAX_MILLIUNITS) {
            throw new DomainException('Số lượng vượt giới hạn cho phép.');
        }

        return $milliunits;
    }

    private function importDecimal(int $milliunits): string
    {
        return intdiv($milliunits, 1000).'.'.str_pad((string) ($milliunits % 1000), 3, '0', STR_PAD_LEFT);
    }
}
