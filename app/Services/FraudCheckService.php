<?php

namespace App\Services;

use App\Models\Order;
use App\Models\FraudLog;
use Illuminate\Support\Facades\Log;

class FraudCheckService
{
    public function checkOrderRisk($user, $requestData)
    {
        $score = 0;
        $reasons = [];

        // ১. IP Check: গত ১ ঘন্টায় একই IP থেকে ৩টির বেশি অর্ডার হলে রিস্ক
        $recentOrders = Order::where('ip_address', request()->ip())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentOrders >= 3) {
            $score += 50;
            $reasons[] = 'High frequency orders from same IP';
        }

        // ২. High Value Order: ১০,০০০ টাকার বেশি হলে ম্যানুয়াল চেক দরকার
        $totalAmount = 0;
        foreach ($requestData['items'] as $item) {
             // (এখানে প্রাইস ক্যালকুলেশন লজিক থাকবে)
             // সিম্পলিসিটির জন্য ধরে নিচ্ছি ফ্রন্টএন্ড থেকে টোটাল আসছে বা লুপ করে বের করা হচ্ছে
        }

        // ৩. Suspicious Email: টেম্পোরারি ইমেইল ডোমেইন চেক
        $tempDomains = ['tempmail.com', 'mailinator.com', 'disposable.com'];
        $emailDomain = substr(strrchr($user->email, "@"), 1);

        if (in_array($emailDomain, $tempDomains)) {
            $score += 80;
            $reasons[] = 'Disposable email detected';
        }

        // ৪. ডাটাবেসে লগ রাখা
        if ($score > 0) {
            FraudLog::create([
                'order_id' => null, // অর্ডার তৈরির পর আপডেট হবে
                'ip_address' => request()->ip(),
                'risk_score' => $score,
                'reasons' => json_encode($reasons), // কলামটি JSON হতে হবে
            ]);
        }

        return [
            'is_fraud' => $score >= 80, // ৮০ এর বেশি স্কোর হলে অটো ব্লক
            'score' => $score,
            'reasons' => $reasons
        ];
    }
}
