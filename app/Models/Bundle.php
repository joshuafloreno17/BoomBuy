<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A bundle deal (Seller Modules S8):
 *   bundle — any min_quantity items (any options) of its products: percent off them
 *   bxgy   — buy buy_quantity of buy_product, get get_quantity of get_product free
 */
class Bundle extends Model
{
    protected $fillable = ['seller_id', 'name', 'type', 'min_quantity', 'percent', 'buy_product_id', 'buy_quantity', 'get_product_id', 'get_quantity', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bundle_products');
    }

    public function buyProduct()
    {
        return $this->belongsTo(Product::class, 'buy_product_id')->withTrashed();
    }

    public function getProduct()
    {
        return $this->belongsTo(Product::class, 'get_product_id')->withTrashed();
    }

    public function summary(): string
    {
        if ($this->type === 'bxgy') {
            return 'Buy ' . $this->buy_quantity . ' × ' . ($this->buyProduct->name ?? 'item') . ', get ' . $this->get_quantity . ' × ' . ($this->getProduct->name ?? 'item') . ' free';
        }

        return 'Any ' . $this->min_quantity . ' items from ' . $this->products->pluck('name')->implode(', ') . ': ' . $this->percent . '% off';
    }
}
