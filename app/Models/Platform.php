<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
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
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'initial_release_year' => 'integer',
        ];
    }
}