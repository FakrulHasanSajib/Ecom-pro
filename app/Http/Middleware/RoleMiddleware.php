<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // ১. চেক করা ইউজার লগইন করা আছে কি না এবং তার রোল আছে কি না
        // এখানে ধরে নেওয়া হয়েছে আপনার 'users' টেবিলে 'role' নামে একটি কলাম আছে (যেমন: 'admin' বা 'customer')
        if (!$request->user() || $request->user()->role !== $role) {

            // যদি এটি API রিকোয়েস্ট হয়, তবে JSON রেসপন্স দেব
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized! You do not have the required role.'
                ], 403);
            }

            // যদি এটি সাধারণ ওয়েব রিকোয়েস্ট হয়
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
