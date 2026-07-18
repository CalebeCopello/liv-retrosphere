<?php

namespace App\Services\Auth;

use App\Enums\Auth\UserAuthEventType;
use App\Models\User;
use App\Models\UserAuthEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserAuthEventService
{
    public function log(User $user, UserAuthEventType $eventType, ?string $ip, ?string $userAgent, bool $isSuccess = true): ?UserAuthEvent
    {
        $ip = $ip ?? 'unknown';
        $userAgent = $userAgent ?? 'unknown';
        try {
            return UserAuthEvent::create([
                'user_id' => $user->id,
                'event_type' => $eventType->value,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'is_success' => $isSuccess,
            ]);
        } catch (Throwable $e) {
            report($e);

            Log::warning('Failed to create authentication log event.', [
                'user_id' => $user->id,
                'event_type' => $eventType->value,
            ]);

            return null;
        }
    }
}
