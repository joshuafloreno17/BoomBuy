<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Accounts only — sellers add their own products (with photos) from the Seller Panel.
        $this->call([
            TestAccountsSeeder::class,
            // One approved seller account per category (no products).
            SellerShopsSeeder::class,
            // Sorting Centers: every town in Laguna and Metro Manila, plus each seller's town.
            SortingCenterSeeder::class,
            // Each seller's town: its Sorting Center, a logistics staff account and a rider.
            LogisticsNetworkSeeder::class,
        ]);
    }
}