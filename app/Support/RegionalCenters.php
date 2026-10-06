<?php

namespace App\Support;

use App\Models\SortingCenter;
use Illuminate\Support\Facades\DB;

/**
 * One BoomBuy Sorting Center per region (17), covering the whole country.
 * Used by the migration that replaced the per-town centers and by the seeder.
 */
class RegionalCenters
{
    /** Make sure every region has its center; returns region key => center. */
    public static function ensure(): array
    {
        $centers = [];

        foreach (PhLocations::REGIONS as $key => $region) {
            [$province, $city] = $region['hub'];

            $center = SortingCenter::where('region', $key)->orderBy('id')->first()
                // A per-town center already standing at the hub city becomes the regional one.
                ?? SortingCenter::whereNull('region')->where('province', $province)->get()
                    ->first(fn ($c) => PhLocations::sameTown($c->city_municipality, $city))
                ?? new SortingCenter(['province' => $province, 'city_municipality' => $city, 'is_active' => true]);

            $center->fill([
                'region' => $key,
                'name' => SortingCenter::nameFor($key),
                'address' => $center->address ?: $city . ', ' . str_replace(' (NCR)', '', $province),
            ])->save();

            $centers[$key] = $center;
        }

        return $centers;
    }

    /**
     * Fold the old per-town centers into their region's center: staff,
     * orders and timeline steps move over, then the town center goes.
     */
    public static function foldTownCenters(): int
    {
        $regional = self::ensure();
        $folded = 0;

        foreach (SortingCenter::whereNull('region')->get() as $old) {
            $target = $regional[PhLocations::region($old->province)] ?? null;

            if (!$target) {
                continue;
            }

            DB::table('users')->where('sorting_center_id', $old->id)->update(['sorting_center_id' => $target->id]);

            foreach (['origin_center_id', 'destination_center_id', 'current_center_id'] as $column) {
                DB::table('orders')->where($column, $old->id)->update([$column => $target->id]);
            }

            DB::table('order_events')->where('center_id', $old->id)->update(['center_id' => $target->id]);

            $old->delete();
            $folded++;
        }

        return $folded;
    }
}
