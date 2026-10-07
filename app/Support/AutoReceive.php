<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Delivered orders the buyer never confirms are marked "received" a few
 * days after delivery, like Shopee/Lazada. Otherwise the order hangs
 * forever and the return window never starts (or starts months later).
 *
 * Runs from a web middleware at most every few minutes, so it needs no
 * cron job on the machine serving the site.
 */
class AutoReceive
{
    /** Days after delivery before an order counts as received. */
    public const DAYS = 3;

    /** Days after receiving that a return or refund can be requested. */
    public const RETURN_WINDOW_DAYS = 7;

    private const THROTTLE_KEY = 'auto-receive-sweep';

    private const THROTTLE_MINUTES = 10;

    /** When a delivered order counts as received if the buyer doesn't confirm it. */
    public static function dueAt(object|array $order): ?Carbon
    {
        $order = (object) $order;
        $deliveredAt = $order->delivered_at ?? $order->updated_at ?? null;

        return $deliveredAt ? Carbon::parse($deliveredAt)->addDays(self::DAYS) : null;
    }

    /** Mark every overdue delivered order as received. Returns how many were marked. */
    public static function sweep(bool $force = false): int
    {
        if (!$force && !Cache::add(self::THROTTLE_KEY, true, now()->addMinutes(self::THROTTLE_MINUTES))) {
            return 0;
        }

        // Older orders have no delivered_at; their last update is when they were delivered.
        $overdue = DB::table('orders')
            ->where('status', 'Delivered')
            ->whereNull('buyer_received_at')
            ->whereRaw('COALESCE(delivered_at, updated_at) <= ?', [now()->subDays(self::DAYS)])
            ->get(['id', 'buyer_id', 'delivered_at', 'updated_at']);

        $marked = 0;

        foreach ($overdue as $order) {
            // Received on the day it fell due, so the return window is the same
            // whether someone happened to open the site that day or not.
            $receivedAt = Carbon::parse($order->delivered_at ?? $order->updated_at)->addDays(self::DAYS);

            $updated = DB::table('orders')
                ->where('id', $order->id)
                ->where('status', 'Delivered')
                ->whereNull('buyer_received_at')
                ->update(['status' => 'Completed', 'buyer_received_at' => $receivedAt, 'updated_at' => now()]);

            if (!$updated) {
                continue;
            }

            OrderTimeline::log((int) $order->id, 'Completed', 'Marked as received automatically', 'No issue reported within ' . self::DAYS . ' days of delivery');

            $marked++;
            $returnUntil = $receivedAt->copy()->addDays(self::RETURN_WINDOW_DAYS);

            createNotification(
                (int) $order->buyer_id,
                'Order Marked as Received',
                "Order #{$order->id} was marked as received " . self::DAYS . ' days after delivery.'
                    . ($returnUntil->isFuture()
                        ? ' You can still request a return or refund until ' . $returnUntil->format('M j') . '.'
                        : ''),
                'order_status',
                $order->id
            );

            notifyOrderSellers(
                $order->id,
                'Order Received by Buyer',
                "Order #{$order->id} was marked as received automatically " . self::DAYS . ' days after delivery.'
            );
        }

        return $marked;
    }
}
