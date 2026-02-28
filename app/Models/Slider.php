<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'title',
        'short_description',
        'image',
        'link',
        'serial',
        'status'
    ];
}
