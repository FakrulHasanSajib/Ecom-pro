<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // সব অর্ডার লিস্ট দেখানোর জন্য
    public function index()
    {
        $orders = \App\Models\Order::with('items')->orderBy('id', 'desc')->get();

        $dbStatuses = \App\Models\OrderStatus::pluck('name')->toArray();
        $statuses = empty($dbStatuses) ? ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] : $dbStatuses;

        return response()->json([
            'status' => 'success',
            'data' => $orders,
            'available_statuses' => $statuses
        ]);
    }

    // নতুন অর্ডার তৈরি করার জন্য
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'items' => 'required|array',
            'total_amount' => 'required|numeric'
        ]);

        try {
            $orderNumber = 'ORD-' . strtoupper(uniqid());

            $fullAddress = $request->address;
            if(!empty($request->area)) $fullAddress .= ', ' . $request->area;
            if(!empty($request->district)) $fullAddress .= ', ' . $request->district;

            // Order create
            $order = \App\Models\Order::create([
                'user_id' => auth()->id() ?? null,
                'order_number' => $orderNumber,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $fullAddress,
                'order_source' => $request->order_source ?? 'Admin',
                'payment_method' => $request->payment_method ?? 'COD',
                'status' => $request->status ?? 'Pending',
                'sub_total' => $request->sub_total,
                'shipping_charge' => $request->shipping_charge ?? 0,
                'grand_total' => $request->total_amount,
            ]);

            // 🔥 Order Items create (total কলামটি বাদ দেওয়া হয়েছে)
            foreach ($request->items as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'] ?? $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    // 'total' লাইনটি মুছে দেওয়া হয়েছে
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Order created successfully']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // অর্ডারের স্ট্যাটাস আপডেট করার জন্য
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|max:100']);
        $order = \App\Models\Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json(['status' => 'success', 'message' => 'Order status updated successfully!', 'data' => $order]);
    }

    // অর্ডার ডিলিট করার জন্য
    public function destroy($id)
    {
        $order = \App\Models\Order::findOrFail($id);
        $order->delete();
        return response()->json(['status' => 'success', 'message' => 'Order deleted successfully!']);
    }

    // Order er details show korar jnno
    public function show($id)
    {
        $order = \App\Models\Order::with('items')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $order]);
    }

    // Order update korar jnno
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'items' => 'required|array',
            'total_amount' => 'required|numeric'
        ]);

        try {
            $order = \App\Models\Order::findOrFail($id);

            $fullAddress = $request->address;
            if(!empty($request->area)) $fullAddress .= ', ' . $request->area;
            if(!empty($request->district)) $fullAddress .= ', ' . $request->district;

            // Basic details update
            $order->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $fullAddress,
                'payment_method' => $request->payment_method ?? $order->payment_method,
                'status' => $request->status ?? $order->status,
                'sub_total' => $request->sub_total,
                'shipping_charge' => $request->shipping_charge ?? 0,
                'grand_total' => $request->total_amount,
            ]);

            // Old items delete kore notun gulo insert kora
            DB::table('order_items')->where('order_id', $order->id)->delete();

            // 🔥 (total কলামটি বাদ দেওয়া হয়েছে)
            foreach ($request->items as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'] ?? $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    // 'total' লাইনটি মুছে দেওয়া হয়েছে
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Order updated successfully']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
