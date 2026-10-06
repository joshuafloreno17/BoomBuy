<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * What a buyer's account must have before they can check out: a name, a
 * phone the rider can call, and an address with a town and province we can
 * route to a Sorting Center (the default saved address, or the profile one).
 */
class BuyerProfile
{
    public const PHONE_PATTERN = '/^[0-9+\-\s]{7,20}$/';

    /** @return string[] what's missing, in words for the buyer; empty when complete */
    public static function missing(int $buyerId): array
    {
        $buyer = DB::table('users')->where('id', $buyerId)->first(['name', 'phone', 'address']);

        if (!$buyer) {
            return ['Your account'];
        }

        $missing = [];

        if (trim((string) $buyer->name) === '') {
            $missing[] = 'Your full name';
        }

        if (!preg_match(self::PHONE_PATTERN, trim((string) $buyer->phone))) {
            $missing[] = 'A mobile number the rider can call';
        }

        $address = DB::table('buyer_addresses')->where('user_id', $buyerId)->where('is_default', true)->value('address')
            ?? $buyer->address;

        if (!PhLocations::locate($address)) {
            $missing[] = 'A delivery address with your province and city/municipality';
        }

        return $missing;
    }

    public static function isComplete(int $buyerId): bool
    {
        return self::missing($buyerId) === [];
    }
}
