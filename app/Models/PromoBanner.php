<?php

namespace App\Models;

use App\Support\Categories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A promo banner on the landing page and buyer home (Admin → Promo Banners).
 * Live = switched on and today falls between its start and end dates.
 */
class PromoBanner extends Model
{
    public const PLACEMENTS = [
        'main' => 'Main carousel',
        'side_top' => 'Side tile · top',
        'side_bottom' => 'Side tile · bottom',
    ];

    public const LINK_TYPES = [
        'products' => 'All products',
        'deals' => 'On sale (products with % off)',
        'category' => 'A category',
        'product' => 'One product',
        'shop' => 'One shop',
    ];

    public const STYLES = [
        'violet' => 'Violet',
        'lavender' => 'Lavender',
        'plum' => 'Plum (dark)',
        'light' => 'Light',
    ];

    protected $fillable = [
        'placement', 'eyebrow', 'title', 'subtitle', 'big_text', 'button_label',
        'link_type', 'link_value', 'style', 'image', 'starts_at', 'ends_at', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    /** Switched on and inside its dates (an empty date means "no limit"). */
    public function scopeLive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today));
    }

    /** Live | Scheduled | Ended | Off, for the admin list. */
    public function status(): string
    {
        if (!$this->is_active) {
            return 'Off';
        }

        $today = now()->startOfDay();

        if ($this->ends_at && $this->ends_at->lt($today)) {
            return 'Ended';
        }

        if ($this->starts_at && $this->starts_at->gt($today)) {
            return 'Scheduled';
        }

        return 'Live';
    }

    /** Where the banner's button goes. Falls back to the shop when its target is gone. */
    public function url(): string
    {
        return match ($this->link_type) {
            'deals' => route('products', ['deals' => 1]),
            'category' => Categories::slug($this->link_value)
                ? route('products', ['category' => Categories::slug($this->link_value)])
                : route('products'),
            'product' => ctype_digit((string) $this->link_value)
                ? route('product.details', (int) $this->link_value)
                : route('products'),
            'shop' => ctype_digit((string) $this->link_value)
                ? route('shop.seller', (int) $this->link_value)
                : route('products'),
            default => route('products'),
        };
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('storage/' . ltrim($this->image, '/')) : null;
    }
}
