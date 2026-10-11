<?php

namespace App\Models;

use App\Models\Base\Product as BaseProduct;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends BaseProduct
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public static function isManagedImagePath(?string $path): bool
    {
        return $path !== null && preg_match('~\Aproducts/[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)\z~i', $path) === 1;
    }

    public function imageUrl(): ?string
    {
        if (static::isManagedImagePath($this->image)) {
            return Storage::disk('public')->exists($this->image)
                ? Storage::disk('public')->url($this->image)
                : null;
        }

        // Preserve display of existing images stored under the public/images directory.
        if (is_string($this->image) && str_starts_with($this->image, 'images/') &&
            ! str_contains($this->image, '..') && is_file(public_path($this->image))) {
            return asset($this->image);
        }

        return null;
    }

    public static function uniqueSlugForName(string $name): string
    {
        $base = substr(Str::slug($name) ?: 'product', 0, 255);
        $candidate = $base;
        $suffix = 2;

        while (static::withTrashed()->where('slug', $candidate)->exists()) {
            $ending = '-'.$suffix++;
            $candidate = rtrim(substr($base, 0, 255 - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
