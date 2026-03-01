<?php

use Illuminate\Support\Facades\Route;

// সকল রিকোয়েস্ট Vue Router এর কাছে পাঠিয়ে দেওয়া হবে (SPA Mode)
Route::get('/{any}', function () {
    return view('app'); // resources/views/app.blade.php ফাইল লোড হবে
})->where('any', '.*');




