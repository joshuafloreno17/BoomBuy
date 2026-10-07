<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Who may be logged in, and the "Remember me" cookie. Shared by the login
 * form and the remember-me middleware so a remembered browser can never
 * skip a check the login form enforces (suspension, pending approval…).
 */
class LoginGate
{
    public const REMEMBER_COOKIE = 'bb_remember';

    /**
     * Set while this browser is logged in, so a visit after the session ran
     * out can say "you were logged out" instead of quietly showing guest pages.
     */
    public const SIGNED_IN_COOKIE = 'bb_signed_in';

    public const REMEMBER_DAYS = 30;

    /** Why this user may not be logged in, or null if they may. */
    public static function blockReason(object $user): ?string
    {
        if (($user->status ?? 'Active') !== 'Active') {
            return $user->status === 'Suspended'
                ? 'Your account has been suspended. Please contact BoomBuy support for assistance.'
                : 'Your account has been deactivated. Please contact BoomBuy support for assistance.';
        }

        // Sellers, riders and logistics accounts need an Approved application
        // — this is what enforces the ID/document checks from registration.
        if (in_array($user->role, ['seller', 'rider', 'logistics'])) {

            $application = DB::table($user->role . '_applications')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            if (!$application || $application->status !== 'Approved') {
                return ($application->status ?? null) === 'Rejected'
                    ? 'Your ' . $user->role . ' application was not approved. You can apply again with the same email from the Register page, or contact support.'
                    : 'Your ' . $user->role . ' account is still pending verification. We will notify you once it has been approved.';
            }
        }

        // Buyers need no approval — they shop as soon as they've verified their
        // email. (Suspension above still applies to them.)

        if (!in_array($user->role, ['buyer', 'seller', 'rider', 'logistics'])) {
            return 'Invalid account role.';
        }

        return null;
    }

    /**
     * Put the user in the session under a fresh session ID (so a session ID
     * planted before login can't be reused — session fixation).
     */
    public static function startSession(object $user): void
    {
        session()->regenerate();

        session()->put('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile_photo' => $user->profile_photo ?? null,
        ]);

        Cookie::queue(self::SIGNED_IN_COOKIE, '1', 60 * 24 * self::REMEMBER_DAYS);
    }

    /** A deliberate log-out: no "your session expired" notice afterwards. */
    public static function signedOut(): void
    {
        Cookie::queue(Cookie::forget(self::SIGNED_IN_COOKIE));
    }

    /**
     * Issue a fresh remember-me token for this browser (only its hash is
     * stored). Each browser has its own, so remembering the phone doesn't
     * sign the laptop out.
     */
    public static function remember(object $user): void
    {
        $token = Str::random(60);

        DB::table('remember_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'last_used_at' => now(),
            'created_at' => now(),
        ]);

        // Tokens no browser has used for longer than a cookie lives.
        DB::table('remember_tokens')
            ->where('user_id', $user->id)
            ->where('last_used_at', '<', now()->subDays(self::REMEMBER_DAYS))
            ->delete();

        Cookie::queue(
            self::REMEMBER_COOKIE,
            $user->id . '|' . $token,
            60 * 24 * self::REMEMBER_DAYS
        );
    }

    /** The user a valid remember-me cookie points to, or null. */
    public static function rememberedUser(?string $cookie): ?object
    {
        if (!$cookie || !str_contains($cookie, '|')) {
            return null;
        }

        [$userId, $token] = explode('|', $cookie, 2);
        $hash = hash('sha256', $token);

        $user = DB::table('users')->where('id', (int) $userId)->first();

        if (!$user) {
            return null;
        }

        $row = DB::table('remember_tokens')->where('user_id', $user->id)->where('token_hash', $hash)->first();

        if ($row) {
            DB::table('remember_tokens')->where('id', $row->id)->update(['last_used_at' => now()]);

            return $user;
        }

        // A token saved the old way (one per account) before this browser came back.
        return !empty($user->remember_token) && hash_equals($user->remember_token, $hash) ? $user : null;
    }

    /**
     * After a password change or reset, no browser may stay signed in through
     * an old "Remember me" cookie (someone who knew the old password could
     * otherwise keep access for 30 days). When the person changed it from
     * their own logged-in, remembered browser, that browser gets a fresh
     * token so only the other devices are signed out.
     */
    public static function passwordChanged(object $user, bool $keepThisBrowser = false): void
    {
        self::forgetEverywhere((int) $user->id);

        if ($keepThisBrowser && request()->hasCookie(self::REMEMBER_COOKIE)) {
            self::remember($user);
        }
    }

    /**
     * Log out of this browser: its remember-me token goes (other remembered
     * browsers stay signed in), and the cookie is dropped. $everywhere: every
     * browser of that user — for a suspended or deactivated account.
     */
    public static function forget(?int $userId, bool $everywhere = false): void
    {
        if ($userId && $everywhere) {
            self::forgetEverywhere($userId);
        } elseif ($userId && ($cookie = request()->cookie(self::REMEMBER_COOKIE)) && str_contains($cookie, '|')) {
            [, $token] = explode('|', $cookie, 2);
            DB::table('remember_tokens')->where('user_id', $userId)->where('token_hash', hash('sha256', $token))->delete();
        }

        Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));
        self::signedOut();
    }

    /** No browser stays remembered for this user. */
    private static function forgetEverywhere(int $userId): void
    {
        DB::table('remember_tokens')->where('user_id', $userId)->delete();
        DB::table('users')->where('id', $userId)->update(['remember_token' => null]);
    }
}
