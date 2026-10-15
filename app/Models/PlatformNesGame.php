<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformNesGame extends Pivot
{
    use HasUuids;

    protected $table = 'platform_nes_game';

    protected $fillable = [
        'id',
        'nes_game_id',
        'platform_id'
    ];

    public function nesGame(): BelongsTo
    {
        return $this->belongsTo(NesGame::class, 'nes_game_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }
}
