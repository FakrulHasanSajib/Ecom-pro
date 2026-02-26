<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    // ১. Bulk Status Update
    public function bulkStatus(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'status' => 'required|string'
        ]);

        \App\Models\Order::whereIn('id', $request->order_ids)->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated for selected orders!']);
    }

    // ২. Bulk Assign Update
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'user_id' => 'required'
        ]);

        // user_id দিয়ে ডেলিভারি ম্যান বা স্টাফ অ্যাসাইন করা হচ্ছে
        \App\Models\Order::whereIn('id', $request->order_ids)->update(['user_id' => $request->user_id]);

        return response()->json(['status' => 'success', 'message' => 'Orders assigned successfully!']);
    }

    // ৩. Export to CSV
   public function export(Request $request)
{
    try {
        $ids = explode(',', $request->query('ids', ''));
        $orders = empty($ids[0]) ? \App\Models\Order::all() : \App\Models\Order::whereIn('id', $ids)->get();

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($orders) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Order ID', 'Customer Name', 'Phone', 'Address', 'Status', 'Total Amount', 'Date']);

            // CSV Rows
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number ?? $order->id,
                    $order->name,
                    $order->phone,
                    $order->address,
                    $order->status,
                    $order->grand_total ?? $order->total_amount,
                    // created_at ফাঁকা থাকলে যেন ক্র্যাশ না করে তাই চেক দেওয়া হলো
                    $order->created_at ? $order->created_at->format('Y-m-d H:i') : 'N/A'
                ]);
            }
            fclose($handle);
        });

        // Headers সেট করা
        $filename = "orders_export_" . date('Y-m-d') . ".csv";
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

    // ৪. Bulk Print
    public function print(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array'
        ]);

        $orders = \App\Models\Order::with('items')->whereIn('id', $request->order_ids)->get();

        // Print এর জন্য একটি সুন্দর HTML Invoice ডিজাইন তৈরি করা হলো
        $html = '<!DOCTYPE html><html><head><title>Print Orders</title><style>
                 body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; padding: 20px; color: #333; }
                 .invoice { border: 1px solid #e2e8f0; padding: 30px; margin-bottom: 30px; border-radius: 8px; page-break-after: always; }
                 .invoice:last-child { page-break-after: auto; }
                 h2 { color: #4f46e5; margin-top: 0; }
                 table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                 th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 14px; }
                 th { background-color: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 12px; }
                 .totals { text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold; color: #10b981; }
                 </style></head><body onload="window.print();">';

        foreach($orders as $order) {
            $html .= '<div class="invoice">';
            $html .= '<h2>Order #' . ($order->order_number ?? $order->id) . '</h2>';
            $html .= '<p><strong>Customer Name:</strong> ' . $order->name . '<br>';
            $html .= '<strong>Phone:</strong> ' . $order->phone . '<br>';
            $html .= '<strong>Address:</strong> ' . $order->address . '</p>';
            $html .= '<table><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr>';

            foreach(($order->items ?? collect()) as $item) {
                $html .= '<tr><td>' . ($item->product_name ?? $item->name) . '</td>';
                $html .= '<td>' . $item->quantity . '</td>';
                $html .= '<td>৳' . $item->price . '</td>';
                $html .= '<td>৳' . ($item->price * $item->quantity) . '</td></tr>';
            }
            $html .= '</table>';
            $html .= '<div class="totals">Grand Total: ৳' . ($order->grand_total ?? $order->total_amount) . '</div>';
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return response()->json(['status' => 'success', 'view' => $html]);
    }
}
