<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'sort_order',
    'allows_free_text',
    'is_active',
    'is_system',
])]
class CancellationReasonCategory extends Model
{
    use HasUuids;

    public const OTHER_REASON_NAME = 'Alasan Lainnya';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'allows_free_text' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
