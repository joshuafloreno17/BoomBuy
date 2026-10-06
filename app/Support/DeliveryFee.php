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
    public const FREE_SHIPPING_MIN = 999;

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
     * @param float  $itemsTotal   item total after voucher discount
     * @param string $fulfillment  "delivery" or "pickup" (at the Sorting Center)
     */
    public static function for(float $itemsTotal, string $zone = 'local', string $fulfillment = 'delivery'): float
    {
        if ($itemsTotal >= self::FREE_SHIPPING_MIN) {
            return 0.0;
        }

        return $fulfillment === 'pickup' ? self::pickupFee($zone) : self::zoneFee($zone);
    }
}
