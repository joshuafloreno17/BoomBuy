<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tokens for the mobile app: "Authorization: Bearer <token>". Only a hash is
 * kept. A token lasts DAYS days from its last use, and is revoked on logout,
 * a password change, or when the account stops being Active.
 */
class ApiToken
{
    public const DAYS = 30;

    /** A new token for this user (shown to the app once). */
    public static function issue(object $user, string $deviceName = 'mobile'): string
    {
        $token = $user->id . '|' . Str::random(60);

        DB::table('api_tokens')->insert([
            'user_id' => $user->id,
            'name' => Str::limit($deviceName, 100, ''),
            'token_hash' => hash('sha256', $token),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::DAYS),
            'created_at' => now(),
        ]);

        return $token;
    }

    /** The token's row and user, or null when it is unknown or expired. */
    public static function find(?string $token): ?array
    {
        if (!$token || !str_contains($token, '|')) {
            return null;
        }

        $row = DB::table('api_tokens')->where('token_hash', hash('sha256', $token))->first();

        if (!$row || ($row->expires_at && now()->greaterThan($row->expires_at))) {
            return null;
        }

        $user = DB::table('users')->where('id', $row->user_id)->first();

        if (!$user) {
            return null;
        }

        // Used again: it lasts another DAYS days.
        DB::table('api_tokens')->where('id', $row->id)->update(['last_used_at' => now(), 'expires_at' => now()->addDays(self::DAYS)]);

        return ['row' => $row, 'user' => $user];
    }

    public static function revoke(?string $token): void
    {
        if ($token) {
            DB::table('api_tokens')->where('token_hash', hash('sha256', $token))->delete();
        }
    }

    public static function revokeAll(int $userId): void
    {
        DB::table('api_tokens')->where('user_id', $userId)->delete();
    }
}
