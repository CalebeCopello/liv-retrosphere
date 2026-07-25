<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class EnsureJwtSessionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = JWTAuth::getToken();
        $tokenPayload = JWTAuth::setToken($token)->getPayload();
        $jti = $tokenPayload->get('jti');

        $session = UserSession::where('token_jti', $jti)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'The authenticated session is no longer active.',
                'data' => null,
                'errors' => [
                    'session' => [
                        'The authenticated session is no longer active.',
                    ],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
