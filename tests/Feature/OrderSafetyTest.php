<?php

namespace Tests\Feature;

use App\Support\LoginGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class OrderSafetyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_seller_cannot_cancel_or_rewind_an_order_that_left_them(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);

        foreach (['Pickup Assigned', 'Picked Up', 'Dropped Off', 'In Transit', 'At Sorting Center', 'Sorted', 'Out for Delivery', 'Delivered', 'Completed', 'Cancelled'] as $status) {
            $orderId = $this->makeOrder($this->makeUser(), $product, $status);

            $this->actingAsUser($seller)
                ->post(route('seller.order.status', $orderId), ['status' => 'Cancelled', 'cancellation_reason' => 'Oops'])
                ->assertSessionHas('error');

            $this->actingAsUser($seller)
                ->post(route('seller.order.status', $orderId), ['status' => 'Confirmed'])
                ->assertSessionHas('error');

            $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => $status]);
        }
    }

    public function test_abandoned_buy_now_does_not_replace_the_cart_at_checkout(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $buyNowProduct = $this->makeProduct($seller, ['name' => 'Buy Now Sneaker']);
        $cartProduct = $this->makeProduct($seller, ['name' => 'Cart Sandal']);

        $session = [
            'buy_now' => ["{$buyNowProduct->id}:0" => 1],
            'cart' => ["{$cartProduct->id}:0" => 1],
        ];

        // Coming from the cart's Checkout button: the old Buy Now is dropped.
        $this->actingAsUser($buyer, $session)
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('Cart Sandal')
            ->assertDontSee('Buy Now Sneaker');

        $this->assertNull(session('buy_now'));

        $this->post('/checkout/place-order', [
            'address' => '1 Rizal St, Cebu City',
            'phone' => '09171234567',
            'payment' => 'Cash on Delivery',
        ]);

        $this->assertDatabaseHas('order_items', ['product_id' => $cartProduct->id]);
        $this->assertDatabaseMissing('order_items', ['product_id' => $buyNowProduct->id]);
    }

    public function test_buy_now_still_checks_out_just_that_item(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['name' => 'Instant Boot']);

        $this->actingAsUser($buyer)
            ->post(route('buy.now', $product->id), ['quantity' => 1])
            ->assertRedirect(route('checkout', ['buy_now' => 1]));

        $this->get(route('checkout', ['buy_now' => 1]))->assertOk()->assertSee('Instant Boot');
    }

    public function test_checkout_rejects_a_phone_that_is_not_a_number(): void
    {
        $product = $this->makeProduct($this->makeSeller());

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$product->id}:0" => 1]])
            ->post('/checkout/place-order', ['address' => '1 Rizal St', 'phone' => 'call me maybe', 'payment' => 'Cash on Delivery'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_password_reset_signs_out_remembered_browsers(): void
    {
        $buyer = $this->makeUser();
        DB::table('users')->where('id', $buyer->id)->update(['remember_token' => hash('sha256', 'old-device')]);

        $this->withSession([
            'password_reset_user_id' => $buyer->id,
            'password_reset_otp' => '123456',
            'password_reset_expires_at' => now()->addMinutes(10),
        ])->post(route('password.update'), [
            'otp_code' => '123456',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('login'));

        $this->assertNull(DB::table('users')->where('id', $buyer->id)->value('remember_token'));
        $this->assertNull(LoginGate::rememberedUser($buyer->id . '|old-device'));
    }

    public function test_changing_password_in_the_profile_signs_out_other_devices(): void
    {
        $buyer = $this->makeUser();
        DB::table('users')->where('id', $buyer->id)->update(['remember_token' => hash('sha256', 'other-phone')]);

        $this->actingAsUser($buyer)->post(route('buyer.profile.password'), [
            'current_password' => 'password123',
            'new_password' => 'another-pass-1',
            'new_password_confirmation' => 'another-pass-1',
        ])->assertSessionHas('success');

        $this->assertNull(LoginGate::rememberedUser($buyer->id . '|other-phone'));
    }

    public function test_suspended_sellers_products_leave_the_shop(): void
    {
        $seller = $this->makeSeller('shoes', 'Gone Shop');
        $product = $this->makeProduct($seller, ['name' => 'Hidden Loafer']);

        $this->get(route('products'))->assertSee('Hidden Loafer');

        $this->actingAsAdmin()->post(route('admin.accounts.status', $seller->id), ['status' => 'Suspended'])
            ->assertSessionHas('success');

        $this->flushSession();

        $this->get(route('products'))->assertDontSee('Hidden Loafer');
        $this->get(route('product.details', $product->id))->assertNotFound();
        $this->get(route('shop.seller', $seller->id))->assertNotFound();

        $this->actingAsUser($this->makeUser())
            ->postJson(route('cart.add', $product->id), ['quantity' => 1])
            ->assertStatus(422);
    }

    public function test_deactivated_rider_hands_their_parcels_back(): void
    {
        $rider = $this->makeRider();
        $logistics = $this->makeLogistics();
        $product = $this->makeProduct($this->makeSeller());

        $assigned = $this->makeOrder($this->makeUser(), $product, 'Assigned for Delivery', ['delivery_rider_id' => $rider->id]);
        $delivery = $this->makeOrder($this->makeUser(), $product, 'Out for Delivery', ['delivery_rider_id' => $rider->id]);

        $this->actingAsUser($logistics)
            ->post(route('logistics.riders.toggle-status', $rider->id), ['status' => 'Deactivated'])
            ->assertSessionHas('success');

        foreach ([$assigned, $delivery] as $orderId) {
            $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Sorted', 'delivery_rider_id' => null]);
        }
        $this->assertDatabaseHas('notifications', ['user_id' => $logistics->id, 'title' => 'Parcels Need a New Rider']);
    }

    public function test_delivered_at_is_stamped_and_drives_delivered_today(): void
    {
        $rider = $this->makeRider();
        $logistics = $this->makeLogistics();

        $orderId = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller()), 'Out for Delivery', [
            'delivery_rider_id' => $rider->id,
        ]);

        $this->actingAsUser($rider)
            ->post(route('rider.delivery.status', $orderId), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])
            ->assertSessionHas('success');

        $this->assertNotNull(DB::table('orders')->where('id', $orderId)->value('delivered_at'));

        // Delivered yesterday, buyer confirms today: not "delivered today".
        $old = $this->makeOrder($this->makeUser(), $this->makeProduct($this->makeSeller('electronics', 'Other')), 'Delivered', [
            'delivered_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $this->flushSession();

        $this->actingAsUser($logistics)
            ->get(route('logistics.dashboard'))
            ->assertViewHas('deliveredToday', 1);

        $this->assertNotNull($old);
    }

    public function test_seller_cannot_add_the_same_option_twice(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        $this->addVariation($product, 'Red');

        $this->actingAsUser($seller)
            ->post(route('seller.products.variations.store', $product->id), [
                'variation_type' => 'color', 'variation_value' => 'Red', 'stock' => 3,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, $product->variations()->count());
    }

    public function test_lowering_the_price_cannot_make_an_option_free(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 500]);
        $variation = $this->addVariation($product, 'Red');
        $variation->update(['price_adjustment' => -300]);

        $this->actingAsUser($seller)
            ->put(route('seller.products.update', $product->id), [
                'name' => $product->name, 'category' => 'shoes', 'price' => 250,
                'stock' => 5, 'description' => 'Still a shoe.',
            ])
            ->assertSessionHas('error');

        $this->assertEquals(500, $product->fresh()->price);
    }

    public function test_an_application_is_only_decided_once(): void
    {
        $seller = $this->makeSeller();
        $applicationId = DB::table('seller_applications')->where('user_id', $seller->id)->value('id');

        // Already approved — a stray Reject must not lock the seller out.
        $this->actingAsAdmin()
            ->post(route('admin.applications.reject', ['seller', $applicationId]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('seller_applications', ['id' => $applicationId, 'status' => 'Approved']);
    }

    public function test_cart_marks_unavailable_items_and_leaves_them_out_of_checkout(): void
    {
        $seller = $this->makeSeller();
        $archived = $this->makeProduct($seller, ['name' => 'Retired Runner', 'is_archived' => true]);
        $live = $this->makeProduct($seller, ['name' => 'Live Runner']);

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$archived->id}:0" => 1, "{$live->id}:0" => 1]])
            ->get(route('cart'))
            ->assertOk()
            ->assertSee('No longer available')
            ->assertSee('data-cart-key="' . $live->id . ':0"', false);
    }
}
