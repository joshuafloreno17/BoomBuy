<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A buyer, seller, rider or logistics account whose latest application was
 * rejected. They may apply again with the same email and phone: the
 * registration form reuses their account and files a new application,
 * instead of saying the email is already registered.
 */
class RejectedApplicant
{
    public const ROLES = ['buyer', 'seller', 'rider', 'logistics'];

    public static function find(?string $email, string $role): ?User
    {
        if (!$email || !in_array($role, self::ROLES, true)) {
            return null;
        }

        $user = User::where('email', strtolower(trim($email)))->where('role', $role)->first();

        if (!$user) {
            return null;
        }

        $latest = DB::table($role . '_applications')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('status');

        return $latest === 'Rejected' ? $user : null;
    }
}
