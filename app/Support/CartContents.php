<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariation;

/**
 * Keeps the session cart honest: lines whose product (or chosen option) was
 * deleted are dropped, so the navbar badge never counts items the cart page
 * can't show. (Archived or out-of-stock items stay — the cart page explains
 * those.)
 */
class CartContents
{
    /** @return array<string, int> the cleaned cart (also saved back to the session) */
    public static function prune(): array
    {
        $cart = session()->get('cart', []);

        if (!$cart) {
            return [];
        }

        $productIds = [];
        $variationIds = [];

        foreach (array_keys($cart) as $key) {
            [$productId, $variationId] = parseCartKey($key);
            $productIds[] = (int) $productId;

            if ($variationId) {
                $variationIds[] = (int) $variationId;
            }
        }

        // A product the seller deleted stays too, shown as unavailable (B6).
        $products = Product::withTrashed()->whereIn('id', $productIds)->pluck('id')->flip();
        $variations = $variationIds
            // Removed (soft-deleted) options stay: the cart shows them as unavailable.
            ? ProductVariation::withTrashed()->whereIn('id', $variationIds)->pluck('product_id', 'id')
            : collect();

        $clean = array_filter($cart, function ($quantity, $key) use ($products, $variations) {
            [$productId, $variationId] = parseCartKey($key);

            if ((int) $quantity <= 0 || !$products->has((int) $productId)) {
                return false;
            }

            // The option must still exist and belong to that product.
            return !$variationId || (int) ($variations[(int) $variationId] ?? 0) === (int) $productId;
        }, ARRAY_FILTER_USE_BOTH);

        if (count($clean) !== count($cart)) {
            session()->put('cart', $clean);
        }

        return $clean;
    }

    /** Items the buyer can still buy: a removed option's line stays in the cart but isn't counted. */
    public static function count(): int
    {
        $cart = self::prune();
        $variationIds = collect(array_keys($cart))->map(fn ($key) => (int) parseCartKey($key)[1])->filter();
        $removed = $variationIds->isNotEmpty()
            ? ProductVariation::onlyTrashed()->whereIn('id', $variationIds)->pluck('id')->flip()
            : collect();
        $deleted = Product::onlyTrashed()->whereIn('id', collect(array_keys($cart))->map(fn ($key) => (int) parseCartKey($key)[0]))->pluck('id')->flip();

        return (int) collect($cart)->reject(fn ($quantity, $key) => $removed->has((int) parseCartKey($key)[1]) || $deleted->has((int) parseCartKey($key)[0]))->sum();
    }
}
