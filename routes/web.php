<?php

use App\Http\Controllers\Web\SellerAuthController;
use App\Http\Controllers\Web\SellerDashboardController;
use App\Http\Controllers\Web\SellerProductController;
use App\Http\Controllers\Web\AgentAuthController;
use App\Http\Controllers\Web\AgentDashboardController;
use App\Http\Controllers\Web\AgentRegistrationController;
use App\Http\Controllers\Web\AgentTenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [SellerAuthController::class, 'create'])->name('login');

Route::prefix('agent')->name('agent.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AgentAuthController::class, 'create'])->name('login');
        Route::post('/login', [AgentAuthController::class, 'store'])->name('login.store');
        Route::get('/register', [AgentRegistrationController::class, 'create'])->name('register');
        Route::post('/register', [AgentRegistrationController::class, 'store'])->name('register.store');
    });

    Route::middleware(['auth', 'role:agent'])->group(function (): void {
        Route::post('/logout', [AgentAuthController::class, 'destroy'])->name('logout');
        Route::get('/', AgentDashboardController::class)->name('dashboard');
        Route::get('/tenants', [AgentTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [AgentTenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [AgentTenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{id}/edit', [AgentTenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{id}', [AgentTenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{id}', [AgentTenantController::class, 'destroy'])->name('tenants.destroy');
    });
});

Route::prefix('seller')->name('seller.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SellerAuthController::class, 'create'])->name('login');
        Route::post('/login', [SellerAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'role:seller'])->group(function (): void {
        Route::post('/logout', [SellerAuthController::class, 'destroy'])->name('logout');
        Route::get('/', SellerDashboardController::class)->name('dashboard');

        Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
        Route::get('/products/{id}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [SellerProductController::class, 'destroy'])->name('products.destroy');
    });
});
