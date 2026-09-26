<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Manufacturer extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'slug',
    ];

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'platform_manufacturer', 'manufacturer_id', 'platform_id')
            ->using(PlatformManufacturer::class)
            ->withPivot('id')
            ->withTimestamps();
    }
}
