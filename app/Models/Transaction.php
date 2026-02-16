<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'order_id', 'transaction_id', 'gateway', 'amount', 'status', 'payment_details'];
protected $casts = ['payment_details' => 'array'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
