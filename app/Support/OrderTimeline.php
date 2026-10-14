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

    /**
     * How far along each status is, in the ERP Flow's order. "At Sorting
     * Center" is the seller's center (6) until the parcel has been sent on,
     * then the buyer's (8).
     */
    private const RANK = [
        'Pending' => 1,
        'Confirmed' => 2, 'Preparing' => 2,
        'Ready for Pickup' => 3, 'Pickup Assigned' => 3,
        'Picked Up' => 4, 'Dropped Off' => 4,
        'Received at Branch' => 5, 'To Sorting Center' => 5,
        'In Transit' => 7,
        'Sorted' => 8, 'To Branch' => 8,
        'At Branch' => 9,
        'Assigned for Delivery' => 10, 'Out for Delivery' => 10, 'Ready to Collect' => 10,
        'Delivered' => 11, 'Completed' => 11,
    ];

    /** Off the normal trip: show only what happened. */
    private const OFF_ROUTE = ['Cancelled', 'Delivery Failed', 'Returning', 'Returning to Branch', 'Return Ready', 'Returned to Seller'];

    /**
     * The whole trip, oldest stop first: the stops already reached (with
     * when, from the logged steps) and the ones still ahead. Null when the
     * order left the normal trip (cancelled, refused, failed, returning) —
     * then only the logged steps make sense.
     *
     * @param  array|object  $order
     * @param  Collection  $steps    this order's steps from forOrders()
     * @param  Collection  $centers  Sorting Centers / branches by id
     * @return Collection<int, object>|null  (title, detail, at, done, current)
     */
    public static function plan($order, Collection $steps, Collection $centers): ?Collection
    {
        $order = (object) $order;
        $status = (string) ($order->status ?? '');

        if (in_array($status, self::OFF_ROUTE, true) || !empty($order->buyer_refused_at)
            || $steps->contains(fn ($s) => in_array($s->status, self::OFF_ROUTE, true))) {
            return null;
        }

        $name = fn ($id) => $id && $centers->has($id) ? $centers->get($id)->name : null;
        $origin = $name($order->origin_center_id ?? null);
        $destination = $name($order->destination_center_id ?? null) ?? $origin;
        $crossesCenters = !empty($order->destination_center_id) && !empty($order->origin_center_id)
            && (int) $order->destination_center_id !== (int) $order->origin_center_id;
        $pickup = ($order->fulfillment ?? 'delivery') === 'pickup';

        $stops = [[1, 'Order placed']];
        $stops[] = [2, 'Seller accepted your order'];
        $stops[] = [3, 'Packed — waiting for the rider'];
        $stops[] = [4, 'Picked up by the rider'];
        if ($originBranch = $name($order->origin_branch_id ?? null)) {
            $stops[] = [5, 'At ' . $originBranch];
        }
        $stops[] = [6, 'Arrived at ' . ($origin ?? 'the Sorting Center')];
        if ($crossesCenters) {
            $stops[] = [7, 'On its way to ' . $destination];
            $stops[] = [8, 'Arrived at ' . $destination];
        }
        if (!$pickup && ($branch = $name($order->branch_id ?? null))) {
            $stops[] = [9, 'At ' . $branch];
        }
        $stops[] = $pickup
            ? [10, 'Ready to collect at ' . ($destination ?? 'the Sorting Center')]
            : [10, 'Out for delivery'];
        $stops[] = [11, $pickup ? 'Collected' : 'Delivered'];

        // Rank of each logged step, in the order they happened.
        $sent = false;
        $ranked = $steps->map(function ($s) use (&$sent) {
            if ($s->status === 'At Sorting Center') {
                $rank = $sent ? 8 : 6;
            } else {
                $rank = self::RANK[$s->status] ?? 0;
                $sent = $sent || $rank >= 7;
            }

            return [$rank, $s];
        });

        $reached = self::RANK[$status] ?? 0;
        if ($status === 'At Sorting Center') {
            $reached = !empty($order->dispatched_at) && $crossesCenters ? 8 : 6;
        }
        $reached = max($reached, (int) $ranked->max(fn ($r) => $r[0]));

        $plan = collect($stops)->map(function ($stop) use ($ranked, $reached) {
            [$rank, $title] = $stop;
            $done = $reached >= $rank;
            // The logged step for this stop (its own wording names the place),
            // else the first one past it for the time (a stop can be skipped over).
            $exact = $done ? ($ranked->first(fn ($r) => $r[0] === $rank)[1] ?? null) : null;
            $past = $done ? ($ranked->first(fn ($r) => $r[0] >= $rank)[1] ?? null) : null;

            return (object) [
                'title' => $exact->title ?? $title,
                'detail' => $exact->detail ?? null,
                'at' => ($exact ?? $past)->at ?? null,
                'done' => $done,
                'current' => false,
                'rank' => $rank,
            ];
        });

        $last = $plan->where('done', true)->keys()->last();
        if ($last !== null) {
            $plan[$last]->current = true;
        }

        return $plan->values();
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
            'buyer_received_at' => ['Completed', 'Received by buyer'],
            'cancelled_at' => ['Cancelled', 'Cancelled'],
        ] as $column => [$status, $title]) {
            if (!empty($order->$column)) {
                $steps->push((object) ['status' => $status, 'title' => $title, 'detail' => null, 'created_at' => $order->$column]);
            }
        }

        return $steps->sortBy('created_at')->values();
    }
}
