<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A flash sale (Seller Modules S8, A5): a flash price for a limited
 * quantity during one time slot. While units are left the flash price wins
 * over a store sale; units beyond them are charged at the store sale price,
 * or the regular price, on a separate line.
 */
class FlashSale extends Model
{
    /** Time slots a flash sale can run in: [start hour, end hour]. */
    public const SLOTS = [[0, 2], [2, 4], [4, 6], [6, 8], [8, 10], [10, 12], [12, 14], [14, 16], [16, 18], [18, 20], [20, 22], [22, 24]];

    protected $fillable = ['seller_id', 'product_id', 'starts_at', 'ends_at', 'flash_price', 'quantity', 'sold'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'flash_price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function remaining(): int
    {
        return max(0, (int) $this->quantity - (int) $this->sold);
    }

    public function status(?Carbon $at = null): string
    {
        $at ??= now();

        return match (true) {
            $at->lt($this->starts_at) => 'Upcoming',
            $at->gte($this->ends_at) => 'Ended',
            $this->remaining() <= 0 => 'Sold out',
            default => 'Ongoing',
        };
    }

    /** Live with units left. */
    public function isLive(): bool
    {
        return $this->status() === 'Ongoing';
    }

    /** Every live flash sale is read once per request, not once per product card. */
    public static function liveFor(int $productId): ?self
    {
        $attributes = request()->attributes;
        $key = self::class . '.live';

        if (!$attributes->has($key)) {
            $attributes->set($key, self::where('starts_at', '<=', now())
                ->where('ends_at', '>', now())
                ->whereColumn('sold', '<', 'quantity')
                ->orderBy('id')
                ->get()
                ->groupBy('product_id'));
        }

        return $attributes->get($key)->get($productId)?->first();
    }

    /** Forget the live list after units are claimed, so this request re-reads it. */
    public static function forgetLive(): void
    {
        request()->attributes->remove(self::class . '.live');
    }

    /** The next flash sale of a product today, before it starts ("upcoming flash sale"). */
    public static function upcomingFor(int $productId): ?self
    {
        return self::where('product_id', $productId)
            ->where('starts_at', '>', now())
            ->where('starts_at', '<', now()->endOfDay())
            ->orderBy('starts_at')
            ->first();
    }
}
