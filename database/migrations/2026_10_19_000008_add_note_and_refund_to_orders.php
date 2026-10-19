<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The buyer's note to the seller (up to 200 characters, checkout) and
     * the amount refunded when a paid order is cancelled.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_note', 200)->nullable()->after('shipping_city');
            $table->decimal('refund_amount', 12, 2)->default(0)->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['buyer_note', 'refund_amount']));
    }
};
