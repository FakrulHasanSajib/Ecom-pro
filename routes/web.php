<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\ProductController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ১. পাবলিক হোমপেজ (Welcome Screen)
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ২. কাস্টমার/ইউজার ড্যাশবোর্ড (লগইন করার পর কাস্টমার যা দেখবে)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ৩. প্রোফাইল ম্যানেজমেন্ট (লারাভেল ব্রিজের ডিফল্ট)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Inertia Render)
|--------------------------------------------------------------------------
*/
// --- ADMIN PANEL ROUTES ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // ড্যাশবোর্ড
    Route::get('/dashboard', function () {
        $stats = app(DashboardController::class)->index()->getData();
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    })->name('dashboard');

    // ক্যাটাগরি পেজ (GET - পেজ দেখার জন্য)
    Route::get('/categories', function () {
        return Inertia::render('Admin/Categories/Index');
    })->name('categories.index');
    Route::get('/get-categories', [CategoryController::class, 'index'])->name('categories.data');

    // ক্যাটাগরি সেভ করা (POST - নতুন ডাটা জমা দেওয়ার জন্য) - এটিই মিসিং ছিল
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    // প্রোডাক্ট পেজ
    Route::get('/products', function () {
        return Inertia::render('Admin/Products/Create');
    })->name('products.create');
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');

    // সেটিংস
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});

// অথেনটিকেশন রাউটগুলো (Login, Register, Logout)
require __DIR__.'/auth.php';
