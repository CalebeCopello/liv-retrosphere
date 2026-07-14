<?php

use Illuminate\Support\Facades\Route;


Route::post('/auth/login', function () {
    return 'Hello World';
})->name('auth.login');

Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('logout', function () {
        return 'logout';
    })->name('auth.logout');
    Route::post('refresh', function () {
        return 'refresh';
    })->name('auth.refresh');
});

Route::middleware('auth:api')->get('/me', function () {
    return 'me';
})->name('auth.me');
