<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    // ১. ইউজার তার সব উইশলিস্ট দেখবে
// app/Http/Controllers/Api/WishlistController.php

public function index(Request $request)
{
    try {
        // উইশলিস্টের সাথে প্রোডাক্টের ডাটা (ছবি, দাম) নিয়ে আসা হচ্ছে
        $wishlist = \App\Models\Wishlist::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $wishlist
        ], 200);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Wishlist Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch wishlist.',
            'error' => $e->getMessage() // এটি দিলে আপনি ব্রাউজারে এররটি দেখতে পাবেন
        ], 500);
    }
}

    // ২. এড অথবা রিমুভ (Toggle)
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $user = Auth::user();
        $productId = $request->product_id;

        // চেক করি আগে থেকে আছে কি না
        $wishlist = Wishlist::where('user_id', $user->id)
                            ->where('product_id', $productId)
                            ->first();

        if ($wishlist) {
            $wishlist->delete(); // থাকলে ডিলিট
            return response()->json(['status' => 'removed', 'message' => 'Removed from wishlist']);
        } else {
            Wishlist::create([ // না থাকলে ক্রিয়েট
                'user_id' => $user->id,
                'product_id' => $productId
            ]);
            return response()->json(['status' => 'added', 'message' => 'Added to wishlist']);
        }
    }
}
