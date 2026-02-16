<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        return response()->json($this->productService->getAllProducts());
    }

  public function store(Request $request)
{
    // ১. ভ্যালিডেশন (Image সহ)
    $validated = $request->validate([
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'base_price' => 'required|numeric',
        'total_stock' => 'required|integer',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // ম্যাক্স ২MB
    ]);

    // ২. সার্ভিস কল করা (ইমেজ ফাইলসহ)
    $product = $this->productService->createProduct(
        $request->except('image'),
        $request->file('image')
    );

    return response()->json([
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
}

    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
