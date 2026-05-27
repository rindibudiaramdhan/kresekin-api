<?php

use App\Http\Controllers\Api\AddCartItemController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ConfirmFinanceBuyerPaymentController;
use App\Http\Controllers\Api\CreateAgentCommissionWithdrawalController;
use App\Http\Controllers\Api\CreateFinanceCancellationReasonCategoryController;
use App\Http\Controllers\Api\CreateSellerProductController;
use App\Http\Controllers\Api\CreateSellerTenantController;
use App\Http\Controllers\Api\DeleteCartItemController;
use App\Http\Controllers\Api\DeleteFinanceCancellationReasonCategoryController;
use App\Http\Controllers\Api\DeleteSellerProductController;
use App\Http\Controllers\Api\DisburseFinanceTransactionController;
use App\Http\Controllers\Api\GetAgentCommissionWithdrawalListController;
use App\Http\Controllers\Api\GetAgentDashboardController;
use App\Http\Controllers\Api\GetAgentProfileController;
use App\Http\Controllers\Api\GetAgentSellerDetailController;
use App\Http\Controllers\Api\GetAgentSellerListController;
use App\Http\Controllers\Api\GetBuyerTenantListController;
use App\Http\Controllers\Api\GetCancellationReasonCategoryListController;
use App\Http\Controllers\Api\GetCartController;
use App\Http\Controllers\Api\GetDeliveryMethodsController;
use App\Http\Controllers\Api\GetFinanceCancellationReasonCategoryListController;
use App\Http\Controllers\Api\GetFinanceDashboardController;
use App\Http\Controllers\Api\GetFinanceTransactionDetailController;
use App\Http\Controllers\Api\GetFinanceTransactionListController;
use App\Http\Controllers\Api\GetHousingAreaListController;
use App\Http\Controllers\Api\GetIndonesiaRegionListController;
use App\Http\Controllers\Api\GetOrderTimeOptionsController;
use App\Http\Controllers\Api\GetPaymentMethodsController;
use App\Http\Controllers\Api\GetProductCategoriesController;
use App\Http\Controllers\Api\GetProductDetailController;
use App\Http\Controllers\Api\GetProductListController;
use App\Http\Controllers\Api\GetProductUnitsController;
use App\Http\Controllers\Api\GetSellerDashboardController;
use App\Http\Controllers\Api\GetSellerOrderDetailController;
use App\Http\Controllers\Api\GetSellerOrderListController;
use App\Http\Controllers\Api\GetSellerProductDetailController;
use App\Http\Controllers\Api\GetSellerProductListController;
use App\Http\Controllers\Api\GetSellerProductSummaryController;
use App\Http\Controllers\Api\GetSellerTenantListController;
use App\Http\Controllers\Api\GetTenantCategoriesController;
use App\Http\Controllers\Api\GetUserProfileController;
use App\Http\Controllers\Api\GetUserTransactionDetailController;
use App\Http\Controllers\Api\GetUserTransactionHistoryController;
use App\Http\Controllers\Api\LoginUserController;
use App\Http\Controllers\Api\LogoutUserController;
use App\Http\Controllers\Api\RefreshUserSessionController;
use App\Http\Controllers\Api\RegisterUserController;
use App\Http\Controllers\Api\RegisterUserDeviceController;
use App\Http\Controllers\Api\ResendOtpController;
use App\Http\Controllers\Api\UpdateAgentProfileController;
use App\Http\Controllers\Api\UpdateCartDeliveryMethodController;
use App\Http\Controllers\Api\UpdateCartItemController;
use App\Http\Controllers\Api\UpdateFinanceCancellationReasonCategoryController;
use App\Http\Controllers\Api\UpdateSellerOrderStatusController;
use App\Http\Controllers\Api\UpdateSellerProductController;
use App\Http\Controllers\Api\UpdateSellerProductStatusController;
use App\Http\Controllers\Api\UpdateUserProfileController;
use App\Http\Controllers\Api\UploadSellerProductImageController;
use App\Http\Controllers\Api\ValidatePromoCodeController;
use App\Http\Controllers\Api\VerifyOtpController;
use App\Models\User;
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
Route::post('/agent/login', LoginUserController::class)->defaults('role', User::ROLE_AGENT);
Route::post('/agent/register', RegisterUserController::class)->defaults('role', User::ROLE_AGENT);
Route::post('/agent/resend-otp', ResendOtpController::class)->defaults('role', User::ROLE_AGENT);
Route::post('/finance/login', LoginUserController::class)->defaults('role', User::ROLE_FINANCE);
Route::post('/finance/register', RegisterUserController::class)->defaults('role', User::ROLE_FINANCE);
Route::post('/finance/resend-otp', ResendOtpController::class)->defaults('role', User::ROLE_FINANCE);
Route::post('/users/{role}/login', LoginUserController::class)->whereIn('role', User::roles());
Route::post('/users/{role}/register', RegisterUserController::class)->whereIn('role', User::roles());
Route::post('/users/{role}/resend-otp', ResendOtpController::class)->whereIn('role', User::roles());
Route::post('/users/verify-otp', VerifyOtpController::class);
Route::post('/users/logout', LogoutUserController::class)->middleware('session.token');
Route::post('/users/devices', RegisterUserDeviceController::class)->middleware('session.token');
Route::post('/users/refresh-session', RefreshUserSessionController::class)->middleware('session.token');
Route::get('/indonesia/provinces', GetIndonesiaRegionListController::class)->defaults('level', 'provinces');
Route::get('/indonesia/regencies', GetIndonesiaRegionListController::class)->defaults('level', 'regencies');
Route::get('/indonesia/districts', GetIndonesiaRegionListController::class)->defaults('level', 'districts');
Route::get('/indonesia/villages', GetIndonesiaRegionListController::class)->defaults('level', 'villages');
Route::get('/users/profile', GetUserProfileController::class)->middleware('session.token');
Route::put('/users/profile', UpdateUserProfileController::class)->middleware('session.token');
Route::get('/housing-areas', GetHousingAreaListController::class)->middleware('session.token');
Route::get('/product-categories', GetProductCategoriesController::class)->middleware('session.token');
Route::get('/product-units', GetProductUnitsController::class)->middleware('session.token');

