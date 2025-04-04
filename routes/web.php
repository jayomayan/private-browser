<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OktaOIDCController;

Route::get('/login/okta', [OktaOIDCController::class, 'login']);


Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/private-browser', function () {
        return view('private-browser');
    })->name('private-browser');
});

