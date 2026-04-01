<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'status', 'title', 'description', 'sequence', 'status_at'])]
class TransactionStatusHistory extends Model
{
    protected function casts(): array
    {
        return [
            'status_at' => 'datetime',
            'sequence' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
