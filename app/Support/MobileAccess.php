<?php

namespace App\Support;

/**
 * Which accounts may use the BoomBuy mobile app. Buyers, sellers and riders
 * do; everyone else (logistics staff, the admin) works on the website.
 */
class MobileAccess
{
    public const ROLES = ['buyer', 'seller', 'rider'];

    public const DESKTOP_ONLY_MESSAGE = 'This account can only log in on the BoomBuy website using a desktop or laptop computer. Please go to boombuy.store to continue.';

    public static function allows(?string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }
}
