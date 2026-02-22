<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // 🔥 $fillable মুছে $guarded দেওয়া হলো, যাতে সব ফিল্ড সেভ হতে পারে
    protected $guarded = [];

    protected $casts = [
        'tracking_info' => 'json',
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }
}
