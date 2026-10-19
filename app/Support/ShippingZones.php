<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Delivery zones and their rates (Logistics → Zones & Rates). A zone is a
 * list of provinces; a rate is the first kg plus each extra whole kg for an
 * origin → destination zone. When zones exist, an address outside every
 * zone can't be delivered to. With no zones at all, the admin's Delivery
 * Fee settings apply as before.
 */
class ShippingZones
{
    /** @return array<int, array{id:int, name:string, provinces:string[]}> */
    public static function all(): array
    {
        return DB::table('shipping_zones')->orderBy('name')->get()
            ->map(fn ($z) => ['id' => (int) $z->id, 'name' => $z->name, 'provinces' => json_decode($z->provinces, true) ?: []])
            ->all();
    }

    public static function configured(): bool
    {
        return self::all() !== [];
    }

    public static function zoneOf(?string $province): ?array
    {
        if (!$province) {
            return null;
        }

        foreach (self::all() as $zone) {
            if (in_array($province, $zone['provinces'], true)) {
                return $zone;
            }
        }

        return null;
    }

    /** False only when zones are set up and none covers this province. */
    public static function serviceable(?string $province): bool
    {
        return !self::configured() || !$province || self::zoneOf($province) !== null;
    }

    public static function unserviceableMessage(?string $province): string
    {
        return 'Sorry, we don\'t deliver to ' . PhLocations::provinceLabel($province) . ' yet — this area is not serviceable. Choose another address.';
    }

    /** The rate row for a route, or null when none is set. */
    public static function rate(?string $fromProvince, ?string $toProvince): ?object
    {
        $from = self::zoneOf($fromProvince);
        $to = self::zoneOf($toProvince);

        if (!$from || !$to) {
            return null;
        }

        return DB::table('shipping_rates')->where('from_zone_id', $from['id'])->where('to_zone_id', $to['id'])->first();
    }

    /** First kg + each extra whole kg (1.2 kg → 2 kg), or null with no rate for the route. */
    public static function fee(?string $fromProvince, ?string $toProvince, ?float $weightKg): ?float
    {
        $rate = self::rate($fromProvince, $toProvince);

        if (!$rate) {
            return null;
        }

        $extraKg = DeliveryFee::chargeableKg((float) ($weightKg ?: 0.01)) - 1;

        return round((float) $rate->first_kg + (float) $rate->per_kg * $extraKg, 2);
    }

    /** A rate value: a number above 0 (₱0.01 at least). */
    public static function rateError(mixed $value, string $label): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || !is_numeric($value) || (float) $value <= 0) {
            return $label . ' must be a number more than ₱0 (at least ₱0.01).';
        }

        if ((float) $value > 100000 || preg_match('/\.\d{3,}$/', $value)) {
            return $label . ' must be at most ₱100,000 with up to 2 decimals.';
        }

        return null;
    }
}
