<?php

namespace App\Models;

use App\Models\Base\Order as BaseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Order extends BaseOrder
{
    protected $fillable = [
        'table_id',
        'customer_id',
        'reservation_id',
        'order_type',
        'purchase_type',
        'online_request_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function cafeTable(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'table_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function orderPromotion(): HasOne
    {
        return $this->hasOne(OrderPromotion::class, 'order_id');
    }

    public function onlineOrderRequest(): BelongsTo
    {
        return $this->belongsTo(OnlineOrderRequest::class, 'online_request_id');
    }

    public function isCounter(): bool
    {
        return ($this->purchase_type ?? 'counter') === 'counter';
    }

    public function isScheduled(): bool
    {
        return ($this->purchase_type ?? 'counter') === 'scheduled';
    }

    public function isOnline(): bool
    {
        return ($this->purchase_type ?? 'counter') === 'online';
    }

    public function isPaidInFull(): bool
    {
        if (! Schema::hasTable('payments')) {
            return false;
        }
        if (! $this->relationLoaded('payments')) {
            $this->load('payments');
        }

        $remaining = $this->moneyCents((string) $this->total_amount);
        if ($remaining <= 0) {
            return false;
        }
        foreach ($this->payments->where('status', 'paid') as $payment) {
            $amount = $this->moneyCents((string) $payment->amount);
            if ($amount >= $remaining) {
                return true;
            }
            $remaining -= $amount;
        }

        return false;
    }

    private function moneyCents(string $value): int
    {
        if (! preg_match('/^(0|[1-9]\d{0,14})(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new \DomainException('Số tiền settlement không hợp lệ.');
        }

        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public function allItemsServed(): bool
    {
        if (! $this->relationLoaded('orderItems')) {
            $this->load('orderItems');
        }

        return $this->orderItems->isNotEmpty()
            && $this->orderItems->every(fn (OrderItem $item) => $item->status === 'served');
    }
}
