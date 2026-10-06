<?php

namespace Tests\Feature;

use App\Models\BuyerAddress;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * End-to-end through the real routes, starting at the buyer's checkout:
 *
 *   buyer checks out → seller packs and drops the parcel at their town's
 *   Sorting Center → origin confirms and dispatches it → the buyer's town
 *   center confirms its arrival and assigns its rider → delivered → received.
 *
 * Plus the variations: same town, two sellers in one cart, no center near
 * the buyer, an order from before Sorting Centers, and the admin side.
 */
class SortingCenterJourneyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private SortingCenter $qc;
    private SortingCenter $santaCruz;
    private User $qcStaff;
    private User $santaCruzStaff;
    private User $santaCruzRider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->qc = $this->makeCenter('Metro Manila (NCR)', 'Quezon City');
        $this->santaCruz = $this->makeCenter('Laguna', 'Santa Cruz');
        $this->qcStaff = $this->makeStaff($this->qc);
        $this->santaCruzStaff = $this->makeStaff($this->santaCruz);
        $this->santaCruzRider = $this->makeAreaRider($this->santaCruz, 'Jun Dela Cruz');
    }

    // ------------------------------------------------------------------
    // Builders
    // ------------------------------------------------------------------

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

    private function makeStaff(?SortingCenter $center): User
    {
        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $center?->id]);

        return $staff;
    }

    private function makeAreaRider(SortingCenter $center, string $name): User
    {
        $rider = $this->makeRider();
        $rider->forceFill(['name' => $name])->save();
        \App\Models\RiderArea::create(['rider_id' => $rider->id, 'province' => $center->province, 'city_municipality' => $center->city_municipality]);

        return $rider;
    }

    private function sellerIn(string $province, string $city, string $category = 'shoes'): User
    {
        $seller = $this->makeSeller($category, 'Shop of ' . $city);
        $seller->forceFill(['province' => $province, 'city_municipality' => $city])->save();

        return $seller;
    }

    private function checkout(User $buyer, array $cart, string $address)
    {
        return $this->actingAsUser($buyer, ['cart' => $cart])->post(route('checkout.place'), [
            'address' => $address,
            'phone' => '09171234567',
            'payment' => 'Cash on Delivery',
        ]);
    }

    private function latestOrder(User $buyer): object
    {
        return DB::table('orders')->where('buyer_id', $buyer->id)->orderByDesc('id')->first();
    }

    private function notified(User $user, string $title, int $orderId): bool
    {
        return DB::table('notifications')->where('user_id', $user->id)->where('title', $title)->where('reference_id', $orderId)->exists();
    }

    // ------------------------------------------------------------------
    // 1. The main journey: Cubao (QC) seller → Santa Cruz, Laguna buyer
    // ------------------------------------------------------------------

    public function test_cubao_to_santa_cruz_from_checkout_to_received(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $product = $this->makeProduct($seller, ['price' => 500, 'stock' => 5]);
        $buyer = $this->makeUser();
        $cart = ["{$product->id}:0" => 1];

        BuyerAddress::create([
            'user_id' => $buyer->id, 'label' => 'Home', 'phone' => '09171234567',
            'address' => '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna', 'is_default' => true,
        ]);

        // Checkout page: knows where it's going and prices the distance (NCR → Laguna = same island group).
        $this->actingAsUser($buyer, ['cart' => $cart])->get(route('checkout'))
            ->assertOk()
            ->assertSee('Delivering to Santa Cruz, Laguna')
            ->assertSee('₱120.00')
            ->assertSee('Same island group');

        // Typing another address re-prices it.
        $this->actingAsUser($buyer, ['cart' => $cart])->get(route('checkout'));
        $this->getJson(route('checkout.quote', ['address' => 'Purok 3, Matina, Davao City, Davao del Sur']))
            ->assertOk()
            ->assertJson(['located' => 'Davao City, Davao del Sur', 'delivery_fee' => 180, 'total' => 680]);
        $this->getJson(route('checkout.quote', ['address' => '123 Test Street, Testville']))
            ->assertOk()
            ->assertJson(['located' => null]);

        // An address without a town is refused.
        $this->checkout($buyer, $cart, '123 Test Street, Testville')->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        // Place the order.
        $this->checkout($buyer, $cart, '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna')->assertRedirect();
        $order = $this->latestOrder($buyer);
        $id = (int) $order->id;

        $this->assertSame('Pending', $order->status);
        $this->assertEquals(120, $order->delivery_fee);
        $this->assertEquals(620, $order->total_amount);
        $this->assertSame('Laguna', $order->shipping_province);
        $this->assertSame('Santa Cruz', $order->shipping_city);
        $this->assertSame($this->qc->id, (int) $order->origin_center_id);
        $this->assertSame($this->santaCruz->id, (int) $order->destination_center_id);
        $this->actingAsUser($buyer)->get(route('orders.success', $id))->assertOk();

        // Seller: told where to drop it off; can't use the old rider pickup.
        $this->actingAsUser($seller)->get(route('seller.order.details', $id))
            ->assertOk()
            ->assertSee('Drop off at: Quezon City Sorting Center');
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Processing'])->assertSessionHas('success');
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Ready for Pickup'])->assertSessionHas('error');
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off'])->assertSessionHas('success');
        $this->assertSame('Dropped Off', $this->orderStatus($id));

        // Only the QC center is told, and only QC can confirm it.
        $this->assertTrue($this->notified($this->qcStaff, 'Parcel Dropped Off', $id));
        $this->assertFalse($this->notified($this->santaCruzStaff, 'Parcel Dropped Off', $id));
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertOk()->assertDontSee('Order #' . $id);
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('error');

        // Each center's dashboard counts only its own work.
        $todo = fn (User $staff) => collect($this->actingAsUser($staff)->get(route('logistics.dashboard'))->assertOk()->viewData('todo'))
            ->pluck('count', 'label');
        $this->assertSame(1, $todo($this->qcStaff)['Confirm arrivals']);
        $this->assertSame(0, $todo($this->santaCruzStaff)['Confirm arrivals']);

        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Order #' . $id)->assertSee('Quezon City Sorting Center');
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', $this->orderStatus($id));

        // QC can't hand it to a rider — it has to go on to Santa Cruz.
        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertSee('Dispatch to Santa Cruz Sorting Center');
        $this->actingAsUser($this->qcStaff)
            ->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->santaCruzRider->id])
            ->assertSessionHas('error');
        $this->assertSame(1, $todo($this->qcStaff)['Dispatch parcels']);
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.dispatch', $id))->assertSessionHas('success');
        $this->assertSame('In Transit', $this->orderStatus($id));
        $this->assertSame(1, $todo($this->santaCruzStaff)['Confirm arrivals']);
        $this->assertSame(0, $todo($this->qcStaff)['Dispatch parcels']);
        $this->assertTrue($this->notified($this->santaCruzStaff, 'Incoming Parcel', $id));

        // Buyer sees it moving.
        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk()->assertSee('In Transit');

        // Santa Cruz: QC can't confirm it for them; they confirm and assign their rider.
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.confirm-arrival', $id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertSee('Order #' . $id)->assertSee('Confirm Arrival');
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-arrival', $id))->assertSessionHas('success');
        $this->assertSame($this->santaCruz->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));

        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertSee('Jun Dela Cruz');
        $this->actingAsUser($this->qcStaff)
            ->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->santaCruzRider->id])
            ->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->santaCruzRider->id])
            ->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($id));

        // Rider delivers, buyer confirms.
        $this->actingAsUser($this->santaCruzRider)->get(route('rider.delivery.details', $id))->assertOk();
        $this->actingAsUser($this->santaCruzRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $this->actingAsUser($this->santaCruzRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivered'])->assertSessionHas('success');
        $this->actingAsUser($buyer)->post(route('buyer.order.received', $id))->assertSessionHas('success');
        $this->assertSame('Delivered', $this->orderStatus($id));
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('buyer_received_at'));

        // The buyer was kept up to date at every hop, in notifications and the shop chat.
        foreach (['Order Processing', 'Order Shipped', 'Parcel at Sorting Center', 'Parcel In Transit', 'Parcel Near You', 'Rider Assigned for Delivery'] as $title) {
            $this->assertTrue($this->notified($buyer, $title, $id), "Buyer was not told: $title");
        }
        $chat = DB::table('messages')->where('order_id', $id)->pluck('message')->implode(' | ');
        $this->assertStringContainsString('preparing your order', $chat);
        $this->assertStringContainsString('dropped it off at the Sorting Center', $chat);

        // The tracking timeline shows each hop, with the centers, in order.
        $steps = DB::table('order_events')->where('order_id', $id)->orderBy('id')->pluck('title')->all();
        $this->assertSame([
            'Order placed',
            'Seller is preparing your order',
            'Seller dropped it off at Quezon City Sorting Center',
            'Received at Quezon City Sorting Center',
            'On its way to Santa Cruz Sorting Center',
            'Arrived at Santa Cruz Sorting Center',
            'Handed to a rider for delivery',
            'Out for delivery',
            'Delivered',
            'You confirmed you received it',
        ], $steps);
        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertSee('Arrived at Santa Cruz Sorting Center');
        $this->actingAsUser($seller)->get(route('seller.order.details', $id))->assertSee('On its way to Santa Cruz Sorting Center');

        // Every page involved still opens.
        $this->actingAsUser($this->santaCruzRider)->get(route('rider.dashboard'))->assertOk()->assertDontSee('No Available Orders');
        $this->actingAsUser($this->santaCruzRider)->get(route('rider.profit'))->assertOk();
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.dashboard'))->assertOk();
        $this->actingAsUser($seller)->get(route('seller.orders', ['tab' => 'completed']))->assertOk()->assertSee('#' . $id);
    }

    // ------------------------------------------------------------------
    // 2. Same town: no dispatch, local fee
    // ------------------------------------------------------------------

    public function test_same_town_order_skips_the_transfer(): void
    {
        $seller = $this->sellerIn('Laguna', 'Santa Cruz');
        $product = $this->makeProduct($seller, ['price' => 300]);
        $buyer = $this->makeUser();

        $this->checkout($buyer, ["{$product->id}:0" => 1], 'Blk 2, Sta. Cruz, Laguna')->assertRedirect();
        $order = $this->latestOrder($buyer);
        $this->assertEquals(50, $order->delivery_fee);

        $this->actingAsUser($seller)->post(route('seller.order.status', $order->id), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $order->id), ['status' => 'Dropped Off']);
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-received', $order->id))->assertSessionHas('success');

        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.dispatch', $order->id))->assertSessionHas('error');
        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $this->santaCruzRider->id])
            ->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($order->id));
    }

    // ------------------------------------------------------------------
    // 3. Two sellers in one cart: two routes, two fees
    // ------------------------------------------------------------------

    public function test_two_sellers_in_one_cart_get_their_own_route_and_fee(): void
    {
        $davao = $this->makeCenter('Davao del Sur', 'Davao City');
        $qcSeller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City', 'shoes');
        $davaoSeller = $this->sellerIn('Davao del Sur', 'Davao City', 'electronics');
        $a = $this->makeProduct($qcSeller, ['price' => 200]);
        $b = $this->makeProduct($davaoSeller, ['price' => 200, 'category' => 'Electronics']);
        $buyer = $this->makeUser();

        $this->checkout($buyer, ["{$a->id}:0" => 1, "{$b->id}:0" => 1], '15 Rizal St, Santa Cruz, Laguna')->assertRedirect();

        $orders = DB::table('orders')->where('buyer_id', $buyer->id)->get()->keyBy('origin_center_id');
        $this->assertCount(2, $orders);
        $this->assertEquals(120, $orders[$this->qc->id]->delivery_fee);   // Luzon → Luzon
        $this->assertEquals(180, $orders[$davao->id]->delivery_fee);      // Mindanao → Luzon
        $this->assertSame($this->santaCruz->id, (int) $orders[$davao->id]->destination_center_id);
    }

    // ------------------------------------------------------------------
    // 4. No center in a province: the nearest open one takes over
    // ------------------------------------------------------------------

    public function test_the_nearest_open_center_is_used_when_a_province_has_none(): void
    {
        // This test only has Metro Manila and Laguna centers.
        $caviteSeller = $this->sellerIn('Cavite', 'Imus City');
        $product = $this->makeProduct($caviteSeller, ['price' => 300]);
        $buyer = $this->makeUser();

        // Seller in Cavite → nearest is in the same region (CALABARZON): Laguna.
        $this->actingAsUser($caviteSeller)->get(route('seller.order.details', $this->makeOrder($buyer, $product, 'Processing', [
            'shipping_address' => '1 Rizal St, Quezon City', 'shipping_province' => 'Metro Manila (NCR)', 'shipping_city' => 'Quezon City',
        ])))->assertSee('Drop off at: Santa Cruz Sorting Center');

        // Buyer in Lucena, Quezon → also delivered from the Laguna center.
        $this->checkout($buyer, ["{$product->id}:0" => 1], 'Purok 3, Lucena City, Quezon')->assertRedirect();
        $order = $this->latestOrder($buyer);
        $this->assertSame($this->santaCruz->id, (int) $order->origin_center_id);
        $this->assertSame($this->santaCruz->id, (int) $order->destination_center_id);

        // Closing Laguna's center: Laguna now goes to the nearest open one (Metro Manila).
        $this->santaCruz->update(['is_active' => false]);
        $this->assertSame($this->qc->id, \App\Support\ParcelRoute::centerFor('Laguna')?->id);
    }

    public function test_nothing_open_on_the_buyers_island_means_the_origin_delivers(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $product = $this->makeProduct($seller, ['price' => 300]);
        $buyer = $this->makeUser();
        $qcRider = $this->makeAreaRider($this->qc, 'QC Rider');

        // No center anywhere in Mindanao in this test.
        $this->checkout($buyer, ["{$product->id}:0" => 1], 'Purok 3, Matina, Davao City, Davao del Sur')->assertRedirect();
        $order = $this->latestOrder($buyer);
        $this->assertSame('Davao del Sur', $order->shipping_province);
        $this->assertNull($order->destination_center_id);

        $this->actingAsUser($seller)->post(route('seller.order.status', $order->id), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $order->id), ['status' => 'Dropped Off']);
        $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.confirm-received', $order->id))->assertSessionHas('success');
        $this->actingAsUser($this->qcStaff)
            ->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $qcRider->id])
            ->assertSessionHas('success');

        // On the road: QC's parcel, not Santa Cruz's.
        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertSee('Order #' . $order->id);
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertDontSee('Order #' . $order->id);
    }

    // ------------------------------------------------------------------
    // 5. An order placed before Sorting Centers existed
    // ------------------------------------------------------------------

    public function test_an_older_order_gets_its_route_when_dropped_off(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($seller), 'Processing', [
            'shipping_address' => '15 Rizal St, Santa Cruz, Laguna',
        ]);

        $this->actingAsUser($seller)->get(route('seller.order.details', $order))->assertOk()->assertSee('Drop off at: Quezon City Sorting Center');
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Dropped Off'])->assertSessionHas('success');

        $row = DB::table('orders')->find($order);
        $this->assertSame($this->qc->id, (int) $row->origin_center_id);
        $this->assertSame($this->santaCruz->id, (int) $row->destination_center_id);
    }

    // ------------------------------------------------------------------
    // 6. Head office sees every center; the admin side
    // ------------------------------------------------------------------

    public function test_head_office_sees_every_center_and_admin_manages_centers_and_fees(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $buyer = $this->makeUser();
        $this->checkout($buyer, ["{$this->makeProduct($seller)->id}:0" => 1], '15 Rizal St, Santa Cruz, Laguna');
        $id = (int) $this->latestOrder($buyer)->id;
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off']);

        $headOffice = $this->makeStaff(null);
        $this->actingAsUser($headOffice)->get(route('logistics.parcels'))->assertSee('Head office')->assertSee('Order #' . $id);

        // Admin: page, open a center, assign staff, change a fee.
        $this->actingAsAdmin()->get(route('admin.sorting-centers'))->assertOk()->assertSee('Santa Cruz Sorting Center');
        // A new province's center.
        $this->actingAsAdmin()->post(route('admin.sorting-centers.store'), ['region' => 'davao', 'province' => 'Davao del Sur', 'city_municipality' => 'Davao City'])
            ->assertSessionHas('success');
        $davao = SortingCenter::where('province', 'Davao del Sur')->firstOrFail();
        $this->assertSame('BoomBuy Sorting Center – Davao del Sur', $davao->name);
        $this->assertSame($davao->id, \App\Support\ParcelRoute::centerFor('Davao del Sur')?->id);
        // Davao Oriental has none yet: its nearest is the Davao del Sur center (same region).
        $this->assertSame($davao->id, \App\Support\ParcelRoute::centerFor('Davao Oriental')?->id);

        // Moving Laguna's center keeps the one center (and its parcels).
        $this->actingAsAdmin()->post(route('admin.sorting-centers.store'), ['region' => 'calabarzon', 'province' => 'Laguna', 'city_municipality' => 'Calamba City'])
            ->assertSessionHas('success');
        $this->assertSame('Calamba City', $this->santaCruz->fresh()->city_municipality);
        $this->assertSame(1, SortingCenter::where('province', 'Laguna')->count());

        // A town outside the region, or not a real town, is refused.
        $this->actingAsAdmin()->post(route('admin.sorting-centers.store'), ['region' => 'calabarzon', 'province' => 'Cebu', 'city_municipality' => 'Cebu City'])
            ->assertSessionHas('error');
        $this->actingAsAdmin()->post(route('admin.sorting-centers.store'), ['region' => 'calabarzon', 'province' => 'Laguna', 'city_municipality' => 'Atlantis'])
            ->assertSessionHas('error');

        $this->actingAsAdmin()->post(route('admin.sorting-centers.staff', $headOffice->id), ['sorting_center_id' => $davao->id])
            ->assertSessionHas('success');
        $this->assertSame($davao->id, (int) DB::table('users')->where('id', $headOffice->id)->value('sorting_center_id'));

        $this->actingAsAdmin()->post(route('admin.settings.delivery-fee.update'), [
            'delivery_fee' => 40, 'delivery_fee_province' => 70, 'delivery_fee_island' => 99, 'delivery_fee_far' => 150,
        ])->assertSessionHas('success');
        $this->assertEquals(99, \App\Support\DeliveryFee::zoneFee('island'));

        // A closed center no longer takes new orders for its town.
        $this->actingAsAdmin()->post(route('admin.sorting-centers.toggle', $this->santaCruz->id))->assertSessionHas('success');
        $this->assertNotSame($this->santaCruz->id, \App\Support\ParcelRoute::centerFor('Laguna', 'Santa Cruz')?->id);
    }

    // ------------------------------------------------------------------
    // 7. Shipping label + scanning it at the Sorting Center
    // ------------------------------------------------------------------

    public function test_seller_prints_a_label_and_the_center_scans_it(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $buyer = $this->makeUser();
        $this->checkout($buyer, ["{$this->makeProduct($seller)->id}:0" => 1], '15 Rizal St, Santa Cruz, Laguna');
        $id = (int) $this->latestOrder($buyer)->id;
        $waybill = \App\Support\Waybill::number($id);

        $this->actingAsUser($seller)->get(route('seller.order.details', $id))->assertSee('Print shipping label (' . $waybill . ')');
        $this->actingAsUser($seller)->get(route('seller.order.label', $id))
            ->assertOk()
            ->assertSee($waybill)
            ->assertSee('Quezon City Sorting Center')
            ->assertSee('Santa Cruz Sorting Center')
            ->assertSee(route('logistics.scan', $waybill), false);

        // Not someone else's label.
        $this->actingAsUser($this->sellerIn('Laguna', 'Santa Cruz', 'electronics'))->get(route('seller.order.label', $id))->assertNotFound();

        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off']);

        // Scanning opens the parcel, ready to confirm; typing the waybill no. works too.
        $this->actingAsUser($this->qcStaff)->get(route('logistics.scan', $waybill))
            ->assertRedirect(route('logistics.parcels', ['q' => $waybill]));
        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels', ['q' => strtolower($waybill)]))
            ->assertSee('Order #' . $id)
            ->assertSee('Confirm Parcel Received');
        $this->actingAsUser($this->qcStaff)->get(route('logistics.scan', 'BB-999999'))->assertSessionHas('error');
    }

    // ------------------------------------------------------------------
    // 8. Buyer-facing details: fee on the product page, address fields,
    //    and only the center's own riders to pick from
    // ------------------------------------------------------------------

    public function test_product_fee_address_fields_and_center_riders(): void
    {
        $seller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $product = $this->makeProduct($seller, ['price' => 300]);
        $buyer = $this->makeUser('buyer', ['address' => '15 Rizal St, Santa Cruz, Laguna']);

        $this->get(route('product.details', $product->id))->assertSee('Delivery from ₱50');
        $this->actingAsUser($buyer)->get(route('product.details', $product->id))->assertSee('Delivery ₱120 to Santa Cruz, Laguna');

        // Checkout's address is picked as Province → City → street, prefilled from the saved one.
        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])->get(route('checkout'))
            ->assertOk()
            ->assertSee('data-address-fields', false)
            ->assertSee('data-province="Laguna"', false)
            ->assertSee('data-city="Santa Cruz"', false)
            ->assertSee('value="15 Rizal St"', false);

        // Profile address needs a town too, and records it.
        $this->actingAsUser($buyer)->post(route('buyer.profile.update'), ['name' => 'Juan', 'phone' => '09170000001', 'address' => 'Somewhere'])
            ->assertSessionHas('error');
        $this->actingAsUser($buyer)->post(route('buyer.profile.update'), ['name' => 'Juan', 'phone' => '09170000001', 'address' => '1 Burgos St, Calamba City, Laguna'])
            ->assertSessionHas('success');
        $this->assertSame('Calamba City', $buyer->fresh()->city_municipality);

        // A parcel at Santa Cruz: its staff only get Laguna riders to pick from.
        $manilaRider = $this->makeAreaRider($this->qc, 'Manila Rider');
        $order = $this->makeOrder($buyer, $product, 'At Sorting Center', [
            'shipping_address' => '15 Rizal St, Santa Cruz, Laguna', 'shipping_province' => 'Laguna', 'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->santaCruz->id, 'destination_center_id' => $this->santaCruz->id, 'current_center_id' => $this->santaCruz->id,
        ]);
        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))
            ->assertSee('Order #' . $order)
            ->assertSee('Jun Dela Cruz (area match)')
            ->assertDontSee('Manila Rider');
        $this->actingAsUser($this->makeStaff(null))->get(route('logistics.parcels'))->assertSee('Manila Rider');
    }

    // ------------------------------------------------------------------
    // 9. Address book needs a town
    // ------------------------------------------------------------------

    public function test_address_book_needs_a_town_and_province(): void
    {
        $buyer = $this->makeUser();

        $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['phone' => '09171234567', 'address' => '123 Test Street, Testville'])
            ->assertSessionHasErrors('address');
        $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['phone' => '09171234567', 'address' => '123 Rizal St, Santa Cruz, Laguna'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('buyer_addresses', ['user_id' => $buyer->id, 'address' => '123 Rizal St, Santa Cruz, Laguna']);
    }
}
