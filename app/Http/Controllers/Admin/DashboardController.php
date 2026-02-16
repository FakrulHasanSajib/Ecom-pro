<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_sales' => Order::where('payment_status', 'paid')->sum('grand_total'),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::where('total_stock', '<', 5)->count(), // Low stock alert
            'recent_orders' => Order::with('user')->latest()->take(5)->get()
        ]);
    }
}
