<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\FraudCheckService;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;
    protected $fraudCheckService;
    protected $trackingService;

    // ১. কনস্ট্রাক্টরে ডিপেন্ডেন্সি ইনজেকশন (Best Practice)
    public function __construct(
        OrderService $orderService,
        FraudCheckService $fraudCheckService,
        TrackingService $trackingService
    ) {
        $this->orderService = $orderService;
        $this->fraudCheckService = $fraudCheckService;
        $this->trackingService = $trackingService;
    }

    public function store(Request $request)
    {
        // ২. ডাটা ভ্যালিডেশন
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'shipping_charge' => 'nullable|numeric',
            'tracking_info' => 'nullable|array', // FB/Google tracking data (fbp, fbc)
            'utm_source' => 'nullable|string'
        ]);

        $user = Auth::user();

        try {
            // ৩. ফ্রড চেক (অর্ডার তৈরি করার আগেই চেক করতে হবে)
            // নোট: checkOrderRisk ফাংশনটি অ্যারে রিটার্ন করবে ['is_fraud' => bool, 'reasons' => []]
            $fraudCheck = $this->fraudCheckService->checkOrderRisk($user, $request->all());

            if ($fraudCheck['is_fraud']) {
                // ফ্রড ডিটেক্ট হলে লগ রাখা এবং অর্ডার রিজেক্ট করা
                Log::warning('Fraud Order Blocked', ['user_id' => $user->id, 'ip' => $request->ip()]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Order rejected due to security risk.',
                    'reasons' => $fraudCheck['reasons']
                ], 403);
            }

            // ৪. অর্ডার সার্ভিস কল করে অর্ডার তৈরি করা
            $order = $this->orderService->createOrder($user, $validated);

            // ৫. ট্র্যাকিং ডাটা পাঠানো (Facebook CAPI / Google)
            // এটি একটি আলাদা try-catch ব্লকে রাখা হলো যাতে ট্র্যাকিং ফেইল করলেও অর্ডার যেন বাতিল না হয়
            try {
                $this->trackingService->sendPurchaseEvent($order);
            } catch (\Exception $e) {
                Log::error('Tracking Failed for Order: ' . $order->order_number . ' - ' . $e->getMessage());
            }

            // ৬. সফল রেসপন্স
            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully!',
                'data' => [
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                    'status' => $order->status
                ]
            ], 201);

        } catch (\Exception $e) {
            // যেকোনো এরর (স্টক নেই বা ডাটাবেস এরর) হলে এখানে আসবে
            return response()->json([
                'status' => 'error',
                'message' => 'Order failed to place.',
                'error_detail' => $e->getMessage()
            ], 400);
        }
    }
}
