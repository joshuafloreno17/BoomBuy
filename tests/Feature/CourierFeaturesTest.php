<?php

namespace Tests\Feature;

use App\Models\BuyerAddress;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\ParcelRoute;
use Database\Seeders\LogisticsNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The courier features on top of the Sorting Center network: delivery
 * estimates, buyer pick-up at a center, returns routed back to the seller's
 * center, dispatching a batch at once, proof-of-delivery photos, Cash on
 * Delivery hand-in, the admin map and the test-account cleanup.
 */
class CourierFeaturesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private SortingCenter $qc;
    private SortingCenter $santaCruz;
    private User $qcStaff;
    private User $santaCruzStaff;
    private User $santaCruzRider;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->qc = $this->makeCenter('Metro Manila (NCR)', 'Quezon City');
        $this->santaCruz = $this->makeCenter('Laguna', 'Santa Cruz');
        $this->qcStaff = $this->makeStaff($this->qc);
        $this->santaCruzStaff = $this->makeStaff($this->santaCruz);

        $this->santaCruzRider = $this->makeRider();
        \App\Models\RiderArea::create(['rider_id' => $this->santaCruzRider->id, 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz']);

        $this->seller = $this->makeSeller('shoes', 'Cubao Kicks');
        $this->seller->forceFill(['province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City'])->save();
    }

    private function makeCenter(string $province, string $city): SortingCenter
    {
        return SortingCenter::create([
            'name' => $city . ' Sorting Center',
            'region' => \App\Support\PhLocations::region($province),
            'province' => $province,
            'city_municipality' => $city,
            'address' => 'Poblacion, ' . $city,
            'is_active' => true,
        ]);
    }

    private function makeStaff(SortingCenter $center): User
    {
        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $center->id]);

        return $staff;
    }

    /** A QC → Santa Cruz order already at the given step. */
    private function routedOrder(User $buyer, string $status, array $overrides = []): int
    {
        return $this->makeOrder($buyer, $this->makeProduct($this->seller, ['price' => 500]), $status, array_merge([
            'shipping_address' => '15 P. Guevarra St, Santa Cruz, Laguna',
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->qc->id,
            'destination_center_id' => $this->santaCruz->id,
            'delivery_zone' => 'island',
        ], $overrides));
    }

    private function buyerInSantaCruz(): User
    {
        $buyer = $this->makeUser();
        BuyerAddress::create([
            'user_id' => $buyer->id, 'label' => 'Home', 'phone' => '09171234567',
            'address' => '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna', 'is_default' => true,
        ]);

        return $buyer;
    }

    private function notified(User $user, string $title, int $orderId): bool
    {
        return DB::table('notifications')->where('user_id', $user->id)->where('title', $title)->where('reference_id', $orderId)->exists();
    }

    // ------------------------------------------------------------------
    // 1. Delivery estimate
    // ------------------------------------------------------------------

    public function test_eta_is_a_date_range_by_distance(): void
    {
        $this->assertSame('Oct 8–9', ParcelRoute::etaLabel('local', Carbon::parse('2026-10-07')));
        $this->assertSame('Oct 9–12', ParcelRoute::etaLabel('island', Carbon::parse('2026-10-07')));
        $this->assertSame('Oct 30 – Nov 2', ParcelRoute::etaLabel('island', Carbon::parse('2026-10-28')));

        // Unknown distance: assume the island-group estimate rather than promise too much.
        $this->assertSame(ParcelRoute::etaLabel('island', Carbon::parse('2026-10-07')), ParcelRoute::etaLabel(null, Carbon::parse('2026-10-07')));
    }

    public function test_checkout_shows_the_estimate_and_prices_pick_up(): void
    {
        $product = $this->makeProduct($this->seller, ['price' => 500, 'stock' => 5]);
        $buyer = $this->buyerInSantaCruz();
        $cart = ["{$product->id}:0" => 1];

        $this->actingAsUser($buyer, ['cart' => $cart])->get(route('checkout'))
            ->assertOk()
            ->assertSee('Estimated arrival')
            ->assertSee('Pick up at the Sorting Center');

        $address = '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna';

        $this->getJson(route('checkout.quote', ['address' => $address]))
            ->assertOk()
            ->assertJson(['delivery_fee' => 120, 'fulfillment' => 'delivery', 'eta' => ParcelRoute::etaLabel('island')]);

        // Picking up skips the rider's leg: ₱120 − ₱50.
        $this->getJson(route('checkout.quote', ['address' => $address, 'fulfillment' => 'pickup']))
            ->assertOk()
            ->assertJson(['delivery_fee' => 70, 'total' => 570, 'fulfillment' => 'pickup', 'pickup_center' => ['name' => 'Santa Cruz Sorting Center']]);

        // The product page shows when it would arrive.
        $this->actingAsUser($buyer)->get(route('product.details', $product->id))->assertOk()->assertSee('arrives');
    }

    // ------------------------------------------------------------------
    // 2. Pick-up at the buyer's Sorting Center, with batch dispatch
    // ------------------------------------------------------------------

    public function test_buyer_picks_up_at_their_sorting_center(): void
    {
        $buyer = $this->buyerInSantaCruz();
        $first = $this->makeProduct($this->seller, ['price' => 300, 'stock' => 5]);
        $second = $this->makeProduct($this->seller, ['price' => 200, 'stock' => 5]);

        $this->actingAsUser($buyer, ['cart' => ["{$first->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna',
            'phone' => '09171234567',
            'payment' => 'Cash on Delivery',
            'fulfillment' => 'pickup',
        ])->assertRedirect();

        $order = DB::table('orders')->where('buyer_id', $buyer->id)->first();
        $id = (int) $order->id;
        $this->assertSame('pickup', $order->fulfillment);
        $this->assertSame('island', $order->delivery_zone);
        $this->assertEquals(70, $order->delivery_fee);
        $this->actingAsUser($buyer)->get(route('orders.success', $id))->assertOk()->assertSee('Santa Cruz Sorting Center');

        // A second parcel for the same center, so QC can send both at once.
        $other = $this->routedOrder($this->makeUser(), 'At Sorting Center', ['current_center_id' => $this->qc->id]);

        $this->actingAsUser($this->seller)->post(route('seller.order.status', $id), ['status' => 'Processing'])->assertSessionHas('success');
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off'])->assertSessionHas('success');
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');

        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Dispatch all 2 to Santa Cruz Sorting Center');

        // Santa Cruz can't dispatch QC's parcels.
        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.dispatch-all'), ['destination_center_id' => $this->santaCruz->id])
            ->assertSessionHas('error');
        $this->actingAsUser($this->qcStaff)
            ->post(route('logistics.parcels.dispatch-all'), ['destination_center_id' => $this->santaCruz->id])
            ->assertSessionHas('success');
        $this->assertSame('In Transit', $this->orderStatus($id));
        $this->assertSame('In Transit', $this->orderStatus($other));

        // At Santa Cruz it waits for the buyer instead of going to a rider.
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-arrival', $id))->assertSessionHas('success');
        $this->assertSame('Ready to Collect', $this->orderStatus($id));
        $this->assertTrue($this->notified($buyer, 'Ready to Collect', $id));
        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->santaCruzRider->id])
            ->assertSessionHas('error');

        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk()->assertSee('Ready to Collect');
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Buyer Collected It');

        // Only Santa Cruz can hand it over; COD is paid there, so the seller's money is in at once.
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.hand-to-buyer', $id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.hand-to-buyer', $id), ['pickup_code' => DB::table('orders')->where('id', $id)->value('pickup_code')])->assertSessionHas('success');

        $done = DB::table('orders')->where('id', $id)->first();
        $this->assertSame('Delivered', $done->status);
        $this->assertNotNull($done->buyer_received_at);
        $this->assertNotNull($done->cod_remitted_at);
        $this->assertSame($this->santaCruz->id, (int) $done->cod_remitted_center_id);
        $this->assertNull($done->delivery_rider_id);
    }

    public function test_pick_up_needs_a_center_near_the_buyer(): void
    {
        $product = $this->makeProduct($this->seller, ['price' => 300, 'stock' => 5]);
        $buyer = $this->makeUser();

        // Davao has no center in this test, and nothing on Mindanao either.
        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => 'Purok 3, Matina, Davao City, Davao del Sur',
            'phone' => '09171234567',
            'payment' => 'GCash',
            'fulfillment' => 'pickup',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    // ------------------------------------------------------------------
    // 3. Returns travel back to the seller's own center
    // ------------------------------------------------------------------

    public function test_uncollected_parcel_goes_back_to_the_sellers_center(): void
    {
        $buyer = $this->makeUser();
        $id = $this->routedOrder($buyer, 'Ready to Collect', ['fulfillment' => 'pickup', 'current_center_id' => $this->santaCruz->id]);

        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.return-to-seller', $id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.return-to-seller', $id))->assertSessionHas('success');
        $this->assertSame('Returning', $this->orderStatus($id));
        $this->assertTrue($this->notified($this->qcStaff, 'Incoming Return', $id));

        // QC sees it coming, confirms it, and the seller is told to collect it there.
        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Confirm Return Arrived');
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('error');
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatus($id));
        $this->assertTrue($this->notified($this->seller, 'Collect Your Returned Parcel', $id));

        $this->actingAsUser($this->seller)->get(route('seller.orders', ['tab' => 'cancelled']))->assertOk()->assertSee('#' . $id);

        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.hand-back', $id))->assertSessionHas('error');
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.hand-back', $id))->assertSessionHas('success');
        $this->assertSame('Returned to Seller', $this->orderStatus($id));
        $this->assertTrue($this->notified($this->seller, 'Order Returned to You', $id));

        // The seller can now restock it.
        $this->actingAsUser($this->seller)->post(route('seller.order.restock', $id))->assertSessionHas('success');
    }

    // ------------------------------------------------------------------
    // 4. Proof of delivery photo
    // ------------------------------------------------------------------

    public function test_delivered_needs_a_photo_that_only_the_order_people_can_see(): void
    {
        $buyer = $this->makeUser();
        $id = $this->routedOrder($buyer, 'Out for Delivery', [
            'delivery_rider_id' => $this->santaCruzRider->id,
            'current_center_id' => $this->santaCruz->id,
            'payment_method' => 'GCash',
        ]);

        $this->actingAsUser($this->santaCruzRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivered'])->assertSessionHas('error');
        $this->assertSame('Out for Delivery', $this->orderStatus($id));

        $this->actingAsUser($this->santaCruzRider)
            ->post(route('rider.delivery.status', $id), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])
            ->assertSessionHas('success');

        $order = DB::table('orders')->where('id', $id)->first();
        $this->assertSame('Delivered', $order->status);
        $this->assertNotNull($order->delivery_proof);
        Storage::disk('local')->assertExists($order->delivery_proof);
        $this->assertNull($order->cod_collected_at, 'A prepaid order has no cash to collect.');

        $url = route('orders.delivery-proof', $id);
        $this->actingAsUser($buyer)->get($url)->assertOk();
        $this->actingAsUser($this->seller)->get($url)->assertOk();
        $this->actingAsUser($this->santaCruzStaff)->get($url)->assertOk();
        $this->actingAsAdmin()->get($url)->assertOk();

        $this->flushSession();
        $this->actingAsUser($this->makeUser())->get($url)->assertForbidden();
        $this->actingAsUser($this->makeStaff($this->makeCenter('Cebu', 'Cebu City')))->get($url)->assertForbidden();

        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk()->assertSee('View the delivery photo');
    }

    // ------------------------------------------------------------------
    // 5. Cash on Delivery money goes from the rider to the center
    // ------------------------------------------------------------------

    public function test_rider_hands_in_cod_cash_and_the_seller_payout_is_released(): void
    {
        $buyer = $this->makeUser();
        $id = $this->routedOrder($buyer, 'Out for Delivery', [
            'delivery_rider_id' => $this->santaCruzRider->id,
            'current_center_id' => $this->santaCruz->id,
        ]);

        $this->actingAsUser($this->santaCruzRider)
            ->post(route('rider.delivery.status', $id), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])
            ->assertSessionHas('success');
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('cod_collected_at'));

        $this->actingAsUser($this->santaCruzRider)->get(route('rider.dashboard'))->assertOk()->assertSee('Cash to hand in: ₱500.00');
        $this->actingAsUser($this->seller)->get(route('seller.dashboard'))->assertOk()->assertSee('still with riders');

        // QC never had this rider's delivery; Santa Cruz receives the money.
        $this->actingAsUser($this->qcStaff)->post(route('logistics.riders.receive-cod', $this->santaCruzRider->id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Receive ₱500.00');
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.riders.receive-cod', $this->santaCruzRider->id))->assertSessionHas('success');

        $order = DB::table('orders')->where('id', $id)->first();
        $this->assertNotNull($order->cod_remitted_at);
        $this->assertSame($this->santaCruz->id, (int) $order->cod_remitted_center_id);
        $this->assertTrue($this->notified($this->seller, 'COD Payout Released', $id));

        // Nothing left to hand in.
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.riders.receive-cod', $this->santaCruzRider->id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzRider)->get(route('rider.dashboard'))->assertOk()->assertDontSee('Cash to hand in');
    }

    // ------------------------------------------------------------------
    // 6. Admin map, 7. account cleanup
    // ------------------------------------------------------------------

    public function test_admin_sees_every_center_on_the_map(): void
    {
        $this->routedOrder($this->makeUser(), 'At Sorting Center', ['current_center_id' => $this->qc->id]);

        $response = $this->actingAsAdmin()->get(route('admin.sorting-centers'))->assertOk()->assertSee('id="scMap"', false);
        $pins = collect($response->viewData('mapPins'))->keyBy('name');

        $this->assertCount(2, $pins);
        $this->assertSame(1, $pins['Quezon City Sorting Center']['parcels']);
        $this->assertSame(0, $pins['Santa Cruz Sorting Center']['parcels']);
        $this->assertCount(count(\App\Support\PhLocations::PROVINCE_CAPITALS), \App\Support\PhCoordinates::PROVINCES);
    }

    public function test_seeder_keeps_one_test_account_per_center(): void
    {
        $cebu = $this->makeCenter('Cebu', 'Cebu City');

        $town = $this->makeLogistics();
        $town->forceFill(['email' => 'hub.mandaue@boombuy.test'])->save();
        $province = $this->makeLogistics();
        $province->forceFill(['email' => 'hub.cebu@boombuy.test'])->save();
        $real = $this->makeLogistics();
        DB::table('users')->whereIn('id', [$town->id, $province->id, $real->id])->update(['sorting_center_id' => $cebu->id]);

        $this->assertSame(1, (new LogisticsNetworkSeeder())->dropDuplicateStaff());

        $this->assertDatabaseMissing('users', ['id' => $town->id]);
        $this->assertDatabaseHas('users', ['id' => $province->id, 'sorting_center_id' => $cebu->id]);
        $this->assertDatabaseHas('users', ['id' => $real->id, 'sorting_center_id' => $cebu->id]);

        $this->assertSame(0, (new LogisticsNetworkSeeder())->dropDuplicateStaff());
    }
}
