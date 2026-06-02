<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class HousingAreaTenant extends Pivot
{
    use HasUuids;

    protected $table = 'housing_area_tenant';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];
}
