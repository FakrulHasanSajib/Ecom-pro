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
/* নিচে PublicProductController হিসেবে সরাসরি Admin এর ProductController ব্যবহার করা হয়েছে।
  কারণ আপনার Api ফোল্ডারের কন্ট্রোলারটি 'media' টেবিল খুঁজছে যা আপনার ডাটাবেসে নেই।
*/
use App\Http\Controllers\Admin\ProductController as PublicProductController;
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

// ১. পাবলিক রাউট (লগইন বা টোকেন ছাড়াই এক্সেস করা যাবে)
// --------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);

Route::prefix('public')->group(function () {
    // এই রুটগুলো এখন সঠিকভাবে ডাটা রিটার্ন করবে
    Route::get('/products', [PublicProductController::class, 'index']);
    Route::get('/products/{slug}', [PublicProductController::class, 'show']);

    // হোমপেজে ক্যাটাগরি এবং স্লাইডার দেখানোর রুট
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/sliders', [SliderController::class, 'index']);
});

// ২. পেমেন্ট গেটওয়ে কলব্যাক
Route::post('/payment/success', [PaymentController::class, 'success']);
Route::post('/payment/fail', [PaymentController::class, 'fail']);
Route::post('/payment/cancel', [PaymentController::class, 'cancel']);


// ৩. কাস্টমার ও অথেন্টিকেটেড রাউট (টোকেন লাগবে)
// --------------------------------------------------------
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/invoice/{uuid}', [InvoiceController::class, 'show']);
    Route::apiResource('addresses', UserAddressController::class);
    Route::post('/apply-coupon', [CouponController::class, 'apply']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::post('/reviews', [ReviewController::class, 'store']);
});


// ৪. অ্যাডমিন রাউট (শুধুমাত্র অ্যাডমিন রোল এবং টোকেনসহ)
// --------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // --- Dashboard ---
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);

    // --- Product Management ---
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'edit']);
    Route::post('/products/{id}/update', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Product Helpers
    Route::post('/products/generate-seo', [ProductController::class, 'generateSeo']);
    Route::get('/list-categories', [ProductController::class, 'getCategories']);
    Route::get('/list-brands', [ProductController::class, 'getBrands']);

    // --- Other Resources ---
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('sliders', SliderController::class);

    // --- Media & Settings ---
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
});
