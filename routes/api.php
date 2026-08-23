<?php

use App\Http\Controllers\Api\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::get('/participants/{nim}', [ParticipantController::class, 'show']);
Route::post('/checkin', [ParticipantController::class, 'checkin']);
