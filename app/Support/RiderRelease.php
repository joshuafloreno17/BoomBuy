<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * When a rider stops being Active (suspended/deactivated by the admin or the
 * Logistics Center), the deliveries they were holding must not get stuck on
 * an account that can no longer log in: each goes back to its Sorting
 * Center's Awaiting Assignment queue, and that center is told. Cash on
 * Delivery money they collected and haven't handed in is called out too,
 * so the center gets it back from them.
 */
class RiderRelease
{
    /** @return array{deliveries:int, cod_orders:int, cod_amount:float} */
    public static function release(int $riderId): array
    {
        // COD money still with the rider, per center that should receive it.
        $cash = app(\App\Services\SortingCenterService::class)->codHeldBy($riderId);
        $riderName = DB::table('users')->where('id', $riderId)->value('name') ?? 'The rider';

        foreach ($cash->groupBy(fn ($o) => $o->destination_center_id ?: $o->current_center_id) as $centerId => $orders) {
            ParcelRoute::notifyCenter(
                $centerId ? (int) $centerId : null,
                'Collect Cash From Rider',
                $riderName . ' is no longer active but still has ₱' . number_format((float) $orders->sum('total_amount'), 2)
                    . ' in Cash on Delivery money (order #' . $orders->pluck('id')->implode(', #') . '). Get it from them, then use "Cash from riders" on the Parcels page.',
                'parcel',
                (int) $orders->first()->id
            );
        }

        // Pickups they accepted but haven't collected: open to other riders again.
        DB::table('orders')
            ->where('rider_id', $riderId)
            ->where('status', 'Pickup Assigned')
            ->update(['status' => 'Ready for Pickup', 'rider_id' => null, 'updated_at' => now()]);

        $held = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->get(['id', 'current_center_id', 'destination_center_id']);

        $deliveries = DB::table('orders')
            ->whereIn('id', $held->pluck('id'))
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->update([
                'status' => 'Sorted',
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

        return ['deliveries' => $deliveries, 'cod_orders' => $cash->count(), 'cod_amount' => (float) $cash->sum('total_amount')];
    }

    /** Human summary for the flash message, or '' when nothing moved. */
    public static function summary(array $released): string
    {
        $note = ($released['deliveries'] ?? 0) > 0
            ? ' ' . $released['deliveries'] . ' delivery(ies) went back to Awaiting Assignment.'
            : '';

        if (($released['cod_orders'] ?? 0) > 0) {
            $note .= ' They still have ₱' . number_format((float) $released['cod_amount'], 2) . ' in COD cash from '
                . $released['cod_orders'] . ' order(s) — their Sorting Center was told to collect it.';
        }

        return $note;
    }
}
