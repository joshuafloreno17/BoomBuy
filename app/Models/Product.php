<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    // A deleted product stays on old orders (name, photo, price paid).
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'name',
        'category',
        'price',
        'original_price',
        'discount_percent',
        'stock',
        'description',
        'image',
        'is_flagged',
        'flag_reason',
        'is_archived',
        'specs',
        'brand',
        'condition',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'is_draft',
    ];

    protected $casts = [
        'is_flagged' => 'boolean',
        'is_archived' => 'boolean',
        'is_draft' => 'boolean',
        'discount_percent' => 'integer',
        // Device details (RAM, screen…) — see App\Support\DeviceSpecs.
        'specs' => 'array',
    ];

    public const MAX_DISCOUNT = 90;

    /**
     * The columns to save for a regular price and a "% off": `price` is what
     * the buyer pays, `original_price` the crossed-out price before the discount.
     *
     * @return array{price: float, original_price: ?float, discount_percent: ?int}
     */
    public static function pricing(float $regular, ?int $discount): array
    {
        $discount = (int) $discount;

        if ($discount <= 0) {
            return ['price' => round($regular, 2), 'original_price' => null, 'discount_percent' => null];
        }

        return [
            'price' => round($regular * (100 - $discount) / 100, 2),
            'original_price' => round($regular, 2),
            'discount_percent' => $discount,
        ];
    }

    /** The store sale running now (loaded once per product per request). */
    private ?ProductSale $liveSaleMemo = null;

    private bool $liveSaleLoaded = false;

    public function liveSale(): ?ProductSale
    {
        if (!$this->liveSaleLoaded) {
            $this->liveSaleMemo = $this->id ? ProductSale::liveFor((int) $this->id) : null;
            $this->liveSaleLoaded = true;
        }

        return $this->liveSaleMemo;
    }

    /** Load the live sales of many products in one query (shop pages). */
    public static function preloadLiveSales(iterable $products): void
    {
        $products = collect($products);
        $sales = ProductSale::whereIn('product_id', $products->pluck('id'))
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', now()))
            ->orderBy('id')
            ->get()
            ->keyBy('product_id');

        foreach ($products as $product) {
            $product->liveSaleMemo = $sales->get($product->id);
            $product->liveSaleLoaded = true;
        }
    }

    /** One unit's price before any discount or sale (an option's own extra included). */
    public function regularUnitPrice(?ProductVariation $variation = null): float
    {
        return round($this->regularPrice() + (float) ($variation->price_adjustment ?? 0), 2);
    }

    /**
     * What one unit costs a buyer right now — the one price used by the
     * product page, cards, cart, checkout and the order: a running store
     * sale, else the product's own "% off", else the regular price.
     */
    public function unitPrice(?ProductVariation $variation = null, bool $withFlash = true): float
    {
        // A live flash sale with units left wins over a store sale (A5).
        if ($withFlash && !$variation && ($flash = $this->liveFlash())) {
            return round((float) $flash->flash_price, 2);
        }

        if ($sale = $this->liveSale()) {
            return $sale->priceFor($this->regularUnitPrice($variation));
        }

        return round((float) $this->price + (float) ($variation->price_adjustment ?? 0), 2);
    }

    /** The flash sale running now with units left (loaded once per request). */
    private ?FlashSale $liveFlashMemo = null;

    private bool $liveFlashLoaded = false;

    public function liveFlash(): ?FlashSale
    {
        if (!$this->liveFlashLoaded) {
            $this->liveFlashMemo = $this->id ? FlashSale::liveFor((int) $this->id) : null;
            $this->liveFlashLoaded = true;
        }

        return $this->liveFlashMemo;
    }

    /** % off shown on the badge right now (flash, sale or the product's own discount), null if none. */
    public function percentOffNow(): ?int
    {
        if ($flash = $this->liveFlash()) {
            $regular = $this->regularUnitPrice();

            return $regular > 0 ? (int) round(100 - $flash->flash_price / $regular * 100) : null;
        }

        if ($sale = $this->liveSale()) {
            return $sale->percentOff($this->regularUnitPrice());
        }

        return $this->isDiscounted() ? (int) $this->discount_percent : null;
    }

    /** The price before any discount (what the seller typed as "Price"). */
    public function regularPrice(): float
    {
        return (float) ($this->original_price ?? $this->price);
    }

    public function isDiscounted(): bool
    {
        return $this->discount_percent > 0 && $this->original_price !== null;
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    /** The seller's own store categories (up to 3); none = "Uncategorized". */
    public function storeCategories()
    {
        return $this->belongsToMany(StoreCategory::class, 'product_store_category')->withTimestamps();
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
            ->where('products.is_draft', false)
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
            . " WHERE oi.product_id = products.id AND o.status IN ('Delivered', 'Completed')) as sold_count"
        );
    }

    public const VACATION_MESSAGE = 'This store is on vacation, so its products can\'t be ordered right now.';

    /**
     * Is the seller's store in vacation mode? Its products stay visible
     * (search, shop page, product page) but can't be added, bought or checked out.
     */
    public function sellerOnVacation(): bool
    {
        return self::storeOnVacation((int) $this->seller_id);
    }

    public static function storeOnVacation(int $sellerId): bool
    {
        return DB::table('seller_applications')
            ->where('user_id', $sellerId)
            ->where('vacation_mode', true)
            ->exists();
    }

    /** Same rule as onSale(), for a single product already loaded. */
    public function isPurchasable(): bool
    {
        if ($this->is_archived || $this->is_flagged || $this->is_draft || $this->trashed()) {
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
            $type = $this->variations()->first()?->kindLabel() ?: 'option';

            return [null, 'Please choose a ' . $type . ' for ' . $this->name . ' first.'];
        }

        return [$variation, null];
    }
}