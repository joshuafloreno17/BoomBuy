<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * When a rider stops being Active (suspended/deactivated by the admin or the
 * Logistics Center), the parcels they were holding must not get stuck on an
 * account that can no longer log in:
 *  - a pickup they claimed but haven't collected goes back to the open pool;
 *  - a delivery assigned to them goes back to the Sorting Center queue so
 *    Logistics can hand it to another rider.
 * "Picked Up" parcels are left alone — Logistics already confirms those on
 * arrival without needing the rider.
 */
class RiderRelease
{
    /** @return array{pickups:int, deliveries:int} */
    public static function release(int $riderId): array
    {
        $pickupIds = DB::table('orders')
            ->where('rider_id', $riderId)
            ->where('status', 'Assigned')
            ->pluck('id');

        $pickups = DB::table('orders')
            ->whereIn('id', $pickupIds)
            ->where('status', 'Assigned')
            ->update([
                'status' => 'Ready for Pickup',
                'rider_id' => null,
                'updated_at' => now(),
            ]);

        $deliveryIds = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->pluck('id');

        $deliveries = DB::table('orders')
            ->whereIn('id', $deliveryIds)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->update([
                'status' => 'At Sorting Center',
                'delivery_rider_id' => null,
                'updated_at' => now(),
            ]);

        foreach ($pickupIds as $orderId) {
            notifyOrderSellers(
                (int) $orderId,
                'Pickup Rider Changed',
                'The rider assigned to collect order #' . $orderId . ' is no longer available. Another rider will pick it up — keep it ready.'
            );

            notifyAllActiveRiders(
                'New Delivery Available',
                'Order #' . $orderId . ' is ready for pickup and available to claim.',
                'delivery',
                (int) $orderId
            );
        }

        if ($deliveries > 0) {
            notifyLogisticsUsers(
                'Parcels Need a New Rider',
                $deliveries . ' parcel(s) of a rider who is no longer active were moved back to Awaiting Assignment (order #'
                    . $deliveryIds->implode(', #') . '). Collect any that were already out for delivery from the rider, then assign them again.',
                'parcel',
                (int) $deliveryIds->first()
            );
        }

        return ['pickups' => $pickups, 'deliveries' => $deliveries];
    }

    /** Human summary for the flash message, or '' when nothing moved. */
    public static function summary(array $released): string
    {
        $parts = [];

        if ($released['pickups'] > 0) {
            $parts[] = $released['pickups'] . ' pickup(s) went back to the open pool';
        }

        if ($released['deliveries'] > 0) {
            $parts[] = $released['deliveries'] . ' delivery(ies) went back to Awaiting Assignment';
        }

        return $parts ? ' ' . ucfirst(implode(' and ', $parts)) . '.' : '';
    }
}
