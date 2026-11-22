<?php

namespace App\Models;

use App\Models\Base\Promotion as BasePromotion;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Promotion extends BasePromotion
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'image',
        'discount_type',
        'discount_value',
        'max_discount',
        'minimum_order',
        'start_at',
        'end_at',
        'usage_limit',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function orderPromotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class, 'promotion_id');
    }

    public static function isManagedImagePath(?string $path): bool
    {
        return is_string($path) && preg_match('#^promotions/[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)$#i', $path) === 1;
    }

    public function imageUrl(): ?string
    {
        if (static::isManagedImagePath($this->image)) {
            return Storage::disk('public')->exists($this->image) ? Storage::disk('public')->url($this->image) : null;
        }

        if (is_string($this->image) && str_starts_with($this->image, 'images/promotions/') &&
            ! str_contains($this->image, '..') && is_file(public_path($this->image))) {
            return asset($this->image);
        }

        return null;
    }
}
