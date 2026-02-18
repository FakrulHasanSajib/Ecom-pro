<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// কন্ট্রোলারগুলো ইমপোর্ট করা হলো
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\BrandController; // যদি ব্র্যান্ডের আলাদা কন্ট্রোলার থাকে

use App\Http\Controllers\Api\ProductController as PublicProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\InvoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ১. ইউজার ইনফো (লগইন করা থাকলে)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ২. পাবলিক রাউট (সবার জন্য উন্মুক্ত)
Route::prefix('public')->group(function () {
    Route::get('/products', [PublicProductController::class, 'index']);
    Route::get('/products/{slug}', [PublicProductController::class, 'show']);
    Route::get('/sliders', [SliderController::class, 'getActiveSliders']);
});

// ৩. পেমেন্ট কলব্যাক (CSRF প্রোটেকশন ছাড়া)
Route::post('/payment/success', [PaymentController::class, 'success']);
Route::post('/payment/fail', [PaymentController::class, 'fail']);
Route::post('/payment/cancel', [PaymentController::class, 'cancel']);

// ৪. কাস্টমার রাউট (শুধুমাত্র লগইন করা ইউজারদের জন্য)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/invoice/{uuid}', [InvoiceController::class, 'show']);
    Route::post('/checkout', [OrderController::class, 'store']);

    // অ্যাড্রেস বুক
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);

    // কুপন, উইশলিস্ট ও রিভিউ
    Route::post('/apply-coupon', [CouponController::class, 'apply']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::post('/reviews', [ReviewController::class, 'store']);
});

// ৫. অ্যাডমিন রাউট (অ্যাডমিন রোল এবং অথেন্টিকেশন প্রয়োজন)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // ক্যাটাগরি ও ব্র্যান্ডের ড্রপডাউন ডাটা (আপনার Vue এর সাথে মিল রেখে)
    Route::get('/list-categories', [ProductController::class, 'getCategories']);
    Route::get('/list-brands', [ProductController::class, 'getBrands']);

    // প্রোডাক্ট ম্যানেজমেন্ট
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/generate-seo', [ProductController::class, 'generateSeo']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('sliders', SliderController::class);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // মিডিয়া ম্যানেজার (গ্যালারি)
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);

    // সেটিংস ও ড্যাশবোর্ড
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);
});
