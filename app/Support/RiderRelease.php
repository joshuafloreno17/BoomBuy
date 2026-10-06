<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * When a rider stops being Active (suspended/deactivated by the admin or the
 * Logistics Center), the deliveries they were holding must not get stuck on
 * an account that can no longer log in: each goes back to its Sorting
 * Center's Awaiting Assignment queue, and that center is told.
 */
class RiderRelease
{
    /** @return array{deliveries:int} */
    public static function release(int $riderId): array
    {
        $held = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->get(['id', 'current_center_id', 'destination_center_id']);

        $deliveries = DB::table('orders')
            ->whereIn('id', $held->pluck('id'))
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->update([
                'status' => 'At Sorting Center',
                'delivery_rider_id' => null,
                'updated_at' => now(),
            ]);

        // Each center hears about its own parcels.
        foreach ($held->groupBy(fn ($o) => $o->current_center_id ?: $o->destination_center_id) as $centerId => $orders) {
            ParcelRoute::notifyCenter(
                $centerId ? (int) $centerId : null,
                'Parcels Need a New Rider',
                $orders->count() . ' parcel(s) of a rider who is no longer active were moved back to Awaiting Assignment (order #'
                    . $orders->pluck('id')->implode(', #') . '). Collect any that were already out for delivery from the rider, then assign them again.',
                'parcel',
                (int) $orders->first()->id
            );
        }

        return ['deliveries' => $deliveries];
    }

    /** Human summary for the flash message, or '' when nothing moved. */
    public static function summary(array $released): string
    {
        return ($released['deliveries'] ?? 0) > 0
            ? ' ' . $released['deliveries'] . ' delivery(ies) went back to Awaiting Assignment.'
            : '';
    }
}
