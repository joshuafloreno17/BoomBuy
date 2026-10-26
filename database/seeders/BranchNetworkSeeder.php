<?php

namespace Database\Seeders;

use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\PhLocations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One branch per provincial Sorting Center, in the province's next big town,
 * so the full Center → Branch → rider flow can be tried anywhere:
 *
 *   - the branch itself (skipped when the center already has a branch there);
 *   - its staff account (branch.<town>@boombuy.test);
 *   - a rider whose area is that town (rider.<town>@boombuy.test), unless one
 *     already covers it.
 *
 * Run after LogisticsNetworkSeeder. Safe to re-run. Password: password123
 */
class BranchNetworkSeeder extends Seeder
{
    /** Province => the town its branch opens in (never the center's own town). */
    private const TOWNS = [
        'Abra' => 'Bucay', 'Agusan del Norte' => 'Cabadbaran City', 'Agusan del Sur' => 'Bayugan City',
        'Aklan' => 'Malay', 'Albay' => 'Tabaco City', 'Antique' => 'Sibalom', 'Apayao' => 'Luna',
        'Aurora' => 'Maria Aurora', 'Basilan' => 'Lamitan City', 'Bataan' => 'Mariveles', 'Batanes' => 'Ivana',
        'Batangas' => 'Lipa City', 'Benguet' => 'La Trinidad', 'Biliran' => 'Caibiran', 'Bohol' => 'Talibon',
        'Bukidnon' => 'Valencia City', 'Bulacan' => 'San Jose del Monte City', 'Cagayan' => 'Aparri',
        'Camarines Norte' => 'Labo', 'Camarines Sur' => 'Naga City', 'Camiguin' => 'Catarman',
        'Capiz' => 'Pontevedra', 'Catanduanes' => 'San Andres', 'Cavite' => 'Dasmariñas City',
        'Cebu' => 'Mandaue City', 'Cotabato' => 'Midsayap', 'Davao de Oro' => 'Monkayo',
        'Davao del Norte' => 'Panabo City', 'Davao del Sur' => 'Digos City', 'Davao Occidental' => 'Santa Maria',
        'Davao Oriental' => 'Baganga', 'Dinagat Islands' => 'Basilisa', 'Eastern Samar' => 'Guiuan',
        'Guimaras' => 'Buenavista', 'Ifugao' => 'Banaue', 'Ilocos Norte' => 'Batac City',
        'Ilocos Sur' => 'Candon City', 'Iloilo' => 'Passi City', 'Isabela' => 'Santiago City', 'Kalinga' => 'Rizal',
        'La Union' => 'Agoo', 'Laguna' => 'Santa Cruz', 'Lanao del Norte' => 'Iligan City',
        'Lanao del Sur' => 'Malabang', 'Leyte' => 'Ormoc City', 'Maguindanao del Norte' => 'Parang',
        'Maguindanao del Sur' => 'Shariff Aguak', 'Marinduque' => 'Santa Cruz', 'Masbate' => 'Aroroy',
        'Metro Manila (NCR)' => 'Makati', 'Misamis Occidental' => 'Ozamiz City',
        'Misamis Oriental' => 'Gingoog City', 'Mountain Province' => 'Sagada', 'Negros Occidental' => 'Silay City',
        'Negros Oriental' => 'Bais City', 'Northern Samar' => 'Laoang', 'Nueva Ecija' => 'Cabanatuan City',
        'Nueva Vizcaya' => 'Solano', 'Occidental Mindoro' => 'San Jose', 'Oriental Mindoro' => 'Pinamalayan',
        'Palawan' => 'Coron', 'Pampanga' => 'Angeles City', 'Pangasinan' => 'Dagupan City',
        'Quezon' => 'Tayabas City', 'Quirino' => 'Diffun', 'Rizal' => 'Cainta', 'Romblon' => 'Odiongan',
        'Samar' => 'Calbayog City', 'Sarangani' => 'Glan', 'Siquijor' => 'Larena', 'Sorsogon' => 'Bulan',
        'South Cotabato' => 'General Santos City', 'Southern Leyte' => 'Sogod',
        'Sultan Kudarat' => 'Tacurong City', 'Sulu' => 'Patikul', 'Surigao del Norte' => 'Dapa',
        'Surigao del Sur' => 'Bislig City', 'Tarlac' => 'Concepcion', 'Tawi-Tawi' => 'Panglima Sugala',
        'Zambales' => 'Olongapo City', 'Zamboanga del Norte' => 'Dapitan City',
        'Zamboanga del Sur' => 'Pagadian City', 'Zamboanga Sibugay' => 'Kabasalan',
    ];

    private const STAFF_NAMES = [
        ['Aileen', 'Ramos', 'Female'], ['Bryan', 'Castro', 'Male'], ['Cherry', 'Lim', 'Female'],
        ['Dennis', 'Navarro', 'Male'], ['Elaine', 'Bautista', 'Female'], ['Francis', 'Aquino', 'Male'],
        ['Gina', 'Pineda', 'Female'], ['Harold', 'Santiago', 'Male'], ['Irene', 'Delos Reyes', 'Female'],
        ['Jomar', 'Cortez', 'Male'], ['Karen', 'Villanueva', 'Female'], ['Leo', 'Marquez', 'Male'],
    ];

    private const RIDER_NAMES = [
        ['Marlon', 'Javier'], ['Efren', 'Dizon'], ['Rodrigo', 'Tan'], ['Alvin', 'Reyes'], ['Bong', 'Salvador'],
        ['Carlo', 'Medina'], ['Danilo', 'Ocampo'], ['Erwin', 'Gomez'], ['Ferdie', 'Lagman'], ['Glenn', 'Paredes'],
    ];

