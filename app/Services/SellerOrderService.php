<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\ChatAutomation;
use App\Support\OrderStock;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;

/**
 * What a seller does with an order (ERP flow): Pending → Accept → Confirmed
 * → Start preparing → Preparing (pack, print the shipping label) → Ready for
 * Pickup (PickupService) → a rider collects it, and the seller confirms the
 * hand-over. Cancelled while it is still with them. Also: take back a
 * returned parcel at the Sorting Center, and put a returned order back into
 * stock.
 */
class SellerOrderService
{
    public const SELLER_STATUSES = ['Confirmed', 'Preparing', 'Cancelled'];

    /** What each status may move to when the seller acts. */
    private const NEXT = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Preparing', 'Cancelled'],
        'Preparing' => ['Cancelled'],
        'Ready for Pickup' => ['Cancelled'],
    ];

    /**
     * @return string  What happened, for the seller.
     *
     * @throws ActionFailed
     */
    public function updateStatus(int $sellerId, int $orderId, string $status, string $reason = ''): string
    {
        $status = trim($status);
        $reason = trim($reason);

        if (!in_array($status, self::SELLER_STATUSES, true)) {
            throw new ActionFailed('Invalid seller order status.');
        }

        $order = $this->sellerOrder($sellerId, $orderId);

        // The seller only owns an order while it is still with them. Anything
        // past that (rider, Sorting Center, delivered, cancelled, returned)
        // follows its own flow and must not be cancelled or rewound here.
        // A booked pickup nobody accepted yet may still be cancelled.
        $unclaimedPickup = $order->status === 'Ready for Pickup' && empty($order->rider_id);

        if (!isset(self::NEXT[$order->status]) || ($order->status === 'Ready for Pickup' && !$unclaimedPickup)) {
            throw new ActionFailed('This order is already ' . $order->status . ' and can no longer be updated by the seller.');
        }

        if (!in_array($status, self::NEXT[$order->status], true)) {
            throw new ActionFailed(match ($order->status) {
                'Pending' => 'Accept the order first.',
                'Confirmed' => 'Start preparing the order next.',
                default => 'Mark it as ready for pickup when it is packed.',
            });
        }

        $cancelling = $status === 'Cancelled';

        if ($cancelling && $reason === '') {
            throw new ActionFailed('Please provide a reason for cancelling this order.');
        }

        // Only from the status just checked, so two overlapping requests
        // can't both act on the same order.
        $updated = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', $order->status)
            ->when($unclaimedPickup, fn ($q) => $q->whereNull('rider_id'))
            ->update([
                'status' => $status,
                'pickup_date' => null,
                'cancellation_reason' => $cancelling ? $reason : null,
                'cancelled_by' => $cancelling ? 'seller' : null,
                'cancelled_at' => $cancelling ? now() : null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            throw new ActionFailed('This order was just updated. Please refresh and try again.');
        }

        if ($cancelling) {
            OrderStock::cancelled($orderId, $order->status);
        }

        $buyerId = (int) $order->buyer_id;

        match ($status) {
            'Confirmed' => OrderTimeline::log($orderId, $status, 'Seller accepted your order'),
            'Preparing' => OrderTimeline::log($orderId, $status, 'Seller is preparing your order'),
            default => OrderTimeline::log($orderId, $status, 'Cancelled by the seller', $reason),
        };

        match ($status) {
            'Confirmed' => createNotification($buyerId, 'Order Confirmed', 'The seller accepted your order #' . $orderId . '.', 'order', $orderId),
            'Preparing' => createNotification($buyerId, 'Order Being Prepared', 'The seller is now preparing your order #' . $orderId . '.', 'order', $orderId),
            default => createNotification($buyerId, 'Order Cancelled by Seller', 'Your order #' . $orderId . ' was cancelled by the seller. Reason: ' . $reason, 'order', $orderId),
        };

        createNotification($sellerId, 'Order Status Updated', 'Order #' . $orderId . ' is now ' . $status . '.', 'order_status', $orderId);

        ChatAutomation::orderUpdate($orderId, match ($status) {
            'Confirmed' => 'confirmed',
            'Preparing' => 'processing',
            default => 'cancelled',
        });

        return 'Order status updated to ' . $status . '.';
    }

    /**
     * The seller confirms they handed the parcel to the pickup rider (the
     * rider confirms on their side too; whoever is first marks it Picked Up).
     *
     * @throws ActionFailed
     */
    public function confirmRiderPickup(int $sellerId, int $orderId): string
    {
        $order = $this->sellerOrder($sellerId, $orderId);

        if (empty($order->rider_id) || !in_array($order->status, ['Pickup Assigned', 'Picked Up'], true)) {
            throw new ActionFailed('No rider is picking up this order right now.');
        }

        if (!empty($order->seller_confirmed_pickup_at)) {
            throw new ActionFailed('You already confirmed this pickup.');
        }

        DB::table('orders')->where('id', $orderId)->whereNull('seller_confirmed_pickup_at')->update(['seller_confirmed_pickup_at' => now()]);

        if ($order->status === 'Pickup Assigned') {
            app(PickupService::class)->markPickedUp($order, 'Seller handed it to the rider');
        }

        return 'Rider pickup confirmed.';
    }

    /**
     * Orders placed before Sorting Centers existed get their route when the
     * parcel leaves the seller.
     *
     * @return array{origin_center_id: ?int, destination_center_id: ?int}
     */
    public function ensureRoute(object $order, int $sellerId): array
    {
        $route = [
            'origin_center_id' => $order->origin_center_id ? (int) $order->origin_center_id : null,
            'destination_center_id' => $order->destination_center_id ? (int) $order->destination_center_id : null,
        ];

        if ($route['origin_center_id'] && $route['destination_center_id']) {
            return $route;
        }

        $seller = ParcelRoute::sellerLocation($sellerId);
        $buyerTown = $order->shipping_province
            ? ['province' => $order->shipping_province, 'city' => $order->shipping_city]
            : PhLocations::locate($order->shipping_address);

        $route = [
            'origin_center_id' => $route['origin_center_id'] ?: ParcelRoute::centerFor($seller['province'], $seller['city'])?->id,
            'destination_center_id' => $route['destination_center_id'] ?: ($buyerTown ? ParcelRoute::centerFor($buyerTown['province'], $buyerTown['city'])?->id : null),
        ];

        DB::table('orders')->where('id', $order->id)->update($route);

        return $route;
    }

    /**
     * A returned order's items go back into the seller's stock, once.
     *
     * @throws ActionFailed
     */
    public function restock(int $sellerId, int $orderId): string
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Order not found.');
        }

        if ($order->status !== 'Returned to Seller') {
            throw new ActionFailed('Only orders returned to you can be restocked.');
        }

        if (!empty($order->restocked_at)) {
            throw new ActionFailed('This order has already been marked as restocked.');
        }

        $hasItems = DB::table('order_items')->where('order_id', $orderId)->where('seller_id', $sellerId)->exists();

        if (!$hasItems) {
            throw new ActionFailed('This order does not belong to you.');
        }

        $result = OrderStock::restore($orderId, $sellerId);

        if ($result === null) {
            throw new ActionFailed('This order has already been marked as restocked.');
        }

        $message = 'Order #' . $orderId . ' marked as restocked (' . $result['restored'] . ' item(s) added back to inventory).';

        if ($result['skipped'] > 0) {
            $message .= ' ' . $result['skipped'] . ' item(s) could not be matched to a variation and were skipped — please adjust stock manually.';
        }

        return $message;
    }

    /** The order, when it exists and has at least one of this seller's items. */
    private function sellerOrder(int $sellerId, int $orderId): object
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Order not found.');
        }

        $hasItems = DB::table('order_items')->where('order_id', $orderId)->where('seller_id', $sellerId)->exists();

        if (!$hasItems) {
            throw new ActionFailed('This order does not belong to you.');
        }

        return $order;
    }
}
