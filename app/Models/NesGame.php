<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(
            Platform::class,
            'platform_nes_game',
            'nes_game_id',
            'platform_id',
        )
            ->using(PlatformNesGame::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function publishers(): BelongsToMany
    {
        return $this->belongsToMany(
            Publisher::class,
            'publisher_nes_game',
            'nes_game_id',
            'publisher_id',
        )
            ->using(PublisherNesGame::class)
            ->withPivot('id', 'regions')
            ->withTimestamps();
    }

    public function developers(): BelongsToMany
    {
        return $this->belongsToMany(
            Developer::class,
            'developer_nes_game',
            'nes_game_id',
            'developer_id',
        )
            ->using(DeveloperNesGame::class)
            ->withPivot('id')
            ->withTimestamps();
    }
}
