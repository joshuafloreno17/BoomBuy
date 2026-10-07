<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the rider earned for a delivery, saved when it is delivered — so a
 * later change of the delivery fee in Settings doesn't rewrite past earnings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rider_fee', 10, 2)->nullable()->after('delivery_fee');
        });

        // Deliveries made before this: the fee as it is set today.
        $fee = (float) (DB::table('platform_settings')->where('key', 'delivery_fee')->value('value') ?? 50);

        DB::table('orders')
            ->where('status', 'Delivered')
            ->where(fn ($q) => $q->whereNotNull('delivery_rider_id')->orWhereNotNull('rider_id'))
            ->update(['rider_fee' => $fee]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('rider_fee');
        });
    }
};
