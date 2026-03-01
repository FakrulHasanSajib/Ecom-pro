<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    // এই লাইনটি না থাকলে 500 Error আসবে
    protected $fillable = ['name', 'status'];
}
