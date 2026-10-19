<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Online payment through the sandbox gateway (no real money): GCash,
     * Maya and card orders wait in To Pay until paid, and cancel themselves
     * when unpaid after an hour. COD orders are 'COD' (paid on delivery).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->nullable()->after('payment_method');
            $table->string('payment_reference', 40)->nullable()->after('payment_status');
            $table->string('payment_group', 36)->nullable()->after('payment_reference');
            $table->timestamp('pay_by')->nullable()->after('payment_group');
            $table->timestamp('paid_at')->nullable()->after('pay_by');
            $table->index('payment_group');
            $table->index(['payment_status', 'pay_by']);
        });

        // Low-stock alert once per crossing (A29): set when stock reaches 5, cleared above 5.
        foreach (['products', 'product_variations'] as $stockTable) {
            Schema::table($stockTable, function (Blueprint $table) {
                $table->boolean('low_stock_alerted')->default(false)->after('stock');
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'product_variations'] as $stockTable) {
            Schema::table($stockTable, fn (Blueprint $table) => $table->dropColumn('low_stock_alerted'));
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_group']);
            $table->dropIndex(['payment_status', 'pay_by']);
            $table->dropColumn(['payment_status', 'payment_reference', 'payment_group', 'pay_by', 'paid_at']);
        });
    }
};
