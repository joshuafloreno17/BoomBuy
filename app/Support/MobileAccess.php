<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Which accounts may use the BoomBuy mobile app. Buyers, sellers and riders
 * do; everyone else (logistics staff, the admin) works on the website.
 */
class MobileAccess
{
    public const ROLES = ['buyer', 'seller', 'rider'];

    public const DESKTOP_ONLY_MESSAGE = 'This account can only log in on the BoomBuy website using a desktop or laptop computer. Please go to boombuy.store to continue.';

    /** The Android app adds this to its browser's User-Agent. */
    public const APP_USER_AGENT = 'BoomBuyApp';

    public static function allows(?string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    public const RIDER_APP_ONLY_MESSAGE = 'Riders use the BoomBuy app. Download it on your phone to apply, log in and see your deliveries.';

    /** Is this page being opened inside the BoomBuy app? */
    public static function isApp(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), self::APP_USER_AGENT);
    }

    /**
     * Riders work only in the app: in a browser they can't apply, log in or
     * open rider pages (config app.riders_app_only, on unless turned off).
     */
    public static function riderBlockedHere(Request $request): bool
    {
        return config('app.riders_app_only', true) && !self::isApp($request);
    }
}
