<?php

use App\Http\Controllers\Api\AddCartItemController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CreateSellerProductController;
use App\Http\Controllers\Api\CreateSellerTenantController;
use App\Http\Controllers\Api\DeleteSellerProductController;
use App\Http\Controllers\Api\DeleteCartItemController;
use App\Http\Controllers\Api\GetDeliveryMethodsController;
use App\Http\Controllers\Api\GetCartController;
use App\Http\Controllers\Api\GetBuyerTenantListController;
use App\Http\Controllers\Api\GetSellerProductDetailController;
use App\Http\Controllers\Api\GetPaymentMethodsController;
use App\Http\Controllers\Api\GetProductDetailController;
use App\Http\Controllers\Api\GetProductCategoriesController;
use App\Http\Controllers\Api\GetProductListController;
use App\Http\Controllers\Api\GetSellerProductListController;
use App\Http\Controllers\Api\GetSellerTenantListController;
use App\Http\Controllers\Api\GetTenantCategoriesController;
use App\Http\Controllers\Api\GetHousingAreaListController;
use App\Http\Controllers\Api\GetUserTransactionHistoryController;
use App\Http\Controllers\Api\GetUserTransactionDetailController;
use App\Http\Controllers\Api\GetUserProfileController;
use App\Http\Controllers\Api\LoginUserController;
use App\Http\Controllers\Api\LogoutUserController;
use App\Http\Controllers\Api\RegisterUserController;
use App\Http\Controllers\Api\RegisterUserDeviceController;
use App\Http\Controllers\Api\RefreshUserSessionController;
use App\Http\Controllers\Api\UpdateCartDeliveryMethodController;
use App\Http\Controllers\Api\UpdateCartItemController;
use App\Http\Controllers\Api\UpdateSellerProductController;
use App\Http\Controllers\Api\UpdateUserProfileController;
use App\Http\Controllers\Api\VerifyOtpController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

$healthcheckHandler = function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'message' => 'API sehat',
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
Route::post('/users/logout', LogoutUserController::class)->middleware('session.token');
Route::post('/users/devices', RegisterUserDeviceController::class)->middleware('session.token');
Route::post('/users/refresh-session', RefreshUserSessionController::class)->middleware('session.token');
Route::get('/users/profile', GetUserProfileController::class)->middleware('session.token');
Route::put('/users/profile', UpdateUserProfileController::class)->middleware('session.token');
Route::get('/housing-areas', GetHousingAreaListController::class)->middleware('session.token');

Route::middleware(['session.token', 'role:buyer'])->group(function (): void {
    Route::post('/checkout', CheckoutController::class);
    Route::get('/delivery-methods', GetDeliveryMethodsController::class);
    Route::get('/payment-methods', GetPaymentMethodsController::class);
    Route::get('/cart', GetCartController::class);
    Route::patch('/cart/delivery-method', UpdateCartDeliveryMethodController::class);
    Route::post('/cart/items', AddCartItemController::class);
    Route::patch('/cart/items/{id}', UpdateCartItemController::class);
    Route::delete('/cart/items/{id}', DeleteCartItemController::class);
    Route::get('/products/{id}', GetProductDetailController::class);
    Route::get('/products', GetProductListController::class);
    Route::get('/product-categories', GetProductCategoriesController::class);
    Route::get('/tenants/categories', GetTenantCategoriesController::class);
    Route::get('/tenants', GetBuyerTenantListController::class);
    Route::get('/users/transactions', GetUserTransactionHistoryController::class);
    Route::get('/users/transactions/{transactionId}', GetUserTransactionDetailController::class);
});

Route::middleware(['session.token', 'role:seller'])->prefix('seller')->group(function (): void {
    Route::get('/tenants', GetSellerTenantListController::class);
    Route::post('/tenants', CreateSellerTenantController::class);
    Route::get('/products', GetSellerProductListController::class);
    Route::post('/products', CreateSellerProductController::class);
    Route::get('/products/{id}', GetSellerProductDetailController::class);
    Route::put('/products/{id}', UpdateSellerProductController::class);
    Route::delete('/products/{id}', DeleteSellerProductController::class);
});
