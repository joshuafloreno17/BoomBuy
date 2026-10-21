<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Buyer home "For you": products from the categories this buyer cares
 * about. Signals, strongest first: bought, in the cart or wishlist, viewed
 * and searched this session; with no history yet, the buyer's sex from
 * registration (or the best-selling categories).
 */
class ForYou
{
    private const VIEWED_KEY = 'bb_viewed';
    private const SEARCHED_KEY = 'bb_searched';

    /** Categories that sell best by the buyer's sex — the starting point before any history. */
    private const PROFILE = [
        'Male' => ['electronics' => 3, 'sports-outdoors' => 3, 'mens-fashion' => 3, 'shoes' => 2.5, 'automotive' => 1.5],
        'Female' => ['womens-fashion' => 3, 'beauty-personal-care' => 3, 'electronics' => 2.5, 'shoes' => 2.5, 'jewelry-accessories' => 1.5, 'home-living' => 1.5],
    ];

    /** Remembers a product page the buyer opened (last 20, this session). */
    public static function viewed(int $productId): void
    {
        $ids = array_values(array_diff(session(self::VIEWED_KEY, []), [$productId]));
        array_unshift($ids, $productId);
        session([self::VIEWED_KEY => array_slice($ids, 0, 20)]);
    }

    /** Remembers a search (last 10, this session). */
    public static function searched(string $term): void
    {
        $term = mb_strtolower(trim($term));
        if ($term === '') {
            return;
        }

        $terms = array_values(array_diff(session(self::SEARCHED_KEY, []), [$term]));
        array_unshift($terms, $term);
        session([self::SEARCHED_KEY => array_slice($terms, 0, 10)]);
    }

    /**
     * Up to $take products (already-bought ones left out), mixed across the
     * buyer's top categories, plus those categories' labels for the heading.
     *
     * @param  \Closure  $visible  Returns the dashboard's base product query.
     * @return array{products: Collection, categories: array<int, string>}
     */
    public static function picks(int $buyerId, ?string $sex, array $wishlistIds, array $cartProductIds, \Closure $visible, int $take = 6): array
    {
        $bought = self::boughtIds($buyerId);
        $terms = session(self::SEARCHED_KEY, []);
        $scores = self::categoryScores($buyerId, $sex, $wishlistIds, $cartProductIds);

        $top = $scores->sortDesc()->keys()->take(4)->values();

        $seed = self::seed($buyerId);
        $byCategory = $top->mapWithKeys(function ($slug) use ($visible, $bought, $terms, $seed) {
            $products = $visible()
                ->withSoldCount()
                ->where('category', Categories::LIST[$slug])
                ->whereNotIn('products.id', $bought)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->get()
                ->sortByDesc(fn ($p) => self::appeal($p, $terms, $seed))
                ->values();

            return [$slug => $products];
        })->filter(fn ($products) => $products->isNotEmpty());

        // Take turns across the categories so one doesn't fill the whole row.
        $picked = collect();
        for ($round = 0; $picked->count() < $take && $byCategory->contains(fn ($p) => $p->has($round)); $round++) {
            foreach ($byCategory as $products) {
                if ($picked->count() < $take && $products->has($round)) {
                    $picked->push($products[$round]);
                }
            }
        }

        return [
            'products' => $picked,
            'categories' => $byCategory->keys()->take(3)->map(fn ($slug) => Categories::LIST[$slug])->all(),
        ];
    }

