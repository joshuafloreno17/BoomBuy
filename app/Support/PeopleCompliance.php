<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin → Compliance for riders and buyers (sellers have their own product
 * rules). Who needs a look, and why:
 *
 *  Riders — a delivery stuck with them for days, too many failed attempts,
 *           or open complaints against them.
 *  Buyers — Cash on Delivery paused (or one strike away) for cancelling or
 *           refusing parcels, or open complaints against them.
 */
class PeopleCompliance
{
    /** A delivery untouched this long with its rider is "stuck". */
    public const STUCK_DAYS = 2;

    /** Failed share of attempts that needs a look, once there are enough attempts to judge. */
    public const FAIL_RATE = 0.3;
    public const MIN_ATTEMPTS = 3;

    public const OPEN_COMPLAINT_STATUSES = ['Pending', 'Under Review'];

    /** @return Collection<int, array> one row per approved rider */
    public static function riders(): Collection
    {
        $riders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.city_municipality', 'users.province')
            ->distinct()
            ->get();

        $ids = $riders->pluck('id');

        $delivered = DB::table('orders')->whereIn('delivery_rider_id', $ids)->where('status', 'Delivered')
            ->selectRaw('delivery_rider_id as id, COUNT(*) as n')->groupBy('delivery_rider_id')->pluck('n', 'id');

        $failed = DB::table('orders')->whereIn('delivery_rider_id', $ids)->where('delivery_attempts', '>', 0)
            ->selectRaw('delivery_rider_id as id, SUM(delivery_attempts) as n')->groupBy('delivery_rider_id')->pluck('n', 'id');

        $active = DB::table('orders')->whereIn('delivery_rider_id', $ids)
            ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
            ->get(['id', 'delivery_rider_id', 'status', 'updated_at', 'shipping_address'])
            ->groupBy('delivery_rider_id');

        $complaints = self::openComplaints($ids);
        $stuckBefore = now()->subDays(self::STUCK_DAYS);

        return $riders->map(function ($rider) use ($delivered, $failed, $active, $complaints, $stuckBefore) {
            $done = (int) ($delivered[$rider->id] ?? 0);
            $fails = (int) ($failed[$rider->id] ?? 0);
            $attempts = $done + $fails;
            $rate = $attempts > 0 ? $fails / $attempts : 0.0;
            $carrying = $active->get($rider->id, collect());
            $stuck = $carrying->filter(fn ($o) => \Illuminate\Support\Carbon::parse($o->updated_at)->lt($stuckBefore))->values();
            $open = $complaints->get($rider->id, collect());

            $issues = [];
            if ($stuck->isNotEmpty()) {
                $issues[] = $stuck->count() . ' delivery(ies) not moved in over ' . self::STUCK_DAYS . ' days';
            }
            if ($attempts >= self::MIN_ATTEMPTS && $rate >= self::FAIL_RATE) {
                $issues[] = round($rate * 100) . '% of delivery attempts failed (' . $fails . ' of ' . $attempts . ')';
            }
            if ($open->isNotEmpty()) {
                $issues[] = $open->count() . ' open complaint(s)';
            }

            return [
                'user_id' => $rider->id,
                'shop' => $rider->name,
                'name' => $rider->name,
                'email' => $rider->email,
                'area' => trim(($rider->city_municipality ? $rider->city_municipality . ', ' : '') . str_replace(' (NCR)', '', (string) $rider->province), ', '),
                'account_status' => $rider->status ?? 'Active',
                'delivered' => $done,
                'failed' => $fails,
                'fail_rate' => $rate,
                'carrying' => $carrying->count(),
                'stuck' => $stuck,
                'complaints' => $open,
                'issues' => $issues,
                'has_issues' => $issues !== [],
            ];
        });
    }

    /** @return Collection<int, array> one row per buyer who has ordered or been complained about */
    public static function buyers(): Collection
    {
        $complainedAbout = DB::table('complaints')->whereIn('status', self::OPEN_COMPLAINT_STATUSES)->pluck('against_user_id');

        $buyers = DB::table('users')
            ->where('role', 'buyer')
            ->where(fn ($q) => $q->whereIn('id', DB::table('orders')->select('buyer_id'))->orWhereIn('id', $complainedAbout))
            ->get(['id', 'name', 'email', 'status']);

        $complaints = self::openComplaints($buyers->pluck('id'));

        return $buyers->map(function ($buyer) use ($complaints) {
            $history = CodPolicy::buyerHistory((int) $buyer->id);
            $cod = CodPolicy::status((int) $buyer->id);
            $open = $complaints->get($buyer->id, collect());

            $issues = [];
            if ($cod['blocked']) {
                $issues[] = 'Cash on Delivery paused until ' . $cod['available_at']->format('M j, Y') . ' (' . $cod['strikes'] . ' cancellations/refusals in ' . CodPolicy::WINDOW_DAYS . ' days)';
            } elseif ($cod['strikes'] >= $cod['limit'] - 1 && $cod['strikes'] > 0) {
                $issues[] = 'One strike away from a COD pause (' . $cod['strikes'] . ' of ' . $cod['limit'] . ' in ' . CodPolicy::WINDOW_DAYS . ' days)';
            }
            if ($open->isNotEmpty()) {
                $issues[] = $open->count() . ' open complaint(s)';
            }

            return [
                'user_id' => $buyer->id,
                'shop' => $buyer->name,
                'name' => $buyer->name,
                'email' => $buyer->email,
                'account_status' => $buyer->status ?? 'Active',
                'orders' => $history['orders'],
                'cancelled' => $history['cancelled'],
                'refused' => $history['refused'],
                'strikes' => $cod['strikes'],
                'cod_blocked' => $cod['blocked'],
                'complaints' => $open,
                'issues' => $issues,
                'has_issues' => $issues !== [],
            ];
        });
    }

    /** Open complaints against these users, grouped by user id. */
    private static function openComplaints(Collection $userIds): Collection
    {
        return DB::table('complaints')
            ->whereIn('against_user_id', $userIds)
            ->whereIn('status', self::OPEN_COMPLAINT_STATUSES)
            ->orderByDesc('created_at')
            ->get(['id', 'against_user_id', 'subject', 'status', 'created_at'])
            ->groupBy('against_user_id');
    }
}
