<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MasterSystemGame extends Model
{
    use HasUuids;

    protected $table = 'master_system_games';

    protected $fillable = ['id', 'name', 'slug', 'image', 'alternate_names', 'release_dates', 'description', 'source_url'];

    protected function casts(): array
    {
        return ['alternate_names' => 'array', 'release_dates' => 'array'];
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'platform_master_system_game', 'master_system_game_id', 'platform_id')
            ->using(PlatformMasterSystemGame::class)->withPivot('id')->withTimestamps();
    }

    public function developers(): BelongsToMany
    {
        return $this->belongsToMany(Developer::class, 'developer_master_system_game', 'master_system_game_id', 'developer_id')
            ->using(DeveloperMasterSystemGame::class)->withPivot('id')->withTimestamps();
    }

    public function publishers(): BelongsToMany
    {
        return $this->belongsToMany(Publisher::class, 'publisher_master_system_game', 'master_system_game_id', 'publisher_id')
            ->using(PublisherMasterSystemGame::class)->withPivot('id', 'regions')->withTimestamps();
    }
}
