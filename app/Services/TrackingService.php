<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    protected $pixelId;
    protected $accessToken;

    public function __construct()
    {
        // .env ফাইল থেকে কি (Key) গুলো নিবে
        $this->pixelId = env('FACEBOOK_PIXEL_ID');
        $this->accessToken = env('FACEBOOK_ACCESS_TOKEN');
    }

    public function sendPurchaseEvent($order)
    {
        // লোকালহোস্টে থাকলে কাজ করবে না, তাই চেক করে নেওয়া
        if (app()->environment('local')) {
            Log::info('Local environment: Skipped FB CAPI event.');
            return;
        }

        $userData = [
            'em' => hash('sha256', $order->user->email), // ইমেইল হ্যাশ করে পাঠাতে হয়
            'ph' => hash('sha256', $order->user->phone ?? ''),
            'client_ip_address' => $order->ip_address,
            'client_user_agent' => $order->user_agent,
            'fbp' => $order->tracking_info['fbp'] ?? null,
            'fbc' => $order->tracking_info['fbc'] ?? null,
        ];

        try {
            $response = Http::post("https://graph.facebook.com/v19.0/{$this->pixelId}/events", [
                'data' => [
                    [
                        'event_name' => 'Purchase',
                        'event_time' => time(),
                        'action_source' => 'website',
                        'user_data' => array_filter($userData),
                        'custom_data' => [
                            'currency' => 'BDT',
                            'value' => $order->grand_total,
                            'order_id' => $order->order_number,
                        ],
                        'event_id' => $order->order_number, // Deduplication এর জন্য জরুরি
                        'access_token' => $this->accessToken,
                    ]
                ]
            ]);

            Log::info('FB CAPI Response: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('FB CAPI Error: ' . $e->getMessage());
        }
    }
}
