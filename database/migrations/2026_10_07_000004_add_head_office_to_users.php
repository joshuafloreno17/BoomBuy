<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Head office (sees every Sorting Center) becomes something the admin
 * chooses, instead of what every logistics account without a center gets —
 * a new account now has no access until the admin assigns it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_head_office')->default(false)->after('sorting_center_id');
        });

        // Accounts already working as head office keep doing so.
        DB::table('users')
            ->where('role', 'logistics')
            ->whereNull('sorting_center_id')
            ->update(['is_head_office' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_head_office');
        });
    }
};
