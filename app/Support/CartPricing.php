<?php

namespace App\Support;

use App\Models\Bundle;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariation;

/**
 * What each cart line costs, quantity by quantity (Seller Modules S8, A5):
 *
 *  - a live flash sale prices the first units it still has at the flash
 *    price; the rest of the line is a separate piece at the store sale
 *    price, or the regular price ("1 × ₱299 + 1 × ₱450");
 *  - a bundle deal takes its % off every item of its products once the
 *    cart has at least its number of them (any options);
 *  - buy X get Y makes Y units of the free product ₱0.00 while X are in the cart.
 *
 * A line is ['key', 'product', 'variation', 'quantity', 'pieces' => [['price', 'quantity', 'note']], 'total'].
 * Used by the cart, checkout and placing the order, so they always agree.
 */
class CartPricing
{
    /** Pieces of one product line before bundle deals: flash units first, then the rest. */
    public static function pieces(Product $product, ?ProductVariation $variation, int $quantity): array
    {
        $flash = !$variation ? $product->liveFlash() : null;
        $pieces = [];

        if ($flash && $quantity > 0) {
            $flashUnits = min($quantity, $flash->remaining());

            if ($flashUnits > 0) {
                $pieces[] = ['price' => round((float) $flash->flash_price, 2), 'quantity' => $flashUnits, 'note' => 'flash', 'flash_id' => $flash->id];
                $quantity -= $flashUnits;
            }
        }

        if ($quantity > 0) {
            $pieces[] = ['price' => $product->unitPrice($variation, false), 'quantity' => $quantity, 'note' => null];
        }

        return $pieces;
    }

    /**
     * Price whole cart lines: pieces for each, then the sellers' bundle deals.
     *
     * @param  array  $lines  [['key', 'product' => Product, 'variation' => ?ProductVariation, 'quantity' => int], ...]
     */
    public static function price(array $lines): array
    {
        foreach ($lines as $i => $line) {
            $lines[$i]['pieces'] = self::pieces($line['product'], $line['variation'] ?? null, (int) $line['quantity']);
        }

        $sellerIds = collect($lines)->map(fn ($line) => (int) $line['product']->seller_id)->unique()->filter();

        if ($sellerIds->isNotEmpty()) {
            $bundles = Bundle::whereIn('seller_id', $sellerIds)->where('is_active', true)->with('products:id')->orderBy('id')->get();

            foreach ($bundles as $bundle) {
                $lines = $bundle->type === 'bxgy' ? self::buyXGetY($lines, $bundle) : self::bundleDeal($lines, $bundle);
            }
        }

        foreach ($lines as $i => $line) {
            $lines[$i]['total'] = round(collect($line['pieces'])->sum(fn ($p) => $p['price'] * $p['quantity']), 2);
        }

        return $lines;
    }

    /** Plan lines for CheckoutPlan: one per piece. */
    public static function planLines(array $pricedLines): array
    {
        $plan = [];

        foreach ($pricedLines as $line) {
            foreach ($line['pieces'] as $piece) {
                $plan[] = [
                    'seller_id' => (int) $line['product']->seller_id,
                    'product_id' => (int) $line['product']->id,
                    'variation_id' => $line['variation']?->id,
                    'price' => (float) $piece['price'],
                    'quantity' => (int) $piece['quantity'],
                    'note' => $piece['note'],
                ];
            }
        }

        return $plan;
    }

    /** "1 × ₱299 (flash) + 1 × ₱450" for a cart or checkout line with more than one price. */
    public static function describe(array $pieces): string
    {
        return collect($pieces)->map(fn ($p) => $p['quantity'] . ' × ' . peso($p['price']) . match ($p['note']) {
            'flash' => ' (flash sale)',
            'bundle' => ' (bundle deal)',
            'free' => ' (free)',
            default => '',
        })->implode(' + ');
    }

    private static function bundleDeal(array $lines, Bundle $bundle): array
    {
        $productIds = $bundle->products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $count = 0;

        foreach ($lines as $line) {
            if ((int) $line['product']->seller_id === (int) $bundle->seller_id && in_array((int) $line['product']->id, $productIds, true)) {
                $count += collect($line['pieces'])->where('note', null)->sum('quantity');
            }
        }

        if ($count < (int) $bundle->min_quantity || (int) $bundle->percent <= 0) {
            return $lines;
        }

        foreach ($lines as $i => $line) {
            if ((int) $line['product']->seller_id !== (int) $bundle->seller_id || !in_array((int) $line['product']->id, $productIds, true)) {
                continue;
            }

            foreach ($line['pieces'] as $j => $piece) {
                if ($piece['note'] === null) {
                    $lines[$i]['pieces'][$j]['price'] = round($piece['price'] * (100 - (int) $bundle->percent) / 100, 2);
                    $lines[$i]['pieces'][$j]['note'] = 'bundle';
                }
            }
        }

        return $lines;
    }

    private static function buyXGetY(array $lines, Bundle $bundle): array
    {
        $bought = collect($lines)->filter(fn ($line) => (int) $line['product']->id === (int) $bundle->buy_product_id)->sum('quantity');
        $sets = intdiv((int) $bought, max(1, (int) $bundle->buy_quantity));
        $free = $sets * (int) $bundle->get_quantity;

        if ($free <= 0) {
            return $lines;
        }

        foreach ($lines as $i => $line) {
            if ((int) $line['product']->id !== (int) $bundle->get_product_id || $free <= 0) {
                continue;
            }

            $pieces = [];

            foreach ($line['pieces'] as $piece) {
                $take = min($free, (int) $piece['quantity']);

                if ($take > 0 && $piece['note'] !== 'flash') {
                    $pieces[] = ['price' => 0.0, 'quantity' => $take, 'note' => 'free'];
                    $free -= $take;

                    if ($piece['quantity'] - $take > 0) {
                        $pieces[] = ['price' => $piece['price'], 'quantity' => $piece['quantity'] - $take, 'note' => $piece['note']];
                    }
                } else {
                    $pieces[] = $piece;
                }
            }

            $lines[$i]['pieces'] = $pieces;
        }

        return $lines;
    }

    /** Cart session keys → lines for price() (unknown products and removed options skipped). */
    public static function fromCart(array $cart): array
    {
        $products = Product::whereIn('id', array_map(fn ($key) => (int) parseCartKey($key)[0], array_keys($cart)))->get()->keyBy('id');
        $lines = [];

        foreach ($cart as $key => $quantity) {
            [$productId, $variationId] = parseCartKey($key);
            $product = $products->get((int) $productId);
            $variation = $variationId ? ProductVariation::find($variationId) : null;

            if (!$product || ($variationId && !$variation) || (int) $quantity <= 0) {
                continue;
            }

            $lines[] = ['key' => $key, 'product' => $product, 'variation' => $variation, 'quantity' => (int) $quantity];
        }

        return $lines;
    }
}
