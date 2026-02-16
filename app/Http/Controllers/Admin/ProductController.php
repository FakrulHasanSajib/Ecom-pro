<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Tag;
use App\Models\ProductTag;

use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * ১. প্রোডাক্ট লিস্ট পেজ (Inertia Render)
     */
    public function index()
    {
        // এটি ডাটাবেস থেকে প্রোডাক্ট নিয়ে ইনডেক্স পেজে পাঠাবে
        $products = Product::with('category')->latest()->get();
        return Inertia::render('Admin/Products/Index', [
            'products' => $products
        ]);
    }

    /**
     * ২. প্রোডাক্ট ক্রিয়েট পেজ (Inertia Render)
     */
    public function create()
    {
        return Inertia::render('Admin/Products/Create');
    }

    /**
     * ৩. ড্রপডাউন ডাটা API (Vue থেকে Axios দিয়ে কল হবে)
     */
    public function getCategories() {
        return response()->json(Category::select('id', 'name')->get());
    }

    public function getBrands() {
        return response()->json(Brand::select('id', 'name')->get());
    }

    /**
     * ৪. গুগল জেমিনি (Gemini) AI SEO জেনারেটর
     */
public function generateSeo(Request $request)
{
    $request->validate(['name' => 'required|string']);

    $apiKey = env('GEMINI_API_KEY');

    if (!$apiKey) {
        return response()->json(['error' => 'API Key missing in .env'], 500);
    }

    // লারাভেল Http ক্লাসে অনেক সময় কুয়েরি প্যারামিটারে সমস্যা হয়, তাই সরাসরি URL এভাবে লিখুন:
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $prompt = "Generate SEO for product: '{$request->name}'. Return ONLY raw JSON with keys: \"meta_title\", \"meta_description\", \"tags\". No markdown.";

    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $result = $response->json();

            // ডাটা চেক করা
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $result['candidates'][0]['content']['parts'][0]['text'];

                // মার্কডাউন ব্যাকটিকস রিমুভ করা
                $cleanJson = trim(str_replace(['```json', '```'], '', $text));
                $seoData = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json($seoData);
                }
            }
            return response()->json(['error' => 'AI returned invalid format'], 500);
        }

        // এপিআই থেকে আসা আসল এরর মেসেজ দেখার জন্য
        return response()->json([
            'error' => 'Gemini API Error',
            'details' => $response->json()
        ], $response->status());

    } catch (\Exception $e) {
        return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
    }
}

    /**
     * ৫. প্রোডাক্ট স্টোর/সেভ (API Method)
     */
    public function store(Request $request)
    {
        // ক) ভ্যালিডেশন
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required',
            'base_price' => 'required|numeric', // Regular Price
            'purchase_price' => 'required|numeric',
            'stock' => 'required|integer',
            'slug' => 'nullable|string|unique:products,slug',
        ]);

        try {
            // খ) ডাটা প্রসেসিং (JSON বা Array হ্যান্ডেলিং)
            $colors = is_string($request->colors) ? json_decode($request->colors) : $request->colors;
            $sizes = is_string($request->sizes) ? json_decode($request->sizes) : $request->sizes;
            $tags = is_string($request->tags) ? json_decode($request->tags) : $request->tags;
            $testimonials = is_string($request->testimonials) ? json_decode($request->testimonials) : $request->testimonials;

            // গ) প্রোডাক্ট ডাটাবেসে সেভ করা
            $product = Product::create([
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
                'base_price' => $request->base_price, // Regular Price
                'sale_price' => $request->offer_price, // Offer Price
                'total_stock' => $request->stock,

                // Details
                'description' => $request->description,
                'video_link' => $request->video_link,
                'thumbnail' => $request->thumbnail, // Gallery থেকে পাওয়া URL

                // Shipping & Status
                'free_shipping' => filter_var($request->free_shipping, FILTER_VALIDATE_BOOLEAN),
                'shipping_inside_dhaka' => $request->shipping_inside_dhaka,
                'shipping_outside_dhaka' => $request->shipping_outside_dhaka,
                'status' => 'published',

                // JSON Fields (Castings in Model handles this)
                'colors' => $colors,
                'sizes' => $sizes,
                'tags' => $tags,
                'testimonials' => $testimonials,

                // SEO
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            // ঘ) গ্যালারি ইমেজ প্রসেসিং (Spatie Media Library)
            if ($request->images && is_array($request->images)) {
                foreach ($request->images as $imageUrl) {
                    try {
                        // লোকাল স্টোরেজ পাথ ডিটেকশন
                        $relativePath = str_replace(url('/storage'), '', $imageUrl);
                        $absolutePath = public_path('storage' . $relativePath);

                        if (file_exists($absolutePath)) {
                            $product->addMedia($absolutePath)
                                    ->preservingOriginal()
                                    ->toMediaCollection('gallery');
                        } else {
                            // যদি এক্সটারনাল লিংক হয়
                            $product->addMediaFromUrl($imageUrl)->toMediaCollection('gallery');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Image attach failed for URL: " . $imageUrl);
                    }
                }
            }

            return response()->json([
                'message' => 'Product Created Successfully!',
                'product_id' => $product->id
            ], 201);

        } catch (\Exception $e) {
            Log::error("Store Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

}
