<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'variation_type',
        'variation_value',
        'price_adjustment',
        'stock',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
