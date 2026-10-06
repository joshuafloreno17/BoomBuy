<?php

namespace Database\Seeders;

use App\Models\SortingCenter;
use App\Support\RegionalCenters;
use Illuminate\Database\Seeder;

/**
 * One BoomBuy Sorting Center per region — 17, covering the whole country
 * (e.g. "BoomBuy Sorting Center – CALABARZON" in Calamba, serving Laguna,
 * Cavite, Batangas, Rizal and Quezon). Safe to re-run; any old per-town
 * centers are folded into their region's.
 */
class SortingCenterSeeder extends Seeder
{
    public function run(): void
    {
        RegionalCenters::foldTownCenters();

        $this->command?->info('Sorting Centers: ' . SortingCenter::count() . ' (one per region)');
    }
}
