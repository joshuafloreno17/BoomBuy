<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variants: an SKU per row, soft deletes (a removed variant still shows
     * on old orders and as "Variation unavailable" in carts).
     */
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('sku', 40)->nullable()->after('option2_value');
            $table->softDeletes();
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropSoftDeletes();
            $table->dropColumn('sku');
        });
    }
};
