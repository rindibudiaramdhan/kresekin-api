<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'tenant_id',
    'name',
    'category',
    'image_url',
    'image_path',
    'price',
    'original_price',
    'stock',
    'unit',
    'minimum_stock',
    'is_active',
    'weight_label',
    'description',
    'delivery_estimate',
])]
class Product extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'original_price' => 'integer',
            'stock' => 'integer',
            'minimum_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function publicImageUrl(): ?string
    {
        if ($this->image_path) {
            return Storage::disk($this->imageDisk())->url($this->image_path);
        }

        return $this->image_url;
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock === null || $this->stock >= $quantity;
    }

    public function isLowStock(): bool
    {
        return $this->stock !== null && $this->stock <= $this->minimum_stock;
    }

    public static function imageDisk(): string
    {
        return config('filesystems.product_images_disk', config('filesystems.default'));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
