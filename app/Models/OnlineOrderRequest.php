<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OnlineOrderRequest extends Model
{
    protected $table = 'online_order_requests';

    protected $fillable = [
        'request_code',
        'purchase_type',
        'customer_id',
        'contact_name',
        'contact_phone',
        'pickup_at',
        'note',
        'status',
        'payment_preference',
        'payment_timing',
        'accepted_by',
        'accepted_at',
        'rejection_reason',
        'submission_key',
        'tracking_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'pickup_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineOrderRequestItem::class, 'request_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'online_request_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function totalAmount(): string
    {
        $totalCents = 0;
        foreach ($this->items as $item) {
            $unitCents = (int) round((float) $item->unit_price * 100);
            $itemTotalCents = $unitCents * (int) $item->quantity;
            foreach ($item->toppings as $topping) {
                $toppingPriceCents = (int) round((float) $topping->price * 100);
                $itemTotalCents += $toppingPriceCents * (int) $topping->quantity;
            }
            $totalCents += $itemTotalCents;
        }

        return number_format($totalCents / 100, 2, '.', '');
    }
}
