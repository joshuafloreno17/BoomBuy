<?php

namespace App\Support;

use App\Models\Voucher;

/**
 * Splits a checkout into one order per seller — each seller packs, ships
 * and cancels their own parcel independently, so one seller's action can
 * never affect another seller's items. Used by both the checkout page (to
 * show the totals) and placeOrder (to create the orders), so they always
 * agree.
 *
 * Each seller's delivery fee depends on how far their parcel travels to the
 * buyer (ParcelRoute::zone); without a known buyer location the middle tier
 * is shown until an address is entered.
 *
 * Each line: ['seller_id', 'price', 'quantity', ...anything else].
 */
class CheckoutPlan
{
    /**
     * @return array{
     *   orders: array<int, array{seller_id:int, lines:array, subtotal:float, discount:float, voucher_code:?string, zone:string, delivery_fee:float, total:float}>,
     *   subtotal: float, discount: float, delivery_fee: float, total: float
     * }
     */
    public static function build(array $lines, ?Voucher $voucher = null, ?array $buyerLocation = null): array
    {
        $orders = [];

        foreach ($lines as $line) {
            $sellerId = (int) $line['seller_id'];

            $orders[$sellerId] ??= ['seller_id' => $sellerId, 'lines' => [], 'subtotal' => 0.0];
            $orders[$sellerId]['lines'][] = $line;
            $orders[$sellerId]['subtotal'] += (float) $line['price'] * (int) $line['quantity'];
        }

        // A seller's voucher applies to that seller's order only. A platform
        // voucher (no seller) goes on the largest order.
        $voucherSellerId = null;

        if ($voucher) {
            $voucherSellerId = $voucher->seller_id
                ? (int) $voucher->seller_id
                : collect($orders)->sortByDesc('subtotal')->keys()->first();

            $target = $orders[$voucherSellerId] ?? null;

            if (!$target || !$voucher->isValidFor($target['subtotal'])) {
                $voucherSellerId = null;
            }
        }

        foreach ($orders as $sellerId => &$order) {
            $order['subtotal'] = round($order['subtotal'], 2);

            $order['discount'] = $sellerId === $voucherSellerId
                ? $voucher->calculateDiscount($order['subtotal'])
                : 0.0;

            $order['voucher_code'] = $sellerId === $voucherSellerId ? $voucher->code : null;

            $itemsTotal = max(0, $order['subtotal'] - $order['discount']);

            // Each seller's parcel is delivered separately, from the seller's town.
            $order['zone'] = ParcelRoute::zone(ParcelRoute::sellerLocation($sellerId), $buyerLocation);
            $order['delivery_fee'] = DeliveryFee::for($itemsTotal, $order['zone']);
            $order['total'] = round($itemsTotal + $order['delivery_fee'], 2);
        }
        unset($order);

        $orders = array_values($orders);

        return [
            'orders' => $orders,
            'subtotal' => round(array_sum(array_column($orders, 'subtotal')), 2),
            'discount' => round(array_sum(array_column($orders, 'discount')), 2),
            'delivery_fee' => round(array_sum(array_column($orders, 'delivery_fee')), 2),
            'total' => round(array_sum(array_column($orders, 'total')), 2),
        ];
    }
}
