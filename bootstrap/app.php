<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TokenExpiredException $exception, Request $request) {
            if (!$request->is('api/*')) return null;
            return response()->json([
                'message' => 'The authentication token has expired.',
                'data' => null,
                'errors' => [
                    'token' => [
                        'The authentication token has expired.',
                    ],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (TokenInvalidException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The authentication token is invalid.',
                'data' => null,
                'errors' => [
                    'token' => [
                        'The authentication token is invalid.',
                    ],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (TokenBlacklistedException $exception, Request $request) {
            if (!$request->is('api/*')) return null;
            return response()->json([
                'message' => 'The authentication token is no longer valid.',
                'data' => null,
                'errors' => [
                    'token' => [
                        'The authentication token is no longer valid.',
                    ],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (JWTException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Authentication could not be completed.',
                'data' => null,
                'errors' => [
                    'token' => [
                        'A valid authentication token is required.',
                    ],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        });
    })->create();
