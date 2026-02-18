<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute; // ইমেজের জন্য প্রয়োজন
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    // ১. Vue ফর্মের সব ফিল্ড এখানে থাকতে হবে, নাহলে ডাটা সেভ হবে না
    protected $fillable = [
        'category_id',
        'brand_id',           // Added
        'name',
        'slug',
        'sku',
        'unit',               // Added
        'description',
        'video_link',         // Added

        // Pricing
        'purchase_price',     // Added
        'wholesale_price',    // Added
        'reseller_price',     // Added
        'base_price',
        'sale_price',         // or offer_price
        'offer_price',        // Added to match Vue form

        // Stock & Status
        'total_stock',
        'status',

        // Variants & Options
        'enable_variants',    // Added
        'colors',
        'sizes',
        'tags',

        // Shipping
        'free_shipping',         // Added
        'shipping_inside_dhaka', // Added
        'shipping_outside_dhaka',// Added

        // Media (Direct paths)
        'thumbnail',
        'images',

        // SEO
        'meta_title',         // Fixed "no such column" error source
        'meta_description',
        'meta_data',

        // Reviews
        'testimonials',       // Added
    ];

    // ২. ডাটাবেসের JSON ডাটাকে পিএইচপি অ্যারেতে রূপান্তর করা
    protected $casts = [
        'meta_data'       => 'array',
        'colors'          => 'array',
        'sizes'           => 'array',
        'tags'            => 'array',
        'images'          => 'array',      // গ্যালারি ইমেজ অ্যারে
        'testimonials'    => 'array',      // রিভিউ অ্যারে
        'enable_variants' => 'boolean',
        'free_shipping'   => 'boolean',
    ];

    // ৩. রিলেশনশিপ
    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }

    // ৪. Spatie Media Conversion (যদি Spatie ব্যবহার করেন)
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(150)
              ->height(150)
              ->sharpen(10);

        $this->addMediaConversion('medium')
              ->width(600)
              ->height(600)
              ->format('webp');
    }

    // ৫. ইমেজ পাথ ফিক্স (থাম্বনেইল যাতে শো করে)
    // ডাটাবেসে শুধু 'products/image.jpg' থাকলে এটি অটোমেটিক 'http://domain/storage/products/image.jpg' বানিয়ে দেবে
    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? (str_starts_with($value, 'http') ? $value : asset('storage/' . $value)) : null,
        );
    }
}
