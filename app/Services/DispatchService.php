<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use App\Support\RiderJobs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who picks a parcel up from the seller (Logistics L3, Rider R3, A14/A34).
 * Next to riders accepting a pickup from their list (PickupService), the
 * logistics company can:
 *
 *  - auto-assign: offer the job to the nearest Online, Active rider of the
 *    company with fewer than 3 jobs; an offer lasts 60 seconds; a declined
 *    or expired offer goes to the next rider; after 3 tries the job waits in
 *    the Unassigned queue for a manual assignment;
 *  - assign it to a rider by hand (one parcel or several), and reassign it
 *    to another rider until it is picked up.
 *
 * $centerId is the acting company's Sorting Center (null = head office).
 */
class DispatchService
{
    public const OFFER_SECONDS = 60;

    public const MAX_TRIES = 3;

    /** Expired offers move on to the next rider. Run on page visits. */
    public function sweep(): int
    {
        $expired = DB::table('job_offers')->where('status', 'pending')->where('expires_at', '<', now())->get();

        foreach ($expired as $offer) {
            $changed = DB::table('job_offers')->where('id', $offer->id)->where('status', 'pending')
                ->update(['status' => 'expired', 'responded_at' => now(), 'updated_at' => now()]);

            if ($changed && ($order = $this->waiting((int) $offer->order_id))) {
                $this->offerNext($order);
            }
        }

        return $expired->count();
    }

    /** @throws ActionFailed */
    public function autoAssign(int $orderId, ?int $centerId = null): string
    {
        $this->sweep();
        $order = $this->pickupJob($orderId, $centerId);

        if (DB::table('job_offers')->where('order_id', $orderId)->where('status', 'pending')->exists()) {
            throw new ActionFailed('This job is already being offered to a rider.');
        }

        if ($order->dispatch_unassigned_at) {
            throw new ActionFailed('This job is in the Unassigned queue — assign a rider by hand.');
        }

        $offer = $this->offerNext($order);

        if (!$offer) {
            return $this->waiting($orderId)?->dispatch_unassigned_at
                ? 'No rider took job #' . $orderId . '. It is now in the Unassigned queue.'
                : 'No Online rider of yours is free for job #' . $orderId . ' right now. Try again shortly.';
        }

        return 'Job #' . $orderId . ' offered to ' . $offer->rider_name . ' (' . self::OFFER_SECONDS . ' seconds to accept).';
    }

    /** @throws ActionFailed */
    public function respond(int $riderId, int $offerId, bool $accept): string
    {
        $this->sweep();

        $offer = DB::table('job_offers')->where('id', $offerId)->where('rider_id', $riderId)->first();

        if (!$offer) {
            throw new ActionFailed('Job offer not found.');
        }

        if ($offer->status === 'expired' || ($offer->status === 'pending' && now()->gt($offer->expires_at))) {
            throw new ActionFailed('This job offer has expired.');
        }

        if ($offer->status !== 'pending') {
            throw new ActionFailed('You already answered this job offer.');
        }

        $order = DB::table('orders')->where('id', $offer->order_id)->first();

        if (!$accept) {
            DB::table('job_offers')->where('id', $offerId)->where('status', 'pending')->update(['status' => 'declined', 'responded_at' => now(), 'updated_at' => now()]);

            if ($waiting = $this->waiting((int) $offer->order_id)) {
                $this->offerNext($waiting);
            }

            return 'Job declined.';
        }

        if ($reason = RiderJobs::blockReason($riderId, $order?->origin_center_id ? (int) $order->origin_center_id : null)) {
            throw new ActionFailed($reason);
        }

        $this->takeJob((int) $offer->order_id, $riderId, 'Accepted the job offer');
        DB::table('job_offers')->where('id', $offerId)->update(['status' => 'accepted', 'responded_at' => now(), 'updated_at' => now()]);

        return 'Job accepted. Go to the seller, then scan the waybill to confirm the pickup.';
    }

    /**
     * Assign a waiting pickup to a rider by hand.
     *
     * @throws ActionFailed
     */
    public function assign(int $orderId, $riderId, ?int $centerId = null): string
    {
        if (empty($riderId)) {
            throw new ActionFailed('Please select a rider.');
        }

        $order = $this->pickupJob($orderId, $centerId);

        if ($reason = RiderJobs::blockReason((int) $riderId, $order->origin_center_id ? (int) $order->origin_center_id : null)) {
            throw new ActionFailed($reason);
        }

        $this->takeJob($orderId, (int) $riderId, 'Assigned by the logistics company');

        return 'Job #' . $orderId . ' assigned to ' . DB::table('users')->where('id', $riderId)->value('name') . '.';
    }

