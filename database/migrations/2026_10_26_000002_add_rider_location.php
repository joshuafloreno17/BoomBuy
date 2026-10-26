<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rider location (mobile sheet M13): the last position the rider app sent
 * while Online, and where each parcel was handed over.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 7)->nullable()->after('online_at');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->dateTime('location_at')->nullable()->after('location_lng');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivered_lat', 10, 7)->nullable()->after('delivered_at');
            $table->decimal('delivered_lng', 10, 7)->nullable()->after('delivered_lat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['location_lat', 'location_lng', 'location_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_lat', 'delivered_lng']);
        });
    }
};
