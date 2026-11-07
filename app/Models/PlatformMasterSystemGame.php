<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlatformMasterSystemGame extends Pivot
{
    use HasUuids;

    protected $table = 'platform_master_system_game';
}
