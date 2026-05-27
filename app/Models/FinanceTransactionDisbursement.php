<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'tenant_id',
    'seller_user_id',
    'unique_code',
    'amount',
    'status',
    'buyer_payment_confirmed_at',
    'disbursed_at',
    'confirmed_by_user_id',
    'disbursed_by_user_id',
])]
class FinanceTransactionDisbursement extends Model
{
    use HasUuids;

    public const STATUS_PENDING_BUYER_PAYMENT = 'pending_buyer_payment';

    public const STATUS_BUYER_PAYMENT_CONFIRMED = 'buyer_payment_confirmed';

    public const STATUS_DISBURSED_TO_SELLER = 'disbursed_to_seller';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'buyer_payment_confirmed_at' => 'datetime',
            'disbursed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }
}
