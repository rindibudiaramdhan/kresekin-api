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
    'requires_schedule',
    'sort_order',
    'is_active',
])]
class OrderTimeOption extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'requires_schedule' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtolower(trim($value)),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
