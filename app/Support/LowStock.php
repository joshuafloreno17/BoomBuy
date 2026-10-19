<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;

/**
 * The seller's low-stock alert (Seller Modules A29): one notification when
 * a product's or option's stock reaches THRESHOLD or fewer, and not again
 * until it has gone back above THRESHOLD and dropped again.
 */
class LowStock
{
    public const THRESHOLD = 5;

    /** Check one product (without options) or one option after its stock changed. */
    public static function check(int $productId, ?int $variationId = null): void
    {
        // With options, only the options' stock is sold (the product's own is unused).
        if (!$variationId && DB::table('product_variations')->where('product_id', $productId)->whereNull('deleted_at')->exists()) {
            return;
        }

        $table = $variationId ? 'product_variations' : 'products';
        $row = DB::table($table)->where('id', $variationId ?: $productId)->first(['id', 'stock', 'low_stock_alerted']);

        if (!$row) {
            return;
        }

        if ((int) $row->stock > self::THRESHOLD) {
            if ($row->low_stock_alerted) {
                DB::table($table)->where('id', $row->id)->update(['low_stock_alerted' => false]);
            }

            return;
        }

        // Claim the alert: only the first request that sees the crossing sends it.
        $claimed = DB::table($table)->where('id', $row->id)->where('low_stock_alerted', false)->update(['low_stock_alerted' => true]);

        if (!$claimed) {
            return;
        }

        $product = Product::withTrashed()->find($productId);

        if (!$product || !$product->seller_id) {
            return;
        }

        $label = $variationId ? ' (' . (ProductVariation::withTrashed()->find($variationId)?->shortLabel() ?? 'option') . ')' : '';
        $stock = (int) $row->stock;

        createNotification(
            (int) $product->seller_id,
            'Low Stock Alert',
            $product->name . $label . ($stock <= 0 ? ' is out of stock.' : ' has only ' . $stock . ' left.') . ' Restock it to keep selling.',
            'product',
            (int) $product->id
        );
    }
}
