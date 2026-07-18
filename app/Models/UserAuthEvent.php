<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

class UserAuthEvent extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'is_success'
    ];

    protected function casts(): array
    {
        return [
            'event_type' => 'integer',
            'is_success' => 'boolean'
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
