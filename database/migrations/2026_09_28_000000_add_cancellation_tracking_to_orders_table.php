<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who cancelled an order and when, plus when a buyer refused a parcel at
     * the door — what the COD anti-abuse rule counts. Deriving this from the
     * free-text cancellation/failure reasons would be too fragile.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancelled_by')->nullable()->after('cancellation_reason');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->timestamp('buyer_refused_at')->nullable()->after('delivery_failed_at');
        });

        // Existing buyer cancellations all used this exact reason text.
        DB::table('orders')
            ->where('status', 'Cancelled')
            ->where('cancellation_reason', 'Cancelled by buyer.')
            ->update([
                'cancelled_by' => 'buyer',
                'cancelled_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancelled_at', 'buyer_refused_at']);
        });
    }
};
