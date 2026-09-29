<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short "about this shop" the seller writes on their profile; shown at
 * the top of their shop page (/shop/{seller}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('shop_description', 500)->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn('shop_description');
        });
    }
};
