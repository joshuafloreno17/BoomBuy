<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Courier pickup from the seller — how every parcel reaches the seller's
 * Sorting Center (ERP flow: Seller → Rider Pickup → Sorting Center):
 *
 *   Preparing → seller marks it Ready for Pickup (riders whose areas cover
 *   the seller's town are notified; if none do, the Sorting Center assigns
 *   one) → a rider accepts it → Pickup Assigned → the parcel is handed over
 *   (rider and seller both confirm) → Picked Up → the Sorting Center
 *   confirms it arrived (SortingCenterService::confirmReceived).
 */
class PickupService
{
    /** A pickup can be booked for today up to this many days ahead. */
    public const MAX_DAYS_AHEAD = 3;

    public const STATUSES = ['Ready for Pickup', 'Pickup Assigned', 'Picked Up'];

    /** The days a seller can choose, as Y-m-d => label. */
    public static function dayChoices(): array
    {
        $days = [];

        for ($i = 0; $i <= self::MAX_DAYS_AHEAD; $i++) {
            $day = now()->startOfDay()->addDays($i);
            $days[$day->toDateString()] = match ($i) {
                0 => 'Today, ' . $day->format('M j'),
                1 => 'Tomorrow, ' . $day->format('M j'),
                default => $day->format('l, M j'),
            };
        }

        return $days;
    }

    /**
     * The seller marks a packed (Preparing) order Ready for Pickup.
     *
     * @throws ActionFailed
     */
    public function request(int $sellerId, int $orderId, string $date): string
    {
        $order = $this->sellerOrder($sellerId, $orderId);

        if ($order->status !== 'Preparing') {
            throw new ActionFailed('Only orders you are preparing can be marked ready for pickup.');
        }

        if (!array_key_exists($date, self::dayChoices())) {
            throw new ActionFailed('Please choose a pickup day from today up to ' . self::MAX_DAYS_AHEAD . ' days ahead.');
        }

        $seller = ParcelRoute::sellerLocation($sellerId);
        $riders = $this->ridersFor($seller['province'], $seller['city']);

        $route = app(SellerOrderService::class)->ensureRoute($order, $sellerId);

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Preparing')->update([
            'status' => 'Ready for Pickup',
            'pickup_date' => $date,
            'rider_id' => null,
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This order was just updated. Please refresh and try again.');
        }

        $day = self::dayChoices()[$date];
        OrderTimeline::log($orderId, 'Ready for Pickup', 'Seller booked a rider pickup', 'Pickup day: ' . $day, $route['origin_center_id']);

        createNotification((int) $order->buyer_id, 'Order Packed', 'Your order #' . $orderId . ' is packed. A BoomBuy rider will pick it up from the seller (' . $day . ').', 'order', $orderId);

        $town = trim(($seller['city'] ? $seller['city'] . ', ' : '') . ($seller['province'] ?? ''), ', ');
        foreach ($riders as $riderId) {
            createNotification((int) $riderId, 'New Pickup Request', 'Order #' . $orderId . ' is ready for pickup in ' . $town . ' (' . $day . '). Accept it under Items for Pickup.', 'pickup', $orderId);
        }

        // No rider covers this town: the seller's Sorting Center assigns one.
        if ($riders->isEmpty()) {
            ParcelRoute::notifyCenter($route['origin_center_id'], 'Pickup Needs a Rider', 'Order #' . $orderId . ' in ' . ($town ?: 'a seller\'s town') . ' is ready for pickup (' . $day . '), but no rider covers that area. Assign one on the Parcels page.', 'parcel', $orderId);

            return 'Marked ready for pickup (' . $day . '). Your Sorting Center will assign a rider — you\'ll be told who is coming.';
        }

