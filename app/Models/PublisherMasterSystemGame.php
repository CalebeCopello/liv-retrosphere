<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PublisherMasterSystemGame extends Pivot
{
    use HasUuids;

    protected $table = 'publisher_master_system_game';

    protected function casts(): array
    {
        return ['regions' => 'array'];
    }
}
