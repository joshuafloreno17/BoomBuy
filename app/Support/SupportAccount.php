<?php

namespace App\Support;

use App\Models\User;

/**
 * The admin account, shown to everyone as "BoomBuy Support". Looked up once
 * per request.
 */
class SupportAccount
{
    /** The admin's email on a fresh install; changed from Admin > My Account. */
    public const EMAIL = 'admin@boombuy.com';

    public static function user(): ?User
    {
        return once(fn () => User::where('role', 'admin')->orderBy('id')->first());
    }

    public static function id(): ?int
    {
        return self::user()?->id;
    }
}
