<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Options in two parts, like Color × Size: each product_variations row is
 * one combination ("Color: Red · Size: M") with its own stock and price.
 * The second part is optional — single options keep working as before.
 *
 * order_items.variation_id: the exact option bought, so restocking and
 * "Buy again" find it without reading the label back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('option2_type', 50)->nullable()->after('variation_value');
            $table->string('option2_value', 50)->nullable()->after('option2_type');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variation_id')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variation_id');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['option2_type', 'option2_value']);
        });
    }
};
