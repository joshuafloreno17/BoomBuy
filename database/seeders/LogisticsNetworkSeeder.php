<?php

namespace Database\Seeders;

use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\RegionalCenters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A working logistics setup everywhere:
 *
 *   - every regional BoomBuy Sorting Center has a logistics staff account
 *     (hub.<region>@boombuy.test) — unless it already has staff;
 *   - every seller's town has an approved rider whose area is that town
 *     (rider.<town>@boombuy.test), for final-mile deliveries there.
 *
 * Run after SellerShopsSeeder and SortingCenterSeeder. Safe to re-run.
 * Password: password123
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

        $staffCount = $this->staffEveryCenter($password);
        $riderCount = $this->riderEverySellerTown($password);

        $this->command?->info("Logistics network: staff added at {$staffCount} center(s), riders in {$riderCount} seller town(s).");
    }

    /** Each regional center without staff gets one account. */
    private function staffEveryCenter(string $password): int
    {
        $added = 0;

        foreach (RegionalCenters::ensure() as $region => $center) {
            if ($center->staff()->exists()) {
                continue;
            }

            [$first, $last, $sex] = self::STAFF_NAMES[$added % count(self::STAFF_NAMES)];
            $staff = User::firstOrCreate(
                ['email' => 'hub.' . $region . '@boombuy.test'],
                [
                    'name' => "$first $last",
                    'first_name' => $first,
                    'last_name' => $last,
                    'sex' => $sex,
                    'birthdate' => '1990-01-15',
                    'age' => 36,
                    'password' => $password,
                    'role' => 'logistics',
                    'phone' => sprintf('0916%07d', 2100001 + crc32($region) % 899999),
                    'is_verified' => true,
                ]
            );

            DB::table('users')->where('id', $staff->id)->update([
                'sorting_center_id' => $center->id,
                'address' => $center->address,
                'province' => $center->province,
                'city_municipality' => $center->city_municipality,
                'street_address' => $center->name,
            ]);

            DB::table('logistics_applications')->updateOrInsert(
                ['user_id' => $staff->id],
                [
                    'full_name' => $staff->name,
                    'business_name' => $center->name,
                    'phone' => $staff->phone,
                    'address' => $center->address,
                    'status' => 'Approved',
                    'reviewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command?->line(sprintf('  %-48s staff %s', $center->name, $staff->email));
            $added++;
        }

        return $added;
    }

    /** Each seller's town gets a rider covering it. */
    private function riderEverySellerTown(string $password): int
    {
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
            // "Iloilo City" → iloilo, but "Quezon City" stays quezon-city (Quezon is also a province).
            $slug = Str::slug($town->city_municipality === 'Quezon City'
                ? $town->city_municipality
                : preg_replace('/ City$/', '', $town->city_municipality));
            $place = $town->city_municipality . ', ' . str_replace(' (NCR)', '', $town->province);

            [$riderFirst, $riderLast] = self::RIDER_NAMES[$i % count(self::RIDER_NAMES)];
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
                    'address' => ($i + 10) . ' Rizal St, Poblacion, ' . $place,
                    'province' => $town->province,
                    'city_municipality' => $town->city_municipality,
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
                'province' => $town->province,
                'city_municipality' => $town->city_municipality,
            ]);

            $i++;
        }

        return $i;
    }
}
