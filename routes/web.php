<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardWebController;
use App\Http\Controllers\Admin\AdminOrderWebController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminStockWebController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\ShopController;
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

// Public Auth Routes (Guest only)
Route::middleware('guest')->group(function () {
    // Customer Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Admin Auth
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });
});

// Customer Routes (Authenticated + Customer role)
Route::middleware(['auth', 'customer'])->group(function () {
    // Home/Shop
    Route::get('/', [ShopController::class, 'index'])->name('home');
    Route::get('/products/{slug}', [ShopController::class, 'show'])->name('products.show');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/order/success/{order_number}', [CheckoutController::class, 'success'])->name('order.success');

    // Orders
    Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/my-orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/my-orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Admin Protected Routes (Authenticated + Admin role)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardWebController::class, 'index'])->name('dashboard');

    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{id}/add-stock', [AdminProductController::class, 'addStock'])->name('products.add-stock');

    // Orders
    Route::get('/orders', [AdminOrderWebController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [AdminOrderWebController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}/status', [AdminOrderWebController::class, 'updateStatus'])->name('orders.update-status');

    // Stock History
    Route::get('/stock-history', [AdminStockWebController::class, 'index'])->name('stock-history.index');

    // Logout
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});
