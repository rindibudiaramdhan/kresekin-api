<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'icon_key',
    'requires_option',
    'sort_order',
    'is_active',
])]
class PaymentMethod extends Model
{
    public const BANK_TRANSFER = 'bank_transfer';

    public const QR_PAYMENT = 'qr_payment';

    public const COD = 'cod';

    protected function casts(): array
    {
        return [
            'requires_option' => 'boolean',
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

    public function options(): HasMany
    {
        return $this->hasMany(PaymentMethodOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }
}
