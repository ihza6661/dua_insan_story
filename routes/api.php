<?php

use App\Http\Controllers\Api\RajaOngkirController;
use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\Customer;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\PublicInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    // Auth routes with aggressive rate limiting (5 requests per minute)
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/guest-checkout', [CheckoutController::class, 'store']);

    Route::get('/rajaongkir/provinces', [RajaOngkirController::class, 'getProvinces']);
    Route::get('/rajaongkir/cities', [RajaOngkirController::class, 'getCities']);
    Route::get('/rajaongkir/subdistricts', [RajaOngkirController::class, 'getSubdistricts']);
    Route::post('/rajaongkir/cost', [RajaOngkirController::class, 'calculateCost']);
    Route::get('/config/payment', [SettingController::class, 'publicPaymentConfig']);

    Route::prefix('customer')->name('customer.v1.')->group(function () {
        Route::apiResource('products', Customer\ProductController::class)->only(['index', 'show']);
        Route::apiResource('product-categories', Customer\ProductCategoryController::class)->only(['index', 'show']);
        Route::apiResource('gallery-items', Customer\GalleryItemController::class)->only(['index', 'show']);

        // Public routes for invitation templates
        Route::get('/invitation-templates', [Customer\InvitationTemplateController::class, 'index']);
        Route::get('/invitation-templates/{slug}', [Customer\InvitationTemplateController::class, 'show']);

        // Public review routes
        Route::get('/products/{productId}/reviews', [Customer\ReviewController::class, 'index'])->middleware('throttle:30,1');
        Route::get('/products/{productId}/reviews/summary', [Customer\ReviewController::class, 'getRatingSummary'])->middleware('throttle:30,1');
        Route::get('/reviews/{review}', [Customer\ReviewController::class, 'show'])->middleware('throttle:30,1');
        Route::post('/reviews/{review}/helpful', [Customer\ReviewController::class, 'markAsHelpful'])->middleware('throttle:10,1');
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
        Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice']);
        Route::post('/orders/{order}/retry-payment', [OrderController::class, 'retryPayment']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'requestCancellation']);

        // Design Proof Routes (Customer)
        Route::get('/design-proofs', [Customer\DesignProofController::class, 'index']);
        Route::get('/orders/{order}/design-proofs', [Customer\DesignProofController::class, 'getByOrder']);
        Route::get('/design-proofs/{designProof}', [Customer\DesignProofController::class, 'show']);
        Route::post('/design-proofs/{designProof}/approve', [Customer\DesignProofController::class, 'approve']);
        Route::post('/design-proofs/{designProof}/request-revision', [Customer\DesignProofController::class, 'requestRevision']);
        Route::post('/design-proofs/{designProof}/reject', [Customer\DesignProofController::class, 'reject']);

        // Review Routes (Customer)
        Route::get('/reviews/my', [Customer\ReviewController::class, 'myReviews']);
        Route::get('/reviews/reviewable', [Customer\ReviewController::class, 'getReviewableProducts']);
        Route::post('/reviews', [Customer\ReviewController::class, 'store'])->middleware('throttle:5,60'); // 5 per hour
        Route::put('/reviews/{review}', [Customer\ReviewController::class, 'update'])->middleware('throttle:5,60'); // 5 per hour
        Route::delete('/reviews/{review}', [Customer\ReviewController::class, 'destroy']);

        // Promo Code Routes (Customer)
        Route::post('/promo-codes/validate', [\App\Http\Controllers\Api\V1\PromoCodeController::class, 'validate']);

        // Wishlist Routes (Customer)
        Route::get('/wishlist', [Customer\WishlistController::class, 'index']);
        Route::post('/wishlist', [Customer\WishlistController::class, 'store']);
        Route::delete('/wishlist/{id}', [Customer\WishlistController::class, 'destroy']);
        Route::get('/wishlist/check/{productId}', [Customer\WishlistController::class, 'check']);

        // Notification Routes (Customer)
        Route::get('/notifications', [Customer\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [Customer\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/mark-as-read', [Customer\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [Customer\NotificationController::class, 'markAllAsRead']);

        // Digital Invitation Routes (Customer - authenticated)
        Route::get('/digital-invitations', [Customer\DigitalInvitationController::class, 'index']);
        Route::get('/digital-invitations/{id}', [Customer\DigitalInvitationController::class, 'show']);
        Route::put('/digital-invitations/{id}/customize', [Customer\DigitalInvitationController::class, 'updateCustomization']);
        Route::post('/digital-invitations/{id}/photos', [Customer\DigitalInvitationController::class, 'uploadPhoto']);
        Route::delete('/digital-invitations/{id}/photos/{photoIndex}', [Customer\DigitalInvitationController::class, 'deletePhoto']);
        Route::post('/digital-invitations/{id}/activate', [Customer\DigitalInvitationController::class, 'activate']);
        Route::post('/digital-invitations/{id}/deactivate', [Customer\DigitalInvitationController::class, 'deactivate']);

        // Recommendation Routes (Customer - authenticated)
        Route::get('/recommendations/personalized', [Customer\RecommendationController::class, 'personalized']);
        Route::get('/recommendations/trending', [Customer\RecommendationController::class, 'trending']);
        Route::get('/recommendations/new-arrivals', [Customer\RecommendationController::class, 'newArrivals']);
    });

    // Public promo code routes
    Route::get('/promo-codes/active', [\App\Http\Controllers\Api\V1\PromoCodeController::class, 'active']);

    // Public wishlist share route
    Route::get('/wishlist/shared/{token}', [Customer\WishlistController::class, 'getByShareToken']);

    // Public recommendation routes
    Route::get('/recommendations/popular', [Customer\RecommendationController::class, 'popular']);
    Route::get('/recommendations/similar/{productId}', [Customer\RecommendationController::class, 'similar']);

    // Public invitation viewing
    Route::get('/invitations/{slug}', [PublicInvitationController::class, 'show']);
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
        Route::post('/orders/bulk-update-status', [AdminOrderController::class, 'bulkUpdateStatus'])->name('orders.bulkUpdateStatus');
        Route::post('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // Design Proof Routes (Admin)
        Route::get('/design-proofs', [Admin\DesignProofController::class, 'index'])->name('design-proofs.index');
        Route::post('/design-proofs', [Admin\DesignProofController::class, 'store'])->name('design-proofs.store');
        Route::get('/design-proofs/{designProof}', [Admin\DesignProofController::class, 'show'])->name('design-proofs.show');
        Route::delete('/design-proofs/{designProof}', [Admin\DesignProofController::class, 'destroy'])->name('design-proofs.destroy');
        Route::get('/order-items/{orderItem}/design-proofs', [Admin\DesignProofController::class, 'getByOrderItem'])->name('order-items.design-proofs');

        // Order Cancellation Routes (Admin)
        Route::get('/cancellation-requests', [Admin\OrderCancellationController::class, 'index'])->name('cancellation-requests.index');
        Route::get('/cancellation-requests/{cancellationRequest}', [Admin\OrderCancellationController::class, 'show'])->name('cancellation-requests.show');
        Route::get('/cancellation-requests/{cancellationRequest}/activity-logs', [Admin\OrderCancellationController::class, 'activityLogs'])->name('cancellation-requests.activity-logs');
        Route::post('/cancellation-requests/{cancellationRequest}/approve', [Admin\OrderCancellationController::class, 'approve'])->name('cancellation-requests.approve');
        Route::post('/cancellation-requests/{cancellationRequest}/reject', [Admin\OrderCancellationController::class, 'reject'])->name('cancellation-requests.reject');
        Route::get('/cancellation-requests/statistics/summary', [Admin\OrderCancellationController::class, 'statistics'])->name('cancellation-requests.statistics');

        // Review Routes (Admin)
        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/statistics', [Admin\ReviewController::class, 'statistics'])->name('reviews.statistics');
        Route::get('/reviews/{review}', [Admin\ReviewController::class, 'show'])->name('reviews.show');
        Route::post('/reviews/{review}/approve', [Admin\ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{review}/reject', [Admin\ReviewController::class, 'reject'])->name('reviews.reject');
        Route::post('/reviews/{review}/toggle-featured', [Admin\ReviewController::class, 'toggleFeatured'])->name('reviews.toggle-featured');
        Route::post('/reviews/{review}/response', [Admin\ReviewController::class, 'addResponse'])->name('reviews.add-response');
        Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::delete('/review-images/{reviewImage}', [Admin\ReviewController::class, 'deleteImage'])->name('review-images.destroy');

        // Promo Code Routes (Admin)
        Route::get('/promo-codes', [Admin\PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::post('/promo-codes', [Admin\PromoCodeController::class, 'store'])->name('promo-codes.store');
        Route::get('/promo-codes/statistics', [Admin\PromoCodeController::class, 'statistics'])->name('promo-codes.statistics');
        Route::get('/promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'show'])->name('promo-codes.show');
        Route::put('/promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'update'])->name('promo-codes.update');
        Route::delete('/promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
        Route::post('/promo-codes/{promoCode}/toggle-status', [Admin\PromoCodeController::class, 'toggleStatus'])->name('promo-codes.toggle-status');
    });
