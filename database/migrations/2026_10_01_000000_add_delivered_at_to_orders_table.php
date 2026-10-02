<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the rider marked the order Delivered. "Delivered today" counts
     * used updated_at, which also moves when the buyer later confirms
     * receipt (or anything else touches the order).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('delivery_failed_at');
        });

        // Best guess for orders delivered before this column existed.
        DB::table('orders')
            ->where('status', 'Delivered')
            ->update(['delivered_at' => DB::raw('COALESCE(buyer_received_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
