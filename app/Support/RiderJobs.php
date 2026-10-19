<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * What a rider is carrying and may take on (Rider Modules R2/R3, A14):
 * Online/Offline, at most 3 active jobs, and only the jobs of their own
 * logistics company (the Sorting Center of the company that added them).
 */
class RiderJobs
{
    public const ACTIVE_LIMIT = 3;

    /** Rider pay (A33): a completed pickup; a delivery earns the base delivery fee. */
    public const PICKUP_PAY = 30.0;

    /** Statuses in which a job still sits with its rider. */
    public const PICKUP_ACTIVE = ['Pickup Assigned', 'Picked Up'];

    public const DELIVERY_ACTIVE = ['Assigned for Delivery', 'Out for Delivery'];

    public static function activeCount(int $riderId): int
    {
        return DB::table('orders')
            ->where(fn ($q) => $q->where(fn ($p) => $p->where('rider_id', $riderId)->whereIn('status', self::PICKUP_ACTIVE))
                ->orWhere(fn ($d) => $d->where('delivery_rider_id', $riderId)->whereIn('status', self::DELIVERY_ACTIVE))
                ->orWhere(fn ($r) => $r->where('rts_rider_id', $riderId)->where('status', 'Returning to Seller')))
            ->count();
    }

    public static function isOnline(int $riderId): bool
    {
        return (bool) DB::table('users')->where('id', $riderId)->value('is_online');
    }

    public static function setOnline(int $riderId, bool $online): void
    {
        DB::table('users')->where('id', $riderId)->update(['is_online' => $online, 'online_at' => $online ? now() : null, 'updated_at' => now()]);
    }

    /** The Sorting Center of the rider's company; null = not tied to one (head office, or applied on their own). */
    public static function companyCenterId(int $riderId): ?int
    {
        $companyId = DB::table('rider_applications')->where('user_id', $riderId)->latest('id')->value('created_by');

        if (!$companyId) {
            return null;
        }

        $center = DB::table('users')->where('id', $companyId)->value('sorting_center_id');

        return $center ? (int) $center : null;
    }

    /** May this rider work on a job handled by this center? */
    public static function worksFor(int $riderId, ?int $centerId): bool
    {
        $own = self::companyCenterId($riderId);

        return $own === null || $centerId === null || $own === (int) $centerId;
    }

    /**
     * Why this rider can't take another job right now, or null when they can:
     * Active, approved, Online, under the limit, and of the job's company.
     */
    public static function blockReason(int $riderId, ?int $centerId = null, bool $mustBeOnline = true): ?string
    {
        $rider = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.id', $riderId)
            ->where('users.role', 'rider')
            ->first(['users.name', 'users.status', 'users.is_online', 'rider_applications.status as application_status']);

        return match (true) {
            !$rider => 'Rider not found.',
            $rider->status !== 'Active' || $rider->application_status !== 'Approved' => $rider->name . ' is not an active rider.',
            !self::worksFor($riderId, $centerId) => $rider->name . ' works for another logistics company.',
            $mustBeOnline && !$rider->is_online => $rider->name . ' is offline.',
            self::activeCount($riderId) >= self::ACTIVE_LIMIT => $rider->name . ' already has ' . self::ACTIVE_LIMIT . ' active jobs (the limit).',
            default => null,
        };
    }

    /** Today's pay: completed pickups + successful deliveries (failed attempts pay nothing). */
    public static function pay(int $riderId, $from, $to): array
    {
        $pickups = DB::table('orders')->where('rider_id', $riderId)->whereNotNull('pickup_fee')->whereBetween('picked_up_at', [$from, $to]);
        $deliveries = DB::table('orders')->where('delivery_rider_id', $riderId)->whereNotNull('delivered_at')->whereBetween('delivered_at', [$from, $to]);

        $pickupPay = round((float) (clone $pickups)->sum('pickup_fee'), 2);
        $deliveryPay = round((float) (clone $deliveries)->sum('rider_fee'), 2);

        return [
            'pickups' => (clone $pickups)->count(),
            'deliveries' => (clone $deliveries)->count(),
            'pickup_pay' => $pickupPay,
            'delivery_pay' => $deliveryPay,
            'total' => round($pickupPay + $deliveryPay, 2),
        ];
    }
}
