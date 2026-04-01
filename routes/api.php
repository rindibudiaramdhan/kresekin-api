<?php

use App\Http\Controllers\Api\RegisterUserController;
use App\Http\Controllers\Api\UpdateUserProfileController;
use App\Http\Controllers\Api\VerifyOtpController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

$healthcheckHandler = function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is healthy',
        'version' => config('api.version'),
        'framework' => [
            'name' => 'Laravel',
            'version' => app()->version(),
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
};

Route::get('/vershealthcheck', $healthcheckHandler);
Route::post('/users/register', RegisterUserController::class);
Route::post('/users/verify-otp', VerifyOtpController::class);
Route::put('/users/profile', UpdateUserProfileController::class)->middleware('session.token');
