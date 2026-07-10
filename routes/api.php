<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileApiController::class, 'login']);
Route::post('/auth/login', [MobileApiController::class, 'login']);

Route::middleware('mobile.api')->group(function () {
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::post('/register', [MobileApiController::class, 'registerDevice']);
    Route::get('/attendance', [MobileApiController::class, 'attendances']);
    Route::post('/check_in', [MobileApiController::class, 'checkIn']);
    Route::post('/check_out', [MobileApiController::class, 'checkOut']);
    Route::post('/tracking/location', [MobileApiController::class, 'updateLocation']);
    Route::post('/devices/live-status', [MobileApiController::class, 'liveStatus']);
    Route::get('/settings/tracking', [MobileApiController::class, 'trackingSettings']);
    Route::get('/admin/employees/live-locations', [MobileApiController::class, 'adminLiveLocations']);
    Route::get('/admin/employees/{employee}/timeline', [MobileApiController::class, 'adminTimeline'])->whereNumber('employee');
    Route::get('/employees/track', [MobileApiController::class, 'trackEmployees']);
    Route::get('/tasks', [MobileApiController::class, 'tasks']);
    Route::post('/tasks/assign', [MobileApiController::class, 'assignTask']);
    Route::get('/tasks/{task}', [MobileApiController::class, 'showTask'])->whereNumber('task');
    Route::put('/tasks/{task}', [MobileApiController::class, 'updateTask'])->whereNumber('task');
    Route::post('/tasks/{task}/update', [MobileApiController::class, 'updateTask'])->whereNumber('task');
    Route::delete('/tasks/{task}', [MobileApiController::class, 'deleteTask'])->whereNumber('task');
    Route::get('/wallet', [MobileApiController::class, 'wallets']);
    Route::get('/wallet/options', [MobileApiController::class, 'walletOptions']);
    Route::post('/wallet/store', [MobileApiController::class, 'transferWallet']);
    Route::post('/wallet/transfer', [MobileApiController::class, 'transferWallet']);
});
