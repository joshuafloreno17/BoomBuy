<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * After a failed delivery the rider still has the parcel. The Sorting Center
 * confirms it was brought back (back_at_center_at) before it is sent out
 * again or returned to the seller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('back_at_center_at')->nullable()->after('delivery_failed_at');
        });

        // Failed deliveries from before this step: treated as already back.
        DB::table('orders')
            ->where('status', 'Delivery Failed')
            ->update(['back_at_center_at' => DB::raw('COALESCE(delivery_failed_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('back_at_center_at');
        });
    }
};
