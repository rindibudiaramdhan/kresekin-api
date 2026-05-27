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
    'requested_at',
    'processed_at',
])]
class AgentCommissionWithdrawal extends Model
{
    use HasUuids;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public static function lockedStatuses(): array
    {
        return [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_PAID,
        ];
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }
}
