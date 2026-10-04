<?php

namespace App\Support;

use App\Models\User;

/**
 * The admin account, shown to everyone as "BoomBuy Support". Looked up once
 * per request.
 */
class SupportAccount
{
    public const EMAIL = 'admin@boombuy.com';

    public static function user(): ?User
    {
        return once(fn () => User::where('email', self::EMAIL)->first());
    }

    public static function id(): ?int
    {
        return self::user()?->id;
    }
}
