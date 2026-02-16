<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Api\ProductController as PublicProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SliderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- Public Routes ---
Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);
Route::get('/sliders', [SliderController::class, 'getActiveSliders']); // Public slider fetch

// Payment Callbacks (CSRF excluded in bootstrap/app.php)
Route::post('/payment/success', [PaymentController::class, 'success']);
Route::post('/payment/fail', [PaymentController::class, 'fail']);
Route::post('/payment/cancel', [PaymentController::class, 'cancel']);
// Invoice Data Route (Auth Required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/invoice/{uuid}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
});

// --- Customer Routes (Login Required) ---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/checkout', [OrderController::class, 'store']);

    // Address
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);

    // Coupon
    Route::post('/apply-coupon', [CouponController::class, 'apply']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

    // Review
    Route::post('/reviews', [ReviewController::class, 'store']);
});

// --- Admin Routes (Role: Admin) ---
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('sliders', SliderController::class); // Admin slider CRUD

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // Dashboard
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);
});