        return 'Marked ready for pickup (' . $day . '). Riders in your area were notified — you\'ll be told when one accepts.';
    }

    /**
     * Pickup requests in this rider's areas that nobody accepted yet.
     */
    public function available(int $riderId): Collection
    {
        $areas = DB::table('rider_areas')->where('rider_id', $riderId)->get(['province', 'city_municipality']);

        if ($areas->isEmpty()) {
            return collect();
        }

        return $this->withDetails(
            DB::table('orders')
                ->where('orders.status', 'Ready for Pickup')
                ->whereNull('orders.rider_id')
                ->whereExists(function ($q) use ($areas) {
                    $q->from('order_items')
                        ->join('users as sellers', 'sellers.id', '=', 'order_items.seller_id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where(function ($any) use ($areas) {
                            foreach ($areas as $area) {
                                $any->orWhere(fn ($a) => $a->where('sellers.province', $area->province)->where('sellers.city_municipality', $area->city_municipality));
                            }
                        });
                })
                ->orderBy('orders.pickup_date')
                ->orderBy('orders.updated_at')
                ->select('orders.*')
                ->get()
        );
    }

    /** Pickups this rider accepted and still has to finish (collect, or bring to the center). */
    public function mine(int $riderId): Collection
    {
        return $this->withDetails(
            DB::table('orders')
                ->where('rider_id', $riderId)
                ->whereIn('status', ['Pickup Assigned', 'Picked Up'])
                ->orderBy('pickup_date')
                ->get()
        );
    }

    /**
     * First come, first served: the UPDATE only takes a row that is still
     * unclaimed, so when two riders accept at once only one wins.
     *
     * @throws ActionFailed
     */
    public function accept(int $riderId, int $orderId): string
    {
        if (!$this->available($riderId)->contains('id', $orderId)) {
            $order = DB::table('orders')->where('id', $orderId)->first(['status', 'rider_id']);

            throw new ActionFailed($order && $order->status === 'Ready for Pickup' && !$order->rider_id
                ? 'This pickup is outside your areas.'
                : 'This pickup was already taken by another rider.');
        }

        $claimed = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', 'Ready for Pickup')
            ->whereNull('rider_id')
            ->update(['rider_id' => $riderId, 'status' => 'Pickup Assigned', 'updated_at' => now()]);

        if (!$claimed) {
            throw new ActionFailed('This pickup was already taken by another rider.');
        }

        $this->assigned($orderId, $riderId);

        return 'Pickup accepted. Go to the seller, then tap "Picked up" once you have the parcel.';
    }

    /**
     * The rider has the parcel and brings it to the seller's Sorting Center.
     *
     * @throws ActionFailed
     */
    public function pickedUp(int $riderId, int $orderId): string
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order || (int) $order->rider_id !== $riderId) {
            throw new ActionFailed('This pickup is not assigned to you.');
        }

        if ($order->status !== 'Pickup Assigned') {
            throw new ActionFailed('This parcel was already picked up.');
        }

        $center = $this->markPickedUp($order, 'Rider picked it up from the seller');

        return 'Picked up! Bring the parcel to ' . $center . '.';
    }

    /**
     * Pickup Assigned → Picked Up, once (the rider or the seller confirms the
     * hand-over first). Returns the Sorting Center the rider brings it to.
     *
     * @throws ActionFailed
     */
    public function markPickedUp(object $order, string $title): string
    {
        $orderId = (int) $order->id;

        $updated = DB::table('orders')->where('id', $orderId)->where('status', 'Pickup Assigned')->update([
            'status' => 'Picked Up',
            'picked_up_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This pickup was just updated. Please refresh and try again.');
        }

        $centerId = $order->origin_center_id ? (int) $order->origin_center_id : null;
        $center = ParcelRoute::centerName($centerId) ?? 'the Sorting Center';

        OrderTimeline::log($orderId, 'Picked Up', $title, 'On the way to ' . $center, $centerId);
        createNotification((int) $order->buyer_id, 'Order Shipped', 'Your order #' . $orderId . ' was picked up from the seller and is on its way to ' . $center . '.', 'order', $orderId);
        notifyOrderSellers($orderId, 'Order Picked Up', 'The rider picked up order #' . $orderId . ' and is bringing it to ' . $center . '.');
        createNotification((int) $order->rider_id, 'Bring Parcel to Sorting Center', 'Order #' . $orderId . ' is picked up. Bring it to ' . $center . '.', 'pickup', $orderId);
        ParcelRoute::notifyCenter($centerId, 'Parcel Picked Up', 'A rider picked up order #' . $orderId . ' from the seller. Confirm once it arrives at your Sorting Center.', 'parcel', $orderId);
        \App\Support\ChatAutomation::orderUpdate($orderId, 'picked_up');

        return $center;
    }

    /**
     * The Sorting Center gives a waiting pickup to one of its riders (when no
     * rider covers the seller's town, or nobody accepted it).
     *
     * @throws ActionFailed
     */
    public function assignByCenter(int $orderId, $riderId, ?int $centerId = null): string
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order || $order->status !== 'Ready for Pickup' || !empty($order->rider_id)) {
            throw new ActionFailed('This pickup is no longer waiting for a rider.');
        }

        if ($centerId !== null && $order->origin_center_id && (int) $order->origin_center_id !== $centerId) {
            throw new ActionFailed('This pickup belongs to another Sorting Center.');
        }

        $rider = app(SortingCenterService::class)->assignableRider($riderId);

        if (!$rider) {
            throw new ActionFailed('Please choose an active, approved rider.');
        }

        $claimed = DB::table('orders')->where('id', $orderId)->where('status', 'Ready for Pickup')->whereNull('rider_id')
            ->update(['rider_id' => $rider->id, 'status' => 'Pickup Assigned', 'updated_at' => now()]);

        if (!$claimed) {
            throw new ActionFailed('A rider just accepted this pickup. Please refresh.');
        }

        $this->assigned($orderId, (int) $rider->id);

        return 'Pickup of order #' . $orderId . ' assigned to ' . $rider->name . '.';
    }

    /** Tells the rider and the seller who is collecting the parcel. */
    private function assigned(int $orderId, int $riderId): void
    {
        $rider = DB::table('users')->where('id', $riderId)->first(['name', 'phone']);
        $day = DB::table('orders')->where('id', $orderId)->value('pickup_date');

        OrderTimeline::log($orderId, 'Pickup Assigned', 'Rider ' . $rider->name . ' will pick it up from the seller');
        createNotification($riderId, 'Pickup Assigned to You', 'Collect order #' . $orderId . ' from the seller' . ($day ? ' on ' . Carbon::parse($day)->format('D, M j') : '') . '. See Items for Pickup.', 'pickup', $orderId);
        notifyOrderSellers($orderId, 'Rider Assigned for Pickup', $rider->name . ' (' . $rider->phone . ') will pick up order #' . $orderId . '. Have the packed parcel and its shipping label ready.');
    }

    /** Active riders whose areas cover this town. */
    public function ridersFor(?string $province, ?string $city): Collection
    {
        if (!$province || !$city) {
            return collect();
        }

        return DB::table('rider_areas')
            ->join('users', 'users.id', '=', 'rider_areas.rider_id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_areas.province', $province)
            ->where('rider_areas.city_municipality', $city)
            ->distinct()
            ->pluck('users.id');
    }

    /** Adds the seller (where to collect), the items and the pickup day label. */
    private function withDetails(Collection $orders): Collection
    {
        if ($orders->isEmpty()) {
            return $orders;
        }

        $items = DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get()->groupBy('order_id');
        $sellerIds = $items->map(fn ($rows) => (int) $rows->first()->seller_id)->unique()->values();
        $sellers = DB::table('users')->whereIn('id', $sellerIds)->get(['id', 'name', 'phone', 'address'])->keyBy('id');
        $shops = \App\Support\SellerShop::many($sellerIds);

        return $orders->map(function ($order) use ($items, $sellers, $shops) {
            $rows = $items->get($order->id, collect());
            $sellerId = (int) ($rows->first()->seller_id ?? 0);
            $seller = $sellers->get($sellerId);

            $order->items = $rows;
            $order->items_count = (int) $rows->sum('quantity');
            $order->shop_name = $shops->get($sellerId)['name'] ?? ($seller->name ?? 'Seller');
            $order->seller_phone = $seller->phone ?? null;
            $order->pickup_address = $seller->address ?? null;
            $order->pickup_day = $order->pickup_date ? Carbon::parse($order->pickup_date)->format('D, M j') : null;
            $order->center_name = ParcelRoute::centerName($order->origin_center_id ? (int) $order->origin_center_id : null);

            return $order;
        });
    }

    private function sellerOrder(int $sellerId, int $orderId): object
    {
        $order = DB::table('orders')
            ->where('id', $orderId)
            ->whereExists(fn ($q) => $q->from('order_items')->whereColumn('order_items.order_id', 'orders.id')->where('order_items.seller_id', $sellerId))
            ->first();

        if (!$order) {
            throw new ActionFailed('Order not found.');
        }

        return $order;
    }
}
