<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OktaOIDCController;
use App\Jobs\PushToBigQueryJob;

Route::get('/login/okta', [OktaOIDCController::class, 'login']);


Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
   // 'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/private-browser', function () {
        return view('private-browser');
    })->name('private-browser');

    Route::get('/snmp-walk', function () {
        return view('snmp-walk');
    })->name('snmp-walk');
});




Route::get('/test-job', function () {
    $log = \App\Models\DeviceLog::latest()->first(); // or create dummy data
    dispatch(new PushToBigQueryJob($log));

    return 'Job dispatched!';
});
