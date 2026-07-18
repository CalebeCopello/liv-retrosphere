<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register'])->name('auth.register');
    Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->name('auth.refresh');
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->name('auth.logout');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->name('auth.me');
});
