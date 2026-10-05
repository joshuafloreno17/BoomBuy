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
 * What a seller does with an order: Pending → Processing → Dropped Off (the
 * seller brings the parcel to the Sorting Center themselves), or Cancelled
 * while it is still with them; confirm a rider's pickup on older orders that
 * still went through one; and put a returned order back into stock.
 */
class SellerOrderService
{
    public const SELLER_STATUSES = ['Processing', 'Dropped Off', 'Cancelled'];

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
        if (!in_array($order->status, ['Pending', 'Processing'], true)) {
            throw new ActionFailed('This order is already ' . $order->status . ' and can no longer be updated by the seller.');
        }

        if ($order->status === 'Pending' && !in_array($status, ['Processing', 'Cancelled'], true)) {
            throw new ActionFailed('Pending orders must be moved to Processing first.');
        }

        if ($order->status === 'Processing' && !in_array($status, ['Dropped Off', 'Cancelled'], true)) {
            throw new ActionFailed('Processing orders must be dropped off at the Sorting Center next.');
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
            ->update([
                'status' => $status,
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

        // Orders placed before Sorting Centers existed get their route now.
        if ($status === 'Dropped Off' && (empty($order->origin_center_id) || empty($order->destination_center_id))) {
            $seller = ParcelRoute::sellerLocation($sellerId);
            $buyerTown = $order->shipping_province
                ? ['province' => $order->shipping_province, 'city' => $order->shipping_city]
                : PhLocations::locate($order->shipping_address);

            $route = [
                'origin_center_id' => $order->origin_center_id ?: ParcelRoute::centerFor($seller['province'], $seller['city'])?->id,
                'destination_center_id' => $order->destination_center_id ?: ($buyerTown ? ParcelRoute::centerFor($buyerTown['province'], $buyerTown['city'])?->id : null),
            ];

            DB::table('orders')->where('id', $orderId)->update($route);
            $order->origin_center_id = $route['origin_center_id'];
        }

        $buyerId = (int) $order->buyer_id;

        match ($status) {
            'Processing' => OrderTimeline::log($orderId, $status, 'Seller is preparing your order'),
            'Dropped Off' => OrderTimeline::log(
                $orderId,
                $status,
                'Seller dropped it off at ' . (ParcelRoute::centerName($order->origin_center_id ? (int) $order->origin_center_id : null) ?? 'the Sorting Center'),
                null,
                $order->origin_center_id ? (int) $order->origin_center_id : null
            ),
            default => OrderTimeline::log($orderId, $status, 'Cancelled by the seller', $reason),
        };

        if ($status === 'Processing') {
            createNotification($buyerId, 'Order Processing', 'Your order #' . $orderId . ' is now being processed by the seller.', 'order', $orderId);
        } elseif ($status === 'Dropped Off') {
            createNotification($buyerId, 'Order Shipped', 'Your order #' . $orderId . ' has been packed and dropped off at the Sorting Center by the seller.', 'order', $orderId);
            ParcelRoute::notifyCenter(
                $order->origin_center_id ? (int) $order->origin_center_id : null,
                'Parcel Dropped Off',
                'The seller dropped off Order #' . $orderId . '. Confirm once it is received at your Sorting Center.',
                'parcel',
                $orderId
            );
        } else {
            createNotification($buyerId, 'Order Cancelled by Seller', 'Your order #' . $orderId . ' was cancelled by the seller. Reason: ' . $reason, 'order', $orderId);
        }

        createNotification($sellerId, 'Order Status Updated', 'Order #' . $orderId . ' is now ' . $status . '.', 'order_status', $orderId);

        ChatAutomation::orderUpdate($orderId, match ($status) {
            'Processing' => 'processing',
            'Dropped Off' => 'dropped_off',
            default => 'cancelled',
        });

        return 'Order status updated to ' . $status . '.';
    }

    /**
     * The seller confirms the rider took the parcel. Only during the hand-over
     * and only once: the time recorded is when it actually left the seller.
     *
     * @throws ActionFailed
     */
    public function confirmPickup(int $sellerId, int $orderId): string
    {
        $order = $this->sellerOrder($sellerId, $orderId);

        if (empty($order->rider_id)) {
            throw new ActionFailed('No rider has picked up this order yet.');
        }

        if (!in_array($order->status, ['Assigned', 'Picked Up'], true)) {
            throw new ActionFailed('This order is not waiting for a rider pickup.');
        }

        if (!empty($order->seller_confirmed_pickup_at)) {
            throw new ActionFailed('You already confirmed this pickup.');
        }

        DB::table('orders')
            ->where('id', $orderId)
            ->whereNull('seller_confirmed_pickup_at')
            ->update(['seller_confirmed_pickup_at' => now()]);

        return 'Rider pickup confirmed.';
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