    /**
     * The Shop's "For you" order for these products (any filters already
     * applied): id => position. Categories take turns in proportion to the
     * buyer's interest, so the favourites fill the first rows while the
     * rest still show further down. Guests get the best-selling categories.
     *
     * @param  Collection  $products  id, category, price, discount_percent, sold_count
     * @return array<int, int>
     */
    public static function shopOrder(Collection $products, ?array $sessionUser): array
    {
        $isBuyer = ($sessionUser['role'] ?? null) === 'buyer';
        $buyerId = $isBuyer ? (int) $sessionUser['id'] : 0;

        $scores = $isBuyer
            ? self::categoryScores(
                $buyerId,
                DB::table('users')->where('id', $buyerId)->value('sex'),
                DB::table('wishlists')->where('user_id', $buyerId)->pluck('product_id')->all(),
                collect(array_keys(CartContents::prune()))->map(fn ($key) => (int) parseCartKey($key)[0])->unique()->all()
            )
            : self::categoryScores(0, null, [], []);

        $terms = $isBuyer ? session(self::SEARCHED_KEY, []) : [];
        $seed = self::seed($buyerId);

        // Within a category: most appealing first. Then category turn n of a
        // category with interest s lands at n / s — higher interest, more turns.
        return $products
            ->groupBy(fn ($p) => Categories::slug($p->category) ?? '')
            ->flatMap(function ($items, $slug) use ($scores, $terms, $seed) {
                $interest = max(0.5, (float) ($scores[$slug] ?? 0.5));

                return $items->sortByDesc(fn ($p) => self::appeal($p, $terms, $seed))
                    ->values()
                    ->map(fn ($p, $turn) => ['id' => $p->id, 'key' => ($turn + 1) / $interest]);
            })
            ->sortBy('key')
            ->values()
            ->mapWithKeys(fn ($row, $position) => [$row['id'] => $position])
            ->all();
    }

    /** How much each category interests the buyer (slug => score). */
    private static function categoryScores(int $buyerId, ?string $sex, array $wishlistIds, array $cartProductIds): Collection
    {
        $bought = $buyerId ? self::boughtIds($buyerId) : [];
        $viewed = $buyerId ? session(self::VIEWED_KEY, []) : [];
        $terms = $buyerId ? session(self::SEARCHED_KEY, []) : [];

        // Category scores: history counts double the profile.
        $scores = collect(self::PROFILE[$sex ?? ''] ?? array_fill_keys(array_slice(Categories::POPULAR, 0, 5), 2));

        $signal = function (array $ids, float $weight) use (&$scores) {
            Product::whereIn('id', array_unique($ids))->pluck('category')
                ->each(function ($category) use (&$scores, $weight) {
                    if ($slug = Categories::slug($category)) {
                        $scores[$slug] = ($scores[$slug] ?? 0) + $weight * 2;
                    }
                });
        };
        $signal($bought, 4);
        $signal($cartProductIds, 3);
        $signal($wishlistIds, 3);
        $signal(array_slice($viewed, 0, 10), 2);

        // A search names a category ("shoes") or matches products in one ("basketball").
        foreach (array_slice($terms, 0, 5) as $term) {
            foreach (Categories::LIST as $slug => $label) {
                if (str_contains(mb_strtolower($label), $term)) {
                    $scores[$slug] = ($scores[$slug] ?? 0) + 4;
                }
            }

            Product::onSale()
                ->where('name', 'like', '%' . addcslashes($term, '%_\\') . '%')
                ->pluck('category')
                ->each(function ($category) use (&$scores) {
                    if ($slug = Categories::slug($category)) {
                        $scores[$slug] = ($scores[$slug] ?? 0) + 2;
                    }
                });
        }

        return $scores;
    }

    /**
     * Searched-for first, then best sellers, deals and the eye-catching
     * pricier items (a phone over a cable), with a daily shuffle so the
     * order changes from day to day.
     */
    private static function appeal($product, array $terms, int $seed): float
    {
        $name = mb_strtolower((string) $product->name);
        $match = collect($terms)->contains(fn ($t) => str_contains($name, $t)) ? 1000 : 0;

        return $match + (int) $product->sold_count * 10 + (int) $product->discount_percent
            + log10(max(10, (float) $product->price)) * 8 + (crc32($seed . '-' . $product->id) % 25);
    }

    private static function seed(int $buyerId): int
    {
        return crc32($buyerId . now()->toDateString());
    }

    private static function boughtIds(int $buyerId): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.buyer_id', $buyerId)
            ->pluck('order_items.product_id')
            ->all();
    }
}
