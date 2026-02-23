<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash; // এটি ইমপোর্ট করা আছে

class AuthController extends Controller
{
    // 🔥 নতুন যুক্ত করা রেজিস্ট্রেশন ফাংশন
    public function register(Request $request)
    {
        // ১. ভ্যালিডেশন (পাসওয়ার্ড কনফার্মেশনসহ)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // ২. ইউজার তৈরি করা
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ৩. টোকেন তৈরি করা
        $token = $user->createToken('auth_token')->plainTextToken;

        // ৪. রেসপন্স পাঠানো (লগিনের রেসপন্সের সাথে মিল রেখে)
        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // 🔥 আপনার আগের লগিন ফাংশন (কোনো পরিবর্তন করা হয়নি)
    public function login(Request $request)
    {
        // ১. ভ্যালিডেশন
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // ২. ইউজার খোঁজা
        $user = User::where('email', $request->email)->first();

        // ৩. পাসওয়ার্ড চেক করা (Auth::attempt এর বদলে Hash::check)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['The provided credentials do not match our records.']
                ]
            ], 401);
        }

        // ৪. টোকেন তৈরি করা
        $token = $user->createToken('auth_token')->plainTextToken;

        // ৫. রেসপন্স পাঠানো
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    // 🔥 আপনার আগের লগআউট ফাংশন
    public function logout(Request $request)
    {
        // বর্তমান টোকেন ডিলিট করা
        if (auth()->user()) {
            auth()->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
