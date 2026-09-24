<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Pre-approved buyer/seller/rider accounts for quickly logging into
     * each role's pages without going through OTP + admin approval.
     * Safe to re-run — uses firstOrCreate so it won't duplicate accounts.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        // ------------------------------------------------------------
        // BUYER
        // ------------------------------------------------------------

        $buyer = User::firstOrCreate(
            ['email' => 'buyer@test.com'],
            [
                'name' => 'Juan Dela Cruz',
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'sex' => 'Male',
                'birthdate' => '2000-05-15',
                'age' => 25,
                'password' => $password,
                'role' => 'buyer',
                'phone' => '09171111111',
                'address' => '123 Test Street, Barangay Commonwealth, Quezon City, Metro Manila (NCR)',
                'province' => 'Metro Manila (NCR)',
                'city_municipality' => 'Quezon City',
                'barangay' => 'Barangay Commonwealth',
                'street_address' => '123 Test Street',
                'is_verified' => true,
            ]
        );

        DB::table('buyer_applications')->updateOrInsert(
            ['user_id' => $buyer->id],
            [
                'full_name' => $buyer->name,
                'phone' => $buyer->phone,
                'address' => $buyer->address,
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ------------------------------------------------------------
        // SELLER
        // ------------------------------------------------------------

        $seller = User::firstOrCreate(
            ['email' => 'seller@test.com'],
            [
                'name' => 'Maria Santos',
                'last_name' => 'Santos',
                'first_name' => 'Maria',
                'sex' => 'Female',
                'birthdate' => '1995-03-10',
                'age' => 30,
                'password' => $password,
                'role' => 'seller',
                'phone' => '09182222222',
                'address' => '456 Sample Ave, Lahug, Cebu City, Cebu',
                'province' => 'Cebu',
                'city_municipality' => 'Cebu City',
                'barangay' => 'Lahug',
                'street_address' => '456 Sample Ave',
                'is_verified' => true,
            ]
        );

        DB::table('seller_applications')->updateOrInsert(
            ['user_id' => $seller->id],
            [
                'full_name' => $seller->name,
                'business_name' => "Maria's Store",
                'phone' => $seller->phone,
                'address' => $seller->address,
                'business_category' => 'electronics',
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ------------------------------------------------------------
        // RIDER
        // ------------------------------------------------------------

        $rider = User::firstOrCreate(
            ['email' => 'rider@test.com'],
            [
                'name' => 'Pedro Reyes',
                'last_name' => 'Reyes',
                'first_name' => 'Pedro',
                'sex' => 'Male',
                'birthdate' => '1998-11-20',
                'age' => 27,
                'password' => $password,
                'role' => 'rider',
                'phone' => '09193333333',
                'address' => '789 Rider St, Real, Calamba City, Laguna',
                'province' => 'Laguna',
                'city_municipality' => 'Calamba City',
                'barangay' => 'Real',
                'street_address' => '789 Rider St',
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
                'vehicle_model' => 'Honda Click 125',
                'plate_number' => 'TEST-001',
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ------------------------------------------------------------
        // LOGISTICS / SORTING CENTER
        // ------------------------------------------------------------

        $logistics = User::firstOrCreate(
            ['email' => 'logistics@test.com'],
            [
                'name' => 'Ana Cruz',
                'last_name' => 'Cruz',
                'first_name' => 'Ana',
                'sex' => 'Female',
                'birthdate' => '1990-06-01',
                'age' => 36,
                'password' => $password,
                'role' => 'logistics',
                'phone' => '09221111111',
                'address' => '1 Hub Street, Real, Calamba City, Laguna',
                'province' => 'Laguna',
                'city_municipality' => 'Calamba City',
                'barangay' => 'Real',
                'street_address' => '1 Hub Street',
                'is_verified' => true,
            ]
        );

        DB::table('logistics_applications')->updateOrInsert(
            ['user_id' => $logistics->id],
            [
                'full_name' => $logistics->name,
                'business_name' => "Ana's Logistics Hub",
                'phone' => $logistics->phone,
                'address' => $logistics->address,
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Assign the test rider to the seller's area so the Sorting
        // Center's "area match" suggestion has something to demo out of
        // the box.
        \App\Models\RiderArea::firstOrCreate([
            'rider_id' => $rider->id,
            'province' => 'Cebu',
            'city_municipality' => 'Cebu City',
        ]);

        $this->command?->info('Test accounts ready (password: password123):');
        $this->command?->info('  Buyer:      buyer@test.com');
        $this->command?->info('  Seller:     seller@test.com');
        $this->command?->info('  Rider:      rider@test.com');
        $this->command?->info('  Logistics:  logistics@test.com');
    }
}
