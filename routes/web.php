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

Route::view('/agent/dashboard', 'dashboard.index', [
    'title' => 'Agent Dashboard',
    'headerTitle' => 'Agent Views',
    'userName' => 'Agent Administrator',
    'role' => 'agent',
    'active' => 'dashboard',
])->name('agent.dashboard');
Route::view('/agent/finance', 'dashboard.empty', [
    'title' => 'Agent Finance',
    'headerTitle' => 'Agent Views',
    'userName' => 'Agent Administrator',
    'role' => 'agent',
    'active' => 'finance',
])->name('agent.finance');
Route::view('/finance/dashboard', 'dashboard.index', [
    'title' => 'Finance Dashboard',
    'headerTitle' => 'Finance Views',
    'userName' => 'Finance Administrator',
    'role' => 'finance',
    'active' => 'dashboard',
])->name('finance.dashboard');
Route::view('/finance/finance', 'dashboard.empty', [
    'title' => 'Finance',
    'headerTitle' => 'Finance Views',
    'userName' => 'Finance Administrator',
    'role' => 'finance',
    'active' => 'finance',
])->name('finance.finance');

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