    /**
     * Several waiting pickups to one rider, up to their job limit.
     *
     * @return array{assigned: int[], skipped: array<int, string>}
     */
    public function bulkAssign(array $orderIds, $riderId, ?int $centerId = null): array
    {
        $assigned = [];
        $skipped = [];

        foreach (array_unique(array_map('intval', $orderIds)) as $orderId) {
            try {
                $this->assign($orderId, $riderId, $centerId);
                $assigned[] = $orderId;
            } catch (ActionFailed $e) {
                $skipped[$orderId] = $e->getMessage();
            }
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /**
     * Move an assigned pickup to another rider — only until it is picked up.
     *
     * @throws ActionFailed
     */
    public function reassign(int $orderId, $riderId, ?int $centerId = null): string
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Parcel not found.');
        }

        $this->mustBeOurs($order, $centerId);

        if ($order->status !== 'Pickup Assigned') {
            throw new ActionFailed(in_array($order->status, ['Picked Up', 'At Sorting Center', 'Sorted'], true) || !empty($order->picked_up_at)
                ? 'This parcel was already picked up, so it can no longer be reassigned.'
                : 'Only an assigned pickup can be reassigned.');
        }

        if ((int) $riderId === (int) $order->rider_id) {
            throw new ActionFailed('This job is already with that rider.');
        }

        if ($reason = RiderJobs::blockReason((int) $riderId, $order->origin_center_id ? (int) $order->origin_center_id : null)) {
            throw new ActionFailed($reason);
        }

        $moved = DB::table('orders')->where('id', $orderId)->where('status', 'Pickup Assigned')->where('rider_id', $order->rider_id)
            ->update(['rider_id' => (int) $riderId, 'updated_at' => now()]);

        if (!$moved) {
            throw new ActionFailed('This job was just updated. Please refresh and try again.');
        }

        $from = DB::table('users')->where('id', $order->rider_id)->value('name');
        $to = DB::table('users')->where('id', $riderId)->value('name');

        OrderTimeline::log($orderId, 'Pickup Assigned', 'Pickup moved from ' . $from . ' to ' . $to);
        createNotification((int) $order->rider_id, 'Pickup Reassigned', 'Order #' . $orderId . ' was moved to another rider. You no longer need to pick it up.', 'pickup', $orderId);
        app(PickupService::class)->notifyAssigned($orderId, (int) $riderId);

        return 'Job #' . $orderId . ' moved from ' . $from . ' to ' . $to . '.';
    }

    /** Pickups no rider took after the tries (manual assignment needed). */
    public function unassigned(?int $centerId = null): Collection
    {
        return DB::table('orders')
            ->where('status', 'Ready for Pickup')
            ->whereNull('rider_id')
            ->whereNotNull('dispatch_unassigned_at')
            ->when($centerId, fn ($q) => $q->where('origin_center_id', $centerId))
            ->orderBy('dispatch_unassigned_at')
            ->get();
    }

    /** The rider's open offers (with a few details for the offer card). */
    public function offersFor(int $riderId): Collection
    {
        $this->sweep();

        return DB::table('job_offers')
            ->join('orders', 'orders.id', '=', 'job_offers.order_id')
            ->where('job_offers.rider_id', $riderId)
            ->where('job_offers.status', 'pending')
            ->where('job_offers.expires_at', '>=', now())
            ->orderBy('job_offers.expires_at')
            ->get(['job_offers.*', 'orders.total_amount', 'orders.payment_method', 'orders.shipping_address'])
            ->map(function ($offer) {
                $sellerId = (int) DB::table('order_items')->where('order_id', $offer->order_id)->value('seller_id');
                $seller = DB::table('users')->where('id', $sellerId)->first(['name', 'phone', 'address']);
                $offer->shop_name = \App\Support\SellerShop::one($sellerId, false)['name'] ?? ($seller->name ?? 'Seller');
                $offer->pickup_address = $seller->address ?? null;
                $offer->dropoff_area = self::area((string) $offer->shipping_address);
                $offer->cod = \App\Support\CodPolicy::isCod((string) $offer->payment_method) ? (float) $offer->total_amount : 0.0;
                $offer->pay = RiderJobs::PICKUP_PAY;
                $offer->seconds_left = max(0, now()->diffInSeconds($offer->expires_at, false));

                return $offer;
            });
    }

    /** "Brgy. Central, Quezon City" from the delivery address (the drop-off area, not the house). */
    public static function area(string $address): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        $start = collect($parts)->search(fn ($p) => stripos($p, 'brgy') === 0 || stripos($p, 'barangay') === 0);
        $place = \App\Support\PhLocations::locate($address);
        $city = $place['city'] ?? null;