Route::middleware(['session.token', 'role:buyer'])->group(function (): void {
    Route::post('/checkout', CheckoutController::class);
    Route::post('/promo-codes/validate', ValidatePromoCodeController::class);
    Route::get('/delivery-methods', GetDeliveryMethodsController::class);
    Route::get('/order-time-options', GetOrderTimeOptionsController::class);
    Route::get('/payment-methods', GetPaymentMethodsController::class);
    Route::get('/cart', GetCartController::class);
    Route::patch('/cart/delivery-method', UpdateCartDeliveryMethodController::class);
    Route::post('/cart/items', AddCartItemController::class);
    Route::patch('/cart/items/{id}', UpdateCartItemController::class);
    Route::delete('/cart/items/{id}', DeleteCartItemController::class);
    Route::get('/products/{id}', GetProductDetailController::class);
    Route::get('/products', GetProductListController::class);
    Route::get('/tenants/categories', GetTenantCategoriesController::class);
    Route::get('/tenants', GetBuyerTenantListController::class);
    Route::get('/users/transactions', GetUserTransactionHistoryController::class);
    Route::get('/users/transactions/{transactionId}', GetUserTransactionDetailController::class);
    Route::get('/cancellation-reason-categories', GetCancellationReasonCategoryListController::class);
});

Route::middleware(['session.token', 'role:seller'])->prefix('seller')->group(function (): void {
    Route::get('/dashboard', GetSellerDashboardController::class);
    Route::get('/dashboard/profile', [GetSellerDashboardController::class, 'profile']);
    Route::get('/dashboard/revenue-today', [GetSellerDashboardController::class, 'todayRevenue']);
    Route::get('/dashboard/revenue-change', [GetSellerDashboardController::class, 'revenueChange']);
    Route::get('/dashboard/transactions-today', [GetSellerDashboardController::class, 'todayTransactions']);
    Route::get('/dashboard/orders-today/counts', [GetSellerDashboardController::class, 'todayOrderCounts']);
    Route::get('/dashboard/orders/new-preview', [GetSellerDashboardController::class, 'newOrderPreview']);
    Route::get('/dashboard/top-products-today', [GetSellerDashboardController::class, 'topProductsToday']);
    Route::get('/tenants', GetSellerTenantListController::class);
    Route::post('/tenants', CreateSellerTenantController::class);
    Route::get('/orders', GetSellerOrderListController::class);
    Route::get('/orders/{id}', GetSellerOrderDetailController::class);
    Route::patch('/orders/{id}/status', UpdateSellerOrderStatusController::class);
    Route::get('/products', GetSellerProductListController::class);
    Route::get('/products/summary', GetSellerProductSummaryController::class);
    Route::post('/product-images', UploadSellerProductImageController::class);
    Route::post('/products', CreateSellerProductController::class);
    Route::get('/products/{id}', GetSellerProductDetailController::class);
    Route::patch('/products/{id}/status', UpdateSellerProductStatusController::class);
    Route::put('/products/{id}', UpdateSellerProductController::class);
    Route::post('/products/{id}', UpdateSellerProductController::class);
    Route::delete('/products/{id}', DeleteSellerProductController::class);
});

Route::middleware(['session.token', 'role:agent'])->prefix('agent')->group(function (): void {
    Route::get('/dashboard', GetAgentDashboardController::class);
    Route::get('/sellers', GetAgentSellerListController::class);
    Route::get('/sellers/{sellerId}', GetAgentSellerDetailController::class);
    Route::get('/profile', GetAgentProfileController::class);
    Route::put('/profile', UpdateAgentProfileController::class);
    Route::get('/commission-withdrawals', GetAgentCommissionWithdrawalListController::class);
    Route::post('/commission-withdrawals', CreateAgentCommissionWithdrawalController::class);
});

Route::middleware(['session.token', 'role:finance'])->prefix('finance')->group(function (): void {
    Route::get('/dashboard', GetFinanceDashboardController::class);
    Route::get('/transactions', GetFinanceTransactionListController::class);
    Route::get('/transactions/{id}', GetFinanceTransactionDetailController::class);
    Route::patch('/transactions/{id}/confirm-buyer-payment', ConfirmFinanceBuyerPaymentController::class);
    Route::patch('/disbursements/{id}/disburse-to-seller', DisburseFinanceTransactionController::class);
    Route::get('/cancellation-reason-categories', GetFinanceCancellationReasonCategoryListController::class);
    Route::post('/cancellation-reason-categories', CreateFinanceCancellationReasonCategoryController::class);
    Route::put('/cancellation-reason-categories/{id}', UpdateFinanceCancellationReasonCategoryController::class);
    Route::delete('/cancellation-reason-categories/{id}', DeleteFinanceCancellationReasonCategoryController::class);
});
