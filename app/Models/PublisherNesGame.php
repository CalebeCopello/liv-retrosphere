<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PublisherNesGame extends Pivot
{
    use HasUuids;

    protected $table = 'publisher_nes_game';

    protected $fillable = [
        'id',
        'nes_game_id',
        'publisher_id',
        'regions',
    ];

    protected function casts(): array
    {
        return [
            'regions' => 'array',
        ];
    }

    public function nesGame(): BelongsTo
    {
        return $this->belongsTo(NesGame::class, 'nes_game_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }
}
