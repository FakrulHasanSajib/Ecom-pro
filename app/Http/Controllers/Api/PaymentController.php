<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * পেমেন্ট সফল হলে কল হবে
     */
    public function success(Request $request)
    {
        $tranId = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        // ১. ট্রানজেকশন এবং অর্ডার খুঁজে বের করা
        $transaction = Transaction::where('transaction_id', $tranId)->first();
        $order = Order::where('order_number', $tranId)->first();

        if (!$order || !$transaction) {
            Log::error('Payment Success: Order or Transaction not found for ID: ' . $tranId);
            return response()->json(['status' => 'Order Not Found'], 404);
        }

        // ২. ভ্যালিডেশন: টাকার পরিমাণ এবং কারেন্সি মিল আছে কি না
        if ($order->grand_total == $amount && $order->payment_status !== 'paid') {

            // ৩. ট্রানজেকশন আপডেট
            $transaction->update([
                'status' => 'success',
                'payment_details' => json_encode($request->all()) // গেটওয়ের সব রেসপন্স সেভ রাখা
            ]);

            // ৪. অর্ডার আপডেট
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing', // পেমেন্ট হলে প্রসেসিং শুরু
                'payment_method' => 'sslcommerz'
            ]);

            // ৫. ফ্রন্টএন্ডে রিডাইরেক্ট (সাকসেস পেজে)
            // আপনার ফ্রন্টএন্ড URL এখানে বসাবেন
            $frontendUrl = get_setting('frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/order-success?order_number=' . $order->order_number);
        }

        return response()->json(['status' => 'Validation Failed'], 400);
    }

    /**
     * পেমেন্ট ফেইল হলে কল হবে
     */
    public function fail(Request $request)
    {
        $tranId = $request->input('tran_id');

        // ১. স্ট্যাটাস ফেইল্ড করা
        Transaction::where('transaction_id', $tranId)->update([
            'status' => 'failed',
            'payment_details' => json_encode($request->all())
        ]);

        Order::where('order_number', $tranId)->update(['payment_status' => 'failed']);

        // ২. ফ্রন্টএন্ড ফেইল পেজে রিডাইরেক্ট
        $frontendUrl = get_setting('frontend_url', 'http://localhost:3000');
        return redirect($frontendUrl . '/payment-failed?order_number=' . $tranId);
    }

    /**
     * ইউজার পেমেন্ট ক্যান্সেল করলে কল হবে
     */
    public function cancel(Request $request)
    {
        $tranId = $request->input('tran_id');

        // ১. স্ট্যাটাস ক্যান্সেলড করা
        Transaction::where('transaction_id', $tranId)->update([
            'status' => 'cancelled',
            'payment_details' => json_encode($request->all())
        ]);

        Order::where('order_number', $tranId)->update(['payment_status' => 'cancelled']);

        // ২. কার্ট পেজে বা চেকআউটে ফেরত পাঠানো
        $frontendUrl = get_setting('frontend_url', 'http://localhost:3000');
        return redirect($frontendUrl . '/cart');
    }
}
