<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia // ১. ইন্টারফেস ইমপ্লিমেন্ট করুন
{
    use HasFactory, SoftDeletes, InteractsWithMedia; // ২. ট্রেইট ব্যবহার করুন

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku',
        'description', 'base_price', 'sale_price',
        'total_stock', 'status', 'meta_data'
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    // ৩. অটোমেটিক ইমেজ সাইজ এবং কনভার্সন সেটআপ
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(150)
              ->height(150)
              ->sharpen(10);

        $this->addMediaConversion('medium')
              ->width(600)
              ->height(600)
              ->format('webp'); // প্রোডাকশনে WebP খুব ফাস্ট
    }
    public function variants()
{
    return $this->hasMany(ProductVariant::class);
}
}
