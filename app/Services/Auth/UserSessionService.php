<?php

namespace App\Services\Auth;

use App\Enums\Auth\SessionRevocationReason;
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

        $agent->setUserAgent($userAgent);

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

    public function revokeCurrentSession(string $token, SessionRevocationReason $reason): UserSession
    {
        $payload = JWTAuth::setToken($token)->getPayload();

        $jti = $payload->get('jti');

        $session = UserSession::where('token_jti', $jti)->whereNull('revoked_at')->firstOrFail();
        $session->update(['revoked_at' => now(), 'revoked_reason' => $reason->value]);

        return $session;
    }

    public function rotateSessionToken(string $oldToken, string $newToken): UserSession
    {
        $oldPayload = JWTAuth::setToken($oldToken)->getPayload();
        $newPayload = JWTAuth::setToken($newToken)->getPayload();

        $session = UserSession::where('token_jti', $oldPayload->get('jti'))->whereNull('revoked_at')->firstOrFail();
        $session->update([
            'token_jti' => $newPayload->get('jti'),
            'expires_at' => Carbon::createFromTimestamp($newPayload->get('exp')),
            'last_seen_at' => now()
        ]);

        return $session;
    }

    public function revokeAllSessions(User $user, SessionRevocationReason $reason): int
    {
        $sessions = $user->sessions()
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason->value,
            ]);

        return $sessions->count();
    }
}
