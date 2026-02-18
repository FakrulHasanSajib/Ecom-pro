<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash; // এটি ইমপোর্ট করতে হবে

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // ১. ভ্যালিডেশন
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // ২. ইউজার খোঁজা
        $user = User::where('email', $request->email)->first();

        // ৩. পাসওয়ার্ড চেক করা (Auth::attempt এর বদলে Hash::check)
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

    public function logout(Request $request)
    {
        // বর্তমান টোকেন ডিলিট করা
        if (auth()->user()) {
            auth()->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
