<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'review_image' => 'nullable|image|max:2048', // 2MB Max
            'review_video' => 'nullable|mimes:mp4,mov,ogg|max:10240', // 10MB Max
        ]);

        $user = Auth::user();

        // ১. Verified Purchase Check (ইউজার কি প্রোডাক্টটি কিনেছে?)
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })->exists();

        // ২. ফাইল আপলোড লজিক
        $imagePath = null;
        $videoPath = null;

        if ($request->hasFile('review_image')) {
            $imagePath = $request->file('review_image')->store('reviews/images', 'public');
        }

        if ($request->hasFile('review_video')) {
            $videoPath = $request->file('review_video')->store('reviews/videos', 'public');
        }

        // ৩. রিভিউ সেভ করা
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'review_image' => $imagePath,
            'review_video' => $videoPath,
            'is_verified' => $hasPurchased, // অটোমেটিক ভেরিফায়েড স্ট্যাটাস
            'status' => 'pending', // এডমিন এপ্রুভালের জন্য
        ]);

        return response()->json([
            'message' => 'Review submitted successfully! Waiting for approval.',
            'data' => $review
        ], 201);
    }
}
