<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ১. Stateful API (লারাভেল ১২ এ 401 Unauthorized ফিক্স করার জন্য মাস্ট)
        // এটি আপনার api.php রাউটগুলোতে ব্রাউজার সেশন এবং কুকি ব্যবহারের অনুমতি দেবে
        $middleware->statefulApi();

        // ২. Web Middleware (Inertia setup)
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // ৩. CSRF Exclude (SSLCommerz বা বাইরের পেমেন্ট গেটওয়ের জন্য)
        $middleware->validateCsrfTokens(except: [
            'api/payment/*',
            'payment/*', // যদি এপিআই প্রিফিক্স ছাড়া থাকে
        ]);

        // ৪. Middleware Alias
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
