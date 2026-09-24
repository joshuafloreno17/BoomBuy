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
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('business_category')->nullable()->after('address');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('stock');
            $table->text('flag_reason')->nullable()->after('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn('business_category');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'flag_reason']);
        });
    }
};
