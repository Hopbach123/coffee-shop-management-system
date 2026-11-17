<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Topping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerCartService
{
    public const SESSION_KEY = 'customer_order_cart';

    private const MAX_QUANTITY = 2147483647;

    // Limits follow DECIMAL(12,2) and DECIMAL(15,2), not a new product policy.
    private const MAX_PRICE_CENTS = 999999999999;

    private const MAX_TOTAL_CENTS = 999999999999999;

    public function add(array $draft, array $attributes): array
    {
        $toppings = [];
        foreach ($attributes['toppings'] ?? [] as $id => $quantity) {
            $id = $this->positiveInteger($id, 'toppings', PHP_INT_MAX);
            if ((string) $quantity !== '0') {
                $toppings[$id] = $this->positiveInteger($quantity, 'toppings');
            }
        }
        ksort($toppings, SORT_NUMERIC);

        $id = (string) Str::uuid();
        $note = isset($attributes['note']) ? trim($attributes['note']) : '';
        // Keep additions separate: different notes/topping configurations are never lost.
        $draft[$id] = [
            'variant_id' => $this->positiveInteger($attributes['variant_id'], 'variant_id', PHP_INT_MAX),
            'quantity' => $this->positiveInteger($attributes['quantity'], 'quantity'),
            'toppings' => $toppings,
            'note' => $note === '' ? null : $note,
        ];

        return $this->validatedChange($draft, $id);
    }

    public function updateQuantity(array $draft, string $id, mixed $quantity): array
    {
        $draft[$id]['quantity'] = $this->positiveInteger($quantity, 'quantity');

        return $this->validatedChange($draft, $id);
    }

    /** Current catalog quote only: no persisted Order, Payment or stock mutation. */
    public function quote(array $draft): array
    {
        $variantIds = [];
        $toppingIds = [];
        foreach ($draft as $line) {
            if (is_array($line)) {
                $variantIds[] = $line['variant_id'] ?? null;
                $toppingIds = array_merge($toppingIds, array_keys(is_array($line['toppings'] ?? null) ? $line['toppings'] : []));
            }
        }
        $variants = $variantIds === [] ? collect() : ProductVariant::withTrashed()
            ->with('product.category')->whereKey($variantIds)->get()->keyBy('id');
        $toppings = $toppingIds === [] ? collect() : Topping::withTrashed()
            ->whereKey($toppingIds)->get()->keyBy('id');

        $items = [];
        $total = 0;
        $totalError = false;
        foreach ($draft as $id => $line) {
            $variant = $variants->get(is_array($line) ? ($line['variant_id'] ?? null) : null);
            $product = $variant?->product;
            $item = [
                'id' => $id,
                'variant_id' => $variant?->id,
                'name' => $product?->name ?? 'Món không còn khả dụng',
                'variant_name' => $variant?->name,
                'slug' => $product?->slug,
                'quantity' => is_array($line) ? ($line['quantity'] ?? '') : '',
                'note' => is_array($line) && is_string($line['note'] ?? null) ? $line['note'] : null,
                'toppings' => [],
                'available' => false,
                'error' => null,
                'unit_price_label' => null,
                'unit_price' => null,
                'topping_amount' => null,
                'topping_amount_label' => null,
                'subtotal' => null,
                'subtotal_label' => null,
            ];

            try {
                if (! is_array($line) || ! is_array($line['toppings'] ?? null)) {
                    throw ValidationException::withMessages(['cart' => 'Dòng giỏ hàng không hợp lệ. Vui lòng xóa và chọn lại món.']);
                }
                $quantity = $this->positiveInteger($line['quantity'] ?? null, 'quantity');
                $category = $product?->category;
                if ($variant === null || $variant->trashed() || $variant->status !== 'available'
                    || $product === null || $product->trashed() || $product->status !== 'available'
                    || $category === null || $category->trashed() || $category->status !== 'active') {
                    throw ValidationException::withMessages(['variant_id' => 'Món hoặc kích cỡ không còn được bán. Vui lòng xóa hoặc chọn món khác.']);
                }
                $price = $this->priceToCents($variant->getRawOriginal('price'));
                $base = $this->multiply($price, $quantity, self::MAX_TOTAL_CENTS);
                $toppingAmount = 0;

                foreach ($line['toppings'] as $toppingId => $toppingQuantity) {
                    $topping = $toppings->get($toppingId);
                    if ($topping === null || $topping->trashed() || $topping->status !== 'available') {
                        throw ValidationException::withMessages(['toppings' => 'Topping đã chọn không còn được bán. Vui lòng xóa dòng và chọn lại món.']);
                    }
                    $toppingQuantity = $this->positiveInteger($toppingQuantity, 'toppings');
                    $toppingPrice = $this->priceToCents($topping->getRawOriginal('price'));
                    $amount = $this->multiply($toppingPrice, $toppingQuantity, self::MAX_PRICE_CENTS);
                    $toppingAmount = $this->sum($toppingAmount, $amount, self::MAX_PRICE_CENTS);
                    $item['toppings'][] = ['id' => $topping->id, 'name' => $topping->name, 'quantity' => $toppingQuantity,
                        'price' => $this->decimal($toppingPrice),
                        'price_label' => $this->formatCents($toppingPrice), 'amount_label' => $this->formatCents($amount)];
                }

                $subtotal = $this->sum($base, $toppingAmount, self::MAX_TOTAL_CENTS);
                $item = array_replace($item, [
                    'available' => true,
                    'unit_price_label' => $this->formatCents($price),
                    'unit_price' => $this->decimal($price),
                    'topping_amount' => $this->decimal($toppingAmount),
                    'topping_amount_label' => $this->formatCents($toppingAmount),
                    'subtotal' => $this->decimal($subtotal),
                    'subtotal_label' => $this->formatCents($subtotal),
                ]);
                if ($subtotal > self::MAX_TOTAL_CENTS - $total) {
                    $totalError = true;
                } else {
                    $total += $subtotal;
                }
            } catch (ValidationException $exception) {
                $item['error'] = collect($exception->errors())->flatten()->first();
            }
            $items[$id] = $item;
        }

        $canQuote = ! $totalError && collect($items)->every(fn (array $item): bool => $item['available']);

        return ['items' => $items, 'total_error' => $totalError,
            'subtotal' => $canQuote ? $this->decimal($total) : null,
            'subtotal_label' => $canQuote ? $this->formatCents($total) : null];
    }

    public function formatPrice(mixed $price): string
    {
        return $this->formatCents($this->priceToCents($price));
    }

    /** Final catalog recheck for submission/checkout; the caller owns the transaction. */
    public function lockAndQuote(array $draft): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \DomainException('Cần transaction để chốt báo giá.');
        }
        $variants = ProductVariant::withTrashed()->whereKey(array_column($draft, 'variant_id'))
            ->orderBy('id')->lockForUpdate()->get();
        $products = Product::withTrashed()->whereKey($variants->pluck('product_id'))
            ->orderBy('id')->lockForUpdate()->get();
        Category::withTrashed()->whereKey($products->pluck('category_id'))->orderBy('id')->lockForUpdate()->get();
        $toppingIds = [];
        foreach ($draft as $line) {
            $toppingIds = array_merge($toppingIds, array_keys($line['toppings']));
        }
        Topping::withTrashed()->whereKey($toppingIds)->orderBy('id')->lockForUpdate()->get();

        return $this->quote($draft);
    }

    private function validatedChange(array $draft, string $id): array
    {
        $quote = $this->quote($draft);
        if (! $quote['items'][$id]['available']) {
            throw ValidationException::withMessages(['cart' => $quote['items'][$id]['error']]);
        }
        if ($quote['total_error']) {
            throw ValidationException::withMessages(['quantity' => 'Tổng giỏ hàng vượt giới hạn số tiền cho phép.']);
        }

        return $draft;
    }

    private function positiveInteger(mixed $value, string $field, int $maximum = self::MAX_QUANTITY): int
    {
        if ((! is_int($value) && ! is_string($value))
            || ! preg_match('/^[1-9][0-9]*$/', (string) $value)
            || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value > $maximum) {
            throw ValidationException::withMessages([$field => 'Số lượng hoặc mã lựa chọn phải là số nguyên dương trong giới hạn dữ liệu.']);
        }

        return (int) $value;
    }

    private function priceToCents(mixed $price): int
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', (string) $price, $matches)) {
            throw ValidationException::withMessages(['cart' => 'Giá bán không hợp lệ. Vui lòng chọn lại món.']);
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    private function multiply(int $price, int $quantity, int $maximum): int
    {
        if ($price > intdiv($maximum, $quantity)) {
            throw ValidationException::withMessages(['quantity' => 'Số lượng làm thành tiền vượt giới hạn dữ liệu.']);
        }

        return $price * $quantity;
    }

    private function sum(int $left, int $right, int $maximum): int
    {
        if ($right > $maximum - $left) {
            throw ValidationException::withMessages(['quantity' => 'Thành tiền vượt giới hạn dữ liệu.']);
        }

        return $left + $right;
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function formatCents(int $cents): string
    {
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) intdiv($cents, 100));

        return $whole.','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT).' ₫';
    }
}
