<?php

namespace App\Support;

use App\Models\SortingCenter;
use Illuminate\Support\Facades\DB;

/**
 * One BoomBuy Sorting Center per province (83), e.g. "BoomBuy Sorting Center
 * – Cavite" in Imus. A parcel for a buyer in another province goes from the
 * seller's province center to the buyer's.
 */
class ProvincialCenters
{
    /** Make sure every province has its center; returns province => center. */
    public static function ensure(): array
    {
        $centers = [];

        foreach (array_keys(PhLocations::all()) as $province) {
            $existing = SortingCenter::where('province', $province)->orderBy('id')->get();
            $center = $existing->first();

            // Any extra center in the same province folds into the first.
            foreach ($existing->slice(1) as $extra) {
                self::moveReferences($extra->id, $center->id);
                $extra->delete();
            }

            $capital = PhLocations::PROVINCE_CAPITALS[$province] ?? (PhLocations::all()[$province][0] ?? $province);

            $center ??= new SortingCenter([
                'province' => $province,
                'city_municipality' => $capital,
                'address' => $capital . ', ' . PhLocations::provinceLabel($province),
                'is_active' => true,
            ]);

            $center->fill([
                'name' => SortingCenter::nameFor($province),
                'region' => PhLocations::region($province),
            ])->save();

            $centers[$province] = $center;
        }

        return $centers;
    }

    /**
     * From regional centers to provincial ones: every province gets its
     * center (a regional one becomes the center of the province it stands
     * in), then orders not yet out for delivery are re-routed to the right
     * province centers.
     */
    public static function convert(): void
    {
        self::ensure();

        // Staff go to their own province's center (a regional center had
        // staff from several provinces).
        $staff = DB::table('users')->where('role', 'logistics')->whereNotNull('sorting_center_id')->whereNotNull('province')->get(['id', 'province']);

        foreach ($staff as $person) {
            if ($center = ParcelRoute::centerFor($person->province)) {
                DB::table('users')->where('id', $person->id)->update(['sorting_center_id' => $center->id]);
            }
        }

        $orders = DB::table('orders')
            ->whereIn('status', ['Pending', 'Processing', 'Dropped Off', 'At Sorting Center', 'In Transit'])
            ->get(['id', 'status', 'shipping_province', 'shipping_address']);

        foreach ($orders as $order) {
            $buyerProvince = $order->shipping_province ?: (PhLocations::locate($order->shipping_address)['province'] ?? null);
            $update = ['destination_center_id' => ParcelRoute::centerFor($buyerProvince)?->id];

            // Not at a center yet: it'll be dropped off at the seller's province center.
            if (in_array($order->status, ['Pending', 'Processing', 'Dropped Off'], true)) {
                $sellerId = (int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id');
                $update['origin_center_id'] = ParcelRoute::centerFor(ParcelRoute::sellerLocation($sellerId)['province'])?->id;
            }

            DB::table('orders')->where('id', $order->id)->update($update);
        }
    }

    private static function moveReferences(int $from, int $to): void
    {
        DB::table('users')->where('sorting_center_id', $from)->update(['sorting_center_id' => $to]);

        foreach (['origin_center_id', 'destination_center_id', 'current_center_id'] as $column) {
            DB::table('orders')->where($column, $from)->update([$column => $to]);
        }

        DB::table('order_events')->where('center_id', $from)->update(['center_id' => $to]);
    }
}
