<?php

namespace Database\Seeders;

use App\Models\SortingCenter;
use App\Support\ProvincialCenters;
use Illuminate\Database\Seeder;

/**
 * One BoomBuy Sorting Center per province — 83, covering the whole country
 * (e.g. "BoomBuy Sorting Center – Cavite" in Imus, "– Laguna" in Santa Cruz).
 * Safe to re-run.
 */
class SortingCenterSeeder extends Seeder
{
    public function run(): void
    {
        ProvincialCenters::ensure();

        $this->command?->info('Sorting Centers: ' . SortingCenter::count() . ' (one per province)');
    }
}
