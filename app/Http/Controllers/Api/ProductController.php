<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ১. প্রোডাক্ট লিস্ট (ফিল্টারিং সহ)
    public function index(Request $request)
    {
        $query = Product::with('category', 'media')
            ->where('status', 'published');

        // Category Filter
        if ($request->has('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        // Price Range Filter
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('sale_price', [$request->min_price, $request->max_price]);
        }

        // Search Filter
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        if ($request->has('sort')) {
            if ($request->sort == 'price_low') {
                $query->orderBy('sale_price', 'asc');
            } elseif ($request->sort == 'price_high') {
                $query->orderBy('sale_price', 'desc');
            } else {
                $query->latest();
            }
        }

        return response()->json($query->paginate(12));
    }

    // ২. সিঙ্গেল প্রোডাক্ট ডিটেইলস (Slug দিয়ে)
    public function show($slug)
    {
        $product = Product::with('category', 'media', 'variants')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json($product);
    }
}
