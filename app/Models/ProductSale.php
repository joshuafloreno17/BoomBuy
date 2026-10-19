<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A store sale on one product for a set time (Seller Modules S6, A28).
 * Percent: 1–99% off every option's regular price. Fixed: one sale price
 * (products without options). It is live from starts_at until ends_at,
 * or until the seller ends it early (ended_at).
 */
class ProductSale extends Model
{
    protected $fillable = ['seller_id', 'product_id', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'ended_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'ended_at' => 'datetime',
        'discount_value' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** When it really stops: its end time, or when it was ended early. */
    public function stopsAt(): Carbon
    {
        return $this->ended_at && $this->ended_at->lt($this->ends_at) ? $this->ended_at : $this->ends_at;
    }

    public function status(?Carbon $at = null): string
    {
        $at ??= now();

        if ($at->lt($this->starts_at)) {
            return 'Upcoming';
        }

        return $at->lt($this->stopsAt()) ? 'Ongoing' : 'Ended';
    }

    public function isLive(?Carbon $at = null): bool
    {
        return $this->status($at) === 'Ongoing';
    }

    /** The sale price of one unit whose regular price is $regular. */
    public function priceFor(float $regular): float
    {
        if ($this->discount_type === 'fixed') {
            return round(min($regular, (float) $this->discount_value), 2);
        }

        return round($regular * (100 - (float) $this->discount_value) / 100, 2);
    }

    /** "-20%" for the badge (a fixed price shows its percentage off the regular price). */
    public function percentOff(float $regular): int
    {
        if ($this->discount_type === 'percent') {
            return (int) round($this->discount_value);
        }

        return $regular > 0 ? (int) round(100 - $this->priceFor($regular) / $regular * 100) : 0;
    }

    /** The sale running on a product right now, if any. */
    public static function liveFor(int $productId): ?self
    {
        return self::where('product_id', $productId)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', now()))
            ->orderByDesc('id')
            ->first();
    }
}
