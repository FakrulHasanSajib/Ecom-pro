<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use Illuminate\Http\Request;

class BlockedIpController extends Controller
{
    public function index()
    {
        return response()->json(BlockedIp::latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip|unique:blocked_ips,ip_address',
        ]);

        BlockedIp::create([
            'ip_address' => $request->ip_address,
            'reason' => $request->reason
        ]);

        return response()->json(['message' => 'IP Blocked successfully']);
    }

    public function destroy($id)
    {
        BlockedIp::findOrFail($id)->delete();
        return response()->json(['message' => 'IP Unblocked successfully']);
    }
}
