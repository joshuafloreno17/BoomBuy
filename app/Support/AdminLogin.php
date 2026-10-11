<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * The admin signs in with the `users` row whose role is "admin" — its
 * email and password are changed from Admin > My Account.
 */
class AdminLogin
{
    /** First sign-in on a fresh install, before any admin row exists. */
    public const DEFAULT_PASSWORD = 'admin123';

    /** The admin row for these credentials, or null. */
    public static function attempt(string $email, string $password): ?User
    {
        $admin = SupportAccount::user();

        if (!$admin) {
            if ($email !== SupportAccount::EMAIL || $password !== self::DEFAULT_PASSWORD) {
                return null;
            }

            $admin = User::create([
                'name' => 'Admin',
                'email' => SupportAccount::EMAIL,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'admin',
            ]);
        }

        if (strtolower($admin->email) !== $email || !Hash::check($password, $admin->password)) {
            return null;
        }

        return $admin;
    }

    public static function startSession(): void
    {
        // Fresh session ID on privilege change (session fixation).
        session()->regenerate();
        session()->put('admin_logged_in', true);
    }
}
