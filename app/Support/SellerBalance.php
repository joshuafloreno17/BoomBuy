<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A seller's delivered sales and BoomBuy's commission on them — the figures
 * on the seller's and the admin's Reports pages.
 *
 * Per delivered order, the seller earns:
 *     their items − their own vouchers − refunds BoomBuy sent − commission
 * (the commission rate saved on the order when it was placed).
 */
class SellerBalance
{
    /**
     * Delivered sales after the seller's vouchers and refunds, and the
     * commission on them (each order at its own saved rate), for orders
     * placed between $from and $to.
     *
     * @return array{sales: float, commission: float, refunds: float, earnings: float}
     */
    public static function summary(int $sellerId, ?string $from = null, ?string $to = null): array
    {
        $orders = self::orders($sellerId, $from, $to);
        $sales = round((float) $orders->sum(fn ($o) => max(0, $o->items - $o->voucher - $o->refunds)), 2);
        $commission = round((float) $orders->sum('commission'), 2);

        return [
            'sales' => $sales,
            'commission' => $commission,
            'refunds' => round((float) $orders->sum('refunds'), 2),
            'earnings' => round($sales - $commission, 2),
        ];
    }

    /** One row per delivered order with this seller's items, newest first (optionally placed between $from and $to). */
    public static function orders(int $sellerId, ?string $from = null, ?string $to = null): Collection
    {
        $rows = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'orders.voucher_code')
            ->where('order_items.seller_id', $sellerId)
            ->whereIn('orders.status', OrderStatus::DONE)
            ->when($from, fn ($q) => $q->whereDate('orders.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('orders.created_at', '<=', $to))
            ->groupBy('orders.id', 'orders.delivered_at', 'orders.discount_amount', 'orders.commission_rate', 'vouchers.seller_id')
            ->orderByDesc('orders.delivered_at')
            ->orderByDesc('orders.id')
            ->selectRaw('orders.id, orders.delivered_at, orders.discount_amount, orders.commission_rate, vouchers.seller_id as voucher_seller_id, SUM(order_items.price * order_items.quantity) as items')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $refunds = DB::table('return_refund_requests')
            ->whereIn('order_id', $rows->pluck('id'))
            ->where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->whereNotNull('refunded_at')
            ->selectRaw('order_id, SUM(refund_amount) as total')
            ->groupBy('order_id')
            ->pluck('total', 'order_id');

        $defaultRate = (float) PlatformSetting::get('commission_rate', '10');

        return $rows->map(function ($row) use ($sellerId, $refunds, $defaultRate) {
            $items = (float) $row->items;
            // Only the seller's own vouchers come out of their sales.
            $voucher = (int) $row->voucher_seller_id === $sellerId ? (float) $row->discount_amount : 0.0;
            $refunded = (float) ($refunds[$row->id] ?? 0);
            $net = max(0, $items - $voucher - $refunded);
            $rate = $row->commission_rate !== null ? (float) $row->commission_rate : $defaultRate;
            $commission = round($net * $rate / 100, 2);

            return (object) [
                'id' => (int) $row->id,
                'delivered_at' => $row->delivered_at,
                'items' => round($items, 2),
                'voucher' => round($voucher, 2),
                'refunds' => round($refunded, 2),
                'rate' => $rate,
                'commission' => $commission,
                'earning' => round($net - $commission, 2),
            ];
        });
    }
}
