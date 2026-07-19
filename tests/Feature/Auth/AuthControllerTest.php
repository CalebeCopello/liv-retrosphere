<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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
            'email' => 'testuser@email.com',
            'password' => Hash::make('password123'),
        ]);
    }

    private function loginAndGetToken(User $user): string
    {
        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk();

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                ],
            ]);

        $token = $response->json('data.access_token');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        return $token;
    }

    public function test_register_return_token(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->postJson(self::REGISTER_ENDPOINT, [
            'username' => 'testUser',
            'email' => 'testuser@email.com',
            'password' => 'password123'
        ]);
        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'username',
                    'display_name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
            ],
            'errors',
        ])
            ->assertJsonPath('message', 'Your account was created.')
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.username', 'testUser')
            ->assertJsonPath('data.user.email', 'testuser@email.com');

        $this->assertDatabaseHas('users', [
            'username' => 'testUser',
            'email' => 'testuser@email.com',
        ]);

        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_login_returns_token(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'username',
                    'display_name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
            ],
            'errors',
        ])
            ->assertJsonPath('message', 'You are logged in.')
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.username', 'testUser')
            ->assertJsonPath('data.user.email', 'testuser@email.com');
    }

    public function test_wrong_login_returns_401()
    {
        $user = $this->createUser();

        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $response->assertUnauthorized();

        $response->assertJsonStructure([
            'message',
            'data' => [],
            'errors' => [
                "credentials"
            ],
        ])
            ->assertJsonPath('message', 'The provided credentials are incorrect.')
            ->assertJsonPath('errors.credentials', ["The provided credentials are incorrect."]);

        $this->assertEmpty($response->json('access_token'));
    }

    public function test_me_without_token_returns_401(): void
    {
        $this->getJson(self::ME_ENDPOINT)
            ->assertUnauthorized();
    }

    public function test_me_with_token_returns_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->getJson(self::ME_ENDPOINT);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'username',
                    'display_name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
            ],
            'errors',
        ])
            ->assertJsonPath('message', 'Request succeeded.')
            ->assertJsonPath('data.user.username', 'testUser')
            ->assertJsonPath('data.user.email', 'testuser@email.com');
    }

    public function test_refresh_returns_a_new_token(): void
    {
        $user = $this->createUser();
        $oldToken = $this->loginAndGetToken($user);

        $this->travel(1)->second();

        $response = $this->withToken($oldToken)->postJson(self::REFRESH_ENDPOINT);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'username',
                    'display_name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
            ],
            'errors',
        ])
            ->assertJsonPath('message', 'Your token was refreshed.')
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.username', 'testUser')
            ->assertJsonPath('data.user.email', 'testuser@email.com');

        $newToken = $response->json('data.access_token');

        $this->assertNotEmpty($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $this
            ->withToken($newToken)
            ->getJson(self::ME_ENDPOINT)
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_logout_invalidates_current_token(): void
    {
        $user = $this->createUser();
        $token = $this->loginAndGetToken($user);

        $this
            ->withToken($token)
            ->postJson(self::LOGOUT_ENDPOINT)
            ->assertOk();

        $this
            ->withToken($token)
            ->getJson(self::ME_ENDPOINT)
            ->assertUnauthorized();
    }
}
