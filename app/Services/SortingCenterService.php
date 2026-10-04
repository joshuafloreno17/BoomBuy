<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use Illuminate\Support\Facades\DB;

/**
 * The Sorting Center (logistics) part of an order: confirm a picked-up parcel
 * arrived, hand it to a delivery rider, reschedule a failed delivery, or send
 * it back to the seller.
 */
class SortingCenterService
{
    /** After this many failed attempts a parcel goes back to the seller. */
    public const MAX_DELIVERY_ATTEMPTS = 2;

    /**
     * @throws ActionFailed
     */
    public function confirmReceived(int $orderId): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'Picked Up') {
            throw new ActionFailed('This parcel is not awaiting Sorting Center confirmation.');
        }

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Picked Up')->update([
            'status' => 'At Sorting Center',
            'sorting_center_received_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $order->buyer_id,
            'Parcel at Sorting Center',
            'Your order #' . $orderId . ' has arrived at the sorting facility and will be assigned to a rider for delivery shortly.',
            'order',
            $orderId
        );

        notifyOrderSellers(
            $orderId,
            'Parcel at Sorting Center',
            'Order #' . $orderId . ' has arrived at the Sorting Center and will be assigned to a rider for delivery.'
        );

        return 'Parcel #' . $orderId . ' confirmed as received.';
    }

    /**
     * @throws ActionFailed
     */
    public function assign(int $orderId, $riderId): string
    {
        if (empty($riderId)) {
            throw new ActionFailed('Please select a rider to assign this parcel to.');
        }

        $order = $this->parcel($orderId);

        if ($order->status !== 'At Sorting Center') {
            throw new ActionFailed('This parcel is not awaiting assignment.');
        }

        $rider = $this->riderOrFail($riderId);
        $this->handTo($orderId, 'At Sorting Center', (int) $rider->id);

        createNotification(
            (int) $rider->id,
            'New Delivery Assignment',
            'You have been assigned to deliver Order #' . $orderId . '. Please pick it up from the Sorting Center.',
            'delivery',
            $orderId
        );

        createNotification(
            (int) $order->buyer_id,
            'Rider Assigned for Delivery',
            'Your order #' . $orderId . ' has been assigned to a rider and will be out for delivery soon.',
            'order',
            $orderId
        );

        return 'Parcel #' . $orderId . ' assigned to ' . $rider->name . '.';
    }

    /**
     * Another delivery attempt — not for a refused parcel, and only up to MAX_DELIVERY_ATTEMPTS.
     *
     * @throws ActionFailed
     */
    public function reschedule(int $orderId, $riderId): string
    {
        if (empty($riderId)) {
            throw new ActionFailed('Please select a rider to reschedule this delivery to.');
        }

        $order = $this->parcel($orderId);

        if ($order->status !== 'Delivery Failed') {
            throw new ActionFailed('This parcel is not marked as a failed delivery.');
        }

        if (!empty($order->buyer_refused_at)) {
            throw new ActionFailed('The buyer refused this parcel, so it can\'t be re-delivered. Please return it to the seller.');
        }

        if ($order->delivery_attempts >= self::MAX_DELIVERY_ATTEMPTS) {
            throw new ActionFailed('This parcel has reached the maximum delivery attempts. Please return it to the seller instead.');
        }

        $rider = $this->riderOrFail($riderId);
        $this->handTo($orderId, 'Delivery Failed', (int) $rider->id);

        createNotification(
            (int) $rider->id,
            'Delivery Rescheduled',
            'Order #' . $orderId . ' has been rescheduled to you for another delivery attempt.',
            'delivery',
            $orderId
        );

        createNotification(
            (int) $order->buyer_id,
            'Delivery Rescheduled',
            'We will attempt to deliver your order #' . $orderId . ' again shortly.',
            'order',
            $orderId
        );

        return 'Parcel #' . $orderId . ' rescheduled to ' . $rider->name . '.';
    }

    /**
     * @throws ActionFailed
     */
    public function returnToSeller(int $orderId): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'Delivery Failed') {
            throw new ActionFailed('This parcel is not marked as a failed delivery.');
        }

        $updated = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', 'Delivery Failed')
            ->update(['status' => 'Returned to Seller', 'updated_at' => now()]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $refused = !empty($order->buyer_refused_at);

        createNotification(
            (int) $order->buyer_id,
            'Order Returned to Seller',
            $refused
                ? 'Order #' . $orderId . ' was refused on delivery and has been returned to the seller.'
                : 'After repeated failed delivery attempts, order #' . $orderId . ' has been returned to the seller.',
            'order',
            $orderId
        );

        foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
            createNotification(
                (int) $sellerId,
                'Order Returned to You',
                $refused
                    ? 'Order #' . $orderId . ' was refused by the buyer on delivery and has been returned to you. Please restock the items once received.'
                    : 'Order #' . $orderId . ' could not be delivered after repeated attempts and has been returned to you. Please restock the items once received.',
                'order',
                $orderId
            );
        }

        return 'Parcel #' . $orderId . ' has been returned to the seller.';
    }

    /** A rider the Sorting Center may hand parcels to: active, with an approved application. */
    public function assignableRider($riderId): ?object
    {
        return DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.id', $riderId)
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name')
            ->first();
    }

    private function parcel(int $orderId): object
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Parcel not found.');
        }

        return $order;
    }

    private function riderOrFail($riderId): object
    {
        $rider = $this->assignableRider($riderId);

        if (!$rider) {
            throw new ActionFailed('Selected rider not found or no longer active.');
        }

        return $rider;
    }

    /** Only from the status just checked, so two logistics staff can't both act on it. */
    private function handTo(int $orderId, string $fromStatus, int $riderId): void
    {
        $updated = DB::table('orders')->where('id', $orderId)->where('status', $fromStatus)->update([
            'delivery_rider_id' => $riderId,
            'status' => 'Assigned for Delivery',
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }
    }
}
