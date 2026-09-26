<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformManufacturer extends Pivot
{
    use HasUuids;

    protected $table = 'platform_manufacturer';

    protected $fillable = [
        'platform_id',
        'manufacturer_id',
    ];
}
