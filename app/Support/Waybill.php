<?php

namespace App\Support;

/**
 * The parcel's waybill number, printed on the shipping label and in its QR
 * code: order #66 → "BB-000066". Sorting Center staff can scan or type it.
 */
class Waybill
{
    public static function number(int $orderId): string
    {
        return 'BB-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
    }

    /** The order id in "BB-000066", "bb000066", "#66" or "66"; null when it isn't one. */
    public static function parse(?string $code): ?int
    {
        $code = strtoupper(trim((string) $code));

        if (preg_match('/^(?:BB-?|#)?0*(\d{1,9})$/', $code, $m)) {
            return (int) $m[1] ?: null;
        }

        return null;
    }
}
