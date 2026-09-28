<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    /** Hidden from the storefront (archived by the seller or flagged by admin). */
    public function isPurchasable(): bool
    {
        return !$this->is_archived && !$this->is_flagged;
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