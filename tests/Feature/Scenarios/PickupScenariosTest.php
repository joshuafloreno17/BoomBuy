<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Courier pickup from the seller (ERP: seller "schedule courier pickup";
 * courier "items for pickup" + "pickup order").
 */
class PickupScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $seller;
    private User $buyer;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz');
        $this->buyer = $this->buyerAt();
        $this->shoes = $this->makeProduct($this->seller, ['price' => 500]);
    }

    private function preparing(): int
    {
        return $this->makeOrder($this->buyer, $this->shoes, 'Preparing', [
            'shipping_address' => self::LAGUNA_ADDRESS,
            'origin_center_id' => $this->laguna->id,
            'destination_center_id' => $this->laguna->id,
        ]);
    }

    private function book(int $orderId, ?string $day = null)
    {
        return $this->actingAsUser($this->seller)
            ->post(route('seller.order.pickup', $orderId), ['pickup_date' => $day ?? now()->addDay()->toDateString()]);
    }

    public function test_P01_full_pickup_from_seller_to_sorting_center(): void
    {
        $rider = $this->riderFor($this->laguna);
        $farRider = $this->riderFor($this->center('Cebu', 'Cebu City'));
        $staff = $this->staffAt($this->laguna);
        $id = $this->preparing();

        // The seller sees the button and marks it ready for pickup tomorrow.
        $this->actingAsUser($this->seller)->get(route('seller.order.details', $id))->assertSee('Mark as Ready for Pickup');
        $this->book($id)->assertSessionHas('success');
        $this->assertSame('Ready for Pickup', DB::table('orders')->where('id', $id)->value('status'));

        // Only riders covering the seller's town are told and see it.
        $this->assertTrue($this->notifiedWith($rider, 'New Pickup Request'));
        $this->assertFalse($this->notifiedWith($farRider, 'New Pickup Request'));
        $this->actingAsUser($rider)->get(route('rider.pickups'))->assertOk()->assertSee('Order #' . $id)->assertSee('Accept pickup');
        $this->actingAsUser($rider)->get(route('rider.dashboard'))->assertSee('1 new pickup request near you');
        $this->actingAsUser($farRider)->post(route('rider.pickup.accept', $id))->assertSessionHas('error');

        // Accept → the seller is told who is coming.
        $this->actingAsUser($rider)->post(route('rider.pickup.accept', $id))->assertSessionHas('success');
        $this->assertSame('Pickup Assigned', DB::table('orders')->where('id', $id)->value('status'));
        $this->assertTrue($this->notifiedWith($this->seller, 'Rider Assigned for Pickup'));
        $this->actingAsUser($this->seller)->get(route('seller.order.details', $id))->assertSee($rider->name)->assertSee('Confirm Rider Pickup');

        // A pickup is not a delivery: it isn't on the rider's delivery pages.
        $this->actingAsUser($rider)->get(route('rider.delivery.details', $id))->assertNotFound();

        // Picked up → the buyer, the seller and the Sorting Center are told;
        // the seller confirms the hand-over too.
        $this->actingAsUser($rider)->post(route('rider.pickup.picked-up', $id))->assertSessionHas('success');
        $this->assertSame('Picked Up', DB::table('orders')->where('id', $id)->value('status'));
        $this->actingAsUser($this->seller)->post(route('seller.order.confirm-pickup', $id))->assertSessionHas('success');
        $this->actingAsUser($this->seller)->post(route('seller.order.confirm-pickup', $id))->assertSessionHas('error');
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('seller_confirmed_pickup_at'));
        $this->assertTrue($this->notifiedWith($this->buyer, 'Order Shipped'));
        $this->assertTrue($this->notifiedWith($staff, 'Parcel Picked Up'));

        // The Sorting Center confirms it arrived; the normal flow continues.
        $this->actingAsUser($staff)->get(route('logistics.parcels'))->assertSee('Picked up by a rider');
        $this->actingAsUser($staff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', DB::table('orders')->where('id', $id)->value('status'));

        // Sorted by the buyer's area, then given to that area's rider.
        $this->actingAsUser($staff)->get(route('logistics.parcels'))->assertSee('Delivery area: <strong>Santa Cruz, Laguna</strong>', false);
        $this->actingAsUser($staff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $rider->id])->assertSessionHas('error', fn ($m) => str_contains($m, 'Sort this parcel'));
        $this->actingAsUser($staff)->post(route('logistics.parcels.sort', $id))->assertSessionHas('success');
        $this->assertSame('Sorted', DB::table('orders')->where('id', $id)->value('status'));
        $this->actingAsUser($staff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $rider->id])->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', DB::table('orders')->where('id', $id)->value('status'));

        // The buyer's order shows it as shipped (To Receive).
        $this->actingAsUser($this->buyer)->get(route('buyer.orders'))->assertOk();
    }

    public function test_P02_no_rider_in_town_means_the_sorting_center_assigns_one(): void
    {
        $staff = $this->staffAt($this->laguna);
        $id = $this->preparing();

        // Bad days are refused.
        $this->book($id, now()->addDays(10)->toDateString())->assertSessionHas('error');
        $this->book($id, now()->subDay()->toDateString())->assertSessionHas('error');

        // No rider covers the town: still ready for pickup; the center is asked to assign one.
        $this->book($id)->assertSessionHas('success', fn ($m) => str_contains($m, 'Sorting Center will assign a rider'));
        $this->assertSame('Ready for Pickup', DB::table('orders')->where('id', $id)->value('status'));
        $this->assertTrue($this->notifiedWith($staff, 'Pickup Needs a Rider'));

        $rider = $this->makeRider();
        $this->actingAsUser($staff)->get(route('logistics.parcels'))->assertSee('Pickups Needing a Rider (1)')->assertSee('Assign Pickup');
        $this->actingAsUser($staff)->post(route('logistics.parcels.assign-pickup', $id), ['rider_id' => $rider->id])->assertSessionHas('success');
        $this->assertSame('Pickup Assigned', DB::table('orders')->where('id', $id)->value('status'));
        $this->assertTrue($this->notifiedWith($rider, 'Pickup Assigned to You'));
        $this->actingAsUser($rider)->get(route('rider.pickups'))->assertSee('Order #' . $id)->assertSee('Picked up');

        // The seller can confirm the hand-over first: that marks it Picked Up.
        $this->actingAsUser($this->seller)->post(route('seller.order.confirm-pickup', $id))->assertSessionHas('success');
        $this->assertSame('Picked Up', DB::table('orders')->where('id', $id)->value('status'));

        // Only packed (Preparing) orders can be marked ready.
        $pending = $this->makeOrder($this->buyer, $this->shoes, 'Pending', ['shipping_address' => self::LAGUNA_ADDRESS]);
        $this->book($pending)->assertSessionHas('error');
    }

    public function test_P03_first_rider_wins_and_sellers_no_longer_drop_off(): void
    {
        $first = $this->riderFor($this->laguna);
        $second = $this->riderFor($this->laguna);

        // Sellers don't bring parcels in themselves any more.
        $id = $this->preparing();
        $this->book($id);
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off'])->assertSessionHas('error');
        $this->assertSame('Ready for Pickup', DB::table('orders')->where('id', $id)->value('status'));

        // Claimed → only the first rider gets it, and the seller can no longer cancel.
        $other = $this->preparing();
        $this->book($other);
        $this->actingAsUser($first)->post(route('rider.pickup.accept', $other))->assertSessionHas('success');
        $this->actingAsUser($second)->post(route('rider.pickup.accept', $other))->assertSessionHas('error', fn ($m) => str_contains($m, 'already taken'));
        $this->actingAsUser($second)->post(route('rider.pickup.picked-up', $other))->assertSessionHas('error');
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $other), ['status' => 'Cancelled', 'cancellation_reason' => 'Changed my mind'])
            ->assertSessionHas('error');
        $this->assertSame('Pickup Assigned', DB::table('orders')->where('id', $other)->value('status'));
    }

    public function test_P04_cancelling_an_unclaimed_pickup_returns_the_stock(): void
    {
        $this->riderFor($this->laguna);
        $this->shoes->update(['stock' => 4]);
        $id = $this->preparing();
        $this->book($id);

        $this->actingAsUser($this->seller)->post(route('seller.order.status', $id), ['status' => 'Cancelled', 'cancellation_reason' => 'Out of stock'])
            ->assertSessionHas('success');
        $this->assertSame('Cancelled', DB::table('orders')->where('id', $id)->value('status'));
        $this->assertSame(5, $this->stockOf($this->shoes));
    }

    public function test_P05_a_rider_who_is_no_longer_active_frees_their_pickups(): void
    {
        $rider = $this->riderFor($this->laguna);
        $id = $this->preparing();
        $this->book($id);
        $this->actingAsUser($rider)->post(route('rider.pickup.accept', $id));

        \App\Support\RiderRelease::release($rider->id);

        $order = DB::table('orders')->where('id', $id)->first();
        $this->assertSame('Ready for Pickup', $order->status);
        $this->assertNull($order->rider_id);
    }
}
