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
 * Vouchers (Buyer Modules A2, A4, A8):
 *  - a store voucher discounts only its store's order (selected products
 *    only when it is limited to some); its minimum spend counts what the
 *    buyer pays for those items;
 *  - a platform voucher is checked on the whole checkout and split across
 *    the store orders in proportion to their merchandise subtotal (rounded
 *    to the centavo, the remainder to the largest store) — BoomBuy funds it;
 *  - a shipping voucher takes up to its amount off the shipping fees, never
 *    more than the fees.
 *
 * Each seller's delivery fee depends on how far their parcel travels to the
 * buyer (ParcelRoute::zone); without a known buyer location the middle tier
 * is shown until an address is entered. Picking up at the buyer's Sorting
 * Center (fulfillment "pickup") drops the rider's part of the fee.
 *
 * Each line: ['seller_id', 'price', 'quantity', 'product_id', ...anything else].
 */
class CheckoutPlan
{
    /**
     * @param  Voucher|iterable<Voucher>|null  $vouchers
     * @return array{
     *   orders: array<int, array>, subtotal: float, discount: float, platform_discount: float,
     *   shipping_discount: float, delivery_fee: float, total: float, applied: string[], problems: array<string, string>
     * }
     */
    public static function build(array $lines, Voucher|iterable|null $vouchers = null, ?array $buyerLocation = null, string $fulfillment = 'delivery'): array
    {
        $vouchers = collect($vouchers instanceof Voucher ? [$vouchers] : ($vouchers ?? []))->filter()->values();
        $orders = [];

        foreach ($lines as $line) {
            $sellerId = (int) $line['seller_id'];

            $orders[$sellerId] ??= ['seller_id' => $sellerId, 'lines' => [], 'subtotal' => 0.0];
            $orders[$sellerId]['lines'][] = $line;
            $orders[$sellerId]['subtotal'] += (float) $line['price'] * (int) $line['quantity'];
        }

        $applied = [];
        $problems = [];

        // Store vouchers: the last one chosen for a store wins (one per store).
        $storeVouchers = [];
        $platform = null;
        $shipping = null;

        foreach ($vouchers as $voucher) {
            match ($voucher->kind()) {
                'store' => $storeVouchers[(int) $voucher->seller_id] = $voucher,
                'shipping' => $shipping = $voucher,
                default => $platform = $voucher,
            };
        }

        foreach ($orders as $sellerId => &$order) {
            $order['subtotal'] = round($order['subtotal'], 2);
            $order['discount'] = 0.0;
            $order['voucher_code'] = null;
            $order['platform_discount'] = 0.0;
            $order['platform_voucher_code'] = null;
            $order['shipping_discount'] = 0.0;
            $order['shipping_voucher_code'] = null;

            $voucher = $storeVouchers[$sellerId] ?? null;

            if ($voucher) {
                $eligible = $voucher->eligibleSubtotal($order['lines']);
                $problem = $eligible <= 0
                    ? 'Voucher ' . $voucher->code . ' does not apply to the items in your order.'
                    : $voucher->problemFor($eligible);

                if ($problem) {
                    $problems[$voucher->code] = $problem;
                } else {
                    $order['discount'] = $voucher->calculateDiscount($eligible);
                    $order['voucher_code'] = $voucher->code;
                    $applied[] = $voucher->code;
                }
            }
        }
        unset($order);

        foreach ($storeVouchers as $sellerId => $voucher) {
            if (!isset($orders[$sellerId])) {
                $problems[$voucher->code] = 'Voucher ' . $voucher->code . ' is for a store whose items are not in this checkout.';
            }
        }

        $merchandise = round(array_sum(array_column($orders, 'subtotal')), 2);

        // Platform voucher: checked on the whole checkout, split by store.
        if ($platform && $orders) {
            if ($problem = $platform->problemFor($merchandise)) {
                $problems[$platform->code] = $problem;
            } else {
                $total = min($platform->calculateDiscount($merchandise), round($merchandise - array_sum(array_column($orders, 'discount')), 2));
                self::split($orders, $total, 'subtotal', 'platform_discount');

                foreach ($orders as &$order) {
                    $order['platform_voucher_code'] = $platform->code;
                }
                unset($order);

                $applied[] = $platform->code;
            }
        }

        foreach ($orders as $sellerId => &$order) {
            $itemsTotal = max(0, $order['subtotal'] - $order['discount'] - $order['platform_discount']);

            // Each seller's parcel is delivered separately, from the seller's town.
            $order['zone'] = ParcelRoute::zone(ParcelRoute::sellerLocation($sellerId), $buyerLocation);
            $order['weight_kg'] = self::weight($order['lines']);
            $order['delivery_fee'] = DeliveryFee::for($itemsTotal, $order['zone'], $fulfillment, $order['weight_kg']);

            // Logistics → Zones & Rates: the route's own rate, when one is set.
            if ($fulfillment === 'delivery' && $order['delivery_fee'] > 0
                && ($zoneFee = ShippingZones::fee(ParcelRoute::sellerLocation($sellerId)['province'], $buyerLocation['province'] ?? null, $order['weight_kg'])) !== null) {
                $order['delivery_fee'] = $zoneFee;
            }
            $order['eta'] = ParcelRoute::etaLabel($order['zone']);
        }
        unset($order);

        // Shipping voucher: up to its amount off the fees, never more than them.
        if ($shipping && $orders) {
            $fees = round(array_sum(array_column($orders, 'delivery_fee')), 2);

            if ($problem = $shipping->problemFor($merchandise)) {
                $problems[$shipping->code] = $problem;
            } elseif ($fees <= 0) {
                $problems[$shipping->code] = 'Shipping is already free, so voucher ' . $shipping->code . ' is not needed.';
            } else {
                $left = $shipping->calculateDiscount($fees);

                foreach (collect($orders)->sortByDesc('delivery_fee')->keys() as $sellerId) {
                    $take = round(min($left, $orders[$sellerId]['delivery_fee']), 2);
                    $orders[$sellerId]['shipping_discount'] = $take;
                    $orders[$sellerId]['shipping_voucher_code'] = $shipping->code;
                    $left = round($left - $take, 2);
                }

                $applied[] = $shipping->code;
            }
        }

        foreach ($orders as &$order) {
            // Never below zero, whatever the vouchers add up to.
            $order['total'] = round(max(0, $order['subtotal'] - $order['discount'] - $order['platform_discount'])
                + max(0, $order['delivery_fee'] - $order['shipping_discount']), 2);
        }
        unset($order);

        $orders = array_values($orders);

        return [
            'orders' => $orders,
            'subtotal' => $merchandise,
            'discount' => round(array_sum(array_column($orders, 'discount')), 2),
            'platform_discount' => round(array_sum(array_column($orders, 'platform_discount')), 2),
            'shipping_discount' => round(array_sum(array_column($orders, 'shipping_discount')), 2),
            'delivery_fee' => round(array_sum(array_column($orders, 'delivery_fee')), 2),
            'total' => round(array_sum(array_column($orders, 'total')), 2),
            'applied' => $applied,
            'problems' => $problems,
            // Set when zones exist and none covers the buyer's province (Place Order is blocked).
            'unserviceable' => $fulfillment === 'delivery' && $orders && !ShippingZones::serviceable($buyerLocation['province'] ?? null)
                ? ShippingZones::unserviceableMessage($buyerLocation['province'] ?? null)
                : null,
        ];
    }

