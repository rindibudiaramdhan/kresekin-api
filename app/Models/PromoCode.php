<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'description',
    'discount_type',
    'discount_value',
    'minimum_order_amount',
    'maximum_discount_amount',
    'quantity',
    'used_quantity',
    'starts_at',
    'ends_at',
    'is_active',
])]
class PromoCode extends Model
{
    use HasUuids;

    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    public const DISCOUNT_TYPE_FIXED_AMOUNT = 'fixed_amount';

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'minimum_order_amount' => 'integer',
            'maximum_discount_amount' => 'integer',
            'quantity' => 'integer',
            'used_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtoupper(trim($value)),
        );
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('quantity')
                    ->orWhereColumn('used_quantity', '<', 'quantity');
            });
    }

    public function remainingQuantity(): ?int
    {
        if ($this->quantity === null) {
            return null;
        }

        return max($this->quantity - $this->used_quantity, 0);
    }
}
