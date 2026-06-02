<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'city',
    'district',
    'subdistrict',
    'village_code',
])]
class HousingArea extends Model
{
    use HasUuids;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->using(HousingAreaTenant::class)
            ->withTimestamps();
    }
}
