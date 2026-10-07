<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A seller's "% off" on a product. `price` stays what the buyer pays (so the
 * cart, orders and reports don't change); `original_price` is the price
 * before the discount, shown crossed out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable()->after('price');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('original_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'discount_percent']);
        });
    }
};
