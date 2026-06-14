<?php

use App\Http\Controllers\Web\SellerAuthController;
use App\Http\Controllers\Web\SellerDashboardController;
use App\Http\Controllers\Web\SellerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect('/');
})->name('login');

Route::view('/agent/dashboard', 'dashboard.blank', [
    'title' => 'Agent Dashboard',
    'headerTitle' => 'Agent Views',
    'userName' => 'Agent Administrator',
    'role' => 'agent',
    'active' => 'dashboard',
])->name('agent.dashboard');
Route::view('/finance/dashboard', 'dashboard.blank', [
    'title' => 'Finance Dashboard',
    'headerTitle' => 'Finance Views',
    'userName' => 'Finance Administrator',
    'role' => 'finance',
    'active' => 'dashboard',
])->name('finance.dashboard');

Route::prefix('seller')->name('seller.')->group(function (): void {
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
