<?php

namespace App\Console\Commands;

use App\Support\PlaceCoordinates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Looks up, once, where every Sorting Center, branch, seller and buyer town
 * is (OpenStreetMap Nominatim) for the Track package map, so buyers never
 * wait on a lookup. Already-known towns are skipped. Nominatim allows one
 * request a second, so ~170 towns take about three minutes.
 *
 *   php artisan boombuy:locate-places
 */
class LocatePlaces extends Command
{
    protected $signature = 'boombuy:locate-places';

    protected $description = 'Find the map position of every Sorting Center, branch, seller and buyer town';

    public function handle(): int
    {
        $towns = DB::table('sorting_centers')->select('city_municipality as town', 'province')
            ->union(DB::table('users')->where('role', 'seller')->whereNotNull('city_municipality')->select('city_municipality as town', 'province'))
            ->union(DB::table('orders')->whereNotNull('shipping_city')->select('shipping_city as town', 'shipping_province as province'))
            ->get()
            ->filter(fn ($row) => trim((string) $row->town) !== '' && $row->province)
            ->reject(fn ($row) => DB::table('place_coordinates')->where('town', trim($row->town))->where('province', $row->province)->whereNotNull('lat')->exists())
            ->values();

        if ($towns->isEmpty()) {
            $this->info('Every town is already on the map.');

            return self::SUCCESS;
        }

        $this->info("Looking up {$towns->count()} towns (about " . ceil($towns->count() * 1.2 / 60) . ' min)…');
        $bar = $this->output->createProgressBar($towns->count());
        $missed = [];

        foreach ($towns as $row) {
            PlaceCoordinates::of($row->town, $row->province);

            if (!DB::table('place_coordinates')->where('town', trim($row->town))->where('province', $row->province)->whereNotNull('lat')->exists()) {
                $missed[] = "{$row->town}, {$row->province}";
            }

            $bar->advance();
            usleep(1_100_000); // Nominatim: at most one request a second.
        }

        $bar->finish();
        $this->newLine();

        if ($missed) {
            $this->warn(count($missed) . ' not found (they use the province capital): ' . implode('; ', $missed));
        }
        $this->info('Done.');

        return self::SUCCESS;
    }
}
