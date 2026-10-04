<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One extra photo of a product; the cover photo is products.image. */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return str_starts_with($this->path, 'http') ? $this->path : asset('storage/' . ltrim($this->path, '/'));
    }
}
