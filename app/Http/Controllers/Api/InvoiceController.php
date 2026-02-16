<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show($orderUuid)
    {
        // ১. অর্ডার ডাটা রিট্রিভ করা
        $order = Order::where('uuid', $orderUuid)
                      ->with(['items.product', 'user', 'transaction']) // ট্রানজেকশনসহ সব তথ্য
                      ->firstOrFail();

        // ২. কোম্পানির তথ্য (সেটিংস থেকে)
        $companyInfo = [
            'name' => get_setting('site_name', 'My Shop'),
            'logo' => get_setting_image('site_logo'),
            'address' => get_setting('footer_address', 'Dhaka, Bangladesh'),
            'phone' => get_setting('header_phone'),
            'email' => get_setting('site_email'),
        ];

        // ৩. শুধু JSON রিটার্ন করা
        return response()->json([
            'status' => 'success',
            'invoice_data' => [
                'order' => $order,
                'company' => $companyInfo,
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
