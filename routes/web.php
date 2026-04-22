<?php

use App\Http\Controllers\Web\SellerAuthController;
use App\Http\Controllers\Web\SellerDashboardController;
use App\Http\Controllers\Web\SellerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [SellerAuthController::class, 'create'])->name('login');

Route::prefix('seller')->name('seller.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SellerAuthController::class, 'create'])->name('login');
        Route::post('/login', [SellerAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function (): void {
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