    private const MOTORCYCLES = ['Honda Click 125', 'Yamaha NMAX 155', 'Suzuki Raider 150', 'Honda TMX 155', 'Yamaha Mio i 125', 'Kawasaki Barako 175'];

    public function run(): void
    {
        $password = Hash::make('password123');
        $branches = $staffAdded = $ridersAdded = 0;

        foreach (SortingCenter::centers()->orderBy('province')->get() as $i => $center) {
            $town = self::TOWNS[$center->province] ?? null;

            if (!$town || !PhLocations::exists($center->province, $town)) {
                $this->command?->warn("  no branch town for {$center->province}");
                continue;
            }

            $branch = SortingCenter::where('parent_id', $center->id)->get()
                ->first(fn ($b) => PhLocations::sameTown($b->city_municipality, $town));

            if (!$branch) {
                $branch = SortingCenter::create([
                    'parent_id' => $center->id,
                    'name' => SortingCenter::branchNameFor($town),
                    'region' => $center->region,
                    'province' => $center->province,
                    'city_municipality' => $town,
                    'address' => 'Poblacion, ' . $town . ', ' . PhLocations::provinceLabel($center->province),
                    'is_active' => true,
                ]);
                $branches++;
            }

            if (!DB::table('users')->where('role', 'logistics')->where('sorting_center_id', $branch->id)->exists()) {
                $this->staff($branch, $i, $password);
                $staffAdded++;
            }

            if ($this->rider($branch, $i, $password)) {
                $ridersAdded++;
            }
        }

        $this->command?->info("Branches: {$branches} opened, {$staffAdded} staff and {$ridersAdded} rider(s) added.");
    }

    private function staff(SortingCenter $branch, int $i, string $password): void
    {
        [$first, $last, $sex] = self::STAFF_NAMES[$i % count(self::STAFF_NAMES)];

        $staff = User::create([
            'email' => $this->freeEmail('branch', $branch),
            'name' => "$first $last",
            'first_name' => $first,
            'last_name' => $last,
            'sex' => $sex,
            'birthdate' => '1992-04-10',
            'age' => 34,
            'password' => $password,
            'role' => 'logistics',
            'phone' => sprintf('0915%07d', 4000001 + $i),
            'is_verified' => true,
        ]);

        DB::table('users')->where('id', $staff->id)->update([
            'status' => 'Active',
            'sorting_center_id' => $branch->id,
            'address' => $branch->address,
            'province' => $branch->province,
            'city_municipality' => $branch->city_municipality,
            'street_address' => $branch->name,
        ]);

        DB::table('logistics_applications')->updateOrInsert(
            ['user_id' => $staff->id],
            [
                'full_name' => $staff->name,
                'business_name' => $branch->name,
                'phone' => $staff->phone,
                'address' => $branch->address,
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command?->line(sprintf('  %-48s staff %s', $branch->name, $staff->email));
    }

    /** A rider covering the branch's town, unless one already does. */
    private function rider(SortingCenter $branch, int $i, string $password): bool
    {
        $covered = RiderArea::where('province', $branch->province)->get()
            ->contains(fn ($a) => PhLocations::sameTown($a->city_municipality, $branch->city_municipality));

        if ($covered) {
            return false;
        }

        [$first, $last] = self::RIDER_NAMES[$i % count(self::RIDER_NAMES)];
        $place = $branch->city_municipality . ', ' . PhLocations::provinceLabel($branch->province);

        $rider = User::create([
            'email' => $this->freeEmail('rider', $branch),
            'name' => "$first $last",
            'first_name' => $first,
            'last_name' => $last,
            'sex' => 'Male',
            'birthdate' => '1996-08-12',
            'age' => 30,
            'password' => $password,
            'role' => 'rider',
            'phone' => sprintf('0918%07d', 5000001 + $i),
            'address' => ($i + 1) . ' Mabini St, Poblacion, ' . $place,
            'province' => $branch->province,
            'city_municipality' => $branch->city_municipality,
            'barangay' => 'Poblacion',
            'street_address' => ($i + 1) . ' Mabini St',
            'is_verified' => true,
        ]);

        DB::table('rider_applications')->updateOrInsert(
            ['user_id' => $rider->id],
            [
                'full_name' => $rider->name,
                'phone' => $rider->phone,
                'address' => $rider->address,
                'vehicle_type' => 'Motorcycle',
                'vehicle_model' => self::MOTORCYCLES[$i % count(self::MOTORCYCLES)],
                'plate_number' => sprintf('BR-%04d', 101 + $i),
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        RiderArea::firstOrCreate([
            'rider_id' => $rider->id,
            'province' => $branch->province,
            'city_municipality' => $branch->city_municipality,
        ]);

        return true;
    }

    /** prefix.<town>@boombuy.test, or prefix.<town>-<province>@… when another town took it. */
    private function freeEmail(string $prefix, SortingCenter $branch): string
    {
        $town = $branch->city_municipality === 'Quezon City' ? 'Quezon City' : preg_replace('/ City$/', '', $branch->city_municipality);
        $email = $prefix . '.' . Str::slug($town) . '@boombuy.test';

        if (DB::table('users')->where('email', $email)->exists()) {
            $email = $prefix . '.' . Str::slug($town . ' ' . PhLocations::provinceLabel($branch->province)) . '@boombuy.test';
        }

        return $email;
    }
}
