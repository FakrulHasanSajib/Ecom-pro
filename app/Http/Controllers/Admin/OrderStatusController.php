<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function index()
    {
        return response()->json(['data' => OrderStatus::all()]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:order_statuses,name']);
        $status = OrderStatus::create([
            'name' => $request->name,
            'color_class' => $request->color_class ?? 'text-slate-800'
        ]);
        return response()->json(['message' => 'Status added successfully', 'data' => $status]);
    }

    public function destroy($id)
    {
        OrderStatus::findOrFail($id)->delete();
        return response()->json(['message' => 'Status deleted successfully']);
    }
}
