<?php

namespace Tests\Feature;

use App\Models\SortingCenter;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use App\Support\ProvincialCenters;
use App\Support\RegionalCenters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * One BoomBuy Sorting Center per province, covering the whole country — and
 * the moves that got there (per town → per region → per province).
 */
class RegionalCentersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    public function test_the_seeder_gives_every_province_its_own_center(): void
    {
        $this->seed(\Database\Seeders\SortingCenterSeeder::class);
        $this->seed(\Database\Seeders\SortingCenterSeeder::class); // safe to re-run

        $this->assertSame(count(PhLocations::all()), SortingCenter::count());

        foreach (array_keys(PhLocations::all()) as $province) {
            $this->assertSame($province, ParcelRoute::centerFor($province)?->province, "No center serves $province");
        }

        // Cavite → Santa Cruz goes between two centers.
        $cavite = ParcelRoute::centerFor('Cavite');
        $laguna = ParcelRoute::centerFor('Laguna');
        $this->assertSame('BoomBuy Sorting Center – Cavite', $cavite->name);
        $this->assertSame('Imus City', $cavite->city_municipality);
        $this->assertSame('BoomBuy Sorting Center – Laguna', $laguna->name);
        $this->assertSame('Santa Cruz', $laguna->city_municipality);
        $this->assertNotSame($cavite->id, $laguna->id);
    }

    public function test_every_seeded_shop_is_in_its_sorting_centers_town(): void
    {
        $this->seed(\Database\Seeders\SortingCenterSeeder::class);
        $this->seed(\Database\Seeders\SellerShopsSeeder::class);

        $shops = DB::table('users')->where('role', 'seller')->where('email', 'like', '%@boombuy.test')->get();
        $this->assertCount(16, $shops);
        $this->assertCount(16, $shops->pluck('province')->unique());

        foreach ($shops as $shop) {
            $center = ParcelRoute::centerFor($shop->province);
            $this->assertTrue(
                PhLocations::sameTown($center->city_municipality, $shop->city_municipality),
                "{$shop->email} is in {$shop->city_municipality}, but its center is in {$center->city_municipality}"
            );
        }

        // Moving a center moves the shop with it on the next run.
        ParcelRoute::centerFor('Laguna')->update(['city_municipality' => 'Calamba City']);
        $this->seed(\Database\Seeders\SellerShopsSeeder::class);
        $this->assertSame('Calamba City', DB::table('users')->where('email', 'maisonbelle@boombuy.test')->value('city_municipality'));
    }

    public function test_regional_centers_become_provincial_ones(): void
    {
        // Per-town centers, then the regional fold (the 2026-10-06 migration)…
        $santaCruz = SortingCenter::create(['name' => 'Santa Cruz Sorting Center', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'is_active' => true]);
        $qc = SortingCenter::create(['name' => 'Quezon City Sorting Center', 'province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City', 'is_active' => true]);

        $lagunaStaff = $this->makeLogistics();
        DB::table('users')->where('id', $lagunaStaff->id)->update(['sorting_center_id' => $santaCruz->id, 'province' => 'Laguna']);
        $caviteStaff = $this->makeLogistics();
        DB::table('users')->where('id', $caviteStaff->id)->update(['sorting_center_id' => $santaCruz->id, 'province' => 'Cavite']);

        $seller = $this->makeSeller();
        $seller->forceFill(['province' => 'Cavite', 'city_municipality' => 'Imus City'])->save();
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($seller), 'Preparing', [
            'shipping_province' => 'Laguna', 'shipping_city' => 'Santa Cruz',
        ]);

        RegionalCenters::foldTownCenters();
        $this->assertSame(17, SortingCenter::count());

        // …then provincial (the 2026-10-07 migration).
        ProvincialCenters::convert();

        $this->assertSame(count(PhLocations::all()), SortingCenter::count());
        $this->assertSame('BoomBuy Sorting Center – Metro Manila', $qc->fresh()->name);

        $laguna = ParcelRoute::centerFor('Laguna');
        $cavite = ParcelRoute::centerFor('Cavite');

        // Staff land in their own province's center.
        $this->assertSame($laguna->id, (int) DB::table('users')->where('id', $lagunaStaff->id)->value('sorting_center_id'));
        $this->assertSame($cavite->id, (int) DB::table('users')->where('id', $caviteStaff->id)->value('sorting_center_id'));

        // An order not yet dropped off is re-routed: Cavite center → Laguna center.
        $row = DB::table('orders')->find($order);
        $this->assertSame($cavite->id, (int) $row->origin_center_id);
        $this->assertSame($laguna->id, (int) $row->destination_center_id);
    }
}
