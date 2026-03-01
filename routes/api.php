<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BannerCategoryController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrderStatusController;


// --- Public/Customer Controllers ---
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

// ১. পাবলিক রাউট (লগইন বা টোকেন ছাড়াই এক্সেস করা যাবে)
// --------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::prefix('public')->group(function () {
    Route::get('/products', [PublicProductController::class, 'index']);
    
    // 🔥 featured রাউটটি {slug} এর উপরে রাখতে হবে, তা না হলে 404 আসবে!
    Route::get('/products/featured', [PublicProductController::class, 'getFeatured']); 
    Route::get('/products/{slug}', [PublicProductController::class, 'show']);

    // হোমপেজে ক্যাটাগরি, স্লাইডার এবং সেটিংস দেখানোর রুট
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/sliders', [SliderController::class, 'getActiveSliders']); 
    Route::get('/settings', [SettingController::class, 'index']); // 👈 এটি আমাদের পাবলিক সেটিংস

    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/invoice/{order_number}', [InvoiceController::class, 'show']);
});s

// ২. পেমেন্ট গেটওয়ে কলব্যাক
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

    // ড্যাশবোর্ডে অর্ডার লিস্ট দেখানোর জন্য
    Route::get('/orders', [OrderController::class, 'index']);

    Route::get('/invoice/{uuid}', [InvoiceController::class, 'show']);
    Route::apiResource('addresses', UserAddressController::class);
    Route::post('/apply-coupon', [CouponController::class, 'apply']);

    // উইশলিস্টের রাউট
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

    // Sliders
    Route::get('/sliders', [SliderController::class, 'index']);
    Route::post('/sliders', [SliderController::class, 'store']);
    Route::post('/sliders/{id}/toggle-status', [SliderController::class, 'toggleStatus']);
    Route::delete('/sliders/{id}', [SliderController::class, 'destroy']);

    // --- Media & Settings ---
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // Banner Categories
    Route::get('/banner-categories', [BannerCategoryController::class, 'index']);
    Route::get('/banner-categories/active', [BannerCategoryController::class, 'getActive']);
    Route::post('/banner-categories', [BannerCategoryController::class, 'store']);
    Route::post('/banner-categories/{id}/toggle-status', [BannerCategoryController::class, 'toggleStatus']);
    Route::delete('/banner-categories/{id}', [BannerCategoryController::class, 'destroy']);

    // Brands
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/list-brands', [BrandController::class, 'getActive']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::post('/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus']);
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

    // Colors
    Route::get('/colors', [ColorController::class, 'index']);
    Route::get('/list-colors', [ColorController::class, 'getActive']);
    Route::post('/colors', [ColorController::class, 'store']);
    Route::post('/colors/{id}/toggle-status', [ColorController::class, 'toggleStatus']);
    Route::delete('/colors/{id}', [ColorController::class, 'destroy']);

    // Sizes
    Route::get('/sizes', [SizeController::class, 'index']);
    Route::get('/list-sizes', [SizeController::class, 'getActive']);
    Route::post('/sizes', [SizeController::class, 'store']);
    Route::post('/sizes/{id}/toggle-status', [SizeController::class, 'toggleStatus']);
    Route::delete('/sizes/{id}', [SizeController::class, 'destroy']);


    // --- Order Management (Admin) ---
    // 🔥 ১. প্রথমে Static Routes এবং Bulk Action-গুলো রাখতে হবে
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::post('/orders', [AdminOrderController::class, 'store']);
    Route::get('/orders/export', [AdminOrderController::class, 'export']);
    Route::post('/orders/print', [AdminOrderController::class, 'print']);
    Route::post('/orders/bulk-status', [AdminOrderController::class, 'bulkStatus']);
    Route::post('/orders/bulk-assign', [AdminOrderController::class, 'bulkAssign']);

    // 🔥 ২. এরপর Dynamic Routes (যেগুলোতে {id} আছে, সেগুলো সবসময় নিচে থাকবে)
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{id}', [AdminOrderController::class, 'update']);
    Route::delete('/orders/{id}', [AdminOrderController::class, 'destroy']);
    Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

    // --- Order Status Settings ---
    Route::get('/order-statuses', [OrderStatusController::class, 'index']);
    Route::post('/order-statuses', [OrderStatusController::class, 'store']);
    Route::delete('/order-statuses/{id}', [OrderStatusController::class, 'destroy']);
});
