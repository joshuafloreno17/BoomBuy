<?php

namespace App\Support;

use App\Models\PlatformSetting;
use App\Services\ReturnRefundService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What BoomBuy owes a seller. The buyer's money comes to BoomBuy (COD cash
 * handed in at a Sorting Center); BoomBuy keeps its commission and pays the
 * seller the rest once the order can no longer be returned.
 *
 * Per delivered order, the seller earns:
 *     their items − their own vouchers − refunds BoomBuy sent − commission
 * (the commission rate saved on the order when it was placed).
 *
 * Each order is in one of three states:
 *   waiting_cash — Cash on Delivery not handed in at a Sorting Center yet
 *   on_hold      — the buyer can still return it (not received, inside the
 *                  return window, or a return/refund request is open)
 *   available    — can be paid out
 *
 * Available balance = available earnings − payouts already sent.
 */
class SellerBalance
{
    /**
     * @return array{
     *   orders: Collection, waiting_cash: float, on_hold: float, released: float,
     *   paid_out: float, available: float, commission: float, refunds: float, sales: float
     * }
     */
    public static function for(int $sellerId): array
    {
        $orders = self::orders($sellerId);
        $paid = (float) DB::table('seller_payouts')->where('seller_id', $sellerId)->sum('amount');

        $sum = fn (string $state) => round((float) $orders->where('state', $state)->sum('earning'), 2);
        $released = $sum('available');

        return [
            'orders' => $orders,
            'waiting_cash' => $sum('waiting_cash'),
            'on_hold' => $sum('on_hold'),
            'released' => $released,
            'paid_out' => round($paid, 2),
            'available' => round(max(0, $released - $paid), 2),
            'commission' => round((float) $orders->sum('commission'), 2),
            'refunds' => round((float) $orders->sum('refunds'), 2),
            'sales' => round((float) $orders->sum('items'), 2),
        ];
    }

    /**
     * Delivered sales after the seller's vouchers and refunds, and the
     * commission on them (each order at its own saved rate) — what the
     * Reports pages show, for orders placed between $from and $to.
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
            ->where('orders.status', 'Delivered')
            ->when($from, fn ($q) => $q->whereDate('orders.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('orders.created_at', '<=', $to))
            ->groupBy('orders.id', 'orders.payment_method', 'orders.cod_remitted_at', 'orders.buyer_received_at', 'orders.delivered_at', 'orders.discount_amount', 'orders.commission_rate', 'vouchers.seller_id')
            ->orderByDesc('orders.delivered_at')
            ->orderByDesc('orders.id')
            ->selectRaw('orders.id, orders.payment_method, orders.cod_remitted_at, orders.buyer_received_at, orders.delivered_at, orders.discount_amount, orders.commission_rate, vouchers.seller_id as voucher_seller_id, SUM(order_items.price * order_items.quantity) as items')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $ids = $rows->pluck('id');

        $refunds = DB::table('return_refund_requests')
            ->whereIn('order_id', $ids)
            ->where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->whereNotNull('refunded_at')
            ->selectRaw('order_id, SUM(refund_amount) as total')
            ->groupBy('order_id')
            ->pluck('total', 'order_id');

        $openRequests = DB::table('return_refund_requests')
            ->whereIn('order_id', $ids)
            ->where('seller_id', $sellerId)
            ->whereIn('status', ReturnRefundService::OPEN)
            ->distinct()
            ->pluck('order_id')
            ->flip();

        $defaultRate = (float) PlatformSetting::get('commission_rate', '10');
        $window = AutoReceive::RETURN_WINDOW_DAYS;

        return $rows->map(function ($row) use ($sellerId, $refunds, $openRequests, $defaultRate, $window) {
            $items = (float) $row->items;
            // Only the seller's own vouchers come out of their money; a BoomBuy voucher is BoomBuy's.
            $voucher = (int) $row->voucher_seller_id === $sellerId ? (float) $row->discount_amount : 0.0;
            $refunded = (float) ($refunds[$row->id] ?? 0);
            $net = max(0, $items - $voucher - $refunded);
            $rate = $row->commission_rate !== null ? (float) $row->commission_rate : $defaultRate;
            $commission = round($net * $rate / 100, 2);

            $returnsUntil = $row->buyer_received_at ? Carbon::parse($row->buyer_received_at)->addDays($window) : null;

            $state = match (true) {
                CodPolicy::isCod($row->payment_method) && empty($row->cod_remitted_at) => 'waiting_cash',
                !$returnsUntil || $returnsUntil->isFuture() || isset($openRequests[$row->id]) => 'on_hold',
                default => 'available',
            };

            return (object) [
                'id' => (int) $row->id,
                'delivered_at' => $row->delivered_at,
                'items' => round($items, 2),
                'voucher' => round($voucher, 2),
                'refunds' => round($refunded, 2),
                'rate' => $rate,
                'commission' => $commission,
                'earning' => round($net - $commission, 2),
                'state' => $state,
                'releases_on' => $state === 'on_hold' && !isset($openRequests[$row->id]) ? $returnsUntil : null,
                'open_request' => isset($openRequests[$row->id]),
            ];
        });
    }

    /** Payment details the seller saved, or nulls. */
    public static function account(int $sellerId): array
    {
        $row = DB::table('seller_applications')
            ->where('user_id', $sellerId)
            ->orderByDesc('id')
            ->first(['payout_method', 'payout_account_name', 'payout_account_number']);

        return [
            'method' => $row->payout_method ?? null,
            'name' => $row->payout_account_name ?? null,
            'number' => $row->payout_account_number ?? null,
        ];
    }
}
