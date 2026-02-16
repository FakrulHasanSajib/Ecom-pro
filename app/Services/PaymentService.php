<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $storeId;
    protected $storePassword;
    protected $baseUrl;
    protected $isSandbox;

    public function __construct()
    {
        // ডাইনামিক সেটিং থেকে ক্রেডেনশিয়াল লোড করা
        $this->storeId = get_setting('ssl_store_id');
        $this->storePassword = get_setting('ssl_store_password');

        // স্যান্ডবক্স নাকি লাইভ মোড চেক করা
        $this->isSandbox = get_setting('ssl_sandbox_mode', 'true') === 'true';
        $this->baseUrl = $this->isSandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    /**
     * পেমেন্ট ইনিশিয়েট করা এবং গেটওয়ে ইউআরএল রিটার্ন করা
     */
    public function initiatePayment(Order $order)
    {
        // ১. ট্রানজেকশন লগ তৈরি করা (Pending অবস্থায়)
        Transaction::updateOrCreate(
            ['transaction_id' => $order->order_number], // অর্ডার নাম্বারের সাথে মিল রাখা
            [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'gateway' => 'sslcommerz',
                'amount' => $order->grand_total,
                'status' => 'pending',
                'payment_details' => json_encode(['initiated_at' => now()]),
            ]
        );

        // ২. SSLCommerz এর জন্য ডাটা প্রস্তুত করা
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $order->grand_total,
            'currency' => 'BDT',
            'tran_id' => $order->order_number, // ইউনিক হতে হবে
            'success_url' => url('/api/payment/success'), // API রাউট
            'fail_url' => url('/api/payment/fail'),
            'cancel_url' => url('/api/payment/cancel'),
            'cus_name' => $order->user->name,
            'cus_email' => $order->user->email,
            'cus_add1' => 'Customer Address', // চাইলে ইউজারের রিয়েল এড্রেস দিতে পারেন
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->user->phone ?? '01700000000',
            'shipping_method' => 'NO',
            'product_name' => 'Order #' . $order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
        ];

        try {
            // ৩. SSLCommerz API কল
            $response = Http::asForm()->post($this->baseUrl . '/gwprocess/v4/api.php', $postData);
            $result = json_decode($response->body(), true);

            // ৪. সফল হলে পেমেন্ট ইউআরএল রিটার্ন
            if (isset($result['status']) && $result['status'] == 'SUCCESS') {
                return $result['GatewayPageURL'];
            }

            // ৫. ব্যর্থ হলে লগ করা এবং এরর থ্রো করা
            Log::error('SSLCommerz Init Failed: ' . json_encode($result));
            throw new \Exception('Payment Gateway Error: ' . ($result['failedreason'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            Log::error('Payment Service Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
