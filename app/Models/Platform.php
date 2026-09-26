<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'type',
        'generation',
        'initial_release_year',
        'description',
        'image',
        'source_url',
        'is_shown'
    ];

    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'initial_release_year' => 'integer',
            'is_shown' => 'boolean',
        ];
    }

    public function manufacturers(): BelongsToMany
    {
        return $this->belongsToMany(Manufacturer::class, 'platform_manufacturer', 'platform_id', 'manufacturer_id')
            ->using(PlatformManufacturer::class)
            ->withPivot('id')
            ->withTimestamps();
    }
}