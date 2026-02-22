<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\FraudCheckService;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // SSLCommerz API Call er jonno
use App\Models\Transaction; // Transaction model add kora holo

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

        try {
            // ১. Fraud Check
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

            // ২. Order toiri kora
            $order = $this->orderService->createOrder($user, $validated);

            // Tracking Data pathano
            try {
                $this->trackingService->sendPurchaseEvent($order);
            } catch (\Exception $e) {
                Log::error('Tracking Failed for Order: ' . $order->order_number);
            }

            $orderTotal = $request->total_amount ?? $order->grand_total;

            // ৩. Jodi Payment Method SSLCommerz hoy
            if ($request->payment_method === 'sslcommerz') {

                // Transaction toiri kora
                Transaction::create([
                    'transaction_id' => $order->order_number,
                    'order_id' => $order->id,
                    'amount' => $orderTotal,
                    'status' => 'pending'
                ]);

                // 🔥 Dynamic Credentials Logic (Prothome Database, tarpor .env)
                $store_id = get_setting('sslcz_store_id') ?: env('SSLCZ_STORE_ID');
                $store_password = get_setting('sslcz_store_password') ?: env('SSLCZ_STORE_PASSWORD');

                // Sandbox (Test) naki Live mode setaw dynamic
                $is_sandbox = get_setting('sslcz_testmode');
                if($is_sandbox === null) {
                    $is_sandbox = env('SSLCZ_TESTMODE', true);
                } else {
                    $is_sandbox = ($is_sandbox == 'true' || $is_sandbox == 1);
                }

                $apiUrl = $is_sandbox
                    ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php"
                    : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

                // SSLCommerz a data pathano
                $post_data = array();
                $post_data['store_id'] = $store_id;
                $post_data['store_passwd'] = $store_password;
                $post_data['total_amount'] = $orderTotal;
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = $order->order_number;

                // Callback URLs
                $frontendUrl = env('APP_URL', 'http://127.0.0.1:8000'); // Apnar local port
                $post_data['success_url'] = $frontendUrl . '/api/payment/success';
                $post_data['fail_url'] = $frontendUrl . '/api/payment/fail';
                $post_data['cancel_url'] = $frontendUrl . '/api/payment/cancel';

                // Customer Info
                $post_data['cus_name'] = $request->name ?? 'Customer';
                $post_data['cus_phone'] = $request->phone ?? '01700000000';
                $post_data['cus_email'] = "customer@example.com";
                $post_data['cus_add1'] = $request->address ?? 'Dhaka';
                $post_data['cus_city'] = "Dhaka";
                $post_data['cus_country'] = "Bangladesh";
                $post_data['shipping_method'] = "NO";
                $post_data['product_name'] = "E-Shop Products";
                $post_data['product_category'] = "General";
                $post_data['product_profile'] = "general";

                // API te request kora
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

            // ৪. Jodi COD (Cash on Delivery) hoy
            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully!',
                'data' => [
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
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
