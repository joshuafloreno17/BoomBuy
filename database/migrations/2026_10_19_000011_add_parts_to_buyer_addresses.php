<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Address book entries in parts (Buyer Modules B2): recipient, region,
     * province, city, barangay and street — the joined "address" stays what
     * checkout and orders use.
     */
    public function up(): void
    {
        Schema::table('buyer_addresses', function (Blueprint $table) {
            $table->string('recipient_name', 100)->nullable()->after('label');
            $table->string('region', 80)->nullable()->after('address');
            $table->string('province', 80)->nullable()->after('region');
            $table->string('city_municipality', 80)->nullable()->after('province');
            $table->string('barangay', 100)->nullable()->after('city_municipality');
            $table->string('street', 180)->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('buyer_addresses', fn (Blueprint $table) => $table->dropColumn(['recipient_name', 'region', 'province', 'city_municipality', 'barangay', 'street']));
    }
};
