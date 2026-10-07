<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How many times one buyer may use a voucher. Without it a single buyer
 * could use up all of a voucher's max_uses. Null = no limit per buyer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedInteger('per_buyer_limit')->nullable()->after('max_uses');
        });

        // Existing vouchers: once per buyer, the usual rule for shop vouchers.
        DB::table('vouchers')->update(['per_buyer_limit' => 1]);
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('per_buyer_limit');
        });
    }
};
