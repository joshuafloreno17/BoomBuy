<?php

namespace Database\Seeders;

use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Every seller's town gets a working logistics setup: a Sorting Center to drop
 * parcels at, one logistics staff account assigned to it, and one approved
 * rider whose area is that town (for final-mile deliveries there).
 *
 *   hub.<town>@boombuy.test    logistics staff of <Town> Sorting Center
 *   rider.<town>@boombuy.test  rider covering <Town>
 *
 * Run after SellerShopsSeeder. Safe to re-run. Password: password123
 */
class LogisticsNetworkSeeder extends Seeder
{
    private const STAFF_NAMES = [
        ['Jessa', 'Domingo', 'Female'], ['Mark', 'Villareal', 'Male'], ['Kristine', 'Abad', 'Female'],
        ['Rodel', 'Pascual', 'Male'], ['Leah', 'Manalo', 'Female'], ['Jerome', 'Castillo', 'Male'],
        ['Precious', 'Ocampo', 'Female'], ['Ryan', 'Salazar', 'Male'], ['Mae', 'Fernandez', 'Female'],
        ['Allan', 'Rivera', 'Male'], ['Joy', 'Valdez', 'Female'], ['Noel', 'Gutierrez', 'Male'],
        ['Shiela', 'Mendoza', 'Female'], ['Ronald', 'Sison', 'Male'], ['Camille', 'Torres', 'Female'],
        ['Edwin', 'Lopez', 'Male'], ['Hazel', 'Aguilar', 'Female'], ['Vincent', 'Morales', 'Male'],
    ];

    private const RIDER_NAMES = [
        ['Jun', 'Dela Cruz'], ['Ramil', 'Bautista'], ['Joel', 'Santos'], ['Arvin', 'Garcia'],
        ['Rey', 'Mercado'], ['Dante', 'Robles'], ['Elmer', 'Navarro'], ['Jayson', 'Ramos'],
        ['Romeo', 'Flores'], ['Christian', 'Tolentino'], ['Ariel', 'Cabrera'], ['Rommel', 'De Leon'],
        ['Benjie', 'Villanueva'], ['Gilbert', 'Soriano'], ['Ronnie', 'Esguerra'], ['Wilson', 'Padilla'],
        ['Nestor', 'Lacson'], ['Marlon', 'Javier'],
    ];

    private const MOTORCYCLES = ['Honda Click 125', 'Yamaha NMAX 155', 'Suzuki Raider 150', 'Honda TMX 155', 'Yamaha Mio i 125', 'Kawasaki Barako 175'];

    public function run(): void
    {
        $password = Hash::make('password123');

        $towns = DB::table('users')
            ->where('role', 'seller')
            ->whereNotNull('province')
            ->whereNotNull('city_municipality')
            ->select('province', 'city_municipality')
            ->distinct()
            ->orderBy('province')
            ->get();

        $i = 0;

        foreach ($towns as $town) {
            $center = $this->centerFor($town->province, $town->city_municipality);
            // "Iloilo City" → iloilo, but "Quezon City" stays quezon-city (Quezon is also a province).
            $slug = Str::slug($center->city_municipality === 'Quezon City'
                ? $center->city_municipality
                : preg_replace('/ City$/', '', $center->city_municipality));
            $place = $center->city_municipality . ', ' . str_replace(' (NCR)', '', $center->province);

            // Logistics staff at this center.
            [$first, $last, $sex] = self::STAFF_NAMES[$i % count(self::STAFF_NAMES)];
            $staff = User::firstOrCreate(
                ['email' => 'hub.' . $slug . '@boombuy.test'],
                [
                    'name' => "$first $last",
                    'first_name' => $first,
                    'last_name' => $last,
                    'sex' => $sex,
                    'birthdate' => '1990-01-15',
                    'age' => 36,
                    'password' => $password,
                    'role' => 'logistics',
                    'phone' => sprintf('0916%07d', 2000001 + $i),
                    'is_verified' => true,
                ]
            );

            DB::table('users')->where('id', $staff->id)->update([
                'sorting_center_id' => $center->id,
                'address' => $center->address ?: 'Poblacion, ' . $place,
                'province' => $center->province,
                'city_municipality' => $center->city_municipality,
                'barangay' => 'Poblacion',
                'street_address' => $center->name,
            ]);

            DB::table('logistics_applications')->updateOrInsert(
                ['user_id' => $staff->id],
                [
                    'full_name' => $staff->name,
                    'business_name' => $center->name,
                    'phone' => $staff->phone,
                    'address' => $center->address ?: 'Poblacion, ' . $place,
                    'status' => 'Approved',
                    'reviewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Rider who delivers in this town.
            [$riderFirst, $riderLast] = self::RIDER_NAMES[$i % count(self::RIDER_NAMES)];
            $riderAddress = ($i + 10) . ' Rizal St, Poblacion, ' . $place;
            $rider = User::firstOrCreate(
                ['email' => 'rider.' . $slug . '@boombuy.test'],
                [
                    'name' => "$riderFirst $riderLast",
                    'first_name' => $riderFirst,
                    'last_name' => $riderLast,
                    'sex' => 'Male',
                    'birthdate' => '1995-05-20',
                    'age' => 31,
                    'password' => $password,
                    'role' => 'rider',
                    'phone' => sprintf('0917%07d', 3000001 + $i),
                    'address' => $riderAddress,
                    'province' => $center->province,
                    'city_municipality' => $center->city_municipality,
                    'barangay' => 'Poblacion',
                    'street_address' => ($i + 10) . ' Rizal St',
                    'is_verified' => true,
                ]
            );

            DB::table('rider_applications')->updateOrInsert(
                ['user_id' => $rider->id],
                [
                    'full_name' => $rider->name,
                    'phone' => $rider->phone,
                    'address' => $rider->address,
                    'vehicle_type' => 'Motorcycle',
                    'vehicle_model' => self::MOTORCYCLES[$i % count(self::MOTORCYCLES)],
                    'plate_number' => sprintf('BB-%04d', 101 + $i),
                    'status' => 'Approved',
                    'reviewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            RiderArea::firstOrCreate([
                'rider_id' => $rider->id,
                'province' => $center->province,
                'city_municipality' => $center->city_municipality,
            ]);

            $this->command?->line(sprintf('  %-34s staff %-32s rider %s', $center->name, $staff->email, $rider->email));
            $i++;
        }

        $this->command?->info("Logistics network: {$i} towns with a Sorting Center, staff and rider.");
    }

    /** The center in this town — opened if the town doesn't have one yet. */
    private function centerFor(string $province, string $city): SortingCenter
    {
        $center = ParcelRoute::centerFor($province, $city);

        if ($center && PhLocations::sameTown($center->city_municipality, $city)) {
            return $center;
        }

        $label = str_replace(' (NCR)', '', $province);

        return SortingCenter::firstOrCreate(
            ['province' => $province, 'city_municipality' => $city],
            ['name' => $city . ' Sorting Center', 'address' => 'Poblacion, ' . $city . ', ' . $label, 'is_active' => true]
        );
    }
}
