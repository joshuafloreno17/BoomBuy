<?php

namespace App\Support;

use App\Models\PlatformSetting;

/**
 * What the buyer pays for delivery. The fee itself is set by the admin
 * (Settings → Delivery Fee); orders at or above the free-shipping threshold
 * advertised on the landing page ("Free Shipping on orders over ₱999") pay
 * nothing. The rider is credited the fee either way.
 */
class DeliveryFee
{
    public const FREE_SHIPPING_MIN = 999;

    public static function baseFee(): float
    {
        return round((float) PlatformSetting::get('delivery_fee', '50'), 2);
    }

    /** @param float $itemsTotal item total after voucher discount */
    public static function for(float $itemsTotal): float
    {
        return $itemsTotal >= self::FREE_SHIPPING_MIN ? 0.0 : self::baseFee();
    }
}
