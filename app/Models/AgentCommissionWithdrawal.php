<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agent_user_id',
    'amount',
    'status',
    'note',
    'rejection_reason',
    'requested_at',
    'processed_at',
    'approved_by_user_id',
    'rejected_by_user_id',
    'paid_by_user_id',
    'approved_at',
    'rejected_at',
    'paid_at',
])]
class AgentCommissionWithdrawal extends Model
{
    use HasUuids;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const REJECTION_INVALID_ACCOUNT = 'invalid_account';

    public const REJECTION_INCOMPLETE_ACCOUNT = 'incomplete_account';

    public const REJECTION_SUSPICIOUS_REQUEST = 'suspicious_request';

    public static function lockedStatuses(): array
    {
        return [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_PAID,
        ];
    }

    public static function rejectionReasons(): array
    {
        return [
            self::REJECTION_INVALID_ACCOUNT,
            self::REJECTION_INCOMPLETE_ACCOUNT,
            self::REJECTION_SUSPICIOUS_REQUEST,
        ];
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }
}
