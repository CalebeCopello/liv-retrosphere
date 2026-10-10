<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Publisher extends Model
{
        use HasUuids;

    protected $table = 'publishers';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'aliases',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
        ];
    }

    public function nesGames(): BelongsToMany
    {
        return $this->belongsToMany(
            NesGame::class,
            'mes_game_publisher',
            'publisher_id',
            'nes_game_id',
        )
            ->using(PublisherNesGame::class)
            ->withPivot('id', 'regions')
            ->withTimestamps();
    }
}
