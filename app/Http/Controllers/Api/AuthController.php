<?php

namespace App\Http\Controllers\Api;

use App\Enums\Auth\UserAuthEventType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\UserAuthEventService;
use App\Services\Auth\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(protected UserAuthEventService $authEventService, protected UserSessionService $userSessionService) {}

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'username' => trim((string) $request->input('username')),
        ]);

        $payload = $request->validate([
            'username' => ['required', 'string', 'min:4', 'max:32', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $user = User::create([
            'username' => $payload['username'],
            'display_name' => $payload['username'] . bin2hex(random_bytes(2)),
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $token = JWTAuth::fromUser($user);

        $this->authEventService->log(user: $user, eventType: UserAuthEventType::REGISTER, ip: $request->ip(), userAgent: $request->userAgent(), isSuccess: true);

        return $this->returnAuthPayload(message: 'Your account was created.', token: $token, user: $user, errors: null, httpCode: Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            $user = User::where('email', $credentials['email'])->first();

            if ($user) {
                $this->authEventService->log(user: $user, eventType: UserAuthEventType::FAILED_LOGIN, ip: $request->ip(), userAgent: $request->userAgent(), isSuccess: false);
            }
            return $this->authErrorReturn(message: 'The provided credentials are incorrect.', errors: ['credentials' => ['The provided credentials are incorrect.']], httpCode: Response::HTTP_UNAUTHORIZED);
        }

        $user = JWTAuth::setToken($token)->authenticate();

        if (! $user) {
            JWTAuth::setToken($token)->invalidate();

            return $this->authErrorReturn(
                message: 'The authenticated user was not found.',
                errors: null,
                httpCode: Response::HTTP_UNAUTHORIZED,
            );
        }

        $this->authEventService->log(user: $user, eventType: UserAuthEventType::LOGIN, ip: $request->ip(), userAgent: $request->userAgent(), isSuccess: true);

        $this->userSessionService->createSession(user: $user, token: $token, request: $request);

        return $this->returnAuthPayload(message: 'You are logged in.', token: $token, user: $user, errors: null, httpCode: Response::HTTP_OK);
    }

    public function refresh(Request $request): JsonResponse
    {
        $newToken = JWTAuth::parseToken()->refresh();
        $user = JWTAuth::setToken($newToken)->toUser();

        if (!$user) {
            JWTAuth::setToken($newToken)->invalidate();

            return $this->authErrorReturn(message: 'The token does not belong to a valid user.', errors: null, httpCode: Response::HTTP_UNAUTHORIZED);
        }

        $this->authEventService->log(user: $user, eventType: UserAuthEventType::TOKEN_REFRESH, ip: $request->ip(), userAgent: $request->userAgent(), isSuccess: true);

        return $this->returnAuthPayload(message: 'Your token was refreshed.', token: $newToken, user: $user, errors: null, httpCode: Response::HTTP_OK);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return $this->authErrorReturn(message: 'The authenticated user was not found.', errors: null, httpCode: Response::HTTP_UNAUTHORIZED);
        }

        $this->authEventService->log(user: $user, eventType: UserAuthEventType::LOGOUT, ip: $request->ip(), userAgent: $request->userAgent(), isSuccess: true);

        JWTAuth::parseToken()->invalidate();

        return $this->returnAuthPayload(message: 'You logged out.', token: null, user: $user, errors: null, httpCode: Response::HTTP_OK);
    }

    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return $this->authErrorReturn(message: 'The authenticated user was not found.', errors: null, httpCode: Response::HTTP_UNAUTHORIZED);
        }

        return $this->returnAuthPayload(message: 'Request succeeded.', token: null, user: $user, errors: null, httpCode: Response::HTTP_OK);
    }

    private function returnAuthPayload(string $message, ?string $token, ?User $user, ?array $errors, int $httpCode): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'access_token' => $token,
                'token_type' => $token !== null ? 'bearer' : null,
                'expires_in' => $token !== null ? JWTAuth::factory()->getTTL() * 60 : null,
                'user' => $user,
            ],
            'errors' => $errors,
        ], $httpCode);
    }

    private function authErrorReturn(string $message, int $httpCode, ?array $errors): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $httpCode);
    }
}
