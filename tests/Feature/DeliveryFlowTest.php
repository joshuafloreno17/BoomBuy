<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * An order's trip: seller drops it at the Sorting Center (logistics) →
 * delivery rider → buyer. The routing between Sorting Centers is covered
 * in SortingCenterJourneyTest.
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
        $otherRider = $this->makeRider();
        $deliveryRider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $order = $this->makeOrder($buyer, $this->makeProduct($seller));

        // Seller packs it and drops it at the Sorting Center themselves
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Dropped Off']);
        $this->assertSame('Dropped Off', $this->orderStatus($order));
        $this->notified($buyer->id, 'Order Shipped');
        $this->notified($logistics->id, 'Parcel Dropped Off');

        // Sorting Center receives it and hands it to the delivery rider
        $this->actingAsUser($logistics)->get(route('logistics.parcels'))->assertOk()->assertSee('#' . $order);
        $this->actingAsUser($logistics)->post(route('logistics.parcels.confirm-received', $order))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', $this->orderStatus($order));

        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.assign', $order), ['rider_id' => $deliveryRider->id])
            ->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($order));
        $this->notified($deliveryRider->id, 'New Delivery Assignment');

        // Another rider can't take over the delivery
        $this->actingAsUser($otherRider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery'])
            ->assertSessionHas('error');

        // Delivery rider delivers
        $this->actingAsUser($deliveryRider)->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery']);
        $this->actingAsUser($deliveryRider)->post(route('rider.delivery.status', $order), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()]);
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
            ->post(route('seller.order.status', $order), ['status' => 'Dropped Off'])
            ->assertSessionHas('error');
        $this->assertSame('Pending', $this->orderStatus($order));

        $this->actingAsUser($this->makeSeller('electronics'))
            ->post(route('seller.order.status', $order), ['status' => 'Processing'])
            ->assertSessionHas('error');
        $this->assertSame('Pending', $this->orderStatus($order));
    }

    public function test_riders_only_see_and_update_their_own_deliveries(): void
    {
        $mine = $this->makeRider();
        $other = $this->makeRider();
        $order = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'Assigned for Delivery', [
            'delivery_rider_id' => $mine->id,
        ]);

        $this->actingAsUser($mine)->get(route('rider.dashboard'))->assertOk()->assertSee('#' . $order);
        $this->actingAsUser($mine)->get(route('rider.delivery.details', $order))->assertOk();

        $this->actingAsUser($other)->get(route('rider.dashboard'))->assertOk()->assertDontSee('Order #' . $order);
        $this->actingAsUser($other)->get(route('rider.delivery.details', $order))->assertNotFound();
        $this->actingAsUser($other)
            ->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery'])
            ->assertSessionHas('error');
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

        // Not while the rider still has it...
        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('error');
        $this->actingAsUser($logistics)->post(route('logistics.parcels.confirm-back', $order))->assertSessionHas('success');

        // First retry is allowed...
        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('success');

        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery']);
        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $order), ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Still nobody home.']);

        // ...but after two attempts it goes back to the seller.
        $this->actingAsUser($logistics)->post(route('logistics.parcels.confirm-back', $order))->assertSessionHas('success');
        $this->actingAsUser($logistics)
            ->post(route('logistics.parcels.reschedule', $order), ['rider_id' => $rider->id])
            ->assertSessionHas('error');

        $this->actingAsUser($logistics)->post(route('logistics.parcels.return-to-seller', $order))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatus($order));
        $this->notified($seller->id, 'Collect Your Returned Parcel');

        // The seller comes for it at the Sorting Center.
        $this->actingAsUser($logistics)->post(route('logistics.parcels.hand-back', $order))->assertSessionHas('success');
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

    public function test_logistics_finds_a_parcel_by_order_number_wherever_it_is(): void
    {
        $logistics = $this->makeLogistics();
        $product = $this->makeProduct($this->makeSeller());

        $atCenter = $this->makeOrder($this->makeUser(), $product, 'At Sorting Center');
        $withSeller = $this->makeOrder($this->makeUser(), $product, 'Processing');

        $this->actingAsUser($logistics)
            ->get(route('logistics.parcels', ['q' => '#' . $atCenter]))
            ->assertOk()
            ->assertSee('Order #' . $atCenter)
            ->assertDontSee('Order #' . $withSeller);

        // Not in any logistics list yet — still shown, with where it is.
        $this->actingAsUser($logistics)
            ->get(route('logistics.parcels', ['q' => $withSeller]))
            ->assertSee('Order #' . $withSeller)
            ->assertSee('Still with the seller');
    }

    public function test_riders_page_filters_by_status_and_shows_current_load(): void
    {
        $logistics = $this->makeLogistics();
        $approved = $this->makeRider('Approved');
        $pending = $this->makeRider('Pending Verification');

        $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'Out for Delivery', [
            'delivery_rider_id' => $approved->id,
        ]);

        // Opens on pending applications while there are any.
        $this->actingAsUser($logistics)
            ->get(route('logistics.riders'))
            ->assertOk()
            ->assertSee($pending->email)
            ->assertDontSee($approved->email);

        $this->actingAsUser($logistics)
            ->get(route('logistics.riders', ['status' => 'approved']))
            ->assertSee($approved->email)
            ->assertSee('1 delivery(ies)');
    }
}
