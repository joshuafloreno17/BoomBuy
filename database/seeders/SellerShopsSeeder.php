<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Support\Categories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One approved seller account per category — like the real flow, where a
 * seller picks one category when registering and may only sell that one
 * (Admin → Compliance flags anything else as a category mismatch).
 *
 * No products are seeded — each seller adds their own, with photos, from the
 * Seller Panel. As a safety net, a product with no real seller (or sitting in
 * one of these shops under the wrong category) is moved to the shop for its
 * category; products of other sellers (e.g. Maria's Store) are left alone.
 *
 * Safe to re-run: accounts use firstOrCreate, applications are upserted, and
 * a second run moves nothing. Password for every shop: password123
 */
class SellerShopsSeeder extends Seeder
{
    private const SHOPS = [
        'electronics' => [
            'email' => 'voltique@boombuy.test', 'shop' => 'Voltique Electronics',
            'first' => 'Carlo', 'last' => 'Mendoza', 'sex' => 'Male', 'birthdate' => '1992-02-14',
            'street' => '21 Jupiter St', 'barangay' => 'Bel-Air', 'city' => 'Makati City', 'province' => 'Metro Manila (NCR)',
        ],
        'womens-fashion' => [
            'email' => 'maisonbelle@boombuy.test', 'shop' => 'Maison Belle',
            'first' => 'Andrea', 'last' => 'Villanueva', 'sex' => 'Female', 'birthdate' => '1996-07-02',
            'street' => '8 Tomas Morato Ave', 'barangay' => 'South Triangle', 'city' => 'Quezon City', 'province' => 'Metro Manila (NCR)',
        ],
        'mens-fashion' => [
            'email' => 'gentryandco@boombuy.test', 'shop' => 'Gentry & Co. Menswear',
            'first' => 'Miguel', 'last' => 'Ramos', 'sex' => 'Male', 'birthdate' => '1994-10-21',
            'street' => '45 Shaw Blvd', 'barangay' => 'Kapitolyo', 'city' => 'Pasig City', 'province' => 'Metro Manila (NCR)',
        ],
        'kids-baby' => [
            'email' => 'littlehaven@boombuy.test', 'shop' => 'Little Haven Baby & Kids',
            'first' => 'Liza', 'last' => 'Bautista', 'sex' => 'Female', 'birthdate' => '1990-04-09',
            'street' => '12 Quimpo Blvd', 'barangay' => 'Matina', 'city' => 'Davao City', 'province' => 'Davao del Sur',
        ],
        'home-living' => [
            'email' => 'hearthstone@boombuy.test', 'shop' => 'Hearthstone Home & Living',
            'first' => 'Ramon', 'last' => 'Aquino', 'sex' => 'Male', 'birthdate' => '1988-12-01',
            'street' => '77 A.S. Fortuna St', 'barangay' => 'Banilad', 'city' => 'Mandaue City', 'province' => 'Cebu',
        ],
        'sports-outdoors' => [
            'email' => 'summitactive@boombuy.test', 'shop' => 'Summit Active Gear',
            'first' => 'Paolo', 'last' => 'Cruz', 'sex' => 'Male', 'birthdate' => '1997-06-18',
            'street' => '3 Session Rd', 'barangay' => 'Session Road Area', 'city' => 'Baguio City', 'province' => 'Benguet',
        ],
        'beauty-personal-care' => [
            'email' => 'lumierebeauty@boombuy.test', 'shop' => 'Lumière Beauty',
            'first' => 'Bea', 'last' => 'Santiago', 'sex' => 'Female', 'birthdate' => '1999-01-27',
            'street' => '15 Diversion Rd', 'barangay' => 'Mandurriao', 'city' => 'Iloilo City', 'province' => 'Iloilo',
        ],
        'food-beverages' => [
            'email' => 'harvesttable@boombuy.test', 'shop' => 'Harvest Table Pantry',
            'first' => 'Nina', 'last' => 'Gonzales', 'sex' => 'Female', 'birthdate' => '1993-09-05',
            'street' => '30 Lacson St', 'barangay' => 'Mandalagan', 'city' => 'Bacolod City', 'province' => 'Negros Occidental',
        ],
        'automotive' => [
            'email' => 'torqueauto@boombuy.test', 'shop' => 'Torque Auto Supply',
            'first' => 'Jomar', 'last' => 'Dizon', 'sex' => 'Male', 'birthdate' => '1991-03-30',
            'street' => '9 P. Burgos St', 'barangay' => 'Kumintang Ibaba', 'city' => 'Batangas City', 'province' => 'Batangas',
        ],
        'office-school' => [
            'email' => 'inkwell@boombuy.test', 'shop' => 'Inkwell Office & School',
            'first' => 'Grace', 'last' => 'Tan', 'sex' => 'Female', 'birthdate' => '1995-08-12',
            'street' => '101 España Blvd', 'barangay' => 'Sampaloc', 'city' => 'Manila', 'province' => 'Metro Manila (NCR)',
        ],
        'pet-supplies' => [
            'email' => 'pawsandwhiskers@boombuy.test', 'shop' => 'Paws & Whiskers Pet Co.',
            'first' => 'Ivy', 'last' => 'Navarro', 'sex' => 'Female', 'birthdate' => '1998-05-23',
            'street' => '6 Sumulong Hwy', 'barangay' => 'Dela Paz', 'city' => 'Antipolo City', 'province' => 'Rizal',
        ],
        'toys-games-hobbies' => [
            'email' => 'wonderbox@boombuy.test', 'shop' => 'Wonderbox Toys & Hobbies',
            'first' => 'Kevin', 'last' => 'Lim', 'sex' => 'Male', 'birthdate' => '1993-11-03',
            'street' => '18 Corrales Ave', 'barangay' => 'Carmen', 'city' => 'Cagayan de Oro City', 'province' => 'Misamis Oriental',
        ],
        'jewelry-accessories' => [
            'email' => 'aurelia@boombuy.test', 'shop' => 'Aurelia Jewelry & Accessories',
            'first' => 'Carmina', 'last' => 'Reyes', 'sex' => 'Female', 'birthdate' => '1997-02-19',
            'street' => '5th Ave cor. 26th St', 'barangay' => 'Bonifacio Global City', 'city' => 'Taguig City', 'province' => 'Metro Manila (NCR)',
        ],
        'shoes' => [
            'email' => 'stridefootwear@boombuy.test', 'shop' => 'Stride Footwear Co.',
            'first' => 'Dennis', 'last' => 'Flores', 'sex' => 'Male', 'birthdate' => '1989-07-07',
            'street' => '40 J.P. Rizal St', 'barangay' => 'Concepcion Uno', 'city' => 'Marikina City', 'province' => 'Metro Manila (NCR)',
        ],
        'tools-home-improvement' => [
            'email' => 'ironclad@boombuy.test', 'shop' => 'Ironclad Hardware & Tools',
            'first' => 'Arnel', 'last' => 'Garcia', 'sex' => 'Male', 'birthdate' => '1987-04-15',
            'street' => '22 MacArthur Hwy', 'barangay' => 'Balibago', 'city' => 'Angeles City', 'province' => 'Pampanga',
        ],
        'garden-outdoor' => [
            'email' => 'evergreen@boombuy.test', 'shop' => 'Evergreen Garden Supply',
            'first' => 'Rosa', 'last' => 'Mercado', 'sex' => 'Female', 'birthdate' => '1991-10-10',
            'street' => '14 Aguinaldo Hwy', 'barangay' => 'Maharlika East', 'city' => 'Tagaytay City', 'province' => 'Cavite',
        ],
    ];

    public function run(): void
    {
        $password = Hash::make('password123');
        $sellerFor = []; // category slug => seller id
        $i = 0;

        foreach (self::SHOPS as $category => $shop) {
            $address = "{$shop['street']}, {$shop['barangay']}, {$shop['city']}, {$shop['province']}";
            $phone = sprintf('0915%07d', 1000001 + $i++);

            $seller = User::firstOrCreate(
                ['email' => $shop['email']],
                [
                    'name' => "{$shop['first']} {$shop['last']}",
                    'first_name' => $shop['first'],
                    'last_name' => $shop['last'],
                    'sex' => $shop['sex'],
                    'birthdate' => $shop['birthdate'],
                    'age' => \Illuminate\Support\Carbon::parse($shop['birthdate'])->age,
                    'password' => $password,
                    'role' => 'seller',
                    'phone' => $phone,
                    'address' => $address,
                    'province' => $shop['province'],
                    'city_municipality' => $shop['city'],
                    'barangay' => $shop['barangay'],
                    'street_address' => $shop['street'],
                    'is_verified' => true,
                ]
            );

            // The one category this seller registered for.
            DB::table('seller_applications')->updateOrInsert(
                ['user_id' => $seller->id],
                [
                    'full_name' => $seller->name,
                    'business_name' => $shop['shop'],
                    'phone' => $seller->phone ?? $phone,
                    'address' => $seller->address ?? $address,
                    'business_category' => $category,
                    'status' => 'Approved',
                    'reviewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $sellerFor[$category] = $seller->id;
        }

        // Products to (re)home: no real seller yet, or in one of these shops
        // under a category that shop didn't register for.
        $realSellerIds = User::where('role', 'seller')->pluck('id')->all();
        $shopIds = array_values($sellerFor);
        $moved = 0;

        Product::query()
            ->where(fn ($q) => $q->whereNull('seller_id')
                ->orWhereNotIn('seller_id', $realSellerIds)
                ->orWhereIn('seller_id', $shopIds))
            ->get(['id', 'category', 'seller_id'])
            ->each(function (Product $product) use ($sellerFor, &$moved) {
                $slug = Categories::slug($product->category);
                $target = $slug ? ($sellerFor[$slug] ?? null) : null;

                if ($target && (int) $product->seller_id !== $target) {
                    Product::whereKey($product->id)->update(['seller_id' => $target]);
                    $moved++;
                }
            });

        $this->command?->info(count(self::SHOPS) . " shops ready (one per category); {$moved} products moved.");
    }
}
