<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The shipping label as first printed: when, and what was on it, so every
     * reprint shows the same label the rider and the Sorting Center hold.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('waybill_printed_at')->nullable();
            $table->unsignedInteger('waybill_print_count')->default(0);
            $table->json('waybill_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['waybill_printed_at', 'waybill_print_count', 'waybill_snapshot']));
    }
};
