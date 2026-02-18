<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id', // এটি জরুরি, নাহলে সাব-ক্যাটাগরি সেভ হবে না
        'is_active'  // স্ট্যাটাস একটিভ/ইনএকটিভ রাখার জন্য
    ];

    /**
     * প্যারেন্ট ক্যাটাগরি রিলেশন
     * (এই ফাংশনটি না থাকার কারণেই 500 Error আসছিল)
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * চাইল্ড বা সাব-ক্যাটাগরি রিলেশন
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * প্রোডাক্টের সাথে রিলেশন
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
