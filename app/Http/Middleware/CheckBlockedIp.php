<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlockedIp;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next)
    {
        // ইউজারের আইপি চেক করা হচ্ছে
        if (BlockedIp::where('ip_address', $request->ip())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access Denied: Your IP address has been blocked by the Administrator.'
            ], 403);
        }

        return $next($request);
    }
}
