<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The admin login used to be checked against fixed credentials in the code,
 * and its `users` row held a random password nobody knew. The login now
 * checks that row, so give it the password the admin has been using; it is
 * changed from Admin > My Account.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->where('email', 'admin@boombuy.com')
            ->update(['password' => Hash::make('admin123'), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo — the old code ignored this password.
    }
};
