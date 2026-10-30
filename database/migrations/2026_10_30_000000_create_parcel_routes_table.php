<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The road between two provinces' Sorting Centers, fetched once from
 * OpenStreetMap routing (OSRM) for the buyer's Track package map.
 * points null = the lookup failed; it is tried again later.
 */
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('parcel_routes');
    }
};
