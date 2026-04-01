<?php

use App\Http\Controllers\Api\AddCartItemController;
use App\Http\Controllers\Api\DeleteCartItemController;
use App\Http\Controllers\Api\GetCartController;
use App\Http\Controllers\Api\GetProductDetailController;
use App\Http\Controllers\Api\GetProductListController;
use App\Http\Controllers\Api\GetTenantCategoriesController;
use App\Http\Controllers\Api\GetTenantListController;
use App\Http\Controllers\Api\GetUserTransactionHistoryController;
use App\Http\Controllers\Api\GetUserTransactionDetailController;
use App\Http\Controllers\Api\LoginUserController;
use App\Http\Controllers\Api\RegisterUserController;
use App\Http\Controllers\Api\UpdateCartItemController;
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
Route::post('/users/login', LoginUserController::class);
Route::post('/users/register', RegisterUserController::class);
Route::post('/users/verify-otp', VerifyOtpController::class);
Route::put('/users/profile', UpdateUserProfileController::class)->middleware('session.token');
Route::get('/cart', GetCartController::class)->middleware('session.token');
Route::post('/cart/items', AddCartItemController::class)->middleware('session.token');
Route::patch('/cart/items/{id}', UpdateCartItemController::class)->middleware('session.token');
Route::delete('/cart/items/{id}', DeleteCartItemController::class)->middleware('session.token');
Route::get('/products/{id}', GetProductDetailController::class)->middleware('session.token');
Route::get('/products', GetProductListController::class)->middleware('session.token');
Route::get('/tenants/categories', GetTenantCategoriesController::class)->middleware('session.token');
Route::get('/tenants', GetTenantListController::class)->middleware('session.token');
Route::get('/users/transactions', GetUserTransactionHistoryController::class)->middleware('session.token');
Route::get('/users/transactions/{transactionId}', GetUserTransactionDetailController::class)->middleware('session.token');
