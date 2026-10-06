<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * One order from the shop to a completed refund, through the real routes,
 * opening every role's pages at every step: views are most likely to break
 * on a status/role combination nobody looked at.
 */
class FullJourneyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private $buyer;
    private $seller;
    private $pickupRider;
    private $deliveryRider;
    private $logistics;
    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** Every page that shows this order, for every role, must open. */
    private function everyoneSeesTheOrder(string $step): void
    {
        $id = $this->orderId;

        foreach ([
            [$this->buyer, route('buyer.orders')],
            [$this->buyer, route('buyer.dashboard')],
            [$this->buyer, route('notifications')],
            [$this->seller, route('seller.orders')],
            [$this->seller, route('seller.order.details', $id)],
            [$this->seller, route('seller.dashboard')],
            [$this->seller, route('seller.reports')],
            [$this->logistics, route('logistics.parcels')],
            [$this->logistics, route('logistics.dashboard')],
        ] as [$user, $url]) {
            $this->flushSession();
            $response = $this->actingAsUser($user)->get($url);
            $this->assertSame(200, $response->status(), "{$step}: {$url} as {$user->role} returned {$response->status()}");
        }

        foreach ([route('admin.orders'), route('admin.order.details', $id), route('admin.reports'), route('admin.dashboard')] as $url) {
            $this->flushSession();
            $response = $this->actingAsAdmin()->get($url);
            $this->assertSame(200, $response->status(), "{$step}: {$url} as admin returned {$response->status()}");
        }

        // Riders only once one holds it.
        $rider = DB::table('orders')->where('id', $id)->value('rider_id');
        if ($rider) {
            $this->flushSession();
            $user = $rider == $this->pickupRider->id ? $this->pickupRider : $this->deliveryRider;
            $this->actingAsUser($user)->get(route('rider.delivery.details', $id))
                ->assertOk()
                // The order date, not "N/A".
                ->assertSee(\Illuminate\Support\Carbon::parse(DB::table('orders')->where('id', $id)->value('created_at'))->format('M j, Y'));
        }

        $this->flushSession();
    }

    private function orderNow(): string
    {
        return (string) DB::table('orders')->where('id', $this->orderId)->value('status');
    }

    public function test_an_order_goes_from_the_shop_to_a_completed_refund(): void
    {
        $this->buyer = $this->makeUser();
        $this->seller = $this->makeSeller('electronics', 'Journey Gadgets');
        $this->pickupRider = $this->makeRider();
        $this->deliveryRider = $this->makeRider();
        $this->logistics = $this->makeLogistics();
        $product = $this->makeProduct($this->seller, ['name' => 'Journey Earbuds', 'category' => 'Electronics', 'price' => 1500, 'stock' => 5]);

        // Shop → product → cart → checkout
        $this->get(route('products'))->assertOk()->assertSee('Journey Earbuds');
        $this->actingAsUser($this->buyer)->get(route('product.details', $product->id))->assertOk();

        $this->flushSession();
        $this->actingAsUser($this->buyer, ['cart' => ["{$product->id}:0" => 1]])->get(route('cart'))->assertOk()->assertSee('Journey Earbuds');
        $this->actingAsUser($this->buyer, ['cart' => ["{$product->id}:0" => 1]])->get(route('checkout'))->assertOk();
        $placed = $this->actingAsUser($this->buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->post(route('checkout.place'), [
                'address' => '1 Rizal St, Cebu City',
                'phone' => '09171234567',
                'payment' => 'Cash on Delivery',
            ]);

        $this->orderId = (int) DB::table('orders')->where('buyer_id', $this->buyer->id)->value('id');
        $this->assertGreaterThan(0, $this->orderId, 'checkout did not create an order');
        $placed->assertRedirect();

        $this->flushSession();
        $this->actingAsUser($this->buyer)->get(route('orders.success', $this->orderId))->assertOk();
        $this->everyoneSeesTheOrder('Pending');

        // Seller prepares it
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $this->orderId), ['status' => 'Processing']);
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $this->orderId), ['status' => 'Dropped Off']);
        $this->assertSame('Dropped Off', $this->orderNow());
        $this->everyoneSeesTheOrder('Dropped Off');

        // The seller brought it to the Sorting Center (head office staff here).
        $this->actingAsUser($this->logistics)->post(route('logistics.parcels.confirm-received', $this->orderId))->assertSessionHas('success');
        $this->everyoneSeesTheOrder('At Sorting Center');
        $this->actingAsUser($this->logistics)
            ->post(route('logistics.parcels.assign', $this->orderId), ['rider_id' => $this->deliveryRider->id])
            ->assertSessionHas('success');
        $this->everyoneSeesTheOrder('Assigned for Delivery');

        // Delivery rider → buyer
        $this->actingAsUser($this->deliveryRider)->post(route('rider.delivery.status', $this->orderId), ['status' => 'Out for Delivery']);
        $this->everyoneSeesTheOrder('Out for Delivery');
        $this->actingAsUser($this->deliveryRider)->post(route('rider.delivery.status', $this->orderId), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()]);
        $this->assertSame('Delivered', $this->orderNow());
        $this->everyoneSeesTheOrder('Delivered');

        // Rider pay pages count it
        $this->actingAsUser($this->deliveryRider)->get(route('rider.profit'))->assertOk();
        $this->flushSession();
        $this->actingAsUser($this->deliveryRider)->get(route('rider.deliveries.history'))->assertOk();
        $this->flushSession();

        // Buyer confirms and reviews
        $this->actingAsUser($this->buyer)->post(route('buyer.order.received', $this->orderId))->assertSessionHas('success');
        $this->flushSession();
        $this->actingAsUser($this->buyer)
            ->post(route('buyer.product.review', [$this->orderId, $product->id]), ['rating' => 4, 'review' => 'Good sound for the price.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_reviews', ['product_id' => $product->id, 'rating' => 4]);
        $this->flushSession();
        $this->get(route('product.details', $product->id))->assertOk()->assertSee('Good sound for the price.');
        $this->actingAsUser($this->seller)->get(route('seller.reviews'))->assertOk()->assertSee('Good sound for the price.');
        $this->everyoneSeesTheOrder('Received + reviewed');

        // Refund: request → approve → processing → completed
        $itemId = DB::table('order_items')->where('order_id', $this->orderId)->value('id');
        $this->actingAsUser($this->buyer)->post(route('buyer.return-refund.store', $this->orderId), [
            'order_item_id' => $itemId,
            'request_type' => 'Refund',
            'reason' => 'Damaged item',
            'message' => 'Left earbud does not charge.',
        ])->assertSessionHas('success');
        $requestId = DB::table('return_refund_requests')->where('order_id', $this->orderId)->value('id');
        $this->everyoneSeesTheOrder('Refund requested');
        $this->actingAsUser($this->seller)->get(route('seller.orders', ['tab' => 'returns']))->assertOk()->assertSee('Left earbud does not charge.');
        $this->flushSession();

        foreach (['approve', 'processing', 'complete'] as $step) {
            $this->actingAsUser($this->seller)->post(route("seller.return-refund.{$step}", $requestId))->assertSessionHas('success');
            $this->flushSession();
            $this->everyoneSeesTheOrder("Refund {$step}");
        }

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'completed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->buyer->id, 'title' => 'Refund Completed']);
    }

    /** The two ways an order ends early: the buyer cancels, or delivery fails and it goes back. */
    public function test_cancelled_and_returned_orders_still_open_everywhere(): void
    {
        $this->buyer = $this->makeUser();
        $this->seller = $this->makeSeller();
        $this->pickupRider = $this->makeRider();
        $this->deliveryRider = $this->makeRider();
        $this->logistics = $this->makeLogistics();
        $product = $this->makeProduct($this->seller);

        // Buyer cancels while it's still Pending (COD).
        $this->orderId = $this->makeOrder($this->buyer, $product, 'Pending', ['payment_method' => 'Cash on Delivery']);
        $this->actingAsUser($this->buyer)
            ->post(route('buyer.order.cancel', $this->orderId), ['cancel_reason' => 'Changed my mind'])
            ->assertSessionHas('success');
        $this->assertSame('Cancelled', $this->orderNow());
        $this->everyoneSeesTheOrder('Cancelled');

        // Delivery fails twice, then it goes back to the seller.
        $this->orderId = $this->makeOrder($this->buyer, $product, 'Out for Delivery', ['delivery_rider_id' => $this->deliveryRider->id]);
        $fail = ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Nobody home.'];
        $this->actingAsUser($this->deliveryRider)->post(route('rider.delivery.status', $this->orderId), $fail);
        $this->everyoneSeesTheOrder('Delivery Failed');

        $this->actingAsUser($this->logistics)->post(route('logistics.parcels.reschedule', $this->orderId), ['rider_id' => $this->deliveryRider->id]);
        $this->actingAsUser($this->deliveryRider)->post(route('rider.delivery.status', $this->orderId), ['status' => 'Out for Delivery']);
        $this->actingAsUser($this->deliveryRider)->post(route('rider.delivery.status', $this->orderId), $fail);
        $this->actingAsUser($this->logistics)->post(route('logistics.parcels.return-to-seller', $this->orderId))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderNow());
        $this->everyoneSeesTheOrder('Return Ready');

        $this->actingAsUser($this->logistics)->post(route('logistics.parcels.hand-back', $this->orderId))->assertSessionHas('success');
        $this->assertSame('Returned to Seller', $this->orderNow());
        $this->everyoneSeesTheOrder('Returned to Seller');
    }
}
