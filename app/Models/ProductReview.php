<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'buyer_id',
        'order_id',
        'product_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];
}