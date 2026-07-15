<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return response()->json($this->returnToken($token), 200);
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh();

        return response()->json($this->returnToken($newToken), 200);
    }

    public function logout()
    {
        JWTAuth::logout();
        return response()->json(['message' => 'You logged out.'], 200);
    }

    public function me()
    {
        $user = JWTAuth::user();
        return response()->json([
            'user' => $user,
        ], 200);
    }

    private function returnToken(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => JWTAuth::user(),
            'message' => 'You are logged in.'
        ];
    }

    //TODO: json standard format to frontend
    private function returnAuthPayload() {
        return;
    }
}
