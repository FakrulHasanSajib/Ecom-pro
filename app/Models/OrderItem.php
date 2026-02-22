<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    // 🔥 $fillable এর বদলে $guarded খালি রাখা হলো যাতে সব ডাটা সেভ হতে পারে
    protected $guarded = [];

    // প্রোডাক্টের সাথে রিলেশন
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // অর্ডারের সাথে রিলেশন
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
