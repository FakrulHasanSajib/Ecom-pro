<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function getAllProducts()
    {
        return Product::with('category')->latest()->paginate(10);
    }

public function createProduct(array $data, $image = null)
{
    // ১. মেইন প্রোডাক্ট তৈরি
    $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
    $product = Product::create($data);

    // ২. ইমেজ আপলোড (যদি থাকে)
    if ($image) {
        $product->addMedia($image)->toMediaCollection('products');
    }

    // ৩. ভেরিয়েশন সেভ করার লজিক (Variation Logic)
    // ফ্রন্টএন্ড থেকে ডাটা আসবে এভাবে: variants: [{size: 'L', color: 'Red', price: 500, stock: 10}, ...]

    if (isset($data['variants']) && is_array($data['variants'])) {
        foreach ($data['variants'] as $variant) {
            $product->variants()->create([
                'size'      => $variant['size'] ?? null,
                'color'     => $variant['color'] ?? null,
                'price'     => $variant['price'] ?? $product->base_price, // দাম না দিলে মেইন দাম
                'stock_qty' => $variant['stock'] ?? 0,
                'sku'       => $product->sku . '-' . strtoupper($variant['size']) . '-' . strtoupper($variant['color']),
            ]);
        }

        // মেইন প্রোডাক্টের টোটাল স্টক আপডেট করা (অপশনাল)
        $product->update(['total_stock' => $product->variants()->sum('stock_qty')]);
    }

    return $product;
}

    public function updateProduct(Product $product, array $data)
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);
        return $product;
    }
}
