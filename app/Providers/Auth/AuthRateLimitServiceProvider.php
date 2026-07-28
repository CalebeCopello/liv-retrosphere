<?php

namespace App\Providers\Auth;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthRateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerLoginLimiter();

    }

    private function registerLoginLimiter(): void
    {
        RateLimiter::for('auth.login',function (Request $request): array {
            $ipAddress = $request->ip();
            $emailHash = $this->emailHash($request);

            return [
                $this->configuredLimit(configKey: 'auth.login.ip', limiterKey: "auth:login:ip:{$ipAddress}"),
                $this->configuredLimit(configKey: 'auth.login.failed_credentials',limiterKey: "auth:login:failed:{$ipAddress}:{$emailHash}")->after( static fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED),
            ];
        });
    }

    private function configuredLimit(string $configKey, string $limiterKey): Limit {
        $attempts = (int) config("rate_limits.{$configKey}.max_attempts");
        $decaySeconds = (int) config("rate_limits.{$configKey}.decay_seconds");

        return Limit::perSecond(
            maxAttempts: $attempts,
            decaySeconds: $decaySeconds,
        )
            ->by($limiterKey)
            ->response($this->tooManyRequestsResponse());
    }

    private function actorKey(Request $request): string
    {
        $user = $request->user('api');
        
        if($user) return "user:{$user->getAuthIdentifier()}";

        return "ip:{$request->ip()}";
    }

    private function emailHash(Request $request): string
    {
        $email = Str::lower(trim((string) $request->input('email')));

        if ($email === '') return "missing-email";

        return hash('sha256', $email);
    }

    private function tooManyRequestsResponse(): Closure
    {
        return static function (Request $request, array $headers): JsonResponse
        {
            return response()->json([
                'message' => 'Too many requests.',
                'retry_after' => (int) ($headers['Retry-After'] ?? 60),
            ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        };
    }
}
