<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;

class OrderStock
{
    /**
     * Statuses where the items are still physically with the seller, so a
     * cancellation can safely put them straight back into inventory. Once a
     * rider has picked the parcel up, stock only comes back through the
     * Returned to Seller → restock flow.
     */
    public const RESTOCKABLE_ON_CANCEL = ['Pending', 'Processing'];

    /**
     * Put an order's items back into inventory and stamp restocked_at.
     *
     * Safe to call more than once — only the first call restores anything,
     * so a double-submit can never add the stock back twice.
     *
     * @param  int|null  $sellerId  Only restore this seller's items (null = all).
     * @return array{restored:int, skipped:int}|null  null if already restocked.
     */
    public static function restore(int $orderId, ?int $sellerId = null): ?array
    {
        return DB::transaction(function () use ($orderId, $sellerId) {

            $claimed = DB::table('orders')
                ->where('id', $orderId)
                ->whereNull('restocked_at')
                ->update(['restocked_at' => now()]);

            if (!$claimed) {
                return null;
            }

            $items = DB::table('order_items')
                ->where('order_id', $orderId)
                ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
                ->get();

            $restored = 0;
            $skipped = 0;

            foreach ($items as $item) {
                self::restockItem($item) ? $restored++ : $skipped++;
            }

            return ['restored' => $restored, 'skipped' => $skipped];
        });
    }

    /**
     * Put one order line's quantity back into stock. Returns false when its
     * variation no longer exists (removed by the seller since the order was
     * placed), so there's nothing safe to restock.
     */
    public static function restockItem(object $item, ?int $quantity = null): bool
    {
        $quantity ??= (int) $item->quantity;

        // Plain products carry no variation_label — restore the base
        // product's stock directly.
        if (empty($item->variation_label)) {

            Product::where('id', $item->product_id)
                ->increment('stock', $quantity);

            return true;
        }

        // Variation items only stored a display label ("Color: Red") at
        // checkout, not a variation_id — but that label is built from the
        // variation's own type/value, so it can be parsed back and matched.
        // Case-insensitive: older labels were saved as "color: red".
        [$variationType, $variationValue] = array_pad(
            explode(': ', $item->variation_label, 2),
            2,
            ''
        );

        $variation = ProductVariation::where('product_id', $item->product_id)
            ->whereRaw('LOWER(variation_type) = ?', [strtolower(trim($variationType))])
            ->whereRaw('LOWER(variation_value) = ?', [strtolower(trim($variationValue))])
            ->first();

        if (!$variation) {
            return false;
        }

        $variation->increment('stock', $quantity);

        return true;
    }

    /**
     * Everything a cancellation has to undo: the voucher use it consumed at
     * checkout, and — if the items never left the seller — the stock.
     *
     * Callers must only call this once per order, right after their own
     * conditional status update to Cancelled succeeds (that update is what
     * keeps a double-submit from running this twice).
     */
    public static function cancelled(int $orderId, string $previousStatus): void
    {
        $voucherCode = DB::table('orders')
            ->where('id', $orderId)
            ->value('voucher_code');

        if ($voucherCode) {
            DB::table('vouchers')
                ->where('code', $voucherCode)
                ->where('used_count', '>', 0)
                ->decrement('used_count');
        }

        if (in_array($previousStatus, self::RESTOCKABLE_ON_CANCEL)) {
            self::restore($orderId);
        }
    }
}
