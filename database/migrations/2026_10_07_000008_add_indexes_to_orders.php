<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The columns every list filters or sorts orders by — status tabs, a
 * rider's deliveries, voucher use, newest first. Without indexes each page
 * reads the whole orders table, which gets slow as orders pile up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('created_at');
            $table->index(['delivery_rider_id', 'status']);
            $table->index('rider_id');
            $table->index(['voucher_code', 'buyer_id']);
            $table->index(['buyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['delivery_rider_id', 'status']);
            $table->dropIndex(['rider_id']);
            $table->dropIndex(['voucher_code', 'buyer_id']);
            $table->dropIndex(['buyer_id', 'status']);
        });
    }
};