    /**
     * Share $amount across the orders in proportion to $by, each rounded to
     * the centavo; what rounding leaves over goes to the largest order.
     */
    private static function split(array &$orders, float $amount, string $by, string $into): void
    {
        $base = array_sum(array_column($orders, $by));

        if ($base <= 0 || $amount <= 0) {
            return;
        }

        $given = 0.0;

        foreach ($orders as $key => $order) {
            $orders[$key][$into] = round($amount * $order[$by] / $base, 2);
            $given += $orders[$key][$into];
        }

        $largest = collect($orders)->sortByDesc($by)->keys()->first();
        $orders[$largest][$into] = round($orders[$largest][$into] + round($amount - $given, 2), 2);
    }

    /** The order's total weight (kg) from its products; null when none is recorded. */
    private static function weight(array $lines): ?float
    {
        $ids = array_filter(array_map(fn ($line) => (int) ($line['product_id'] ?? 0), $lines));

        if (!$ids) {
            return null;
        }

        $weights = \App\Models\Product::withTrashed()->whereIn('id', $ids)->pluck('weight_kg', 'id');
        $total = 0.0;
        $known = false;

        foreach ($lines as $line) {
            $kg = $weights[(int) ($line['product_id'] ?? 0)] ?? null;

            if ($kg !== null) {
                $known = true;
                $total += (float) $kg * (int) $line['quantity'];
            }
        }

        return $known ? round($total, 2) : null;
    }
}
