<?php

namespace Tests\Feature\Auth;

use App\Enums\Auth\UserAuthEventType;
use App\Models\User;
use App\Models\UserAuthEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthControllerLogTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER_ENDPOINT = '/api/auth/register';
    private const LOGIN_ENDPOINT = '/api/auth/login';
    private const REFRESH_ENDPOINT = '/api/auth/refresh';
    private const LOGOUT_ENDPOINT = '/api/auth/logout';
    private const ME_ENDPOINT = '/api/me';


    private function createUser(): User
    {
        return User::factory()->create([
            'username' => 'testUser',
            'display_name' => 'Test User',
            'email' => 'testuser@email.com',
            'password' => Hash::make('password123'),
        ]);
    }

    private function loginAndGetToken(User $user): string
    {
        $response = $this
            ->withHeader('User-Agent', 'PHPUnit Login Agent')
            ->postJson(self::LOGIN_ENDPOINT, [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertOk();

        return $response->json('data.access_token');
    }

    public function test_register_creates_successful_register_event(): void
    {
        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.10',
            ])
            ->withHeader('User-Agent', 'PHPUnit Register Agent')
            ->postJson(self::REGISTER_ENDPOINT, [
                'username' => 'newUser',
                'email' => 'newuser@email.com',
                'password' => 'password123',
            ]);

        $response->assertCreated();

        $user = User::where('email', 'newuser@email.com')->firstOrFail();

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::REGISTER->value,
            'ip_address' => '192.168.1.10',
            'user_agent' => 'PHPUnit Register Agent',
            'is_success' => true,
        ]);
    }

    public function test_successful_login_creates_login_event(): void
    {
        $user = $this->createUser();

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.11',
            ])
            ->withHeader('User-Agent', 'PHPUnit Login Agent')
            ->postJson(self::LOGIN_ENDPOINT, [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::LOGIN->value,
            'ip_address' => '192.168.1.11',
            'user_agent' => 'PHPUnit Login Agent',
            'is_success' => true,
        ]);
    }

    public function test_failed_login_for_existing_user_creates_failed_login_event(): void
    {
        $user = $this->createUser();

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.12',
            ])
            ->withHeader('User-Agent', 'PHPUnit Failed Login Agent')
            ->postJson(self::LOGIN_ENDPOINT, [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::FAILED_LOGIN->value,
            'ip_address' => '192.168.1.12',
            'user_agent' => 'PHPUnit Failed Login Agent',
            'is_success' => false,
        ]);
    }

    public function test_failed_login_for_unknown_email_does_not_create_event(): void
    {
        $response = $this
            ->withHeader('User-Agent', 'PHPUnit Unknown User Agent')
            ->postJson(self::LOGIN_ENDPOINT, [
                'email' => 'unknown@email.com',
                'password' => 'wrong-password',
            ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('user_auth_events', 0);
    }

    public function test_refresh_creates_token_refresh_event(): void
    {
        $user = $this->createUser();
        $oldToken = $this->loginAndGetToken($user);

        UserAuthEvent::query()->delete();

        $this->travel(1)->second();

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.13',
            ])
            ->withHeader('User-Agent', 'PHPUnit Refresh Agent')
            ->withToken($oldToken)
            ->postJson(self::REFRESH_ENDPOINT);

        $response->assertOk();

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::TOKEN_REFRESH->value,
            'ip_address' => '192.168.1.13',
            'user_agent' => 'PHPUnit Refresh Agent',
            'is_success' => true,
        ]);
    }

    public function test_logout_creates_logout_event(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken($user);

        UserAuthEvent::query()->delete();

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.14',
            ])
            ->withHeader('User-Agent', 'PHPUnit Logout Agent')
            ->withToken($token)
            ->postJson(self::LOGOUT_ENDPOINT);

        $response->assertOk();

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::LOGOUT->value,
            'ip_address' => '192.168.1.14',
            'user_agent' => 'PHPUnit Logout Agent',
            'is_success' => true,
        ]);
    }

    public function test_me_does_not_create_authentication_event(): void
    {
        $user = $this->createUser();
        $token = JWTAuth::fromUser($user);

        $this
            ->withToken($token)
            ->getJson(self::ME_ENDPOINT)
            ->assertOk();

        $this->assertDatabaseCount('user_auth_events', 0);
    }

    public function test_validation_failure_does_not_create_authentication_event(): void
    {
        $this
            ->postJson(self::LOGIN_ENDPOINT, [])
            ->assertUnprocessable();

        $this->assertDatabaseCount('user_auth_events', 0);
    }
}