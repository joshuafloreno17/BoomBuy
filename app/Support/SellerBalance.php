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

    /** Least a payout request may be (A15). */
    public const PAYOUT_MIN = 100;

    /**
     * Pending: earnings of orders still on their way or delivered but not
     * yet Completed (paid online or COD). Available: earnings of Completed
     * orders, less payouts requested or paid (A15). Cancelled, returned and
     * refunded amounts never count.
     *
     * @return array{pending: float, available: float, completed: float, paid_out: float, requested: float}
     */
    public static function balances(int $sellerId): array
    {
        $inProgress = DB::table('orders')
            ->whereNotIn('status', array_merge(OrderStatus::FINISHED, ['Returning', 'Returning to Branch', 'Return Ready']))
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhereNotIn('payment_status', array_merge(Payments::UNPAID, ['Refund Pending', 'Refunded'])))
            ->pluck('id');

        $pending = self::orders($sellerId, null, null, null, $inProgress->all())->sum('earning');
        $completed = self::orders($sellerId, null, null, ['Completed'])->sum('earning');

        $payouts = DB::table('seller_payout_requests')->where('seller_id', $sellerId)->whereIn('status', ['Pending', 'Paid'])
            ->selectRaw("SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid, SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as requested")
            ->first();

        $paid = round((float) ($payouts->paid ?? 0), 2);
        $requested = round((float) ($payouts->requested ?? 0), 2);

        return [
            'pending' => round((float) $pending, 2),
            'available' => round(max(0, (float) $completed - $paid - $requested), 2),
            'completed' => round((float) $completed, 2),
            'paid_out' => $paid,
            'requested' => $requested,
        ];
    }

    /**
     * One row per order with this seller's items, newest first: delivered
     * ones by default, or the given statuses / order ids (optionally placed
     * between $from and $to).
     */
    public static function orders(int $sellerId, ?string $from = null, ?string $to = null, ?array $statuses = null, ?array $orderIds = null): Collection
    {
        if ($orderIds !== null && !$orderIds) {
            return collect();
        }

        $rows = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'orders.voucher_code')
            ->where('order_items.seller_id', $sellerId)
            ->when($orderIds !== null, fn ($q) => $q->whereIn('orders.id', $orderIds), fn ($q) => $q->whereIn('orders.status', $statuses ?? OrderStatus::DONE))
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
