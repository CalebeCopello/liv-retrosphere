<?php

namespace App\Services\Auth;

use App\Enums\Auth\UserAuthEventType;
use App\Models\User;
use App\Models\UserAuthEvent;

class UserAuthEventService
{
    public function log(User $user, UserAuthEventType $eventType, string $ip, string $userAgent, bool $isSuccess = true): UserAuthEvent
    {
        return UserAuthEvent::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'is_success' => $isSuccess,
        ]);
    }
}
