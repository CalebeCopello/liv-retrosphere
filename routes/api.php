<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:auth.login')->name('auth.login');
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:auth.register')->name('auth.register');
    Route::middleware(['auth:api', 'jwt.session.active'])->group(function () {
        Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('throttle:auth.refresh')->name('auth.refresh');
        Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('throttle:auth.logout')->name('auth.logout');
        Route::post('logout-all', [App\Http\Controllers\Api\AuthController::class, 'logoutAll'])->middleware('throttle:auth.logout-all')->name('auth.logout_all');
    });
});

Route::middleware(['auth:api', 'jwt.session.active', 'throttle:api.authenticated'])->group(function () {
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->name('auth.me');
});
