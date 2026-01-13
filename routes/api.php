<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Admin routes
    Route::middleware('admin')->group(function () {
        // Products
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::post('/admin/products/{id}/add-stock', [ProductController::class, 'addStock']);
        Route::put('/admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);

        // Orders
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
        Route::put('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        // Stock History
        Route::get('/admin/stock-history', [StockController::class, 'index']);

        // Dashboard
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    });

    // Customer routes (Cart & Checkout)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/checkout', [OrderController::class, 'checkout']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // Payment (using Sanctum token auth)
    Route::post('/payment/snap-token', [MidtransController::class, 'generateSnapToken']);
    Route::post('/payment/check-status', [MidtransController::class, 'checkPaymentStatus']);
});

// Midtrans Webhook (public, excluded from CSRF, must be accessible from internet)
Route::match(['get', 'post'], '/payment/midtrans-webhook', [MidtransController::class, 'handleNotification']);
