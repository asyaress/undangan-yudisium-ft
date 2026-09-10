<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileCheckinController;
use App\Http\Controllers\Api\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::get('/participants/{nim}', [ParticipantController::class, 'show']);
Route::post('/checkin', [ParticipantController::class, 'checkin']);

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:12,1');

    Route::middleware('mobile')->group(function () {
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::get('/events', [MobileCheckinController::class, 'events']);
        Route::get('/events/{period}/roster', [MobileCheckinController::class, 'roster']);
        Route::post('/events/{period}/scan', [MobileCheckinController::class, 'scan']);
        Route::post('/events/{period}/sync', [MobileCheckinController::class, 'sync']);
    });
});
