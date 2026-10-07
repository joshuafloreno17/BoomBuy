<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courier pickup from the seller (ERP: "schedule courier pickup"). The seller
 * picks a day; a rider in their town accepts it (orders.rider_id), collects
 * the parcel and brings it to the seller's Sorting Center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('rider_id');
            $table->timestamp('picked_up_at')->nullable()->after('pickup_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_date', 'picked_up_at']);
        });
    }
};
