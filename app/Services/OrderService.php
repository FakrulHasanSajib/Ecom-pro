<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function createOrder($user, $data)
    {
        return DB::transaction(function () use ($user, $data) {
            // ১. অর্ডার টেবিল তৈরি
            $order = Order::create([
                'uuid' => Str::uuid(),
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'user_id' => $user->id,
                'sub_total' => 0, // পরে ক্যালকুলেট হবে
                'grand_total' => 0,
                'payment_method' => $data['payment_method'],
                'shipping_charge' => $data['shipping_charge'] ?? 60,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                // ট্র্যাকিং ডাটা সেভ (FB, TikTok)
                'tracking_info' => $data['tracking_info'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
            ]);

            $subTotal = 0;

            // ২. অর্ডার আইটেম লুপ করা এবং স্টক চেক করা
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // স্টক চেক
                if ($product->total_stock < $item['quantity']) {
                    throw new \Exception("Stock not available for: " . $product->name);
                }

                // স্টক কমানো
                $product->decrement('total_stock', $item['quantity']);

                // আইটেম সেভ
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name, // স্ন্যাপশট
                    'price' => $product->sale_price ?? $product->base_price,
                    'quantity' => $item['quantity'],
                ]);

                $subTotal += ($product->sale_price ?? $product->base_price) * $item['quantity'];
            }

            // ৩. টোটাল আপডেট করা
            $order->update([
                'sub_total' => $subTotal,
                'grand_total' => $subTotal + $order->shipping_charge
            ]);

            return $order;
        });
    }
}
