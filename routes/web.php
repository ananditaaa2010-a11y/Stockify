<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductAttributeDefinitionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::view('/', 'landing')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegistration'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/orders/{order}/payment-proof', [CommerceController::class, 'paymentProof'])
        ->whereNumber('order')
        ->middleware('role:Admin,Customer')
        ->name('orders.payment-proof');

    Route::middleware('role:Customer')->group(function () {
        Route::get('/shop', [CommerceController::class, 'shop'])->name('shop.index');
        Route::get('/shop/{product}', [CommerceController::class, 'product'])->whereNumber('product')->name('shop.product');
        Route::post('/shop/{product}/purchase', [CommerceController::class, 'purchase'])->whereNumber('product')->name('shop.purchase');
        Route::get('/purchases', [CommerceController::class, 'purchases'])->name('purchases.index');
        Route::get('/refund-history', [CommerceController::class, 'refunds'])->name('refunds.index');
        Route::post('/order-items/{item}/refund', [CommerceController::class, 'requestRefund'])->whereNumber('item')->name('refunds.store');
        Route::get('/feedback', [CommerceController::class, 'feedback'])->name('feedback.index');
        Route::post('/order-items/{item}/feedback', [CommerceController::class, 'saveFeedback'])->whereNumber('item')->name('feedback.store');
    });

    Route::middleware('role:Admin')->group(function () {
        Route::get('/sales', [CommerceController::class, 'adminOrders'])->name('sales.index');
        Route::post('/sales/{order}/{decision}', [CommerceController::class, 'reviewPayment'])
            ->whereNumber('order')
            ->whereIn('decision', ['approve', 'reject'])
            ->name('sales.review');
        Route::get('/refunds', [CommerceController::class, 'adminRefunds'])->name('refunds.admin');
        Route::post('/refunds/{refund}/{decision}', [CommerceController::class, 'reviewRefund'])
            ->whereNumber('refund')
            ->whereIn('decision', ['approve', 'reject'])
            ->name('refunds.review');
    });

    Route::middleware('role:Admin,Manajer Gudang,Staff Gudang')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    });

    Route::middleware('role:Admin,Manajer Gudang')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::middleware('role:Admin,Manajer Gudang,Staff Gudang')->group(function () {
        Route::get('/transactions', [StockTransactionController::class, 'index'])->name('transactions.index');
    });

    Route::middleware('role:Admin')->group(function () {
        Route::get('/products/import-template', [ProductController::class, 'importTemplate'])->name('products.import-template');
        Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/product-attributes', [ProductAttributeDefinitionController::class, 'index'])->name('attribute-definitions.index');
        Route::post('/product-attributes', [ProductAttributeDefinitionController::class, 'store'])->name('attribute-definitions.store');
        Route::put('/product-attributes/{definition}', [ProductAttributeDefinitionController::class, 'update'])->name('attribute-definitions.update');
        Route::delete('/product-attributes/{definition}', [ProductAttributeDefinitionController::class, 'destroy'])->name('attribute-definitions.destroy');

        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/transactions/{transaction}/reject', [StockTransactionController::class, 'reject'])->name('transactions.reject');
    });

    Route::middleware('role:Admin,Manajer Gudang,Staff Gudang')->group(function () {
        Route::post('/transactions', [StockTransactionController::class, 'store'])->name('transactions.store');
        Route::post('/stock-opnames', [StockTransactionController::class, 'storeOpname'])->name('opnames.store');
    });

    Route::post('/transactions/{transaction}/confirm', [StockTransactionController::class, 'confirm'])
        ->middleware('role:Staff Gudang')
        ->name('transactions.confirm');
    Route::post('/transactions/{transaction}/prepare', [StockTransactionController::class, 'markPrepared'])
        ->middleware('role:Staff Gudang')
        ->name('transactions.prepare');
});