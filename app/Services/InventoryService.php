<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Record a stock import and its immutable audit row atomically.
     */
    public function recordImport(Ingredient $ingredient, User $actor, string $quantity, ?string $note = null): InventoryTransaction
    {
        $quantityInMilliunits = $this->milliunits($quantity);

        return DB::transaction(function () use ($ingredient, $actor, $quantityInMilliunits, $note): InventoryTransaction {
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

            $beforeInMilliunits = $this->milliunits($currentIngredient->current_stock, false);
            $afterInMilliunits = $beforeInMilliunits + $quantityInMilliunits;
            if ($afterInMilliunits > self::MAX_MILLIUNITS) {
                throw new DomainException('Lượng tồn sau nhập vượt giới hạn schema.');
            }

            $currentIngredient->current_stock = $this->decimal($afterInMilliunits);
            $currentIngredient->save();

            $transaction = new InventoryTransaction;
            $transaction->ingredient_id = $currentIngredient->getKey();
            $transaction->type = 'import';
            $transaction->quantity = $this->decimal($quantityInMilliunits);
            $transaction->before_quantity = $this->decimal($beforeInMilliunits);
            $transaction->after_quantity = $this->decimal($afterInMilliunits);
            $transaction->reference_type = null;
            $transaction->reference_id = null;
            $transaction->note = $note;
            $transaction->created_by = $currentActor->getKey();
            $transaction->save();

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

    private function milliunits(string $amount, bool $mustBePositive = true): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $amount)) {
            throw new DomainException('Số lượng phải là số dương, có tối đa ba chữ số thập phân.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $milliunits = (int) $whole * 1000 + (int) str_pad($fraction, 3, '0');
        if (($mustBePositive && $milliunits <= 0) || $milliunits > self::MAX_MILLIUNITS) {
            throw new DomainException('Số lượng vượt giới hạn cho phép.');
        }

        return $milliunits;
    }

    private function decimal(int $milliunits): string
    {
        return intdiv($milliunits, 1000).'.'.str_pad((string) ($milliunits % 1000), 3, '0', STR_PAD_LEFT);
    }
}
