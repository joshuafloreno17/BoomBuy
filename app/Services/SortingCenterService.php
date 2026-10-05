<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use Illuminate\Support\Facades\DB;

/**
 * The Sorting Center (logistics) part of an order. A parcel goes:
 *
 *   seller drops it at the origin center → origin confirms it → (buyer in
 *   another town) dispatched, In Transit → the destination center confirms
 *   its arrival → a delivery rider takes it to the buyer.
 *
 * Also: reschedule a failed delivery, or send it back to the seller.
 *
 * $centerId is the acting staff member's center; null = head office, who may
 * act for any center.
 */
class SortingCenterService
{
    /** After this many failed attempts a parcel goes back to the seller. */
    public const MAX_DELIVERY_ATTEMPTS = 2;

    /**
     * The origin center received what the seller dropped off.
     *
     * @throws ActionFailed
     */
    public function confirmReceived(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        // Dropped off by the seller, or (older orders) brought in by a pickup rider.
        if (!in_array($order->status, ['Dropped Off', 'Picked Up'], true)) {
            throw new ActionFailed('This parcel is not awaiting Sorting Center confirmation.');
        }

        $this->mustBeAt($order->origin_center_id, $centerId, 'was dropped off at another Sorting Center');

        $here = $order->origin_center_id ?: $centerId;

        $updated = DB::table('orders')->where('id', $orderId)->where('status', $order->status)->update([
            'status' => 'At Sorting Center',
            'current_center_id' => $here,
            'sorting_center_received_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $hereName = ParcelRoute::centerName($here ? (int) $here : null) ?? 'the Sorting Center';
        $goesOn = $this->needsTransfer((object) ['current_center_id' => $here, 'destination_center_id' => $order->destination_center_id]);
        $nextName = ParcelRoute::centerName($order->destination_center_id ? (int) $order->destination_center_id : null);

        OrderTimeline::log($orderId, 'At Sorting Center', 'Received at ' . $hereName, null, $here ? (int) $here : null);

        createNotification(
            (int) $order->buyer_id,
            'Parcel at Sorting Center',
            'Your order #' . $orderId . ' arrived at ' . $hereName . '. ' . ($goesOn
                ? 'It will be sent on to ' . $nextName . ', near you.'
                : 'It will be assigned to a rider for delivery shortly.'),
            'order',
            $orderId
        );

        notifyOrderSellers(
            $orderId,
            'Parcel at Sorting Center',
            'Order #' . $orderId . ' was received at ' . $hereName . '.'
        );

        return 'Parcel #' . $orderId . ' confirmed as received.' . ($goesOn ? ' Next: dispatch it to ' . $nextName . '.' : '');
    }

    /**
     * Send a received parcel on to the buyer's town center.
     *
     * @throws ActionFailed
     */
    public function dispatch(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'At Sorting Center' || !$this->needsTransfer($order)) {
            throw new ActionFailed('This parcel is not waiting to be dispatched to another Sorting Center.');
        }

        $this->mustBeAt($order->current_center_id, $centerId, 'is not at your Sorting Center');

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'At Sorting Center')->update([
            'status' => 'In Transit',
            'current_center_id' => null,
            'dispatched_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $destinationId = (int) $order->destination_center_id;
        $destinationName = ParcelRoute::centerName($destinationId);

        OrderTimeline::log(
            $orderId,
            'In Transit',
            'On its way to ' . $destinationName,
            'Dispatched from ' . (ParcelRoute::centerName((int) $order->current_center_id) ?? 'another Sorting Center'),
            (int) $order->current_center_id
        );

        createNotification(
            (int) $order->buyer_id,
            'Parcel In Transit',
            'Your order #' . $orderId . ' is on its way to ' . $destinationName . ', the Sorting Center near you.',
            'order',
            $orderId
        );

        ParcelRoute::notifyCenter(
            $destinationId,
            'Incoming Parcel',
            'Order #' . $orderId . ' was dispatched to you from ' . (ParcelRoute::centerName((int) $order->current_center_id) ?? 'another Sorting Center') . '. Confirm when it arrives.',
            'parcel',
            $orderId
        );

        return 'Parcel #' . $orderId . ' dispatched to ' . $destinationName . '.';
    }

    /**
     * The buyer's town center got a parcel dispatched to it.
     *
     * @throws ActionFailed
     */
    public function confirmArrival(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'In Transit') {
            throw new ActionFailed('This parcel is not in transit.');
        }

        $this->mustBeAt($order->destination_center_id, $centerId, 'is headed to another Sorting Center');

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'In Transit')->update([
            'status' => 'At Sorting Center',
            'current_center_id' => $order->destination_center_id,
            'sorting_center_received_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        OrderTimeline::log(
            $orderId,
            'At Sorting Center',
            'Arrived at ' . (ParcelRoute::centerName((int) $order->destination_center_id) ?? 'the Sorting Center near you'),
            null,
            (int) $order->destination_center_id
        );

        createNotification(
            (int) $order->buyer_id,
            'Parcel Near You',
            'Your order #' . $orderId . ' arrived at ' . (ParcelRoute::centerName((int) $order->destination_center_id) ?? 'the Sorting Center near you') . ' and will be assigned to a rider for delivery shortly.',
            'order',
            $orderId
        );

        return 'Parcel #' . $orderId . ' confirmed as arrived.';
    }

    /** At a center, but not the one that delivers to the buyer. */
    public function needsTransfer(object $order): bool
    {
        return !empty($order->destination_center_id)
            && !empty($order->current_center_id)
            && (int) $order->current_center_id !== (int) $order->destination_center_id;
    }

    /**
     * @throws ActionFailed
     */
    public function assign(int $orderId, $riderId, ?int $centerId = null): string
    {
        if (empty($riderId)) {
            throw new ActionFailed('Please select a rider to assign this parcel to.');
        }

        $order = $this->parcel($orderId);

        if ($order->status !== 'At Sorting Center') {
            throw new ActionFailed('This parcel is not awaiting assignment.');
        }

        if ($this->needsTransfer($order)) {
            throw new ActionFailed('Dispatch this parcel to ' . ParcelRoute::centerName((int) $order->destination_center_id) . ' first — that center delivers it to the buyer.');
        }

        $this->mustBeAt($order->current_center_id, $centerId, 'is not at your Sorting Center');

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
    public function reschedule(int $orderId, $riderId, ?int $centerId = null): string
    {
        if (empty($riderId)) {
            throw new ActionFailed('Please select a rider to reschedule this delivery to.');
        }

        $order = $this->parcel($orderId);

        $this->mustBeAt($order->destination_center_id ?: $order->current_center_id, $centerId, 'is handled by another Sorting Center');

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
    public function returnToSeller(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        $this->mustBeAt($order->destination_center_id ?: $order->current_center_id, $centerId, 'is handled by another Sorting Center');

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

        OrderTimeline::log($orderId, 'Returned to Seller', 'Returned to the seller', $refused ? 'Refused on delivery' : 'Could not be delivered');

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

    /**
     * Staff of one center may only act on parcels at (or headed to) their own
     * center. Head office (no center), and parcels with no center, pass.
     *
     * @throws ActionFailed
     */
    private function mustBeAt($parcelCenterId, ?int $staffCenterId, string $otherwise): void
    {
        if ($staffCenterId && $parcelCenterId && (int) $parcelCenterId !== $staffCenterId) {
            throw new ActionFailed('This parcel ' . $otherwise . ' (' . ParcelRoute::centerName((int) $parcelCenterId) . ').');
        }
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

        OrderTimeline::log(
            $orderId,
            'Assigned for Delivery',
            $fromStatus === 'Delivery Failed' ? 'Rescheduled for another delivery attempt' : 'Handed to a rider for delivery',
            'Rider: ' . (DB::table('users')->where('id', $riderId)->value('name') ?? 'BoomBuy rider'),
            DB::table('orders')->where('id', $orderId)->value('current_center_id')
        );
    }
}
