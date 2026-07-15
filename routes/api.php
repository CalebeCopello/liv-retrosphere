<?php

use Illuminate\Support\Facades\Route;


Route::post('/auth/login',[App\Http\Controllers\Api\AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->name('auth.logout');
    Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->name('auth.refresh');
});

Route::middleware('auth:api')->get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->name('auth.me');
