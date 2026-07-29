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
        $this->registerRegistrationLimiter();
        $this->registerRefreshLimiter();
        $this->registerLogoutLimiter();
        $this->registerLogoutAllLimiter();
        $this->registerAuthenticatedApiLimiter();
    }

    private function registerLoginLimiter(): void
    {
        RateLimiter::for('auth.login', function (Request $request): array {
            $ipAddress = $request->ip();
            $emailHash = $this->emailHash($request);

            return [
                $this->configuredLimit(configKey: 'auth.login.ip', limiterKey: "auth:login:ip:{$ipAddress}"),
                $this->configuredLimit(configKey: 'auth.login.failed_credentials', limiterKey: "auth:login:failed:{$ipAddress}:{$emailHash}")->after(static fn(Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED),
            ];
        });
    }

    private function registerRegistrationLimiter(): void
    {
        RateLimiter::for('auth.register', function (Request $request): array {
            $ipAddress = $request->ip();

            return [
                $this->configuredLimit(configKey: 'auth.register.short_window', limiterKey: "auth:register:short-window:{$ipAddress}"),
                $this->configuredLimit(configKey: 'auth.register.daily', limiterKey: "auth:register:daily:{$ipAddress}"),
            ];
        });
    }

    private function registerAuthenticatedLimiter(string $limiterName, string $configKey): void
    {
        RateLimiter::for($limiterName, function (Request $request) use ($limiterName, $configKey): Limit {
            return $this->configuredLimit(configKey: $configKey, limiterKey: "{$limiterName}:{$this->actorKey($request)}");
        });
    }

    private function registerRefreshLimiter(): void
    {
        $this->registerAuthenticatedLimiter(
            limiterName: 'auth.refresh',
            configKey: 'auth.refresh',
        );
    }

    private function registerLogoutLimiter(): void
    {
        $this->registerAuthenticatedLimiter(
            limiterName: 'auth.logout',
            configKey: 'auth.logout',
        );
    }

    private function registerLogoutAllLimiter(): void
    {
        $this->registerAuthenticatedLimiter(
            limiterName: 'auth.logout-all',
            configKey: 'auth.logout_all',
        );
    }
    private function registerAuthenticatedApiLimiter(): void
    {
        $this->registerAuthenticatedLimiter(
            limiterName: 'api.authenticated',
            configKey: 'api.authenticated',
        );
    }

    private function configuredLimit(string $configKey, string $limiterKey,): Limit
    {
        $maxAttempts = (int) config("rate_limits.{$configKey}.max_attempts");

        $decaySeconds = (int) config("rate_limits.{$configKey}.decay_seconds");

        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException("Rate limit [{$configKey}] must have max_attempts greater than zero.");
        }

        if ($decaySeconds < 1) {
            throw new \InvalidArgumentException("Rate limit [{$configKey}] must have decay_seconds greater than zero.");
        }

        return Limit::perSecond(maxAttempts: $maxAttempts, decaySeconds: $decaySeconds)
            ->by($limiterKey)
            ->response($this->tooManyRequestsResponse());
    }

    private function actorKey(Request $request): string
    {
        $user = $request->user('api');

        if ($user) {
            return "user:{$user->getAuthIdentifier()}";
        }

        return "ip:{$request->ip()}";
    }

    private function emailHash(Request $request): string
    {
        $email = Str::lower(trim((string) $request->input('email')));

        if ($email === '') {
            return "missing-email";
        }

        return hash('sha256', $email);
    }

    private function tooManyRequestsResponse(): Closure
    {
        return static function (Request $_request, array $headers): JsonResponse {
            return response()->json([
                'message' => 'Too many requests.',
                'retry_after' => (int) ($headers['Retry-After'] ?? 60),
            ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        };
    }
}
