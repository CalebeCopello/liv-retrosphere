<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserSessionService
{
    private function detectDevice(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (! $userAgent) {
            return 'Unknown device';
        }

        $agent = new Agent();

        $agent->setUserAgent($request->userAgent());

        $browser = $agent->browser() ?: 'Unknown browser';
        $platform = $agent->platform() ?: 'Unknown platform';

        return "{$browser} on {$platform}";
    }

    public function createSession(User $user, string $token, Request $request): UserSession
    {
        $payload = JWTAuth::setToken($token)->getPayload();

        $ip = $request->ip() ?? 'unknown';
        $userAgent = $request->userAgent() ?? 'unknown';

        return UserSession::create([
            'user_id' => $user->id,
            'token_jti' => $payload->get('jti'),
            'device_name'  => $this->detectDevice($request),
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
            'last_seen_at' => now(),
            'expires_at'   => Carbon::createFromTimestamp($payload->get('exp')),
        ]);
    }
}
