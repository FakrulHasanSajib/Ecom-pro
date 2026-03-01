<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * ১. প্রোডাক্ট লিস্ট (API)
     * URL: GET /api/admin/products
     */
    public function index()
    {
        $products = Product::with(['category', 'brand'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ], 200);
    }

    /**
     * ২. প্রোডাক্ট এডিট ডাটা (API)
     * URL: GET /api/admin/products/{id}
     * (Create এর জন্য আলাদা API লাগে না, ফ্রন্টএন্ডে ফর্ম লোড হবে)
     */
    public function edit($id)
    {
        try {
            $product = Product::with(['category', 'brand'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    /**
     * [নতুন যুক্ত করা হয়েছে]
     * সিঙ্গেল প্রোডাক্ট ডাটা (Public/Frontend View)
     * URL: GET /api/public/products/{id}
     */
    public function show($id)
    {
        try {
            // ID অথবা Slug যেকোনো একটি দিয়ে প্রোডাক্ট খুঁজবে
            $product = Product::with(['category', 'brand'])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    /**
     * ৩. ড্রপডাউন ডাটা API (Categories)
     * URL: GET /api/admin/list-categories
     */
    public function getCategories() {
        return response()->json(Category::select('id', 'name')->get());
    }

    /**
     * ৪. ড্রপডাউন ডাটা API (Brands)
     * URL: GET /api/admin/list-brands
     */
    public function getBrands() {
        return response()->json(Brand::select('id', 'name')->get());
    }

    /**
     * ৫. গুগল জেমিনি (Gemini) AI SEO
     * URL: POST /api/admin/products/generate-seo
     */
    public function generateSeo(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) return response()->json(['error' => 'API Key missing'], 500);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        $prompt = "Generate SEO metadata for product: '{$request->name}'. Return ONLY raw JSON with keys: \"meta_title\", \"meta_description\", \"tags\". No markdown.";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false]) // Localhost SSL Fix
                ->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $cleanJson = trim(str_replace(['```json', '```'], '', $text));

                $seoData = json_decode($cleanJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json($seoData);
                }
            }
            return response()->json(['error' => 'AI Response Invalid'], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ৬. প্রোডাক্ট স্টোর (Create API)
     * URL: POST /api/admin/products
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required',
            'base_price' => 'required|numeric',
            'stock' => 'required|integer',
            'thumbnail' => 'nullable|string',
        ]);

        try {
            $productData = [
                'name' => $request->name,
                'slug' => $request->slug ?? Str::slug($request->name),
                'sku' => $request->sku ?? 'SKU-' . time(),
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit' => $request->unit,

                // Pricing
                'purchase_price' => $request->purchase_price,
                'wholesale_price' => $request->wholesale_price ?? 0,
                'reseller_price' => $request->reseller_price ?? 0,
                'base_price' => $request->base_price,
                'offer_price' => $request->offer_price,
                'total_stock' => $request->stock,

                // Details
                'description' => $request->description,
                'video_link' => $request->video_link,

                // Shipping & Status
                'free_shipping' => filter_var($request->free_shipping, FILTER_VALIDATE_BOOLEAN),
                'shipping_inside_dhaka' => $request->shipping_inside_dhaka,
                'shipping_outside_dhaka' => $request->shipping_outside_dhaka,
                'status' => 'published',

                // JSON Fields
                'colors' => $request->colors,
                'sizes' => $request->sizes,
                'tags' => $request->tags,
                'testimonials' => $request->testimonials,
                'images' => $request->images,

                // SEO
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ];

            // থাম্বনেইল হ্যান্ডলিং
            if ($request->thumbnail) {
                $productData['thumbnail'] = $this->handleThumbnailPath($request->thumbnail);
            }

            $product = Product::create($productData);

            return response()->json([
                'status' => 'success',
                'message' => 'Product Created Successfully!',
                'product_id' => $product->id
            ], 201);

        } catch (\Exception $e) {
            Log::error("Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ৭. প্রোডাক্ট আপডেট (Update API)
     * URL: POST /api/admin/products/{id}/update
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'name' => 'required|string',
                'base_price' => 'required|numeric',
            ]);

            $updateData = [
                'name' => $request->name,
                'slug' => $request->slug ?? Str::slug($request->name),
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit' => $request->unit,

                'purchase_price' => $request->purchase_price,
                'wholesale_price' => $request->wholesale_price,
                'reseller_price' => $request->reseller_price,
                'base_price' => $request->base_price,
                'offer_price' => $request->offer_price,
                'total_stock' => $request->stock,

                'description' => $request->description,
                'video_link' => $request->video_link,

                'free_shipping' => filter_var($request->free_shipping, FILTER_VALIDATE_BOOLEAN),
                'shipping_inside_dhaka' => $request->shipping_inside_dhaka,
                'shipping_outside_dhaka' => $request->shipping_outside_dhaka,

                'colors' => $request->colors,
                'sizes' => $request->sizes,
                'tags' => $request->tags,
                'testimonials' => $request->testimonials,
                'images' => $request->images,

                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ];

            if ($request->has('thumbnail') && $request->thumbnail) {
                 $updateData['thumbnail'] = $this->handleThumbnailPath($request->thumbnail);
            }

            $product->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error("Update Error: " . $e->getMessage());
            return response()->json(['message' => 'Update Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ৮. প্রোডাক্ট ডিলিট (Delete API)
     * URL: DELETE /api/admin/products/{id}
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json(['status' => 'success', 'message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }

    /**
     * Helper: থাম্বনেইল পাথ ক্লিন করা
     */
    private function handleThumbnailPath($path)
    {
        if (str_contains($path, '/storage/')) {
             $parts = explode('/storage/', $path);
             return end($parts);
        }
        return $path;
    }
    // 🔥 হোমপেজের ফিচার্ড প্রোডাক্টের জন্য
    public function getFeatured()
    {
        // সর্বশেষ ৮টি একটিভ প্রোডাক্ট ফিচার্ড হিসেবে পাঠানো হচ্ছে
        $products = Product::where('status', 1)
                    ->latest()
                    ->take(8)
                    ->get()
                    ->map(function($product) {
                        $product->thumbnail = asset('storage/' . $product->thumbnail);
                        return $product;
                    });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
