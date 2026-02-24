<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // সব অর্ডার লিস্ট দেখানোর জন্য
   public function index()
    {
        $orders = \App\Models\Order::orderBy('id', 'desc')->get();

        // 🔥 ডাটাবেস থেকে স্ট্যাটাসগুলো আনবে। যদি টেবিল ফাঁকা থাকে, তাহলে ডিফল্ট কিছু স্ট্যাটাস দিয়ে দিবে।
        $dbStatuses = \App\Models\OrderStatus::pluck('name')->toArray();
        $statuses = empty($dbStatuses) ? ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] : $dbStatuses;

        return response()->json([
            'status' => 'success',
            'data' => $orders,
            'available_statuses' => $statuses
        ]);
    }

    // অর্ডারের স্ট্যাটাস আপডেট করার জন্য (যেমন: Pending থেকে Delivered)
   // অর্ডারের স্ট্যাটাস আপডেট করার জন্য
    public function updateStatus(Request $request, $id)
    {
        // 🔥 আগে এখানে 'in:Pending,Processing...' হার্ডকোড করা ছিল।
        // এখন আমরা শুধু 'string' চেক করছি, যাতে যেকোনো ডাইনামিক স্ট্যাটাস একসেপ্ট করে।
        $request->validate([
            'status' => 'required|string|max:100'
        ]);

        $order = \App\Models\Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully!',
            'data' => $order
        ]);
    }
}
