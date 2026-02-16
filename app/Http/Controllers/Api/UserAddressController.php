<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class UserAddressController extends Controller
{
    // ১. সব এড্রেস দেখা
    public function index()
    {
        return response()->json(Auth::user()->addresses);
    }

    // ২. নতুন এড্রেস সেভ করা
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'post_code' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        $user = Auth::user();

        // যদি ডিফল্ট হয়, তবে আগের সব ডিফল্ট ফলস করে দিতে হবে
        if ($request->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        return response()->json([
            'message' => 'Address saved successfully',
            'data' => $address
        ], 201);
    }

    // ৩. এড্রেস ডিলিট করা
    public function destroy($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->delete();
        return response()->json(['message' => 'Address deleted']);
    }
}
