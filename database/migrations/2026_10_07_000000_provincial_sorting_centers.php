<?php

use App\Support\ProvincialCenters;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sorting Centers go from one per region to one per province ("BoomBuy
 * Sorting Center – Cavite"). Each regional center becomes the center of the
 * province it stands in, the other provinces get theirs, and orders not yet
 * out for delivery are re-routed. A fresh database gets the 83 centers from
 * SortingCenterSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('sorting_centers')->exists()) {
            ProvincialCenters::convert();
        }
    }

    public function down(): void
    {
        // Not reversible.
    }
};
