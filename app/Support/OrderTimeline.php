<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The tracking timeline of an order: each step, when it happened and at
 * which Sorting Center ("Arrived at Santa Cruz Sorting Center · 5:40 PM").
 * Written by the services that move an order along.
 */
class OrderTimeline
{
    public static function log(int $orderId, string $status, string $title, ?string $detail = null, ?int $centerId = null): void
    {
        DB::table('order_events')->insert([
            'order_id' => $orderId,
            'status' => $status,
            'title' => $title,
            'detail' => $detail !== null ? mb_substr($detail, 0, 255) : null,
            'center_id' => $centerId,
            'created_at' => now(),
        ]);
    }

    /**
     * Steps per order, oldest first. Orders from before the timeline existed
     * get what can be read off the order itself.
     *
     * @param  int[]  $orderIds
     * @return Collection<int, Collection>  order id => steps (title, detail, at, status)
     */
    public static function forOrders(array $orderIds): Collection
    {
        if (!$orderIds) {
            return collect();
        }

        $events = DB::table('order_events')
            ->whereIn('order_id', $orderIds)
            ->orderBy('id')
            ->get(['order_id', 'status', 'title', 'detail', 'created_at'])
            ->groupBy('order_id');

        $missing = array_diff($orderIds, $events->keys()->all());

        if ($missing) {
            foreach (DB::table('orders')->whereIn('id', $missing)->get() as $order) {
                $events[$order->id] = self::fromOrder($order);
            }
        }

        return $events->map(fn ($steps) => collect($steps)->map(fn ($s) => (object) [
            'status' => $s->status,
            'title' => $s->title,
            'detail' => $s->detail,
            'at' => $s->created_at,
        ])->values());
    }

    /** Best effort for an order with no logged steps. */
    private static function fromOrder(object $order): Collection
    {
        $steps = collect([(object) ['status' => 'Pending', 'title' => 'Order placed', 'detail' => null, 'created_at' => $order->created_at]]);

        foreach ([
            'sorting_center_received_at' => ['At Sorting Center', 'At a Sorting Center'],
            'dispatched_at' => ['In Transit', 'Sent to the Sorting Center near you'],
            'delivery_failed_at' => ['Delivery Failed', 'Delivery attempt failed'],
            'delivered_at' => ['Delivered', 'Delivered'],
            'buyer_received_at' => ['Delivered', 'Received by buyer'],
            'cancelled_at' => ['Cancelled', 'Cancelled'],
        ] as $column => [$status, $title]) {
            if (!empty($order->$column)) {
                $steps->push((object) ['status' => $status, 'title' => $title, 'detail' => null, 'created_at' => $order->$column]);
            }
        }

        return $steps->sortBy('created_at')->values();
    }
}
