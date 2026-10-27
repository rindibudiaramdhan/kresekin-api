<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'mode', 'flat_fee', 'fee_per_km', 'max_distance_km'])]
class TenantShippingSetting extends Model
{
    protected function casts(): array
    {
        return [
            'flat_fee' => 'integer',
            'fee_per_km' => 'integer',
            'max_distance_km' => 'float',
        ];
    }

    public function settingsData(): array
    {
        return $this->only(['tenant_id', 'mode', 'flat_fee', 'fee_per_km', 'max_distance_km']);
    }
}
