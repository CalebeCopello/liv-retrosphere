<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Developer extends Model
{
    use HasUuids;

    protected $table = 'developers';

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
            'developer_nes_game',
            'developer_id',
            'nes_game_id',
        )
            ->using(DeveloperNesGame::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function masterSystemGames(): BelongsToMany
    {
        return $this->belongsToMany(MasterSystemGame::class, 'developer_master_system_game', 'developer_id', 'master_system_game_id')
            ->using(DeveloperMasterSystemGame::class)->withPivot('id')->withTimestamps();
    }
}
