<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Api\AuthController;
use App\Services\Auth\UserAuthEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $authEventService = $this->mock(
            UserAuthEventService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('log');
            },
        );

        $this->controller = new AuthController($authEventService);
    }

    public function test_login_returns_unauthorized_when_token_user_cannot_be_found(): void
    {
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'testuser@email.com',
                'password' => 'password123',
            ])
            ->andReturn('generated-token');

        JWTAuth::shouldReceive('setToken')
            ->twice()
            ->with('generated-token')
            ->andReturnSelf();

        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturnNull();

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->andReturnTrue();

        $request = Request::create(
            uri: '/api/auth/login',
            method: 'POST',
            parameters: [
                'email' => ' TESTUSER@EMAIL.COM ',
                'password' => 'password123',
            ],
        );

        $response = $this->controller->login($request);

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
        );

        $this->assertSame([
            'message' => 'The authenticated user was not found.',
            'data' => null,
            'errors' => null,
        ], $response->getData(true));
    }

    public function test_refresh_returns_unauthorized_when_new_token_has_no_valid_user(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('refresh')
            ->once()
            ->andReturn('refreshed-token');

        JWTAuth::shouldReceive('setToken')
            ->twice()
            ->with('refreshed-token')
            ->andReturnSelf();

        JWTAuth::shouldReceive('toUser')
            ->once()
            ->andReturnNull();

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->andReturnTrue();

        $request = Request::create(
            uri: '/api/auth/refresh',
            method: 'POST',
        );

        $response = $this->controller->refresh($request);

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
        );

        $this->assertSame([
            'message' => 'The token does not belong to a valid user.',
            'data' => null,
            'errors' => null,
        ], $response->getData(true));
    }

    public function test_logout_returns_unauthorized_when_authenticated_user_cannot_be_found(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturnNull();

        JWTAuth::shouldNotReceive('invalidate');

        $request = Request::create(
            uri: '/api/auth/logout',
            method: 'POST',
        );

        $response = $this->controller->logout($request);

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
        );

        $this->assertSame([
            'message' => 'The authenticated user was not found.',
            'data' => null,
            'errors' => null,
        ], $response->getData(true));
    }

    public function test_me_returns_unauthorized_when_authenticated_user_cannot_be_found(): void
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturnNull();

        $response = $this->controller->me();

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $response->getStatusCode(),
        );

        $this->assertSame([
            'message' => 'The authenticated user was not found.',
            'data' => null,
            'errors' => null,
        ], $response->getData(true));
    }
}