<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Enums\Auth\SessionRevocationReason;

class UserSessionTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
    private const REFRESH_ENDPOINT = '/api/auth/refresh';
    private const LOGOUT_ENDPOINT = '/api/auth/logout';
    private const LOGOUT_ALL_ENDPOINT = '/api/auth/logout-all';
    private const ME_ENDPOINT = '/api/me';

    private function authenticatedHeader(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }

    private function createUserSession(
        User $user,
        ?string $jti = null,
        array $attributes = [],
    ): UserSession {
        return UserSession::create(array_merge([
            'user_id' => $user->id,
            'token_jti' => $jti ?? (string) Str::uuid(),
            'device_name' => 'Firefox on Linux',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
            'revoked_at' => null,
            'revoked_reason' => null,
        ], $attributes));
    }

    public function test_creates_a_user_session_when_registering(): void
    {
        $response = $this->postJson(self::REGISTER_ENDPOINT, [
            'username' => 'testUser',
            'email' => 'testuser@email.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Your account was created.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user',
                ],
                'errors',
            ]);

        $user = User::where('email', 'testuser@email.com')->firstOrFail();

        $token = $response->json('data.access_token');

        $this->assertIsString($token);

        $payload = JWTAuth::setToken($token)->getPayload();

        $this->assertDatabaseCount('user_sessions', 1);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'token_jti' => $payload->get('jti'),
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        $session = UserSession::firstOrFail();

        $this->assertSame($user->id, $session->user_id);
        $this->assertSame($payload->get('jti'), $session->token_jti);
        $this->assertNotEmpty($session->device_name);
        $this->assertNotEmpty($session->ip_address);
        $this->assertNotEmpty($session->user_agent);
        $this->assertNotNull($session->last_seen_at);
        $this->assertNotNull($session->expires_at);
        $this->assertNull($session->revoked_at);
        $this->assertNull($session->revoked_reason);
    }

    public function test_creates_a_user_session_when_logging_in(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'You are logged in.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user',
                ],
                'errors',
            ]);

        $token = $response->json('data.access_token');

        $this->assertIsString($token);

        $payload = JWTAuth::setToken($token)->getPayload();

        $this->assertDatabaseCount('user_sessions', 1);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'token_jti' => $payload->get('jti'),
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        $session = UserSession::firstOrFail();

        $this->assertSame($user->id, $session->user_id);
        $this->assertSame($payload->get('jti'), $session->token_jti);
        $this->assertNotEmpty($session->device_name);
        $this->assertNotEmpty($session->ip_address);
        $this->assertNotEmpty($session->user_agent);
        $this->assertNotNull($session->last_seen_at);
        $this->assertNotNull($session->expires_at);
    }

    public function test_refresh_updates_token_jti_instead_of_creating_a_new_session(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk();

        $oldToken = $loginResponse->json('data.access_token');

        $this->assertIsString($oldToken);

        $oldPayload = JWTAuth::setToken($oldToken)->getPayload();
        $oldJti = $oldPayload->get('jti');

        $session = UserSession::firstOrFail();
        $sessionId = $session->id;

        $this->assertDatabaseCount('user_sessions', 1);

        $response = $this
            ->withHeaders($this->authenticatedHeader($oldToken))
            ->postJson(self::REFRESH_ENDPOINT);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Your token was refreshed.');

        $newToken = $response->json('data.access_token');

        $this->assertIsString($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $newPayload = JWTAuth::setToken($newToken)->getPayload();
        $newJti = $newPayload->get('jti');

        $this->assertNotSame($oldJti, $newJti);

        $this->assertDatabaseCount('user_sessions', 1);

        $this->assertDatabaseMissing('user_sessions', [
            'token_jti' => $oldJti,
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'id' => $sessionId,
            'user_id' => $user->id,
            'token_jti' => $newJti,
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        $session->refresh();

        $this->assertSame($sessionId, $session->id);
        $this->assertSame($newJti, $session->token_jti);
        $this->assertSame(
            (int) $newPayload->get('exp'),
            $session->expires_at->timestamp,
        );
        $this->assertNotNull($session->last_seen_at);
    }

    public function test_logout_revokes_the_current_session_with_logout_reason(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk();

        $token = $loginResponse->json('data.access_token');

        $this->assertIsString($token);

        $payload = JWTAuth::setToken($token)->getPayload();
        $jti = $payload->get('jti');

        $session = UserSession::where('token_jti', $jti)->firstOrFail();

        $this->assertNull($session->revoked_at);
        $this->assertNull($session->revoked_reason);

        $response = $this
            ->withHeaders($this->authenticatedHeader($token))
            ->postJson(self::LOGOUT_ENDPOINT);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'You logged out.')
            ->assertJsonPath('data.access_token', null);

        $session->refresh();

        $this->assertNotNull($session->revoked_at);
        $this->assertSame(
            SessionRevocationReason::LOGOUT->value,
            $session->revoked_reason,
        );

        $this->assertDatabaseHas('user_sessions', [
            'id' => $session->id,
            'user_id' => $user->id,
            'token_jti' => $jti,
            'revoked_reason' => SessionRevocationReason::LOGOUT->value,
        ]);
    }

    public function test_logout_all_revokes_all_active_sessions_with_logout_all_reason(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk();

        $token = $loginResponse->json('data.access_token');

        $this->assertIsString($token);

        $currentSession = UserSession::firstOrFail();

        $secondSession = $this->createUserSession($user);
        $thirdSession = $this->createUserSession($user);

        $alreadyRevokedAt = now()->subDay();

        $alreadyRevokedSession = $this->createUserSession(
            user: $user,
            attributes: [
                'revoked_at' => $alreadyRevokedAt,
                'revoked_reason' => SessionRevocationReason::LOGOUT->value,
            ],
        );

        $otherUser = User::factory()->create();

        $otherUserSession = $this->createUserSession($otherUser);

        $this->assertDatabaseCount('user_sessions', 5);

        $response = $this
            ->withHeaders($this->authenticatedHeader($token))
            ->postJson(self::LOGOUT_ALL_ENDPOINT);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'You logged out from all 3 devices.',
            )
            ->assertJsonPath('data.access_token', null);

        $activeSessionIds = [
            $currentSession->id,
            $secondSession->id,
            $thirdSession->id,
        ];

        $revokedSessions = UserSession::whereIn('id', $activeSessionIds)->get();

        $this->assertCount(3, $revokedSessions);

        foreach ($revokedSessions as $session) {
            $this->assertNotNull($session->revoked_at);
            $this->assertSame(
                SessionRevocationReason::LOGOUT_ALL->value,
                $session->revoked_reason,
            );
        }

        $alreadyRevokedSession->refresh();

        $this->assertSame(
            SessionRevocationReason::LOGOUT->value,
            $alreadyRevokedSession->revoked_reason,
        );

        $this->assertSame(
            $alreadyRevokedAt->timestamp,
            $alreadyRevokedSession->revoked_at->timestamp,
        );

        $otherUserSession->refresh();

        $this->assertNull($otherUserSession->revoked_at);
        $this->assertNull($otherUserSession->revoked_reason);
    }

    public function test_revoked_session_cannot_access_me(): void
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();

        $this->createUserSession(
            user: $user,
            jti: $payload->get('jti'),
            attributes: [
                'revoked_at' => now(),
                'revoked_reason' => SessionRevocationReason::LOGOUT->value,
            ],
        );

        $this
            ->withHeaders($this->authenticatedHeader($token))
            ->getJson(self::ME_ENDPOINT)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The authenticated session is no longer active.');
    }

    public function test_expired_session_cannot_access_me(): void
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();

        $this->createUserSession(
            user: $user,
            jti: $payload->get('jti'),
            attributes: [
                'expires_at' => now()->subMinute(),
            ],
        );

        $this
            ->withHeaders($this->authenticatedHeader($token))
            ->getJson(self::ME_ENDPOINT)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The authenticated session is no longer active.');
    }

    public function test_valid_session_can_access_me(): void
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();

        $this->createUserSession(
            user: $user,
            jti: $payload->get('jti'),
            attributes: [
                'revoked_at' => null,
                'expires_at' => now()->addHour(),
            ],
        );

        $this
            ->withHeaders($this->authenticatedHeader($token))
            ->getJson(self::ME_ENDPOINT)
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_logout_all_makes_another_device_unable_to_access_me(): void
    {
        User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $firstLoginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $firstLoginResponse->assertOk();

        $firstDeviceToken = $firstLoginResponse->json('data.access_token');

        $this->assertIsString($firstDeviceToken);

        $this->travel(10)->second();

        $secondLoginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $secondLoginResponse->assertOk();

        $secondDeviceToken = $secondLoginResponse->json('data.access_token');

        $this->assertIsString($secondDeviceToken);
        $this->assertNotSame($firstDeviceToken, $secondDeviceToken);

        $this
            ->withHeaders($this->authenticatedHeader($secondDeviceToken))
            ->getJson(self::ME_ENDPOINT)
            ->assertOk();

        $this
            ->withHeaders($this->authenticatedHeader($firstDeviceToken))
            ->postJson(self::LOGOUT_ALL_ENDPOINT)
            ->assertOk();

        $this
            ->withHeaders($this->authenticatedHeader($secondDeviceToken))
            ->getJson(self::ME_ENDPOINT)
            ->assertUnauthorized();
    }

    public function test_access_token_can_be_refreshed_before_its_thirty_day_expiration(): void
    {
        $originalTtl = JWTAuth::factory()->getTTL();

        try {
            JWTAuth::factory()->setTTL(43200);

            $user = User::factory()->create([
                'email' => 'testuser@email.com',
                'password' => 'password123',
            ]);

            $loginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
                'email' => $user->email,
                'password' => 'password123',
            ]);

            $loginResponse->assertOk();

            $oldToken = $loginResponse->json('data.access_token');

            $this->assertIsString($oldToken);

            $oldPayload = JWTAuth::setToken($oldToken)->getPayload();
            $oldJti = (string) $oldPayload->get('jti');

            // The token remains valid because its TTL is 30 days.
            $this->travel(29)->days();

            $refreshResponse = $this
                ->withHeaders($this->authenticatedHeader($oldToken))
                ->postJson(self::REFRESH_ENDPOINT);

            $refreshResponse
                ->assertOk()
                ->assertJsonPath('message', 'Your token was refreshed.');

            $newToken = $refreshResponse->json('data.access_token');

            $this->assertIsString($newToken);
            $this->assertNotSame($oldToken, $newToken);

            $newPayload = JWTAuth::setToken($newToken)->getPayload();
            $newJti = (string) $newPayload->get('jti');

            $this->assertNotSame($oldJti, $newJti);

            $this->assertDatabaseMissing('user_sessions', [
                'token_jti' => $oldJti,
            ]);

            $this->assertDatabaseHas('user_sessions', [
                'user_id' => $user->id,
                'token_jti' => $newJti,
                'revoked_at' => null,
            ]);

            $this
                ->withHeaders($this->authenticatedHeader($newToken))
                ->getJson(self::ME_ENDPOINT)
                ->assertOk()
                ->assertJsonPath('data.user.id', $user->id);
        } finally {
            JWTAuth::factory()->setTTL($originalTtl);
        }
    }

    public function test_expired_access_token_cannot_be_refreshed(): void
    {
        $originalTtl = JWTAuth::factory()->getTTL();

        try {
            JWTAuth::factory()->setTTL(1);

            $user = User::factory()->create([
                'email' => 'testuser@email.com',
                'password' => 'password123',
            ]);

            $loginResponse = $this->postJson(self::LOGIN_ENDPOINT, [
                'email' => $user->email,
                'password' => 'password123',
            ]);

            $loginResponse->assertOk();

            $oldToken = $loginResponse->json('data.access_token');

            $this->assertIsString($oldToken);

            $this->travel(2)->minutes();

            $this
                ->withHeaders($this->authenticatedHeader($oldToken))
                ->postJson(self::REFRESH_ENDPOINT)
                ->assertUnauthorized();

            // The failed refresh must not rotate the session.
            $oldJti = JWTAuth::manager()
                ->getPayloadFactory()
                ->setRefreshFlow(true);

            $this->assertDatabaseCount('user_sessions', 1);
        } finally {
            JWTAuth::factory()->setTTL($originalTtl);
        }
    }

    public function test_access_token_has_a_thirty_day_lifetime(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@email.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk();

        $token = $response->json('data.access_token');

        $this->assertIsString($token);

        $payload = JWTAuth::setToken($token)->getPayload();

        $lifetimeInSeconds =
            (int) $payload->get('exp') - (int) $payload->get('iat');

        $this->assertSame(30 * 24 * 60 * 60, $lifetimeInSeconds);

        $response->assertJsonPath(
            'data.expires_in',
            30 * 24 * 60 * 60,
        );
    }
}
