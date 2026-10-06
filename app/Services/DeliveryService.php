<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\ChatAutomation;
use App\Support\OrderTimeline;
use Illuminate\Support\Facades\DB;

/**
 * A rider's work on an order — the last leg, from the Sorting Center to the
 * buyer (sellers drop parcels at the Sorting Center themselves): Assigned
 * for Delivery → Out for Delivery → Delivered or Delivery Failed (a refusal
 * included). Used by the rider web pages and meant for the mobile app.
 */
class DeliveryService
{
    public const REFUSED_REASON = 'Buyer refused the parcel';

    public const FAILURE_REASONS = [
        self::REFUSED_REASON,
        'Buyer not available',
        'Buyer unreachable by phone',
        'Wrong or incomplete address',
        'Other',
    ];

    public const RIDER_STATUSES = ['Out for Delivery', 'Delivered', 'Delivery Failed'];

    /**
     * @return string  What happened, for the rider.
     *
     * @throws ActionFailed
     */
    public function updateStatus(int $riderId, int $orderId, string $status, string $failureCode = '', string $failureDetails = ''): string
    {
        $status = trim($status);

        if (!in_array($status, self::RIDER_STATUSES, true)) {
            throw new ActionFailed('Invalid delivery status.');
        }

        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Delivery not found.');
        }

        // The last leg belongs to whoever the Sorting Center assigned
        // (delivery_rider_id); orders from before the Sorting Center hop
        // existed fall back to rider_id.
        if ((int) ($order->delivery_rider_id ?? $order->rider_id) !== $riderId) {
            throw new ActionFailed('This delivery is not assigned to you.');
        }

        if ($order->status === 'Assigned for Delivery') {
            if ($status !== 'Out for Delivery') {
                throw new ActionFailed('This order must be moved to Out for Delivery first.');
            }
        } elseif ($order->status === 'Out for Delivery') {
            if (!in_array($status, ['Delivered', 'Delivery Failed'], true)) {
                throw new ActionFailed('Out for Delivery orders can only be marked as Delivered or Delivery Failed.');
            }

            if ($status === 'Delivery Failed') {
                return $this->fail($order, trim($failureCode), trim($failureDetails));
            }
        } else {
            throw new ActionFailed('This order cannot be updated from its current status.');
        }

        // Only from the status just checked, so a double tap can't send the
        // buyer duplicate notifications.
        $updated = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', $order->status)
            ->update(array_merge(
                ['status' => $status, 'updated_at' => now()],
                $status === 'Delivered' ? ['delivered_at' => now()] : []
            ));

        if (!$updated) {
            throw new ActionFailed('This delivery was just updated. Please refresh and try again.');
        }

        OrderTimeline::log(
            $orderId,
            $status,
            $status === 'Delivered' ? 'Delivered' : 'Out for delivery',
            $status === 'Delivered' ? null : 'The rider is on the way to you'
        );

        if ($status === 'Out for Delivery') {
            createNotification((int) $order->buyer_id, 'Order Out for Delivery', 'Your order #' . $orderId . ' is now out for delivery.', 'order', $orderId);
            createNotification($riderId, 'Delivery Out for Delivery', 'Order #' . $orderId . ' is now out for delivery.', 'delivery_status', $orderId);
        } else {
            createNotification((int) $order->buyer_id, 'Order Delivered', 'Your order #' . $orderId . ' has been delivered successfully.', 'order', $orderId);
            notifyOrderSellers($orderId, 'Order Delivered', 'Order #' . $orderId . ' has been delivered to the buyer.');
            createNotification($riderId, 'Delivery Completed', 'Order #' . $orderId . ' has been successfully delivered.', 'delivery_status', $orderId);
        }

        ChatAutomation::orderUpdate($orderId, $status === 'Delivered' ? 'delivered' : 'out_for_delivery');

        return 'Delivery status updated to ' . $status . '!';
    }

    /** A failed attempt; "Buyer refused the parcel" is final and counts toward the buyer's COD limit. */
    private function fail(object $order, string $code, string $details): string
    {
        $orderId = (int) $order->id;

        if (!in_array($code, self::FAILURE_REASONS, true)) {
            throw new ActionFailed('Please choose a reason for the failed delivery.');
        }

        if ($code === 'Other' && $details === '') {
            throw new ActionFailed('Please describe why the delivery failed.');
        }

        $reason = $code === 'Other' ? $details : $code . ($details !== '' ? ' — ' . $details : '');
        $refused = $code === self::REFUSED_REASON;

        // Conditional on still being Out for Delivery so a double submit
        // can't count the attempt (or the refusal) twice.
        $updated = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', 'Out for Delivery')
            ->update([
                'status' => 'Delivery Failed',
                'failure_reason' => $reason,
                'delivery_failed_at' => now(),
                'delivery_attempts' => $order->delivery_attempts + 1,
                // A refusal is final: the Sorting Center returns it to the
                // seller instead of rescheduling.
                'buyer_refused_at' => $refused ? now() : null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            throw new ActionFailed('This delivery was just updated. Please refresh and try again.');
        }

        OrderTimeline::log($orderId, 'Delivery Failed', 'Delivery attempt failed', $reason);

        if ($refused) {
            createNotification(
                (int) $order->buyer_id,
                'Parcel Refused',
                'You refused order #' . $orderId . ' on delivery, so it will be returned to the seller. Refused and cancelled orders count toward the Cash on Delivery limit on your account.',
                'order',
                $orderId
            );

            notifyOrderSellers(
                $orderId,
                'Parcel Refused by Buyer',
                'The buyer refused order #' . $orderId . ' on delivery. The parcel will be brought back to the Sorting Center and returned to you.'
            );

            return 'Marked as refused by the buyer. Please bring the parcel back to the Sorting Center — it will be returned to the seller.';
        }

        createNotification(
            (int) $order->buyer_id,
            'Delivery Attempt Failed',
            'We were unable to deliver your order #' . $orderId . '. Reason: ' . $reason . '. It will be rescheduled shortly.',
            'order',
            $orderId
        );

        notifyOrderSellers(
            $orderId,
            'Delivery Attempt Failed',
            'The rider could not deliver order #' . $orderId . '. Reason: ' . $reason . '. The Sorting Center will reschedule or return it.'
        );

        return 'Delivery marked as failed. The Sorting Center will reschedule or return this parcel.';
    }
}
