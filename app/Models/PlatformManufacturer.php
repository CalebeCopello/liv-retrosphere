<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlatformManufacturer extends Model
{
    use HasUuids;

    protected $table = 'platform_manufacturer';

    protected $fillable = [
        'platform_id',
        'manufacturer_id',
    ];
}
