<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWT;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_too_many_requests_after_failed_credentials_limit(): void
    {
        $ipAddress = $this->uniqueIpAddress();

        $this->setRateLimit(
            configKey: 'auth.login.ip',
            maxAttempts: 100,
        );

        $this->setRateLimit(
            configKey: 'auth.login.failed_credentials',
            maxAttempts: 2,
        );

        $payload = [
            'email' => 'missing-' . Str::uuid() . '@example.com',
            'password' => 'IncorrectPassword123!',
        ];

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $payload,
            ipAddress: $ipAddress,
        )->assertUnauthorized();

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $payload,
            ipAddress: $ipAddress,
        )->assertUnauthorized();

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $payload,
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonStructure([
                'message',
                'retry_after',
            ])
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_successful_login_does_not_increment_failed_credentials_limit(): void
    {
        $ipAddress = $this->uniqueIpAddress();
        $password = 'CorrectPassword123!';

        $user = User::factory()->create([
            'email' => 'login-' . Str::uuid() . '@example.com',
            'password' => $password,
        ]);

        $this->setRateLimit(
            configKey: 'auth.login.ip',
            maxAttempts: 100,
        );

        $this->setRateLimit(
            configKey: 'auth.login.failed_credentials',
            maxAttempts: 2,
        );

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: [
                'email' => $user->email,
                'password' => $password,
            ],
            ipAddress: $ipAddress,
        )->assertOk();

        $failedPayload = [
            'email' => $user->email,
            'password' => 'IncorrectPassword123!',
        ];

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $failedPayload,
            ipAddress: $ipAddress,
        )->assertUnauthorized();

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $failedPayload,
            ipAddress: $ipAddress,
        )->assertUnauthorized();

        $this->postJsonAsIp(
            uri: '/api/auth/login',
            payload: $failedPayload,
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_registration_hits_short_window_limit(): void
    {
        $ipAddress = $this->uniqueIpAddress();

        $this->setRateLimit(
            configKey: 'auth.register.short_window',
            maxAttempts: 2,
            decaySeconds: 600,
        );

        $this->setRateLimit(
            configKey: 'auth.register.daily',
            maxAttempts: 100,
            decaySeconds: 86400,
        );

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )->assertCreated();

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )->assertCreated();

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_registration_hits_daily_limit(): void
    {
        $ipAddress = $this->uniqueIpAddress();

        $this->setRateLimit(
            configKey: 'auth.register.short_window',
            maxAttempts: 100,
            decaySeconds: 600,
        );

        $this->setRateLimit(
            configKey: 'auth.register.daily',
            maxAttempts: 2,
            decaySeconds: 86400,
        );

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )->assertCreated();

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )->assertCreated();

        $this->postJsonAsIp(
            uri: '/api/auth/register',
            payload: $this->registrationPayload(),
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_authenticated_route_limits_are_keyed_per_user(): void
    {
        $this->setRateLimit(
            configKey: 'api.authenticated',
            maxAttempts: 2,
        );

        $sharedIpAddress = '198.51.100.10';

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstUserToken = $this->createSessionToken(
            user: $firstUser,
            ipAddress: $sharedIpAddress,
        );

        $secondUserToken = $this->createSessionToken(
            user: $secondUser,
            ipAddress: $sharedIpAddress,
        );

        $this->getMe(
            token: $firstUserToken,
            ipAddress: $sharedIpAddress,
        )->assertOk();

        $this->getMe(
            token: $firstUserToken,
            ipAddress: $sharedIpAddress,
        )->assertOk();

        $this->getMe(
            token: $firstUserToken,
            ipAddress: $sharedIpAddress,
        )
            ->assertTooManyRequests()
            ->assertJson([
                'message' => 'Too many requests.',
            ]);

        $this->getMe(
            token: $secondUserToken,
            ipAddress: $sharedIpAddress,
        )->assertOk();
    }

    public function test_refresh_returns_too_many_requests_after_limit(): void
    {
        $this->setRateLimit(
            configKey: 'auth.refresh',
            maxAttempts: 1,
        );

        $ipAddress = $this->uniqueIpAddress();
        $user = User::factory()->create();

        $token = $this->createSessionToken(
            user: $user,
            ipAddress: $ipAddress,
        );

        $response = $this->postAuthenticatedJson(
            uri: '/api/auth/refresh',
            token: $token,
            ipAddress: $ipAddress,
        )
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'access_token',
                ],
            ]);

        $refreshedToken = $this->tokenFromResponse($response);

        $this->postAuthenticatedJson(
            uri: '/api/auth/refresh',
            token: $refreshedToken,
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_logout_returns_too_many_requests_after_limit(): void
    {
        $this->setRateLimit(
            configKey: 'auth.logout',
            maxAttempts: 1,
        );

        $ipAddress = $this->uniqueIpAddress();
        $user = User::factory()->create();

        $firstToken = $this->createSessionToken(
            user: $user,
            ipAddress: $ipAddress,
        );

        $secondToken = $this->createSessionToken(
            user: $user,
            ipAddress: $ipAddress,
        );

        $this->postAuthenticatedJson(
            uri: '/api/auth/logout',
            token: $firstToken,
            ipAddress: $ipAddress,
        )->assertOk();

        $this->postAuthenticatedJson(
            uri: '/api/auth/logout',
            token: $secondToken,
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    public function test_logout_all_returns_too_many_requests_after_limit(): void
    {
        $this->setRateLimit(
            configKey: 'auth.logout_all',
            maxAttempts: 1,
        );

        $ipAddress = $this->uniqueIpAddress();
        $user = User::factory()->create();

        $firstToken = $this->createSessionToken(
            user: $user,
            ipAddress: $ipAddress,
        );

        $this->postAuthenticatedJson(
            uri: '/api/auth/logout-all',
            token: $firstToken,
            ipAddress: $ipAddress,
        )->assertOk();

        /*
     * The first request revoked all previous sessions, so create a new
     * valid session for the same user before testing the limiter.
     */
        $secondToken = $this->createSessionToken(
            user: $user,
            ipAddress: $ipAddress,
        );

        $this->postAuthenticatedJson(
            uri: '/api/auth/logout-all',
            token: $secondToken,
            ipAddress: $ipAddress,
        )
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJson([
                'message' => 'Too many requests.',
            ]);
    }

    private function setRateLimit(
        string $configKey,
        int $maxAttempts,
        int $decaySeconds = 60,
    ): void {
        config()->set(
            "rate_limits.{$configKey}.max_attempts",
            $maxAttempts,
        );

        config()->set(
            "rate_limits.{$configKey}.decay_seconds",
            $decaySeconds,
        );
    }

    private function postJsonAsIp(
        string $uri,
        array $payload,
        string $ipAddress,
    ): TestResponse {
        return $this
            ->withServerVariables([
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => 'PHPUnit',
            ])
            ->postJson($uri, $payload);
    }

    private function getMe(
        string $token,
        string $ipAddress,
    ): TestResponse {
        Auth::forgetGuards();
        JWTAuth::unsetToken();
        app(JWT::class)->unsetToken();

        return $this
            ->withServerVariables([
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => 'PHPUnit',
            ])
            ->withToken($token)
            ->getJson('/api/me');
    }

    private function registrationPayload(): array
    {
        $suffix = Str::lower(Str::random(12));

        return [
            'username' => 'user' . $suffix,
            'email' => $suffix . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
    }

    private function uniqueIpAddress(): string
    {
        $hexadecimal = substr(
            str_replace('-', '', (string) Str::uuid()),
            0,
            24,
        );

        return '2001:db8:' . implode(
            ':',
            str_split($hexadecimal, 4),
        );
    }
    private function createSessionToken(
        User $user,
        string $ipAddress,
    ): string {
        $token = JWTAuth::fromUser($user);

        try {
            $payload = JWTAuth::setToken($token)->getPayload();

            UserSession::create([
                'user_id' => $user->id,
                'token_jti' => (string) $payload->get('jti'),
                'device_name' => 'PHPUnit',
                'ip_address' => $ipAddress,
                'user_agent' => 'PHPUnit',
                'expires_at' => Carbon::createFromTimestamp(
                    (int) $payload->get('exp'),
                ),
            ]);
        } finally {
            JWTAuth::unsetToken();
            app(JWT::class)->unsetToken();
        }

        return $token;
    }

    private function postAuthenticatedJson(
        string $uri,
        string $token,
        string $ipAddress,
        array $payload = [],
    ): TestResponse {
        $this->resetJwtAuthenticationState();

        return $this
            ->withServerVariables([
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => 'PHPUnit',
            ])
            ->withToken($token)
            ->postJson($uri, $payload);
    }

    private function tokenFromResponse(TestResponse $response): string
    {
        $token = $response->json('data.access_token');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        return $token;
    }

    private function resetJwtAuthenticationState(): void
    {
        Auth::forgetGuards();
        JWTAuth::unsetToken();
        app(JWT::class)->unsetToken();
    }
}
