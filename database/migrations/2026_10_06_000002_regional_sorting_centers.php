<?php

use App\Support\RegionalCenters;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sorting Centers go from one per town to one per region ("BoomBuy Sorting
 * Center – CALABARZON"). Where per-town centers already exist, they're folded
 * into their region's center (staff, orders and timeline steps move over).
 * A fresh database gets the 17 regional centers from SortingCenterSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->string('region', 40)->nullable()->after('name');
            $table->index('region');
        });

        if (DB::table('sorting_centers')->exists()) {
            RegionalCenters::foldTownCenters();
        }
    }

    public function down(): void
    {
        Schema::table('sorting_centers', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropColumn('region');
        });
    }
};
