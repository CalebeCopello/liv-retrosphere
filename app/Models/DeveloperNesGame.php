<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DeveloperNesGame extends Pivot
{
    use HasUuids;

    protected $table = 'developer_nes_game';

    protected $fillable = [
        'id',
        'nes_game_id',
        'developer_id'
    ];

    public function nesGame(): BelongsTo
    {
        return $this->belongsTo(NesGame::class, 'nes_game_id');
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }
}
