<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeded Sorting Center staff log in as sortingcenter.<place>@boombuy.test
     * instead of hub.<place>@…, so the name says what they staff. The one
     * hub.* account on a branch (Santa Cruz, Laguna) becomes a branch.* one;
     * branch.santa-cruz is already Marinduque's.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'logistics')
            ->where('email', 'branch.santa-cruz-laguna@boombuy.test')
            ->doesntExistOr(fn () => throw new RuntimeException('branch.santa-cruz-laguna@boombuy.test is taken.'));

        DB::table('users')
            ->where('role', 'logistics')
            ->where('email', 'hub.santa-cruz@boombuy.test')
            ->whereIn('sorting_center_id', DB::table('sorting_centers')->whereNotNull('parent_id')->select('id'))
            ->update(['email' => 'branch.santa-cruz-laguna@boombuy.test']);

        DB::table('users')
            ->where('role', 'logistics')
            ->where('email', 'like', 'hub.%@boombuy.test')
            ->update(['email' => DB::raw("CONCAT('sortingcenter.', SUBSTRING(email, 5))")]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'logistics')
            ->where('email', 'like', 'sortingcenter.%@boombuy.test')
            ->update(['email' => DB::raw("CONCAT('hub.', SUBSTRING(email, 15))")]);

        DB::table('users')
            ->where('role', 'logistics')
            ->where('email', 'branch.santa-cruz-laguna@boombuy.test')
            ->update(['email' => 'hub.santa-cruz@boombuy.test']);
    }
};
