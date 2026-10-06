<?php

namespace Tests\Feature;

use App\Models\SortingCenter;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use App\Support\RegionalCenters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** One BoomBuy Sorting Center per region, covering every province. */
class RegionalCentersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    public function test_the_seeder_covers_every_province_with_17_regional_centers(): void
    {
        $this->seed(\Database\Seeders\SortingCenterSeeder::class);
        $this->seed(\Database\Seeders\SortingCenterSeeder::class); // safe to re-run

        $this->assertSame(17, SortingCenter::count());

        foreach (array_keys(PhLocations::all()) as $province) {
            $this->assertNotNull(ParcelRoute::centerFor($province), "No center serves $province");
        }

        $this->assertSame('BoomBuy Sorting Center – CALABARZON', ParcelRoute::centerFor('Quezon')->name);
        $this->assertSame('Calamba City', ParcelRoute::centerFor('Laguna')->city_municipality);
        $this->assertSame(ParcelRoute::centerFor('Cebu')->id, ParcelRoute::centerFor('Bohol')->id);
    }

    public function test_old_per_town_centers_fold_into_their_region(): void
    {
        // The way things were before: one center per town.
        $santaCruz = SortingCenter::create(['name' => 'Santa Cruz Sorting Center', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'is_active' => true]);
        $calamba = SortingCenter::create(['name' => 'Calamba City Sorting Center', 'province' => 'Laguna', 'city_municipality' => 'Calamba City', 'is_active' => true]);
        $qc = SortingCenter::create(['name' => 'Quezon City Sorting Center', 'province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City', 'is_active' => true]);

        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $santaCruz->id]);
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'In Transit', [
            'origin_center_id' => $qc->id, 'destination_center_id' => $santaCruz->id,
        ]);
        DB::table('order_events')->insert(['order_id' => $order, 'status' => 'In Transit', 'title' => 'On its way', 'center_id' => $santaCruz->id, 'created_at' => now()]);

        RegionalCenters::foldTownCenters();

        $this->assertSame(17, SortingCenter::count());
        $this->assertNull(SortingCenter::find($santaCruz->id));

        // The hub-city centers became the regional ones; everything else moved to them.
        $calabarzon = $calamba->fresh();
        $this->assertSame('calabarzon', $calabarzon->region);
        $this->assertSame('ncr', $qc->fresh()->region);
        $this->assertSame($calabarzon->id, (int) DB::table('users')->where('id', $staff->id)->value('sorting_center_id'));
        $this->assertSame($calabarzon->id, (int) DB::table('orders')->where('id', $order)->value('destination_center_id'));
        $this->assertSame($qc->id, (int) DB::table('orders')->where('id', $order)->value('origin_center_id'));
        $this->assertSame($calabarzon->id, (int) DB::table('order_events')->where('order_id', $order)->value('center_id'));
    }
}
