<?php

namespace App\Services;

use App\Models\OnlineOrderRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlineRequestSubmissionService
{
    /** The database unique submission_key arbitrates concurrent workers. */
    public function submit(array $attributes, array $items): OnlineOrderRequest
    {
        $existing = OnlineOrderRequest::where('submission_key', $attributes['submission_key'])->first();
        if ($existing) {
            return $this->replay($existing, $attributes, $items);
        }
        try {
            return DB::transaction(function () use ($attributes, $items) {
                $record = OnlineOrderRequest::create($attributes);
                foreach ($items as $line) {
                    $item = $record->items()->create([
                        'variant_id' => $line['variant_id'], 'quantity' => $line['quantity'],
                        'note' => $line['note'], 'product_name' => $line['product_name'],
                        'variant_name' => $line['variant_name'], 'unit_price' => $line['unit_price'],
                    ]);
                    foreach ($line['toppings'] as $topping) {
                        $item->toppings()->create([
                            'topping_id' => $topping['id'], 'topping_name' => $topping['name'],
                            'price' => $topping['price'], 'quantity' => $topping['quantity'] * $line['quantity'],
                        ]);
                    }
                }

                return $record;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $record = OnlineOrderRequest::where('submission_key', $attributes['submission_key'])->first();
            if (! $record) {
                throw $exception;
            }

            return $this->replay($record, $attributes, $items);
        }
    }

    private function replay(OnlineOrderRequest $record, array $attributes, array $items): OnlineOrderRequest
    {
        foreach (['purchase_type', 'contact_name', 'contact_phone', 'pickup_at', 'note', 'payment_preference', 'payment_timing', 'tracking_token_hash'] as $field) {
            if ((string) $record->getRawOriginal($field) !== (string) ($attributes[$field] ?? null)) {
                throw ValidationException::withMessages(['cart' => 'Yêu cầu đã gửi có nội dung khác. Vui lòng bắt đầu checkout mới.']);
            }
        }
        $stored = $record->items()->with('toppings')->orderBy('id')->get();
        if ($stored->count() !== count($items)) {
            throw ValidationException::withMessages(['cart' => 'Nội dung yêu cầu đã gửi không khớp.']);
        }
        foreach ($stored as $index => $item) {
            $line = $items[$index];
            if ((int) $item->variant_id !== (int) $line['variant_id'] || (int) $item->quantity !== (int) $line['quantity']
                || (string) $item->note !== (string) $line['note'] || (string) $item->unit_price !== $line['unit_price']) {
                throw ValidationException::withMessages(['cart' => 'Nội dung món đã gửi không khớp.']);
            }
            $expected = collect($line['toppings'])->map(fn ($top) => [(int) $top['id'], $top['price'], $top['quantity'] * $line['quantity']])->sort()->values()->all();
            $actual = $item->toppings->map(fn ($top) => [(int) $top->topping_id, (string) $top->price, (int) $top->quantity])->sort()->values()->all();
            if ($expected !== $actual) {
                throw ValidationException::withMessages(['cart' => 'Topping đã gửi không khớp.']);
            }
        }

        return $record;
    }
}
