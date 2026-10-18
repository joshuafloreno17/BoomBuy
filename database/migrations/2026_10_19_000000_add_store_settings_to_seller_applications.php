<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Settings: the shop's logo, banner and contact number buyers see,
     * and vacation mode (products stay visible but can't be bought).
     */
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('shop_logo')->nullable()->after('shop_description');
            $table->string('shop_banner')->nullable()->after('shop_logo');
            $table->string('shop_phone', 20)->nullable()->after('shop_banner');
            $table->boolean('vacation_mode')->default(false)->after('shop_phone');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn(['shop_logo', 'shop_banner', 'shop_phone', 'vacation_mode']);
        });
    }
};
