<?php

namespace App\Support;

use App\Models\SortingCenter;
use Illuminate\Support\Facades\DB;

/**
 * Which Sorting Centers a parcel goes through and how far it travels.
 *
 * There is one BoomBuy Sorting Center per region. The seller drops the parcel
 * at their region's center (origin). If the buyer is in another region it is
 * sent on to the buyer's region's center (destination), where a delivery
 * rider takes it to the door.
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
     * The center serving a town: its region's BoomBuy Sorting Center
     * (e.g. any town in Laguna → "BoomBuy Sorting Center – CALABARZON").
     * None when that region's center is closed.
     */
    public static function centerFor(?string $province, ?string $city = null): ?SortingCenter
    {
        $region = PhLocations::region($province);

        if (!$region) {
            return null;
        }

        return SortingCenter::where('is_active', true)->where('region', $region)->orderBy('id')->first();
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
