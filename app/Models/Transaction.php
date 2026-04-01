<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'order_number',
    'status',
    'transaction_at',
    'subtotal_amount',
    'delivery_fee',
    'total_amount',
    'delivery_method',
    'delivery_method_code',
    'pickup_time_option',
    'pickup_scheduled_at',
    'payment_method',
    'payment_method_code',
    'payment_method_option_code',
    'payment_method_option_name',
])]
class Transaction extends Model
{
    public const STATUS_PENDING_PAYMENT = 'menunggu pembayaran';
    public const STATUS_ACCEPTED_BY_STORE = 'diterima toko';
    public const STATUS_PROCESSING = 'sedang diproses';
    public const STATUS_ON_THE_WAY = 'dalam perjalanan';
    public const STATUS_COMPLETED = 'pesanan selesai';
    public const STATUS_CANCELED = 'pesanan dibatalkan';

    public const PAYMENT_METHOD_BANK_TRANSFER = 'Transfer Bank';
    public const PAYMENT_METHOD_QRIS = 'QRIS';
    public const PAYMENT_METHOD_VIRTUAL_ACCOUNT = 'Virtual Account';

    protected function casts(): array
    {
        return [
            'transaction_at' => 'datetime',
            'subtotal_amount' => 'integer',
            'delivery_fee' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class)->orderBy('sequence')->orderBy('status_at');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