        return trim(($start !== false ? $parts[$start] . ', ' : '') . ($city ?? ''), ', ');
    }

    /** Offer the job to the next nearest free rider, or park it as Unassigned after the tries. */
    private function offerNext(object $order): ?object
    {
        $tries = DB::table('job_offers')->where('order_id', $order->id)->whereIn('status', ['declined', 'expired'])->count();

        if ($tries >= self::MAX_TRIES) {
            $this->park($order);

            return null;
        }

        $asked = DB::table('job_offers')->where('order_id', $order->id)->pluck('rider_id')->map(fn ($id) => (int) $id)->all();
        $rider = $this->candidates($order)->first(fn ($r) => !in_array((int) $r->id, $asked, true));

        if (!$rider) {
            // Everyone who could was asked: no more automatic offers.
            if ($tries > 0) {
                $this->park($order);
            }

            return null;
        }

        $id = DB::table('job_offers')->insertGetId([
            'order_id' => $order->id, 'rider_id' => $rider->id, 'status' => 'pending',
            'offered_at' => now(), 'expires_at' => now()->addSeconds(self::OFFER_SECONDS),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        createNotification((int) $rider->id, 'New Job Offer', 'Pickup for order #' . $order->id . '. Accept it within ' . self::OFFER_SECONDS . ' seconds.', 'pickup', (int) $order->id);

        $offer = DB::table('job_offers')->find($id);
        $offer->rider_name = $rider->name;

        return $offer;
    }

    private function park(object $order): void
    {
        DB::table('orders')->where('id', $order->id)->whereNull('dispatch_unassigned_at')->update(['dispatch_unassigned_at' => now(), 'updated_at' => now()]);
        ParcelRoute::notifyCenter($order->origin_center_id ? (int) $order->origin_center_id : null, 'Job Unassigned', 'No rider took the pickup for order #' . $order->id . '. Assign one from the Unassigned queue.', 'parcel', (int) $order->id);
    }

    /**
     * Free riders of the company for this pickup, nearest first: one whose
     * area is the seller's town, then the seller's province, then the rest
     * (fewest jobs first on a tie).
     */
    private function candidates(object $order): Collection
    {
        $sellerId = (int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id');
        $town = ParcelRoute::sellerLocation($sellerId);
        $centerId = $order->origin_center_id ? (int) $order->origin_center_id : null;

        return DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('users.is_online', true)
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name', 'users.is_online', 'users.location_at')
            ->distinct()
            ->get()
            // Location off, or the app closed for 5 minutes: no new offers.
            ->filter(fn ($r) => RiderJobs::reachable($r))
            ->filter(fn ($r) => RiderJobs::worksFor((int) $r->id, $centerId) && RiderJobs::activeCount((int) $r->id) < RiderJobs::ACTIVE_LIMIT)
            ->sortBy(function ($r) use ($town) {
                $areas = DB::table('rider_areas')->where('rider_id', $r->id)->get(['province', 'city_municipality']);
                $rank = $areas->contains(fn ($a) => $a->province === $town['province'] && $a->city_municipality === $town['city']) ? 0
                    : ($areas->contains('province', $town['province']) ? 1 : 2);

                return sprintf('%d-%02d-%08d', $rank, RiderJobs::activeCount((int) $r->id), $r->id);
            })
            ->values();
    }

    /** First one wins: only a job still waiting with no rider can be taken. */
    private function takeJob(int $orderId, int $riderId, string $how): void
    {
        $taken = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', 'Ready for Pickup')
            ->whereNull('rider_id')
            ->update(['rider_id' => $riderId, 'status' => 'Pickup Assigned', 'dispatch_unassigned_at' => null, 'updated_at' => now()]);

        if (!$taken) {
            $holder = DB::table('orders')->join('users', 'users.id', '=', 'orders.rider_id')->where('orders.id', $orderId)->value('users.name');

            throw new ActionFailed($holder ? 'This job was already assigned to ' . $holder . '.' : 'This job is no longer waiting for a rider.');
        }

        // Any other open offer for it is withdrawn.
        DB::table('job_offers')->where('order_id', $orderId)->where('status', 'pending')->where('rider_id', '!=', $riderId)
            ->update(['status' => 'cancelled', 'responded_at' => now(), 'updated_at' => now()]);

        app(PickupService::class)->notifyAssigned($orderId, $riderId, $how);
    }

    /** A pickup still waiting for a rider, at the acting company's center. */
    private function pickupJob(int $orderId, ?int $centerId): object
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Parcel not found.');
        }

        $this->mustBeOurs($order, $centerId);

        if ($order->status === 'Pickup Assigned' && $order->rider_id) {
            throw new ActionFailed('This job was already assigned to ' . DB::table('users')->where('id', $order->rider_id)->value('name') . '.');
        }

        if ($order->status !== 'Ready for Pickup') {
            throw new ActionFailed('This parcel is not ready for pickup, so it can\'t be assigned.');
        }

        return $order;
    }

    private function mustBeOurs(object $order, ?int $centerId): void
    {
        if ($centerId && $order->origin_center_id && (int) $order->origin_center_id !== $centerId) {
            throw new ActionFailed('This parcel belongs to another logistics company.');
        }
    }

    private function waiting(int $orderId): ?object
    {
        return DB::table('orders')->where('id', $orderId)->where('status', 'Ready for Pickup')->whereNull('rider_id')->first();
    }
}
