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

        $products = Product::whereIn('id', $productIds)->pluck('id')->flip();
        $variations = $variationIds
            ? ProductVariation::whereIn('id', $variationIds)->pluck('product_id', 'id')
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

    public static function count(): int
    {
        return (int) array_sum(self::prune());
    }
}
