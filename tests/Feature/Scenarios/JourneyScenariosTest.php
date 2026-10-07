<?php

namespace Tests\Feature\Scenarios;

use App\Models\Message;
use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use App\Models\Voucher;
use App\Support\AutoReceive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test Plan → "Buong daloy" (E01–E08): whole trips through the real routes, every role taking its turn. */
class JourneyScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $lagunaStaff;
    private User $lagunaRider;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->lagunaStaff = $this->staffAt($this->laguna);
        $this->lagunaRider = $this->riderFor($this->laguna);
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Santa Cruz Kicks');
        $this->shoes = $this->makeProduct($this->seller, ['name' => 'Canvas Sneakers', 'price' => 500, 'stock' => 10]);
    }

    private function as(User $user): static
    {
        $this->flushSession();

        return $this->actingAsUser($user);
    }

    private function sellerShips(int $orderId, ?User $seller = null): void
    {
        $this->as($seller ?? $this->seller)->post(route('seller.order.status', $orderId), ['status' => 'Processing'])->assertSessionHas('success');
        $this->post(route('seller.order.status', $orderId), ['status' => 'Dropped Off'])->assertSessionHas('success');
    }

    private function riderTakes(int $orderId, User $rider, string $outcome = 'Delivered', array $fail = []): void
    {
        $this->as($rider)->post(route('rider.delivery.status', $orderId), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $data = $outcome === 'Delivered'
            ? ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()]
            : array_merge(['status' => 'Delivery Failed'], $fail);
        $this->post(route('rider.delivery.status', $orderId), $data)->assertSessionHas('success');
    }

    public function test_E01_same_town_cash_on_delivery_from_cart_to_review(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1])->assertRedirect();
        $order = $this->lastOrder($buyer);
        $this->assertSame('local', $order->delivery_zone);
        $this->assertEquals(550, $order->total_amount);

        $this->sellerShips($order->id);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $this->lagunaRider->id])->assertSessionHas('success');
        $this->riderTakes($order->id, $this->lagunaRider);

        $this->as($this->lagunaStaff)->post(route('logistics.riders.receive-cod', $this->lagunaRider->id))->assertSessionHas('success');
        $this->assertTrue($this->notifiedWith($this->seller, 'COD Payout Released'));

        $this->travel(4)->days();
        AutoReceive::sweep(true);
        $this->as($buyer)->post(route('buyer.product.review', [$order->id, $this->shoes->id]), ['rating' => 5, 'review' => 'Ganda!'])->assertSessionHas('success');

        // Each step told the buyer, and the shop's chat shows the order cards.
        foreach (['Order Confirmed', 'Order Processing', 'Order Shipped', 'Parcel at Sorting Center', 'Rider Assigned for Delivery', 'Order Out for Delivery', 'Order Delivered', 'Order Marked as Received'] as $title) {
            $this->assertTrue($this->notifiedWith($buyer, $title), "Buyer was not told: {$title}");
        }
        $this->assertGreaterThanOrEqual(4, Message::where('recipient_id', $buyer->id)->where('kind', Message::ORDER_UPDATE)->count());
        $this->assertGreaterThanOrEqual(6, DB::table('order_events')->where('order_id', $order->id)->count());
    }

    public function test_E02_cavite_seller_to_cebu_buyer_across_islands(): void
    {
        $cavite = $this->center('Cavite', 'Imus City');
        $cebu = $this->center('Cebu', 'Cebu City');
        $caviteStaff = $this->staffAt($cavite);
        $cebuStaff = $this->staffAt($cebu);
        $cebuRider = $this->riderFor($cebu);
        $seller = $this->sellerIn('Cavite', 'Imus City', 'shoes');
        $product = $this->makeProduct($seller, ['price' => 500]);
        $buyer = $this->buyerAt(self::CEBU_ADDRESS);

        $this->placeOrder($buyer, [$product->id . ':0' => 1], self::CEBU_ADDRESS);
        $order = $this->lastOrder($buyer);
        $this->assertSame('far', $order->delivery_zone);
        $this->assertEquals(180, $order->delivery_fee);
        $this->assertSame($cavite->id, (int) $order->origin_center_id);
        $this->assertSame($cebu->id, (int) $order->destination_center_id);

        $this->sellerShips($order->id, $seller);
        $this->as($caviteStaff)->post(route('logistics.parcels.confirm-received', $order->id));
        $this->post(route('logistics.parcels.dispatch', $order->id))->assertSessionHas('success');
        $this->assertSame('In Transit', $this->orderStatus($order->id));

        $this->as($cebuStaff)->post(route('logistics.parcels.confirm-arrival', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $cebuRider->id])->assertSessionHas('success');
        $this->riderTakes($order->id, $cebuRider);

        $this->assertSame('Delivered', $this->orderStatus($order->id));
    }

    public function test_E03_pick_up_with_cash_paid_at_the_counter(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1], self::LAGUNA_ADDRESS, 'Cash on Delivery', ['form' => ['fulfillment' => 'pickup']]);
        $order = $this->lastOrder($buyer);
        $this->assertEquals(0, $order->delivery_fee); // same town: no rider part to pay

        $this->sellerShips($order->id);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $order->id));
        $this->assertSame('Ready to Collect', $this->orderStatus($order->id));

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $order->pickup_code);
        $this->post(route('logistics.parcels.hand-to-buyer', $order->id), ['pickup_code' => $order->pickup_code])->assertSessionHas('success');
        $fresh = DB::table('orders')->find($order->id);
        $this->assertSame('Delivered', $fresh->status);
        $this->assertNotNull($fresh->buyer_received_at);
        $this->assertNotNull($fresh->cod_remitted_at);
    }

    public function test_E04_refused_parcel_goes_back_and_is_restocked(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1]);
        $order = $this->lastOrder($buyer);
        $this->assertSame(9, $this->stockOf($this->shoes));

        $this->sellerShips($order->id);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $order->id));
        $this->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $this->lagunaRider->id]);
        $this->riderTakes($order->id, $this->lagunaRider, 'Failed', ['failure_code' => \App\Services\DeliveryService::REFUSED_REASON]);

        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-back', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.return-to-seller', $order->id))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatus($order->id));
        $this->post(route('logistics.parcels.hand-back', $order->id))->assertSessionHas('success');

        $this->as($this->seller)->post(route('seller.order.restock', $order->id))->assertSessionHas('success');
        $this->assertSame(10, $this->stockOf($this->shoes));
        $this->assertSame(1, \App\Support\CodPolicy::status($buyer->id)['strikes']);
    }

    public function test_E05_two_failed_attempts_then_return(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1]);
        $order = $this->lastOrder($buyer);
        $this->sellerShips($order->id);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $order->id));
        $this->post(route('logistics.parcels.assign', $order->id), ['rider_id' => $this->lagunaRider->id]);

        $this->riderTakes($order->id, $this->lagunaRider, 'Failed', ['failure_code' => 'Buyer not available']);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-back', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.reschedule', $order->id), ['rider_id' => $this->lagunaRider->id])->assertSessionHas('success');
        $this->riderTakes($order->id, $this->lagunaRider, 'Failed', ['failure_code' => 'Buyer unreachable by phone']);

        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-back', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.reschedule', $order->id), ['rider_id' => $this->lagunaRider->id])->assertSessionHas('error');
        $this->post(route('logistics.parcels.return-to-seller', $order->id))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatus($order->id));
    }

    public function test_E06_three_sellers_one_voucher_separate_fees(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->center('Metro Manila (NCR)', 'Quezon City');
        $second = $this->sellerIn('Laguna', 'Santa Cruz');
        $third = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $p2 = $this->makeProduct($second, ['price' => 300]);
        $p3 = $this->makeProduct($third, ['price' => 200]);
        Voucher::create(['seller_id' => $this->seller->id, 'code' => 'KICKS100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 0, 'used_count' => 0, 'is_active' => true]);

        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1, $p2->id . ':0' => 1, $p3->id . ':0' => 1], self::QC_ADDRESS, 'Cash on Delivery', ['session' => ['applied_voucher' => 'KICKS100']])
            ->assertRedirect();

        $orders = DB::table('orders')->where('buyer_id', $buyer->id)->get()->keyBy(fn ($o) => DB::table('order_items')->where('order_id', $o->id)->value('seller_id'));
        $this->assertCount(3, $orders);
        $this->assertEquals(100, $orders[$this->seller->id]->discount_amount);
        $this->assertEquals(0, $orders[$second->id]->discount_amount);
        $this->assertEquals(120, $orders[$second->id]->delivery_fee); // Laguna → QC
        $this->assertEquals(50, $orders[$third->id]->delivery_fee);   // QC → QC
    }

    public function test_E07_closed_center_hands_its_area_to_the_region_hub(): void
    {
        $cavite = $this->center('Cavite', 'Imus City');
        $cavite->update(['is_active' => false]);
        $buyer = $this->buyerAt('5 Nueno Ave, Imus City, Cavite');

        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1], '5 Nueno Ave, Imus City, Cavite')->assertRedirect();

        $this->assertSame($this->laguna->id, (int) $this->lastOrder($buyer)->destination_center_id);
    }

    public function test_E08_suspended_seller_open_orders_cancelled_by_admin(): void
    {
        Voucher::create(['seller_id' => $this->seller->id, 'code' => 'KICKS50', 'discount_type' => 'fixed', 'discount_value' => 50, 'min_order_amount' => 0, 'used_count' => 0, 'is_active' => true]);
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 2], self::LAGUNA_ADDRESS, 'Cash on Delivery', ['session' => ['applied_voucher' => 'KICKS50']]);
        $order = $this->lastOrder($buyer);

        $this->flushSession();
        $this->actingAsAdmin()->post(route('admin.accounts.status', $this->seller->id), ['status' => 'Suspended']);
        $this->post(route('admin.order.cancel', $order->id), ['reason' => 'Seller suspended'])->assertSessionHas('success');

        $this->assertSame(10, $this->stockOf($this->shoes));
        $this->assertSame(0, (int) Voucher::where('code', 'KICKS50')->value('used_count'));
        $this->assertTrue($this->notifiedWith($buyer, 'Order Cancelled'));
    }
}
