<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DeveloperMasterSystemGame extends Pivot
{
    use HasUuids;

    protected $table = 'developer_master_system_game';
}
