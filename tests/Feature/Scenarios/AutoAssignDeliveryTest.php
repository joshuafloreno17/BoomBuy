<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Last-mile auto-assign: a parcel ready for delivery goes straight to the
 * Online area-match rider; with nobody free it waits for the staff.
 */
class AutoAssignDeliveryTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $cebu;
    private SortingCenter $laguna;
    private SortingCenter $santaCruz;
    private User $branchStaff;
    private User $lagunaStaff;
    private User $branchRider;
    private User $buyer;
    private Product $phone;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.auto_assign_delivery' => true]);

        $this->cebu = $this->center('Cebu', 'Cebu City');
        $this->laguna = $this->center('Laguna', 'Calamba City');
        $this->santaCruz = SortingCenter::create([
            'parent_id' => $this->laguna->id,
            'name' => SortingCenter::branchNameFor('Santa Cruz'),
            'region' => $this->laguna->region,
            'province' => 'Laguna',
            'city_municipality' => 'Santa Cruz',
            'address' => 'Poblacion, Santa Cruz',
            'is_active' => true,
        ]);

        $this->lagunaStaff = $this->staffAt($this->laguna);
        $this->branchStaff = $this->staffAt($this->santaCruz);
        $this->branchRider = $this->riderFor($this->laguna, 'Santa Cruz');

        $this->buyer = $this->buyerAt();
        $this->phone = $this->makeProduct($this->sellerIn('Cebu', 'Cebu City', 'electronics'), ['price' => 1500]);
    }

    /** A Santa Cruz parcel on the truck to the Santa Cruz branch. */
    private function onTheWayToBranch(): int
    {
        return $this->makeOrder($this->buyer, $this->phone, 'To Branch', [
            'shipping_address' => self::LAGUNA_ADDRESS,
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
            'branch_id' => $this->santaCruz->id,
        ]);
    }

    public function test_parcel_arriving_at_the_branch_goes_to_the_online_area_rider(): void
    {
        $id = $this->onTheWayToBranch();

        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.at-branch', $id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'auto-assigned to ' . $this->branchRider->name));

        $order = DB::table('orders')->where('id', $id)->first();
        $this->assertSame('Assigned for Delivery', $order->status);
        $this->assertSame($this->branchRider->id, (int) $order->delivery_rider_id);

        // The rider is told (the same notification goes to their phone as a push).
        $this->assertTrue($this->notifiedWith($this->branchRider, 'New Delivery Assignment'));
        $this->assertTrue(DB::table('order_events')->where('order_id', $id)->where('title', 'Auto-assigned to ' . $this->branchRider->name)->exists());
    }

    public function test_no_online_rider_leaves_it_for_the_staff(): void
    {
        DB::table('users')->where('id', $this->branchRider->id)->update(['is_online' => false]);
        $id = $this->onTheWayToBranch();

        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.at-branch', $id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'No area rider is online'));

        $this->assertSame('At Branch', DB::table('orders')->where('id', $id)->value('status'));
        $this->actingAsUser($this->branchStaff)->get(route('logistics.parcels'))->assertSee('Awaiting Assignment (1)');
    }

    public function test_rider_at_the_job_limit_is_skipped(): void
    {
        foreach (range(1, 3) as $n) {
            $this->makeOrder($this->buyer, $this->phone, 'Assigned for Delivery', ['delivery_rider_id' => $this->branchRider->id]);
        }
        $id = $this->onTheWayToBranch();

        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.at-branch', $id))->assertSessionHas('success');

        $this->assertSame('At Branch', DB::table('orders')->where('id', $id)->value('status'));
    }

    public function test_town_without_a_branch_is_auto_assigned_when_sorted(): void
    {
        $rider = $this->riderFor($this->laguna, 'Pagsanjan');
        $id = $this->makeOrder($this->buyer, $this->phone, 'At Sorting Center', [
            'shipping_address' => '3 Rizal St, Pagsanjan, Laguna',
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Pagsanjan',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
        ]);

        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'auto-assigned to ' . $rider->name));

        $this->assertSame($rider->id, (int) DB::table('orders')->where('id', $id)->value('delivery_rider_id'));
    }
}
