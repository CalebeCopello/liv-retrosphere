<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('web.auth.login');
Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('web.auth.register');
