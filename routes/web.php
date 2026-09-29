<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\BusinessResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderVoidController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReceiptSettingController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('posetivacsilogpos')->group(function () {
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/pos', PosController::class)->name('pos');
    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{productKey}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{productKey}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/checkout', [CartController::class, 'checkout'])->name('checkout');
    Route::get('/kitchen', KitchenController::class)->name('kitchen');
    Route::get('/kitchen/archive', [KitchenController::class, 'archive'])->name('kitchen.archive');
    Route::patch('/kitchen/{order}/status', [KitchenController::class, 'updateStatus'])->name('kitchen.status');
    Route::post('/orders/{order}/void', OrderVoidController::class)->name('orders.void');

    Route::prefix('superadmin')->name('superadmin.')->middleware('superadmin')->group(function () {
        Route::get('/', SuperAdminController::class)->name('dashboard');
        Route::get('/reset-business-data', [BusinessResetController::class, 'index'])->name('reset.index');
        Route::post('/reset-business-data', [BusinessResetController::class, 'reset'])->name('reset.run');
        Route::get('/voided-orders', [OrderVoidController::class, 'index'])->name('voided-orders');
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
        Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
        Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->name('menu.update');
        Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy'])->name('menu.destroy');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::get('/cash-flow', [CashFlowController::class, 'index'])->name('cash-flow');
        Route::post('/cash-flow/expenses', [CashFlowController::class, 'storeExpense'])->name('cash-flow.expenses.store');
        Route::get('/receipt-settings', [ReceiptSettingController::class, 'edit'])->name('receipt-settings');
        Route::put('/receipt-settings', [ReceiptSettingController::class, 'update'])->name('receipt-settings.update');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::post('/inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::post('/inventory/recipes', [InventoryController::class, 'storeRecipe'])->name('inventory.recipes.store');
        Route::delete('/inventory/recipes/{recipe}', [InventoryController::class, 'destroyRecipe'])->name('inventory.recipes.destroy');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
});
