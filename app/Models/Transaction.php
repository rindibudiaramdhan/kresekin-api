<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    'buyer_address',
    'buyer_landmark',
    'buyer_latitude',
    'buyer_longitude',
    'buyer_address_snapshot_at',
    'promo_code_id',
    'promo_code',
    'promo_name',
    'promo_discount_type',
    'promo_discount_value',
    'discount_amount',
    'cancellation_reason_category_id',
    'cancellation_reason_text',
])]
class Transaction extends Model
{
    use HasUuids;

    public const STATUS_CODE_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_CODE_ACCEPTED_BY_STORE = 'accepted_by_store';

    public const STATUS_CODE_PROCESSING = 'processing';

    public const STATUS_CODE_ON_THE_WAY = 'on_the_way';

    public const STATUS_CODE_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_CODE_COMPLETED = 'completed';

    public const STATUS_CODE_CANCELED = 'canceled';

    public const STATUS_PENDING_PAYMENT = 'menunggu pembayaran';

    public const STATUS_ACCEPTED_BY_STORE = 'diterima toko';

    public const STATUS_PROCESSING = 'sedang diproses';

    public const STATUS_ON_THE_WAY = 'dalam perjalanan';

    public const STATUS_READY_FOR_PICKUP = 'siap diambil';

    public const STATUS_COMPLETED = 'pesanan selesai';

    public const STATUS_CANCELED = 'pesanan dibatalkan';

    public const PAYMENT_METHOD_BANK_TRANSFER = 'Transfer Bank';

    public const PAYMENT_METHOD_QRIS = 'QRIS';

    public const PAYMENT_METHOD_VIRTUAL_ACCOUNT = 'Virtual Account';

    public static function statusMap(): array
    {
        return [
            self::STATUS_CODE_PENDING_PAYMENT => self::STATUS_PENDING_PAYMENT,
            self::STATUS_CODE_ACCEPTED_BY_STORE => self::STATUS_ACCEPTED_BY_STORE,
            self::STATUS_CODE_PROCESSING => self::STATUS_PROCESSING,
            self::STATUS_CODE_ON_THE_WAY => self::STATUS_ON_THE_WAY,
            self::STATUS_CODE_READY_FOR_PICKUP => self::STATUS_READY_FOR_PICKUP,
            self::STATUS_CODE_COMPLETED => self::STATUS_COMPLETED,
            self::STATUS_CODE_CANCELED => self::STATUS_CANCELED,
        ];
    }

    public static function statusCodes(): array
    {
        return array_keys(self::statusMap());
    }

    public static function statusFromCode(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return self::statusMap()[$code] ?? null;
    }

    public function statusCode(): ?string
    {
        $normalizedStatus = strtolower($this->status);

        foreach (self::statusMap() as $code => $status) {
            if ($normalizedStatus === strtolower($status)) {
                return $code;
            }
        }

        return null;
    }

    public function canBeCompletedByBuyer(): bool
    {
        return in_array($this->statusCode(), [
            self::STATUS_CODE_ON_THE_WAY,
            self::STATUS_CODE_READY_FOR_PICKUP,
        ], true);
    }

    public function canBeCanceledByBuyer(): bool
    {
        return in_array($this->statusCode(), [
            self::STATUS_CODE_PENDING_PAYMENT,
            self::STATUS_CODE_ACCEPTED_BY_STORE,
            self::STATUS_CODE_PROCESSING,
            self::STATUS_CODE_ON_THE_WAY,
            self::STATUS_CODE_READY_FOR_PICKUP,
        ], true);
    }

    protected function casts(): array
    {
        return [
            'transaction_at' => 'datetime',
            'subtotal_amount' => 'integer',
            'delivery_fee' => 'integer',
            'total_amount' => 'integer',
            'promo_discount_value' => 'integer',
            'discount_amount' => 'integer',
            'buyer_latitude' => 'float',
            'buyer_longitude' => 'float',
            'buyer_address_snapshot_at' => 'datetime',
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

    public function rating(): HasOne
    {
        return $this->hasOne(TransactionRating::class);
    }

    public function financeDisbursements(): HasMany
    {
        return $this->hasMany(FinanceTransactionDisbursement::class);
    }

    public function cancellationReasonCategory(): BelongsTo
    {
        return $this->belongsTo(CancellationReasonCategory::class);
    }
}
