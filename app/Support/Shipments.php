<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The logistics company's shipments (L1, L4, L5): the dashboard counts and
 * the shipment list use the same buckets and the same company scope (parcels
 * picked up for, passing through or delivered by its Sorting Center), so a
 * count always equals its filtered list.
 */
class Shipments
{
    public const BUCKETS = [
        'pending' => ['label' => 'Pending pickup', 'statuses' => ['Ready for Pickup', 'Pickup Assigned']],
        'in_transit' => ['label' => 'In transit', 'statuses' => ['Picked Up', 'Received at Branch', 'To Sorting Center', 'At Sorting Center', 'In Transit', 'Sorted', 'To Branch', 'At Branch', 'Assigned for Delivery', 'Out for Delivery']],
        'delivered' => ['label' => 'Delivered', 'statuses' => ['Delivered', 'Completed']],
        'failed' => ['label' => 'Failed / returns', 'statuses' => ['Delivery Failed', 'Returning', 'Returning to Branch', 'Return Ready', 'Returning to Seller', 'Returned to Seller', 'Lost', 'Damaged']],
    ];

    /** Orders of the company's center (all for head office), placed between the dates (both included). */
    public static function query(?int $centerId, ?string $from = null, ?string $to = null)
    {
        $all = collect(self::BUCKETS)->flatMap(fn ($b) => $b['statuses'])->all();

        return DB::table('orders')
            ->whereIn('orders.status', $all)
            ->when($centerId, fn ($q) => $q->where(fn ($c) => $c->where('orders.origin_center_id', $centerId)
                ->orWhere('orders.destination_center_id', $centerId)
                ->orWhere('orders.current_center_id', $centerId)
                ->orWhere('orders.branch_id', $centerId)))
            ->when($from, fn ($q) => $q->whereDate('orders.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('orders.created_at', '<=', $to));
    }

    /** @return array<string, int> bucket => count */
    public static function counts(?int $centerId, ?string $from = null, ?string $to = null): array
    {
        $byStatus = self::query($centerId, $from, $to)->select('orders.status', DB::raw('COUNT(*) as total'))->groupBy('orders.status')->pluck('total', 'status');

        return collect(self::BUCKETS)->map(fn ($b) => (int) collect($b['statuses'])->sum(fn ($s) => $byStatus[$s] ?? 0))->all();
    }

    /**
     * The shipment list: one bucket (or all), optionally searched by tracking
     * number, order id or buyer name (typed text is matched literally).
     */
    public static function list(?int $centerId, ?string $bucket = null, ?string $from = null, ?string $to = null, string $search = '')
    {
        $search = trim(mb_substr($search, 0, 100));

        return self::query($centerId, $from, $to)
            ->when(isset(self::BUCKETS[$bucket]), fn ($q) => $q->whereIn('orders.status', self::BUCKETS[$bucket]['statuses']))
            ->when($search !== '', function ($q) use ($search) {
                $id = Waybill::parse($search);
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $q->where(fn ($m) => $m->when($id, fn ($x) => $x->orWhere('orders.id', $id))
                    ->orWhere('orders.shipping_name', 'like', $like)
                    ->orWhereExists(fn ($b) => $b->select(DB::raw(1))->from('users as buyers')->whereColumn('buyers.id', 'orders.buyer_id')->where('buyers.name', 'like', $like)));
            })
            ->orderByDesc('orders.id')
            ->get(['orders.*'])
            ->map(function ($order) {
                $order->tracking_number = Waybill::number((int) $order->id);
                $order->status_label = OrderStatus::shipmentLabel($order);
                $order->bucket = collect(self::BUCKETS)->search(fn ($b) => in_array($order->status, $b['statuses'], true));

                return $order;
            });
    }

    /** The delivery zone a parcel is sorted to: its Zones & Rates zone, else its province. */
    public static function zoneFor(object $order): string
    {
        $place = PhLocations::locate((string) $order->shipping_address);
        $zone = ShippingZones::zoneOf($place['province'] ?? null);

        return $zone['name'] ?? PhLocations::provinceLabel($place['province'] ?? 'Other');
    }

    /** Sorted parcels waiting at the center, grouped by zone. */
    public static function zoneLists(?int $centerId)
    {
        return DB::table('orders')
            ->where('status', 'Sorted')
            ->when($centerId, fn ($q) => $q->where('current_center_id', $centerId))
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($order) => self::zoneFor($order))
            ->map(fn ($orders) => $orders->pluck('id')->all());
    }

    /** Failed parcels going back (refused, or the last attempt) with every failure reason. */
    public static function rtsQueue(?int $centerId)
    {
        return DB::table('orders')
            ->whereNotNull('rts_at')
            ->whereIn('status', ['Delivery Failed', 'Return Ready', 'Returning', 'Returning to Branch'])
            ->when($centerId, fn ($q) => $q->where(fn ($c) => $c->where('current_center_id', $centerId)->orWhere('destination_center_id', $centerId)->orWhere('branch_id', $centerId)))
            ->orderBy('rts_at')
            ->get()
            ->map(function ($order) {
                $order->tracking_number = Waybill::number((int) $order->id);
                $order->status_label = OrderStatus::shipmentLabel($order);
                $order->failure_reasons = DB::table('order_events')->where('order_id', $order->id)->where('status', 'Delivery Failed')
                    ->whereNotNull('detail')->where('title', 'like', 'Delivery attempt%')->orderBy('id')->pluck('detail')->all();

                return $order;
            });
    }
}
