<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real map positions for the Track package page.
 *
 * place_coordinates: where a town is (Sorting Centers, branches, sellers,
 * buyers), looked up once from OpenStreetMap (Nominatim). lat null = the
 * lookup failed; it is tried again later.
 *
 * parcel_routes is only a cache of OSRM roads: it is rebuilt keyed by the
 * whole trip (seller's town → centers → buyer's town) instead of two provinces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_coordinates', function (Blueprint $table) {
            $table->id();
            $table->string('town', 120);
            $table->string('province', 100);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->unique(['town', 'province']);
        });

        Schema::dropIfExists('parcel_routes');
        Schema::create('parcel_routes', function (Blueprint $table) {
            $table->id();
            $table->string('waypoints', 191)->unique();
            $table->json('points')->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->timestamp('fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_coordinates');

        Schema::dropIfExists('parcel_routes');
        Schema::create('parcel_routes', function (Blueprint $table) {
            $table->id();
            $table->string('from_province', 100);
            $table->string('to_province', 100);
            $table->json('points')->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->unique(['from_province', 'to_province']);
        });
    }
};
