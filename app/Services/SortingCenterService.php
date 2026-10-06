<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\ChatAutomation;
use App\Support\CodPolicy;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use Illuminate\Support\Facades\DB;

/**
 * The Sorting Center (logistics) part of an order. A parcel goes:
 *
 *   seller drops it at the origin center → origin confirms it → (buyer in
 *   another province) dispatched, In Transit → the destination center
 *   confirms its arrival → a delivery rider takes it to the buyer, or — when
 *   the buyer chose pick-up — it waits as Ready to Collect until they come.
 *
 * Also: reschedule a failed delivery; send a parcel back to the seller (it
 * travels back to the seller's center as Returning, waits there as Return
 * Ready, and is handed back); and take in the Cash on Delivery money riders
 * collected.
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

        if ($order->status !== 'Dropped Off') {
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

        notifyOrderSellers(
            $orderId,
            'Parcel at Sorting Center',
            'Order #' . $orderId . ' was received at ' . $hereName . '.'
        );

        if (!$goesOn && $this->isPickup($order)) {
            $this->readyToCollect($order, $here ? (int) $here : null);

            return 'Parcel #' . $orderId . ' confirmed as received. The buyer collects it here.';
        }

        createNotification(
            (int) $order->buyer_id,
            'Parcel at Sorting Center',
            'Your order #' . $orderId . ' arrived at ' . $hereName . '. ' . ($goesOn
                ? 'It will be sent on to ' . $nextName . ', near you.'
                : 'It will be assigned to a rider for delivery shortly.'),
            'order',
            $orderId
        );

        return 'Parcel #' . $orderId . ' confirmed as received.' . ($goesOn ? ' Next: dispatch it to ' . $nextName . '.' : '');
    }

    /**
     * Send a received parcel on to the buyer's province center.
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
     * Dispatch every parcel here that is going to one center, in one go.
     *
     * @throws ActionFailed
     */
    public function dispatchAll(int $destinationCenterId, ?int $centerId = null): string
    {
        $ids = DB::table('orders')
            ->where('status', 'At Sorting Center')
            ->where('destination_center_id', $destinationCenterId)
            ->whereNotNull('current_center_id')
            ->whereColumn('current_center_id', '!=', 'destination_center_id')
            ->when($centerId, fn ($q) => $q->where('current_center_id', $centerId))
            ->orderBy('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            throw new ActionFailed('There is nothing here waiting to go to that Sorting Center.');
        }

        foreach ($ids as $id) {
            $this->dispatch((int) $id, $centerId);
        }

        return $ids->count() . ' parcel(s) dispatched to ' . ParcelRoute::centerName($destinationCenterId) . ' (order #' . $ids->implode(', #') . ').';
    }

    /**
     * The buyer's province center got a parcel dispatched to it.
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

        $hereName = ParcelRoute::centerName((int) $order->destination_center_id) ?? 'the Sorting Center near you';

        OrderTimeline::log($orderId, 'At Sorting Center', 'Arrived at ' . $hereName, null, (int) $order->destination_center_id);

        if ($this->isPickup($order)) {
            $this->readyToCollect($order, (int) $order->destination_center_id);

            return 'Parcel #' . $orderId . ' confirmed as arrived. The buyer collects it here.';
        }

        createNotification(
            (int) $order->buyer_id,
            'Parcel Near You',
            'Your order #' . $orderId . ' arrived at ' . $hereName . ' and will be assigned to a rider for delivery shortly.',
            'order',
            $orderId
        );

        return 'Parcel #' . $orderId . ' confirmed as arrived.';
    }

    /**
     * The buyer came to the center and took their parcel (pick-up orders).
     * Cash on Delivery is paid right here, so it counts as handed in.
     *
     * @throws ActionFailed
     */
    public function handToBuyer(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'Ready to Collect') {
            throw new ActionFailed('This parcel is not waiting for the buyer to collect it.');
        }

        $this->mustBeAt($order->current_center_id, $centerId, 'is waiting at another Sorting Center');

        $isCod = CodPolicy::isCod((string) $order->payment_method);

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Ready to Collect')->update(array_merge([
            'status' => 'Delivered',
            'delivered_at' => now(),
            'buyer_received_at' => now(),
            'updated_at' => now(),
        ], $isCod ? [
            'cod_collected_at' => now(),
            'cod_remitted_at' => now(),
            'cod_remitted_center_id' => $order->current_center_id,
        ] : []));

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $hereName = ParcelRoute::centerName($order->current_center_id ? (int) $order->current_center_id : null) ?? 'the Sorting Center';

        OrderTimeline::log($orderId, 'Delivered', 'Collected by the buyer at ' . $hereName, $isCod ? 'Paid in cash at the counter' : null, $order->current_center_id ? (int) $order->current_center_id : null);

        createNotification((int) $order->buyer_id, 'Order Delivered', 'You collected order #' . $orderId . ' at ' . $hereName . '. Enjoy!', 'order', $orderId);
        notifyOrderSellers($orderId, 'Order Delivered', 'Order #' . $orderId . ' was collected by the buyer at ' . $hereName . '.' . ($isCod ? ' The COD payment is in — your payout is released.' : ''));
        ChatAutomation::orderUpdate($orderId, 'delivered');

        return 'Order #' . $orderId . ' handed to the buyer.' . ($isCod ? ' Cash collected: ₱' . number_format((float) $order->total_amount, 2) . '.' : '');
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

        if ($this->isPickup($order)) {
            throw new ActionFailed('The buyer chose to pick this parcel up at the Sorting Center — no rider needed.');
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
     * Send a parcel that couldn't be delivered (or was never collected) back
     * to the seller: to the seller's center if it's elsewhere (Returning),
     * else it waits here for the seller (Return Ready).
     *
     * @throws ActionFailed
     */
    public function returnToSeller(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        $here = $order->current_center_id ?: $order->destination_center_id;

        $this->mustBeAt($here, $centerId, 'is handled by another Sorting Center');

        if (!in_array($order->status, ['Delivery Failed', 'Ready to Collect'], true)) {
            throw new ActionFailed('Only a failed delivery or a parcel the buyer never collected can be returned to the seller.');
        }

        $here = $here ?: $centerId;
        $origin = $order->origin_center_id;
        $travels = $origin && $here && (int) $origin !== (int) $here;

        $updated = DB::table('orders')->where('id', $orderId)->where('status', $order->status)->update($travels ? [
            'status' => 'Returning',
            'current_center_id' => null,
            'dispatched_at' => now(),
            'updated_at' => now(),
        ] : [
            'status' => 'Return Ready',
            'current_center_id' => $here ?: $origin,
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $why = !empty($order->buyer_refused_at) ? 'Refused on delivery'
            : ($order->status === 'Ready to Collect' ? 'Not collected by the buyer' : 'Could not be delivered');
        $originName = ParcelRoute::centerName($origin ? (int) $origin : null) ?? 'the seller\'s Sorting Center';

        OrderTimeline::log(
            $orderId,
            $travels ? 'Returning' : 'Return Ready',
            $travels ? 'Going back to the seller via ' . $originName : 'Returned — waiting for the seller to collect it',
            $why,
            $here ? (int) $here : null
        );

        createNotification(
            (int) $order->buyer_id,
            'Order Returned to Seller',
            'Order #' . $orderId . ' is going back to the seller (' . strtolower($why) . ').',
            'order',
            $orderId
        );

        if ($travels) {
            ParcelRoute::notifyCenter((int) $origin, 'Incoming Return', 'Order #' . $orderId . ' is coming back to you for its seller (' . strtolower($why) . '). Confirm when it arrives.', 'parcel', $orderId);
        } else {
            $this->tellSellerToCollect($orderId, $here ? (int) $here : null, $why);
        }

        return $travels
            ? 'Parcel #' . $orderId . ' sent back to ' . $originName . ' for the seller.'
            : 'Parcel #' . $orderId . ' is waiting here for the seller to collect it.';
    }

    /**
     * A returned parcel reached the seller's center.
     *
     * @throws ActionFailed
     */
    public function confirmReturnArrival(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'Returning') {
            throw new ActionFailed('This parcel is not on its way back.');
        }

        $this->mustBeAt($order->origin_center_id, $centerId, 'is going back to another Sorting Center');

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Returning')->update([
            'status' => 'Return Ready',
            'current_center_id' => $order->origin_center_id,
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $hereName = ParcelRoute::centerName((int) $order->origin_center_id);

        OrderTimeline::log($orderId, 'Return Ready', 'Back at ' . $hereName . ' — waiting for the seller', null, (int) $order->origin_center_id);
        $this->tellSellerToCollect($orderId, (int) $order->origin_center_id, null);

        return 'Returned parcel #' . $orderId . ' is here. The seller has been told to collect it.';
    }

    /**
     * The seller came for their returned parcel.
     *
     * @throws ActionFailed
     */
    public function handBackToSeller(int $orderId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId);

        if ($order->status !== 'Return Ready') {
            throw new ActionFailed('This parcel is not waiting for its seller.');
        }

        $this->mustBeAt($order->current_center_id, $centerId, 'is waiting at another Sorting Center');

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Return Ready')->update([
            'status' => 'Returned to Seller',
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        OrderTimeline::log($orderId, 'Returned to Seller', 'Handed back to the seller', null, $order->current_center_id ? (int) $order->current_center_id : null);

        foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
            createNotification((int) $sellerId, 'Order Returned to You', 'You collected returned order #' . $orderId . '. Restock the items from your Orders page if they can be sold again.', 'order', $orderId);
        }

        return 'Order #' . $orderId . ' handed back to the seller.';
    }

    /**
     * A rider hands in the Cash on Delivery money they collected for this
     * center's deliveries. That money is then the seller's payout.
     *
     * @throws ActionFailed
     */
    public function receiveCod(int $riderId, ?int $centerId = null): string
    {
        $orders = $this->codHeldBy($riderId, $centerId);

        if ($orders->isEmpty()) {
            throw new ActionFailed('This rider has no Cash on Delivery money to hand in here.');
        }

        $receivingCenter = $centerId ?: ($orders->first()->destination_center_id ?: $orders->first()->current_center_id);

        DB::table('orders')
            ->whereIn('id', $orders->pluck('id'))
            ->whereNull('cod_remitted_at')
            ->update(['cod_remitted_at' => now(), 'cod_remitted_center_id' => $receivingCenter, 'updated_at' => now()]);

        $total = (float) $orders->sum('total_amount');
        $centerName = ParcelRoute::centerName($receivingCenter ? (int) $receivingCenter : null) ?? 'the Sorting Center';

        createNotification($riderId, 'Cash Handed In', '₱' . number_format($total, 2) . ' for ' . $orders->count() . ' COD delivery(ies) was received by ' . $centerName . '.', 'delivery_status');

        foreach ($orders as $order) {
            notifyOrderSellers((int) $order->id, 'COD Payout Released', 'The Cash on Delivery payment for order #' . $order->id . ' reached ' . $centerName . ' — your payout is released.');
        }

        return '₱' . number_format($total, 2) . ' received from ' . (DB::table('users')->where('id', $riderId)->value('name') ?? 'the rider') . ' for ' . $orders->count() . ' delivery(ies).';
    }

    /** COD orders a rider delivered and still holds the cash for (this center's, or all for head office). */
    public function codHeldBy(int $riderId, ?int $centerId = null)
    {
        return DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereNotNull('cod_collected_at')
            ->whereNull('cod_remitted_at')
            ->when($centerId, fn ($q) => $q->where(fn ($w) => $w->where('destination_center_id', $centerId)
                ->orWhere('current_center_id', $centerId)
                ->orWhere(fn ($n) => $n->whereNull('destination_center_id')->where('origin_center_id', $centerId))))
            ->orderBy('cod_collected_at')
            ->get(['id', 'total_amount', 'destination_center_id', 'current_center_id', 'cod_collected_at']);
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

    private function isPickup(object $order): bool
    {
        return ($order->fulfillment ?? 'delivery') === 'pickup';
    }

    /** A pick-up parcel reached the buyer's center: it waits there for them. */
    private function readyToCollect(object $order, ?int $here): void
    {
        DB::table('orders')->where('id', $order->id)->where('status', 'At Sorting Center')->update([
            'status' => 'Ready to Collect',
            'updated_at' => now(),
        ]);

        $center = $here ? \App\Models\SortingCenter::find($here) : null;
        $where = $center ? $center->name . ' (' . ($center->address ?: $center->town) . ')' : 'the Sorting Center';

        OrderTimeline::log((int) $order->id, 'Ready to Collect', 'Ready to collect at ' . ($center->name ?? 'the Sorting Center'), $center ? ($center->address ?: $center->town) : null, $here);

        createNotification(
            (int) $order->buyer_id,
            'Ready to Collect',
            'Your order #' . $order->id . ' is ready to collect at ' . $where . '. Show your order number'
                . (CodPolicy::isCod((string) $order->payment_method) ? ' and pay ₱' . number_format((float) $order->total_amount, 2) . ' in cash' : '') . '.',
            'order',
            (int) $order->id
        );
    }

    private function tellSellerToCollect(int $orderId, ?int $centerId, ?string $why): void
    {
        $center = $centerId ? \App\Models\SortingCenter::find($centerId) : null;
        $where = $center ? $center->name . ' (' . ($center->address ?: $center->town) . ')' : 'your Sorting Center';

        foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
            createNotification(
                (int) $sellerId,
                'Collect Your Returned Parcel',
                'Order #' . $orderId . ' was returned' . ($why ? ' (' . strtolower($why) . ')' : '') . '. Collect it at ' . $where . '.',
                'order',
                $orderId
            );
        }
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
