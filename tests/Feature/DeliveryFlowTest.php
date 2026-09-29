<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * An order's trip: seller → pickup rider → Sorting Center (logistics)
 * → delivery rider → buyer.
 */
class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function notified(int $userId, string $title): void
    {
        $this->assertDatabaseHas('notifications', ['user_id' => $userId, 'title' => $title]);
    }

    public function test_an_order_travels_from_seller_to_buyer(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $pickupRider = $this->makeRider();
        $deliveryRider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($buyer, $this->makeProduct($seller));

        // Seller
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Ready for Pickup']);
        $this->assertSame('Ready for Pickup', $this->orderStatus($order));

        // Pickup rider sees it, claims it, picks it up
        $this->actingAsUser($pickupRider)->get(route('rider.dashboard'))->assertOk()->assertSee('#' . $order);
        $this->actingAsUser($pickupRider)->post(route('rider.delivery.claim', $order))->assertSessionHas('success');
        $this->assertSame('Assigned', $this->orderStatus($order));
        $this->notified($buyer->id, 'Rider Assigned');

        $this->actingAsUser($pickupRider)
            ->post(route('rider.delivery.confirm-pickup', $order))
            ->assertSessionHas('success', 'Pickup confirmed! Please bring the parcel to the Sorting Center.');
        $this->assertSame('Picked Up', $this->orderStatus($order));
        $this->notified($logistics->id, 'Parcel En Route');

        // Sorting Center receives it and hands it to the delivery rider
        $this->actingAsUser($logistics)->get(route('logistics.parcels'))->assertOk()->assertSee('#' . $order);
        $this->actingAsUser($logistics)->post(route('logistics.parcels.confirm-received', $order))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', $this->orderStatus($order));

        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.assign', $order), ['rider_id' => $deliveryRider->id])
            ->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($order));
        $this->notified($deliveryRider->id, 'New Delivery Assignment');

        // The pickup rider no longer owns the final leg
        $this->actingAsUser($pickupRider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery'])
            ->assertSessionHas('error');

        // Delivery rider delivers
        $this->actingAsUser($deliveryRider)->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery']);
        $this->actingAsUser($deliveryRider)->post(route('rider.delivery.status', $order), ['status' => 'Delivered']);
        $this->assertSame('Delivered', $this->orderStatus($order));
        $this->notified($buyer->id, 'Order Delivered');
        $this->notified($seller->id, 'Order Delivered');

        // Buyer confirms
        $this->actingAsUser($buyer)->post(route('buyer.order.received', $order))->assertSessionHas('success');
        $this->assertNotNull(DB::table('orders')->where('id', $order)->value('buyer_received_at'));
        $this->notified($seller->id, 'Order Received by Buyer');
    }

    public function test_seller_cannot_skip_steps_or_touch_other_orders(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($seller));

        $this->actingAsUser($seller)
            ->post(route('seller.order.status', $order), ['status' => 'Ready for Pickup'])
            ->assertSessionHas('error');
        $this->assertSame('Pending', $this->orderStatus($order));

        $this->actingAsUser($this->makeSeller('electronics'))
            ->post(route('seller.order.status', $order), ['status' => 'Processing'])
            ->assertSessionHas('error');
        $this->assertSame('Pending', $this->orderStatus($order));
    }

    public function test_only_one_rider_can_claim_an_order(): void
    {
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'Ready for Pickup');
        $first = $this->makeRider();
        $second = $this->makeRider();

        $this->actingAsUser($first)->post(route('rider.delivery.claim', $order))->assertSessionHas('success');
        $this->actingAsUser($second)->post(route('rider.delivery.claim', $order))->assertSessionHas('error');

        $this->assertSame($first->id, (int) DB::table('orders')->where('id', $order)->value('rider_id'));
    }

    public function test_parcels_only_go_to_active_approved_riders(): void
    {
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'At Sorting Center');

        $pending = $this->makeRider('Pending Verification');
        $suspended = $this->makeRider();
        $suspended->forceFill(['status' => 'Suspended'])->save();

        foreach ([$pending, $suspended] as $rider) {
            $this->actingAsUser($logistics)
                ->post(route('logistics.parcels.assign', $order), ['rider_id' => $rider->id])
                ->assertSessionHas('error');
        }

        $this->assertSame('At Sorting Center', $this->orderStatus($order));
    }

    public function test_failed_delivery_needs_a_reason_and_is_retried_at_most_twice(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $rider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($buyer, $this->makeProduct($seller), 'Out for Delivery', ['delivery_rider_id' => $rider->id]);

        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Delivery Failed'])
            ->assertSessionHas('error');
        $this->assertSame('Out for Delivery', $this->orderStatus($order));

        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Gate locked, no answer.']);
        $this->assertSame('Delivery Failed', $this->orderStatus($order));
        $this->notified($buyer->id, 'Delivery Attempt Failed');

        // First retry is allowed...
        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('success');

        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery']);
        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Still nobody home.']);

        // ...but after two attempts it goes back to the seller.
        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('error');

        $this->actingAsUser($logistics)->post(route('logistics.parcels.return-to-seller', $order))->assertSessionHas('success');
        $this->assertSame('Returned to Seller', $this->orderStatus($order));
        $this->notified($seller->id, 'Order Returned to You');
    }

    public function test_refused_parcel_cannot_be_redelivered(): void
    {
        $rider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'Out for Delivery', ['delivery_rider_id' => $rider->id]);

        $refused = \App\Http\Controllers\RiderController::REFUSED_REASON;

        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Delivery Failed', 'failure_code' => $refused]);
        $this->assertNotNull(DB::table('orders')->where('id', $order)->value('buyer_refused_at'));

        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('error');
    }

    public function test_logistics_and_rider_pages_load_and_are_role_locked(): void
    {
        $logistics = $this->makeLogistics();
        $rider = $this->makeRider();
        $buyer = $this->makeUser();

        foreach (['logistics.dashboard', 'logistics.parcels', 'logistics.riders', 'logistics.profile', 'logistics.notifications'] as $route) {
            $this->actingAsUser($logistics)->get(route($route))->assertOk();
            $this->actingAsUser($buyer)->get(route($route))->assertRedirect(route('login'));
        }

        foreach (['rider.dashboard', 'rider.deliveries', 'rider.deliveries.history', 'rider.profile', 'rider.profit', 'rider.notifications'] as $route) {
            $this->actingAsUser($rider)->get(route($route))->assertOk();
            $this->actingAsUser($buyer)->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_logistics_approves_a_rider(): void
    {
        $logistics = $this->makeLogistics();
        $rider = $this->makeRider('Pending Verification');
        $applicationId = DB::table('rider_applications')->where('user_id', $rider->id)->value('id');

        $this->actingAsUser($logistics)
            ->post(route('logistics.riders.approve', $applicationId))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rider_applications', ['id' => $applicationId, 'status' => 'Approved']);
    }
}
