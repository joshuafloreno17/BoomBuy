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

    /**
     * Set once a request shows the app's User-Agent. Android's WebView sends
     * the pages its service worker (public/sw.js) fetches with the phone's
     * plain User-Agent, without "BoomBuyApp", so the cookie (which those
     * requests do carry) keeps the app recognised.
     */
    public const APP_COOKIE = 'bb_app';

    /** Is this page being opened inside the BoomBuy app? */
    public static function isApp(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), self::APP_USER_AGENT)
            || $request->cookie(self::APP_COOKIE) === '1';
    }

    /** Mark this phone's app with the cookie when its User-Agent shows it (every web request). */
    public static function rememberApp(Request $request): void
    {
        if (str_contains((string) $request->userAgent(), self::APP_USER_AGENT) && $request->cookie(self::APP_COOKIE) !== '1') {
            \Illuminate\Support\Facades\Cookie::queue(self::APP_COOKIE, '1', 60 * 24 * 365 * 5);
        }
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
