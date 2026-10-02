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
                ->first();

            if (!$application || $application->status !== 'Approved') {
                return ($application->status ?? null) === 'Rejected'
                    ? 'Your ' . $user->role . ' application was not approved. Please contact support for more information.'
                    : 'Your ' . $user->role . ' account is still pending verification. We will notify you once it has been approved.';
            }
        }

        // Buyers with no application row are legacy accounts from before the
        // approval gate existed — they're left alone.
        if ($user->role === 'buyer') {

            $application = DB::table('buyer_applications')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            if ($application && $application->status !== 'Approved') {
                return $application->status === 'Rejected'
                    ? 'Your account application was not approved. Please contact support for more information.'
                    : 'Your account is still pending administrator verification. We will notify you by email once it has been approved.';
            }
        }

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
    }

    /** Issue a fresh remember-me token (only its hash is stored). */
    public static function remember(object $user): void
    {
        $token = Str::random(60);

        DB::table('users')
            ->where('id', $user->id)
            ->update(['remember_token' => hash('sha256', $token)]);

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

        $user = DB::table('users')->where('id', (int) $userId)->first();

        if (!$user || empty($user->remember_token)) {
            return null;
        }

        return hash_equals($user->remember_token, hash('sha256', $token)) ? $user : null;
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
        DB::table('users')->where('id', $user->id)->update(['remember_token' => null]);

        if ($keepThisBrowser && request()->hasCookie(self::REMEMBER_COOKIE)) {
            self::remember($user);
        }
    }

    /** Invalidate every remembered browser for this user and drop the cookie. */
    public static function forget(?int $userId): void
    {
        if ($userId) {
            DB::table('users')->where('id', $userId)->update(['remember_token' => null]);
        }

        Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));
    }
}
