<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * One form, one submission (Seller Modules A37): a form carries a random
 * token, and the first request with that token claims it. Double-clicking,
 * pressing Enter twice, or going Back and sending the same form again
 * reuses the token, so nothing is created or charged a second time.
 *
 *   Blade:       {!! \App\Support\SubmitOnce::field() !!}
 *   Controller:  if (!SubmitOnce::claim(request(SubmitOnce::FIELD))) { … already done … }
 */
class SubmitOnce
{
    public const FIELD = '_submit_token';

    /** How long a used token is remembered. */
    private const HOURS = 12;

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . Str::uuid() . '">';
    }

    /**
     * True the first time a token is seen. A request without a token (an
     * older form, the mobile app) is let through — the other checks still apply.
     */
    public static function claim(?string $token): bool
    {
        $token = trim((string) $token);

        if ($token === '') {
            return true;
        }

        return Cache::add('submit-once:' . sha1($token), true, now()->addHours(self::HOURS));
    }

    /** Forget a claimed token, so a form that failed validation can be fixed and sent again. */
    public static function release(?string $token): void
    {
        $token = trim((string) $token);

        if ($token !== '') {
            Cache::forget('submit-once:' . sha1($token));
        }
    }
}
