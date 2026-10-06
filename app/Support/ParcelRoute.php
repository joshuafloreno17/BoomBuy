<?php

namespace App\Support;

use App\Models\SortingCenter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Which Sorting Centers a parcel goes through and how far it travels.
 *
 * There is one BoomBuy Sorting Center per province. The seller drops the
 * parcel at their province's center (origin). If the buyer is in another
 * province it is sent on to the buyer's province center (destination), where
 * a delivery rider takes it to the door.
 */
class ParcelRoute
{
    /** Distance tiers, nearest first. */
    public const ZONES = [
        'local' => 'Same town',
        'province' => 'Same province',
        'island' => 'Same island group',
        'far' => 'Another island group',
    ];

    /**
     * The nearest open BoomBuy Sorting Center to a town: its own province's
     * (Santa Cruz, Laguna → "BoomBuy Sorting Center – Laguna"); if that one is
     * closed or missing, another in the same region, then one on the same
     * island group. None only when there's nothing open nearby at all.
     */
    public static function centerFor(?string $province, ?string $city = null): ?SortingCenter
    {
        if (!$province) {
            return null;
        }

        $open = fn (array $provinces) => SortingCenter::where('is_active', true)->whereIn('province', $provinces);

        if ($own = $open([$province])->orderBy('id')->first()) {
            return $own;
        }

        // Same region — the region's main center first (e.g. Calamba for CALABARZON).
        $region = PhLocations::region($province);

        if ($region) {
            $hubProvince = PhLocations::REGIONS[$region]['hub'][0];
            $inRegion = $open(PhLocations::REGIONS[$region]['provinces'])->get();

            if ($inRegion->isNotEmpty()) {
                return $inRegion->firstWhere('province', $hubProvince) ?? $inRegion->sortBy('id')->first();
            }
        }

        // Same island group (Luzon / Visayas / Mindanao).
        $island = PhLocations::islandGroup($province);
        $sameIsland = array_values(array_filter(
            array_keys(PhLocations::all()),
            fn ($p) => PhLocations::islandGroup($p) === $island
        ));

        return $open($sameIsland)->orderBy('id')->first();
    }

    /** Days from order to arrival by distance: [earliest, latest]. */
    public const ETA_DAYS = [
        'local' => [1, 2],
        'province' => [1, 3],
        'island' => [2, 5],
        'far' => [4, 8],
    ];

    /** @return array{0: Carbon, 1: Carbon} earliest and latest arrival */
    public static function eta(?string $zone, $from = null): array
    {
        [$min, $max] = self::ETA_DAYS[$zone] ?? self::ETA_DAYS['island'];
        $start = $from ? Carbon::parse($from) : now();

        return [$start->copy()->addDays($min)->startOfDay(), $start->copy()->addDays($max)->startOfDay()];
    }

    /** "Oct 9–11", or "Oct 30 – Nov 2" across months. */
    public static function etaLabel(?string $zone, $from = null): string
    {
        [$early, $late] = self::eta($zone, $from);

        return $early->month === $late->month
            ? $early->format('M j') . '–' . $late->format('j')
            : $early->format('M j') . ' – ' . $late->format('M j');
    }

    /**
     * Tell the logistics staff of one center (plus head-office staff, who have
     * no center and see them all). No center known → every logistics account.
     */
    public static function notifyCenter(?int $centerId, string $title, string $message, ?string $type = null, ?int $referenceId = null): void
    {
        DB::table('users')
            ->where('role', 'logistics')
            ->where('status', 'Active')
            ->when($centerId, fn ($q) => $q->where(fn ($w) => $w->where('sorting_center_id', $centerId)->orWhereNull('sorting_center_id')))
            ->pluck('id')
            ->each(fn ($userId) => createNotification((int) $userId, $title, $message, $type, $referenceId));
    }

    public static function centerName(?int $centerId): ?string
    {
        return $centerId ? SortingCenter::whereKey($centerId)->value('name') : null;
    }

    /** @return array{province: ?string, city: ?string} */
    public static function sellerLocation(int $sellerId): array
    {
        $seller = DB::table('users')->where('id', $sellerId)->first(['province', 'city_municipality']);

        return ['province' => $seller->province ?? null, 'city' => $seller->city_municipality ?? null];
    }

    /**
     * How far a parcel goes from the seller to the buyer. Unknown locations
     * count as "Same island group", the middle tier.
     */
    public static function zone(?array $from, ?array $to): string
    {
        $fromProvince = $from['province'] ?? null;
        $toProvince = $to['province'] ?? null;

        if (!$fromProvince || !$toProvince) {
            return 'island';
        }

        if ($fromProvince === $toProvince) {
            return PhLocations::sameTown($from['city'] ?? null, $to['city'] ?? null) ? 'local' : 'province';
        }

        return PhLocations::islandGroup($fromProvince) === PhLocations::islandGroup($toProvince) ? 'island' : 'far';
    }
}
