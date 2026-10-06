<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

class NesGame extends Model
{
    use HasUuids;

    protected $table = 'nes_games';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'image',
        'alternate_names',
        'release_dates',
        'description',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'alternate_names' => 'array',
            'release_dates' => 'array',
        ];
    }
}
