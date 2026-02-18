<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\BrandController;

// --- Public/Customer Controllers ---
use App\Http\Controllers\Api\ProductController as PublicProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes (Pure Backend)
|--------------------------------------------------------------------------
*/

// ১. ইউজার ইনফো
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ২. পাবলিক রাউট (লগইন ছাড়াই এক্সেস করা যাবে)
Route::prefix('public')->group(function () {
    Route::get('/products', [PublicProductController::class, 'index']); // শপ পেজ
    Route::get('/products/{slug}', [PublicProductController::class, 'show']); // সিঙ্গেল প্রোডাক্ট
    Route::get('/sliders', [SliderController::class, 'getActiveSliders']); // হোম স্লাইডার
});

// ৩. পেমেন্ট গেটওয়ে কলব্যাক
Route::post('/payment/success', [PaymentController::class, 'success']);
Route::post('/payment/fail', [PaymentController::class, 'fail']);
Route::post('/payment/cancel', [PaymentController::class, 'cancel']);

// ৪. কাস্টমার রাউট (লগইন করা ইউজার)
Route::middleware(['auth:sanctum'])->group(function () {
    // অর্ডার ও চেকআউট
    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/invoice/{uuid}', [InvoiceController::class, 'show']);

    // অ্যাড্রেস বুক
    Route::apiResource('addresses', UserAddressController::class);

    // অ্যাক্টিভিটিস
    Route::post('/apply-coupon', [CouponController::class, 'apply']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    // এই লাইনটি যোগ করুন
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
});

// ৫. অ্যাডমিন রাউট (শুধুমাত্র অ্যাডমিনদের জন্য)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // --- Dashboard ---
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);

    // --- Product Management ---
    Route::get('/products', [ProductController::class, 'index']); // লিস্ট
    Route::post('/products', [ProductController::class, 'store']); // তৈরি
    Route::get('/products/{id}', [ProductController::class, 'edit']); // এডিট ডাটা (Vue ফর্মে দেখানোর জন্য)
    Route::post('/products/{id}/update', [ProductController::class, 'update']); // আপডেট (POST for Image Upload)
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); // ডিলিট

    // Product Helpers
    Route::post('/products/generate-seo', [ProductController::class, 'generateSeo']);
    Route::get('/list-categories', [ProductController::class, 'getCategories']); // ড্রপডাউন
    Route::get('/list-brands', [ProductController::class, 'getBrands']); // ড্রপডাউন

    // --- Other Resources ---
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class); // যদি ব্র্যান্ড কন্ট্রোলার থাকে
    Route::apiResource('sliders', SliderController::class);

    // --- Media & Settings ---
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
});
