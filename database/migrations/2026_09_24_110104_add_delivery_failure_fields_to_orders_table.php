<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('delivery_attempts')->default(0)->after('sorting_center_received_at');
            $table->text('failure_reason')->nullable()->after('delivery_attempts');
            $table->timestamp('delivery_failed_at')->nullable()->after('failure_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_attempts', 'failure_reason', 'delivery_failed_at']);
        });
    }
};
