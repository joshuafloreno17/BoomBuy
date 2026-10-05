<?php

namespace Database\Seeders;

use App\Models\SortingCenter;
use App\Support\PhLocations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A Sorting Center in every town of Laguna and every city of Metro Manila,
 * so routes like Cubao (Quezon City) → Santa Cruz, Laguna work out of the box.
 * The admin adds more under Admin → Sorting Centers.
 */
class SortingCenterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Laguna', PhLocations::NCR] as $province) {
            foreach (PhLocations::all()[$province] ?? [] as $city) {
                $provinceLabel = $province === PhLocations::NCR ? 'Metro Manila' : $province;

                SortingCenter::firstOrCreate(
                    ['province' => $province, 'city_municipality' => $city],
                    [
                        'name' => $city . ' Sorting Center',
                        'address' => 'Poblacion, ' . $city . ', ' . $provinceLabel,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Every seller already on the platform gets a center in their own town to drop off at.
        $sellerTowns = DB::table('users')
            ->where('role', 'seller')
            ->whereNotNull('province')
            ->whereNotNull('city_municipality')
            ->select('province', 'city_municipality')
            ->distinct()
            ->get();

        foreach ($sellerTowns as $town) {
            // "Makati City" is already covered by the "Makati" center.
            if (SortingCenter::where('province', $town->province)->get()->contains(fn ($c) => PhLocations::sameTown($c->city_municipality, $town->city_municipality))) {
                continue;
            }

            SortingCenter::firstOrCreate(
                ['province' => $town->province, 'city_municipality' => $town->city_municipality],
                [
                    'name' => $town->city_municipality . ' Sorting Center',
                    'address' => 'Poblacion, ' . $town->city_municipality . ', ' . $town->province,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Sorting Centers: ' . SortingCenter::count());
    }
}
