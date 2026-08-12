<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\CheckUserIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-connection', fn () => response()->json([
    'status' => 'ok',
    'message' => 'API AlertBook accessible !',
]))->middleware('throttle:60,1');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', CheckUserIsActive::class])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/sync/reference-data', [SyncController::class, 'getReferenceData']);
    Route::post('/incidents', [IncidentController::class, 'store'])->middleware('throttle:api');
});
