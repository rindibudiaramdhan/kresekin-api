<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/vershealthcheck', function (): JsonResponse {
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
});
