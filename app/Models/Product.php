<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'category',
        'price',
        'stock',
        'description',
        'image',
        'is_flagged',
        'flag_reason',
        'is_archived',
    ];

    protected $casts = [
        'is_flagged' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    /** Extra photos after the cover (products.image), in the seller's order. */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Products buyers may see and buy: not archived by the seller, not
     * flagged by the admin, and sold by an account that is still Active — a
     * suspended or deactivated seller can't fulfil new orders.
     */
    public function scopeOnSale($query)
    {
        return $query
            ->where('products.is_flagged', false)
            ->where('products.is_archived', false)
            ->whereExists(function ($sellers) {
                $sellers->select(DB::raw(1))
                    ->from('users as product_sellers')
                    ->whereColumn('product_sellers.id', 'products.seller_id')
                    ->where('product_sellers.status', 'Active');
            });
    }

    /**
     * What a buyer can actually buy: the variations' stock when the product
     * has options (its own stock column isn't sold then), otherwise its own.
     */
    public const SELLABLE_STOCK_SQL = '(CASE WHEN EXISTS (SELECT 1 FROM product_variations pv WHERE pv.product_id = products.id)'
        . ' THEN (SELECT COALESCE(SUM(pv2.stock), 0) FROM product_variations pv2 WHERE pv2.product_id = products.id)'
        . ' ELSE products.stock END)';

    /** Adds `sellable_stock` (see SELLABLE_STOCK_SQL) to each product. */
    public function scopeWithSellableStock($query)
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select('products.*');
        }

        return $query->selectRaw(self::SELLABLE_STOCK_SQL . ' as sellable_stock');
    }

    /** Adds `sold_count`: units from delivered orders. */
    public function scopeWithSoldCount($query)
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select('products.*');
        }

        return $query->selectRaw(
            "(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi"
            . " JOIN orders o ON o.id = oi.order_id"
            . " WHERE oi.product_id = products.id AND o.status = 'Delivered') as sold_count"
        );
    }

    /** Same rule as onSale(), for a single product already loaded. */
    public function isPurchasable(): bool
    {
        if ($this->is_archived || $this->is_flagged) {
            return false;
        }

        return DB::table('users')
            ->where('id', $this->seller_id)
            ->where('status', 'Active')
            ->exists();
    }

    /**
     * Resolve what a buyer is actually buying. A product with variations must
     * be bought as one of them — its base stock/price don't apply on their own.
     *
     * @return array{0: ?ProductVariation, 1: ?string} [variation, error]
     */
    public function resolveVariation(int $variationId): array
    {
        $variation = $variationId
            ? $this->variations()->where('id', $variationId)->first()
            : null;

        if (!$variation && $this->variations()->exists()) {
            $type = $this->variations()->value('variation_type') ?: 'option';

            return [null, 'Please choose a ' . strtolower($type) . ' for ' . $this->name . ' first.'];
        }

        return [$variation, null];
    }
}