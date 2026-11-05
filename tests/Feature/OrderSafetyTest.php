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
            'new_password' => 'Another-pass-1',
            'new_password_confirmation' => 'Another-pass-1',
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
                'stock' => 5, 'description' => 'Still a shoe — sample product for the tests.', 'weight_kg' => 0.5,
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

    /** The hidden fields of the checkout page: what its Place Order button sends. */
    private function placeOrderForm($page): array
    {
        preg_match_all('/<input type="hidden" name="(expected_total|checkout_source|checkout_items|_submit_token)"[^>]*value="([^"]*)"/', $page->getContent(), $found);

        return array_combine($found[1], $found[2]) + ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'Cash on Delivery'];
    }

    public function test_buy_now_in_another_tab_does_not_replace_the_cart_being_checked_out(): void
    {
        $seller = $this->makeSeller();
        $cartItem = $this->makeProduct($seller, ['name' => 'Cart Item', 'price' => 500]);
        $other = $this->makeProduct($seller, ['name' => 'Other Tab Item', 'price' => 500]);

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$cartItem->id}:0" => 1]]);
        $cartTab = $this->placeOrderForm($this->get(route('checkout'))->assertOk());
        $this->post(route('buy.now', $other->id), ['quantity' => 1]);

        $this->post(route('checkout.place'), $cartTab)->assertRedirect();

        $this->assertSame(['Cart Item'], \Illuminate\Support\Facades\DB::table('order_items')->pluck('product_name')->all());
        $this->assertSame(10, (int) $other->fresh()->stock);

        // The Buy Now tab is still good, and places only its own item.
        $this->post(route('checkout.place'), $this->placeOrderForm($this->get(route('checkout', ['buy_now' => 1]))))->assertRedirect();
        $this->assertSame(['Cart Item', 'Other Tab Item'], \Illuminate\Support\Facades\DB::table('order_items')->orderBy('id')->pluck('product_name')->all());
    }

    public function test_a_second_buy_now_stops_the_first_tab_from_ordering_it(): void
    {
        $seller = $this->makeSeller();
        $first = $this->makeProduct($seller, ['price' => 500]);
        $second = $this->makeProduct($seller, ['price' => 500]);

        $this->actingAsUser($this->makeUser());
        $this->post(route('buy.now', $first->id), ['quantity' => 1]);
        $firstTab = $this->placeOrderForm($this->get(route('checkout', ['buy_now' => 1])));
        $this->post(route('buy.now', $second->id), ['quantity' => 1]);

        $this->post(route('checkout.place'), $firstTab)
            ->assertRedirect(route('checkout', ['buy_now' => 1]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_cart_changed_in_another_tab_is_reviewed_before_ordering(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 500, 'stock' => 10]);

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$product->id}:0" => 1]]);
        $stale = $this->placeOrderForm($this->get(route('checkout')));
        $this->post(route('cart.update', "{$product->id}:0"), ['action' => 'set', 'quantity' => 2]);

        // Left out so only the items can refuse it, not the price check.
        $this->post(route('checkout.place'), array_diff_key($stale, ['expected_total' => 1]))
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        // The reviewed page goes through.
        $this->post(route('checkout.place'), $this->placeOrderForm($this->get(route('checkout'))))->assertRedirect();
        $this->assertSame(2, (int) \Illuminate\Support\Facades\DB::table('order_items')->value('quantity'));
    }

    public function test_a_buy_now_form_with_no_buy_now_left_orders_nothing(): void
    {
        $seller = $this->makeSeller();
        $item = $this->makeProduct($seller);
        $inCart = $this->makeProduct($seller);

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$inCart->id}:0" => 1]]);
        $this->post(route('buy.now', $item->id), ['quantity' => 1]);
        $buyNowTab = $this->placeOrderForm($this->get(route('checkout', ['buy_now' => 1])));
        $this->get(route('checkout')); // Opening the cart's checkout drops the Buy Now.

        $this->post(route('checkout.place'), $buyNowTab)->assertRedirect(route('checkout'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_double_clicking_buy_now_place_order_says_already_placed(): void
    {
        $item = $this->makeProduct($this->makeSeller());

        $this->actingAsUser($this->makeUser());
        $this->post(route('buy.now', $item->id), ['quantity' => 1]);
        $form = $this->placeOrderForm($this->get(route('checkout', ['buy_now' => 1])));

        $this->post(route('checkout.place'), $form)->assertRedirect();
        $this->post(route('checkout.place'), $form)
            ->assertRedirect(route('buyer.orders'))
            ->assertSessionHas('success', 'Your order was already placed.');

        $this->assertDatabaseCount('orders', 1);
    }

    private function flashSale(\App\Models\Product $product, int $units): \App\Models\FlashSale
    {
        return \App\Models\FlashSale::create([
            'seller_id' => $product->seller_id, 'product_id' => $product->id,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
            'flash_price' => 500, 'quantity' => $units, 'sold' => 0,
        ]);
    }

    private function orderFlashUnits(\App\Models\User $buyer, \App\Models\Product $product, int $quantity, string $payment = 'Cash on Delivery'): int
    {
        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => $quantity]])
            ->post(route('checkout.place'), ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => $payment])
            ->assertRedirect();

        return (int) DB::table('orders')->where('buyer_id', $buyer->id)->latest('id')->value('id');
    }

    public function test_cancelling_gives_the_flash_units_back_for_the_next_buyer(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 10]);
        $flash = $this->flashSale($product, 3);
        $buyer = $this->makeUser();

        $orderId = $this->orderFlashUnits($buyer, $product, 3);
        $this->assertSame(3, (int) $flash->fresh()->sold);

        $this->actingAsUser($buyer)->post(route('buyer.order.cancel', $orderId), ['cancel_reason' => 'Changed my mind']);
        $this->assertSame('Cancelled', $this->orderStatus($orderId));
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(0, (int) $flash->fresh()->sold);

        // Someone else gets the flash price again.
        $next = $this->orderFlashUnits($this->makeUser(), $product, 1);
        $this->assertEquals(500, DB::table('order_items')->where('order_id', $next)->value('price'));
    }

    public function test_an_unpaid_order_running_out_of_time_gives_the_flash_units_back(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 10]);
        $flash = $this->flashSale($product, 2);

        $this->orderFlashUnits($this->makeUser(), $product, 2, 'GCash');
        $this->assertSame(2, (int) $flash->fresh()->sold);

        DB::table('orders')->update(['pay_by' => now()->subMinute()]);
        \App\Support\Payments::sweep();

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(0, (int) $flash->fresh()->sold);
    }

    public function test_only_the_flash_part_of_a_line_goes_back_to_the_sale(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 10]);
        $flash = $this->flashSale($product, 2);
        $flash->update(['sold' => 1]); // Someone else's unit stays sold.

        $orderId = $this->orderFlashUnits($this->makeUser(), $product, 3); // 1 flash + 2 regular
        $this->assertSame(2, (int) $flash->fresh()->sold);

        \App\Support\OrderStock::cancelled($orderId, 'Pending');
        $this->assertSame(1, (int) $flash->fresh()->sold);

        // A second call can't give them back twice.
        \App\Support\OrderStock::cancelled($orderId, 'Pending');
        $this->assertSame(1, (int) $flash->fresh()->sold);
    }

    public function test_flash_units_stay_sold_once_the_parcel_left_the_seller(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 10]);
        $flash = $this->flashSale($product, 2);

        $orderId = $this->orderFlashUnits($this->makeUser(), $product, 2);
        \App\Support\OrderStock::cancelled($orderId, 'Picked Up');

        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(2, (int) $flash->fresh()->sold);
    }

    public function test_cancelling_an_older_order_without_a_saved_flash_sale_still_works(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 10]);
        $flash = $this->flashSale($product, 2);

        $orderId = $this->orderFlashUnits($this->makeUser(), $product, 2);
        DB::table('order_items')->where('order_id', $orderId)->update(['flash_id' => null]);

        \App\Support\OrderStock::cancelled($orderId, 'Pending');

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(2, (int) $flash->fresh()->sold);
    }

    /** A product of at most 2 per buyer, in two colors. */
    private function limitedProduct(): array
    {
        $product = $this->makeProduct($this->makeSeller(), ['purchase_limit' => 2]);

        return [$product, $this->addVariation($product, 'Red', 10), $this->addVariation($product, 'Blue', 10)];
    }

    public function test_the_purchase_limit_counts_every_option_when_adding_to_the_cart(): void
    {
        [$product, $red, $blue] = $this->limitedProduct();

        $this->actingAsUser($this->makeUser());
        $this->post(route('cart.add', $product->id), ['quantity' => 1, 'variation_id' => $red->id])->assertSessionHas('success');
        $this->post(route('cart.add', $product->id), ['quantity' => 1, 'variation_id' => $blue->id])->assertSessionHas('success');
        $this->post(route('cart.add', $product->id), ['quantity' => 1, 'variation_id' => $blue->id])->assertSessionHas('error');

        $this->assertSame(["{$product->id}:{$red->id}" => 1, "{$product->id}:{$blue->id}" => 1], session('cart'));
    }

    public function test_the_purchase_limit_counts_every_option_when_changing_the_quantity(): void
    {
        [$product, $red, $blue] = $this->limitedProduct();
        $redKey = "{$product->id}:{$red->id}";
        $blueKey = "{$product->id}:{$blue->id}";

        $this->actingAsUser($this->makeUser(), ['cart' => [$redKey => 1, $blueKey => 1]]);

        $this->post(route('cart.update', $blueKey), ['action' => 'increase'])->assertSessionHas('error');
        $this->post(route('cart.update', $blueKey), ['action' => 'set', 'quantity' => 2])->assertSessionHas('error');
        $this->assertSame(1, session('cart')[$blueKey]);

        // Lowering one option makes room for the other.
        $this->post(route('cart.update', $redKey), ['action' => 'decrease']);
        $this->post(route('cart.update', $blueKey), ['action' => 'set', 'quantity' => 2])->assertSessionMissing('error');
        $this->assertSame([$blueKey => 2], session('cart'));
    }

    public function test_the_purchase_limit_counts_every_option_at_checkout(): void
    {
        [$product, $red, $blue] = $this->limitedProduct();
        $form = ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'Cash on Delivery'];

        // A cart from before the limit was set, or a selection sent straight to Place Order.
        $this->actingAsUser($this->makeUser(), ['cart' => ["{$product->id}:{$red->id}" => 2, "{$product->id}:{$blue->id}" => 2]])
            ->post(route('checkout.place'), $form)
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        // Exactly the limit across two options goes through.
        $this->actingAsUser($this->makeUser(), ['cart' => ["{$product->id}:{$red->id}" => 1, "{$product->id}:{$blue->id}" => 1]])
            ->post(route('checkout.place'), $form)
            ->assertRedirect();
        $this->assertSame(2, (int) DB::table('order_items')->sum('quantity'));
    }

    public function test_the_purchase_limit_still_counts_earlier_orders_of_any_option(): void
    {
        [$product, $red, $blue] = $this->limitedProduct();
        $buyer = $this->makeUser();
        $form = ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'Cash on Delivery'];

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:{$red->id}" => 2]])->post(route('checkout.place'), $form)->assertRedirect();

        $this->post(route('cart.add', $product->id), ['quantity' => 1, 'variation_id' => $blue->id])->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 1);
    }
}
