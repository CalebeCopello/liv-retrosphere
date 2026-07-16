<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->returnAuthPayload(message: 'The provided credentials are incorrect.', token: null, user: null, errors: ['email' => ['The provided credentials are incorrect.']], httpCode: 401);
        }

        return $this->returnAuthPayload(message: 'You are logged in.', token: $token, user: JWTAuth::user(), errors: null, httpCode: 200);
    }

    public function refresh(): JsonResponse
    {
        $newToken = JWTAuth::refresh();

        return $this->returnAuthPayload(message: 'Your token was refreshed.', token: $newToken, user: JWTAuth::setToken($newToken)->toUser(), errors: null, httpCode: 200);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::logout();
        return $this->returnAuthPayload(message: 'You logged out.', token: null, user: null, errors: null, httpCode: 200);
    }

    public function me(): JsonResponse
    {
        return $this->returnAuthPayload(message: 'Request succeeded.', token: null, user: JWTAuth::user(), errors: null, httpCode: 200);

    }

    private function returnAuthPayload(string $message, ?string $token, mixed $user, ?array $errors, int $httpCode): JsonResponse
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
}
