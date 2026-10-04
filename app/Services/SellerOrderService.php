<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\ChatAutomation;
use App\Support\OrderStock;
use Illuminate\Support\Facades\DB;

/**
 * What a seller does with an order: Pending → Processing → Ready for Pickup
 * (or Cancelled while it is still with them), confirm the rider's pickup,
 * and put a returned order back into stock.
 */
class SellerOrderService
{
    public const SELLER_STATUSES = ['Processing', 'Ready for Pickup', 'Cancelled'];

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

        if ($order->status === 'Processing' && !in_array($status, ['Ready for Pickup', 'Cancelled'], true)) {
            throw new ActionFailed('Processing orders must be moved to Ready for Pickup.');
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

        $buyerId = (int) $order->buyer_id;

        if ($status === 'Processing') {
            createNotification($buyerId, 'Order Processing', 'Your order #' . $orderId . ' is now being processed by the seller.', 'order', $orderId);
        } elseif ($status === 'Ready for Pickup') {
            createNotification($buyerId, 'Order Ready for Pickup', 'Your order #' . $orderId . ' has been packed and is ready for a rider to pick up.', 'order', $orderId);
            notifyAllActiveRiders('New Delivery Available', 'Order #' . $orderId . ' is ready for pickup and available to claim.', 'delivery', $orderId);
        } else {
            createNotification($buyerId, 'Order Cancelled by Seller', 'Your order #' . $orderId . ' was cancelled by the seller. Reason: ' . $reason, 'order', $orderId);
        }

        createNotification($sellerId, 'Order Status Updated', 'Order #' . $orderId . ' is now ' . $status . '.', 'order_status', $orderId);

        if ($status !== 'Processing') {
            ChatAutomation::orderUpdate($orderId, $cancelling ? 'cancelled' : 'ready');
        }

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
