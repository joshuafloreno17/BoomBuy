<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One photo of a product: for one option (product_variation_id) or for all options (null). */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'product_variation_id', 'path', 'sort_order', 'is_cover'];

    protected $casts = ['is_cover' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return str_starts_with($this->path, 'http') ? $this->path : asset('storage/' . ltrim($this->path, '/'));
    }
}
