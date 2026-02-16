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
    public function index()
    {
        $wishlists = Auth::user()->wishlists()->with('product')->get();
        return response()->json($wishlists);
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
