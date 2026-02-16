<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'cart_total' => 'required|numeric'
        ]);

        $code = $request->code;
        $total = $request->cart_total;

        // ১. কুপন খোঁজা
        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();

        // ২. ভ্যালিডেশন চেক
        if (!$coupon) {
            return response()->json(['message' => 'Invalid coupon code.'], 404);
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            return response()->json(['message' => 'Coupon has expired.'], 400);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit reached.'], 400);
        }

        if ($coupon->min_spend && $total < $coupon->min_spend) {
            return response()->json(['message' => 'Minimum spend of ' . $coupon->min_spend . ' required.'], 400);
        }

        // ৩. ডিসকাউন্ট ক্যালকুলেশন
        $discountAmount = 0;
        if ($coupon->type === 'fixed') {
            $discountAmount = $coupon->value;
        } elseif ($coupon->type === 'percent') {
            $discountAmount = ($total * $coupon->value) / 100;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully!',
            'discount_amount' => $discountAmount,
            'coupon_code' => $code
        ]);
    }
}
