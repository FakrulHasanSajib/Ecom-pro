<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
public function show($orderNumber)
    {
        // 🔥 'transaction' মুছে ফেলা হলো
        $order = Order::where('order_number', $orderNumber)
                      ->with(['items.product', 'user'])
                      ->firstOrFail();

        $companyInfo = [
            'name' => function_exists('get_setting') ? get_setting('site_name', 'My Shop') : 'E-Shop Pro',
            'logo' => function_exists('get_setting_image') ? get_setting_image('site_logo') : null,
            'address' => function_exists('get_setting') ? get_setting('footer_address', 'Dhaka, Bangladesh') : 'Dhaka, Bangladesh',
            'phone' => function_exists('get_setting') ? get_setting('header_phone') : '01700000000',
            'email' => function_exists('get_setting') ? get_setting('site_email') : 'support@eshop.com',
        ];

        return response()->json([
            'status' => 'success',
            'invoice_data' => [
                'order' => $order,
                'company' => $companyInfo,
                'generated_at' => now()->format('Y-m-d h:i A'),
            ]
        ]);
    }
}
