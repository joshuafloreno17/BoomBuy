<?php

namespace App\Support;

use App\Models\PlatformSetting;

/**
 * What the buyer pays for delivery, by how far the parcel travels (see
 * ParcelRoute::ZONES). The fees are set by the admin (Settings → Delivery
 * Fee); orders at or above the free-shipping threshold advertised on the
 * landing page ("Free Shipping on orders over ₱999") pay nothing. The rider
 * is credited the base fee either way.
 */
class DeliveryFee
{
    /** The free-shipping minimum until the admin sets one (Settings → Delivery Fee). */
    public const FREE_SHIPPING_MIN = 999;

    public const FREE_SHIPPING_KEY = 'free_shipping_min';

    /** Item total (per seller's order) from which delivery is free. */
    public static function freeShippingMin(): float
    {
        return round((float) PlatformSetting::get(self::FREE_SHIPPING_KEY, (string) self::FREE_SHIPPING_MIN), 2);
    }

    /** Zone => [setting key, default]. "local" is also the rider's fee. */
    public const ZONE_SETTINGS = [
        'local' => ['delivery_fee', '50'],
        'province' => ['delivery_fee_province', '80'],
        'island' => ['delivery_fee_island', '120'],
        'far' => ['delivery_fee_far', '180'],
    ];

    /** The lowest fee — same town. Shown as "from ₱…" before an address is known. */
    public static function baseFee(): float
    {
        return self::zoneFee('local');
    }

    public static function zoneFee(string $zone): float
    {
        [$key, $default] = self::ZONE_SETTINGS[$zone] ?? self::ZONE_SETTINGS['island'];

        return round((float) PlatformSetting::get($key, $default), 2);
    }

    /**
     * Picking the parcel up at the buyer's Sorting Center skips the rider's
     * last leg, so the same-town (rider) part of the fee comes off.
     */
    public static function pickupFee(string $zone): float
    {
        return max(0.0, round(self::zoneFee($zone) - self::zoneFee('local'), 2));
    }

    /**
     * Each kg after the first, per zone (Logistics → Zones & Rates; 0 = the
     * zone fee covers any weight, as before weights were recorded).
     */
    public static function perKgFee(string $zone): float
    {
        return round((float) PlatformSetting::get(self::perKgKey($zone), '0'), 2);
    }

    public static function perKgKey(string $zone): string
    {
        return 'delivery_fee_per_kg_' . (isset(self::ZONE_SETTINGS[$zone]) ? $zone : 'island');
    }

    /**
     * @param float  $itemsTotal   item total after voucher discount
     * @param string $fulfillment  "delivery" or "pickup" (at the Sorting Center)
     * @param ?float $weightKg     the parcel's actual weight; the zone fee is the first kg
     */
    public static function for(float $itemsTotal, string $zone = 'local', string $fulfillment = 'delivery', ?float $weightKg = null): float
    {
        if ($itemsTotal >= self::freeShippingMin()) {
            return 0.0;
        }

        $fee = $fulfillment === 'pickup' ? self::pickupFee($zone) : self::zoneFee($zone);

        // First kg in the zone fee, then a rate per extra whole kg (1.2 kg → 2 kg).
        if ($weightKg !== null && $weightKg > 0) {
            $fee += self::perKgFee($zone) * (self::chargeableKg($weightKg) - 1);
        }

        return round($fee, 2);
    }

    /**
     * The weight shipping is charged for: the actual weight rounded up to
     * the next whole kg, at least 1 kg (0.01 kg → 1 kg, 1.2 kg → 2 kg).
     */
    public static function chargeableKg(float $kg): int
    {
        return max(1, (int) ceil(round($kg, 2)));
    }
}
