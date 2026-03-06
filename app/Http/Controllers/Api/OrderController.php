<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\FraudCheckService;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\Setting; // 🔥 যুক্ত করা হলো
use App\Models\Product; // 🔥 যুক্ত করা হলো

class OrderController extends Controller
{
    protected $orderService;
    protected $fraudCheckService;
    protected $trackingService;

    public function __construct(
        OrderService $orderService,
        FraudCheckService $fraudCheckService,
        TrackingService $trackingService
    ) {
        $this->orderService = $orderService;
        $this->fraudCheckService = $fraudCheckService;
        $this->trackingService = $trackingService;
    }

    public function index(Request $request)
    {
        try {
            $orders = \App\Models\Order::where('user_id', $request->user()->id)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            Log::error('Order Fetch Exception: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch orders.',
                'data' => []
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'shipping_charge' => 'required|numeric',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'area' => 'required|string',
            'total_amount' => 'required|numeric'
        ]);

        $user = Auth::user();

        // 🔥 ১. ডাইনামিক ডেলিভারি চার্জ ক্যালকুলেশন (ডাটাবেস থেকে)
        $insideDhakaCharge = Setting::where('key', 'shipping_inside_dhaka')->value('value') ?? 70;
        $outsideDhakaCharge = Setting::where('key', 'shipping_outside_dhaka')->value('value') ?? 130;

        $actualShippingCharge = ($validated['area'] === 'inside_dhaka') ? (int) $insideDhakaCharge : (int) $outsideDhakaCharge;

        // 🔥 ২. প্রোডাক্টের আসল দাম চেক করে সিকিউর টোটাল বিল তৈরি করা
        $actualSubTotal = 0;
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $price = $product->sale_price ?? $product->base_price;
                $actualSubTotal += ($price * $item['quantity']);
            }
        }
        $actualTotalAmount = $actualSubTotal + $actualShippingCharge;

        // ফ্রন্টএন্ড থেকে আসা ডাটা ওভাররাইড (Override) করা হলো
        $validated['shipping_charge'] = $actualShippingCharge;
        $validated['total_amount'] = $actualTotalAmount;

        try {
            // ৩. Fraud Check
            if ($user) {
                $fraudCheck = $this->fraudCheckService->checkOrderRisk($user, $request->all());
                if ($fraudCheck['is_fraud']) {
                    Log::warning('Fraud Order Blocked', ['user_id' => $user->id, 'ip' => $request->ip()]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order rejected due to security risk.',
                        'reasons' => $fraudCheck['reasons']
                    ], 403);
                }
            }

            // ৪. Order তৈরি করা (OrderService এ সিকিউর ডাটা পাঠানো হলো)
            $order = $this->orderService->createOrder($user, $validated);

            // Tracking Data পাঠানো
            try {
                $this->trackingService->sendPurchaseEvent($order);
            } catch (\Exception $e) {
                Log::error('Tracking Failed for Order: ' . $order->order_number);
            }

            $orderTotal = $order->grand_total ?? $actualTotalAmount;

            // ৫. Payment Method SSLCommerz হলে
            if ($validated['payment_method'] === 'sslcommerz') {

                // Transaction তৈরি করা
                Transaction::create([
                    'transaction_id' => $order->order_number,
                    'order_id' => $order->id,
                    'amount' => $orderTotal,
                    'status' => 'pending'
                ]);

                // Dynamic Credentials Logic
                $store_id = get_setting('sslcz_store_id') ?: env('SSLCZ_STORE_ID');
                $store_password = get_setting('sslcz_store_password') ?: env('SSLCZ_STORE_PASSWORD');

                $is_sandbox = get_setting('sslcz_testmode');
                if($is_sandbox === null) {
                    $is_sandbox = env('SSLCZ_TESTMODE', true);
                } else {
                    $is_sandbox = ($is_sandbox == 'true' || $is_sandbox == 1);
                }

                $apiUrl = $is_sandbox
                    ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php"
                    : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

                // SSLCommerz এ ডাটা পাঠানো
                $post_data = array();
                $post_data['store_id'] = $store_id;
                $post_data['store_passwd'] = $store_password;
                $post_data['total_amount'] = $orderTotal;
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = $order->order_number;

                // Callback URLs
                $frontendUrl = env('APP_URL', 'http://127.0.0.1:8000');
                $post_data['success_url'] = $frontendUrl . '/api/payment/success';
                $post_data['fail_url'] = $frontendUrl . '/api/payment/fail';
                $post_data['cancel_url'] = $frontendUrl . '/api/payment/cancel';

                // Customer Info
                $post_data['cus_name'] = $validated['name'];
                $post_data['cus_phone'] = $validated['phone'];
                $post_data['cus_email'] = "customer@example.com";
                $post_data['cus_add1'] = $validated['address'];
                $post_data['cus_city'] = "Dhaka";
                $post_data['cus_country'] = "Bangladesh";
                $post_data['shipping_method'] = "NO";
                $post_data['product_name'] = "E-Shop Products";
                $post_data['product_category'] = "General";
                $post_data['product_profile'] = "general";

                // API কল
                $response = Http::asForm()->post($apiUrl, $post_data);
                $sslcz = $response->json();

                if (isset($sslcz['GatewayPageURL'])) {
                    return response()->json([
                        'status' => 'success',
                        'payment_url' => $sslcz['GatewayPageURL']
                    ]);
                } else {
                    Log::error('SSLCommerz Error: ' . json_encode($sslcz));
                    return response()->json(['status' => 'error', 'message' => 'Failed to generate SSLCommerz payment link. Please check credentials.'], 500);
                }
            }

            // ৬. COD (Cash on Delivery) হলে
            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully!',
                'data' => [
                    'order_number' => $order->order_number,
                    'grand_total' => $orderTotal,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order Create Exception: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Order failed to place.',
                'error_detail' => $e->getMessage()
            ], 400);
        }
    }
}
