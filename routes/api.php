<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\Customer;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\RajaOngkirController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;



Route::post('/v1/webhook/midtrans', [WebhookController::class, 'midtrans']);
Route::post('/v1/checkout', [CheckoutController::class, 'store']);
Route::post('/v1/shipping-cost', [CheckoutController::class, 'calculateShippingCost'])
    ->middleware('auth.optional');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Semua rute API berada di dalam prefix v1
Route::prefix('v1')->group(function () {

    // --- Rute Publik (Tidak Perlu Login) ---
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/guest-checkout', [CheckoutController::class, 'store']);

    Route::get('/rajaongkir/provinces', [RajaOngkirController::class, 'getProvinces']);
    Route::get('/rajaongkir/cities', [RajaOngkirController::class, 'getCities']);
    Route::get('/rajaongkir/subdistricts', [RajaOngkirController::class, 'getSubdistricts']);
    Route::post('/rajaongkir/cost', [RajaOngkirController::class, 'calculateCost']);

    Route::prefix('customer')->name('customer.v1.')->group(function () {
        Route::apiResource('products', Customer\ProductController::class)->only(['index', 'show']);
        Route::apiResource('product-categories', Customer\ProductCategoryController::class)->only(['index', 'show']);
        Route::apiResource('gallery-items', Customer\GalleryItemController::class)->only(['index', 'show']);
    });

    // --- Rute dengan Autentikasi Opsional (Untuk Keranjang Tamu) ---
    Route::middleware('auth.optional')->group(function () {
        Route::get('/cart', [CartController::class, 'show']);
        Route::delete('/cart', [CartController::class, 'clear']);
        Route::post('/cart/items', [CartItemController::class, 'store']);
        Route::patch('/cart/items/{cartItem}', [CartItemController::class, 'update']);
        Route::delete('/cart/items/{cartItem}', [CartItemController::class, 'destroy']);
    });

    // --- Rute Terproteksi (Wajib Login) ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/checkout', [CheckoutController::class, 'store']);
        Route::post('/orders/{order}/pay-final', [CheckoutController::class, 'initiateFinalPayment']);

        // Rute untuk mengelola profil pengguna
        Route::get('/user', [ProfileController::class, 'show']);
        Route::put('/user', [ProfileController::class, 'update']);
        Route::post('/user/change-password', [ProfileController::class, 'changePassword']);

        // Rute untuk mengelola pesanan pengguna
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/retry-payment', [OrderController::class, 'retryPayment']);
    });
});

// --- Endpoint untuk Administrator (Admin) ---
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('api.v1.admin.')
    ->group(function () {
        Route::apiResource('users', Admin\UserController::class);
        Route::apiResource('product-categories', Admin\ProductCategoryController::class);
        Route::apiResource('products', Admin\ProductController::class);
        Route::post('variants/{variant}/images', [Admin\ProductImageController::class, 'store'])->name('variants.images.store');
        Route::delete('images/{image}', [Admin\ProductImageController::class, 'destroy'])->name('images.destroy');
        Route::apiResource('add-ons', Admin\AddOnController::class);
        Route::apiResource('attributes', Admin\AttributeController::class);
        Route::apiResource('attributes.values', Admin\AttributeValueController::class)->shallow();
        Route::apiResource('products.variants', Admin\ProductVariantController::class)->shallow();
        Route::post('products/{product}/add-ons', [Admin\ProductAddOnController::class, 'store'])->name('products.addons.store');
        Route::delete('products/{product}/add-ons/{add_on}', [Admin\ProductAddOnController::class, 'destroy'])->name('products.addons.destroy');
        Route::apiResource('gallery-items', Admin\GalleryItemController::class);
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    });

Route::post('/midtrans/webhook', [WebhookController::class, 'midtrans']);