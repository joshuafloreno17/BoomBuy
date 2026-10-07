<?php

namespace Tests\Feature\Scenarios;

use App\Models\BuyerAddress;
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

/** Test Plan → "Buyer" (B01–B27). */
class BuyerScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->center('Metro Manila (NCR)', 'Quezon City');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Santa Cruz Kicks');
        $this->shoes = $this->makeProduct($this->seller, ['name' => 'Canvas Sneakers', 'price' => 500, 'stock' => 10]);
    }

    private function key(Product $product, int $variationId = 0): string
    {
        return $product->id . ':' . $variationId;
    }

    /** A delivered order of $product for $buyer, received $receivedDaysAgo days ago (null = not yet). */
    private function deliveredOrder(User $buyer, Product $product, ?int $receivedDaysAgo = 1): int
    {
        $orderId = $this->makeOrder($buyer, $product, 'Delivered', [
            'delivered_at' => now()->subDays(($receivedDaysAgo ?? 0) + 1),
            'buyer_received_at' => $receivedDaysAgo === null ? null : now()->subDays($receivedDaysAgo),
        ]);

        return $orderId;
    }

    // ---------------- Account ----------------

    public function test_B01_dashboard_shows_active_orders_buy_again_and_discover(): void
    {
        $buyer = $this->buyerAt();
        foreach (range(1, 4) as $i) {
            $this->makeOrder($buyer, $this->shoes, 'Processing');
        }
        $this->makeOrder($buyer, $this->makeProduct($this->seller, ['name' => 'Bought Before Boots']), 'Delivered');

        $this->actingAsUser($buyer)->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertViewHas('activeOrders', fn ($orders) => count($orders) === 3 && $orders[0]['step'] === 2)
            ->assertViewHas('buyAgain', fn ($cards) => collect($cards)->pluck('name')->contains('Bought Before Boots'))
            ->assertViewHas('discover', fn ($d) => isset($d['latest'], $d['top'], $d['budget']));
    }

    public function test_B02_profile_needs_a_town_and_a_phone_nobody_else_uses(): void
    {
        $buyer = $this->buyerAt();
        $other = $this->makeUser('buyer');

        $this->actingAsUser($buyer)->post(route('buyer.profile.update'), ['name' => 'Ana', 'phone' => '09171112222', 'address' => 'Somewhere far'])
            ->assertSessionHas('error', 'Pick your province and city/municipality for the address.');

        $this->actingAsUser($buyer)->post(route('buyer.profile.update'), ['name' => 'Ana', 'phone' => $other->phone, 'address' => self::QC_ADDRESS])
            ->assertSessionHas('error', 'This phone number is already registered.');

        $this->actingAsUser($buyer)->post(route('buyer.profile.update'), ['name' => 'Ana', 'phone' => '09171112222', 'address' => self::CEBU_ADDRESS])
            ->assertSessionHas('success');

        $fresh = $buyer->fresh();
        $this->assertSame('Cebu', $fresh->province);
        $this->assertSame('Cebu City', $fresh->city_municipality);
    }

    public function test_B03_address_book_keeps_ten_and_always_one_default(): void
    {
        $buyer = $this->buyerAt(); // already has 1 (default)

        foreach (range(2, 10) as $i) {
            $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['label' => 'A' . $i, 'phone' => '09171234567', 'address' => self::QC_ADDRESS])
                ->assertSessionHas('success');
        }

        $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['phone' => '09171234567', 'address' => self::QC_ADDRESS])
            ->assertSessionHas('error', 'You can save up to 10 addresses. Delete one first.');

        $second = BuyerAddress::where('user_id', $buyer->id)->where('label', 'A2')->first();
        $this->actingAsUser($buyer)->post(route('buyer.addresses.default', $second->id));
        $this->assertSame(1, BuyerAddress::where('user_id', $buyer->id)->where('is_default', true)->count());
        $this->assertTrue($second->fresh()->is_default);

        $this->actingAsUser($buyer)->delete(route('buyer.addresses.delete', $second->id));
        $this->assertSame(1, BuyerAddress::where('user_id', $buyer->id)->where('is_default', true)->count());
    }

    public function test_B04_password_and_photo_rules(): void
    {
        $buyer = $this->buyerAt();
        $go = fn (array $data) => $this->actingAsUser($buyer)->post(route('buyer.profile.password'), $data);

        $go(['current_password' => 'nope', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('error', 'Current password is incorrect.');
        $go(['current_password' => 'password123', 'new_password' => 'short', 'new_password_confirmation' => 'short'])
            ->assertSessionHas('error', 'New password must be at least 8 characters.');
        $go(['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass999'])
            ->assertSessionHas('error', 'New passwords do not match.');
        $go(['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('success');

        $this->actingAsUser($buyer)->post(route('buyer.profile.photo'), ['profile_photo' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('profile_photo');
    }

    // ---------------- Cart ----------------

    public function test_B05_product_with_options_needs_one_picked(): void
    {
        $buyer = $this->buyerAt();
        $shirt = $this->makeProduct($this->seller, ['name' => 'Tee']);
        $this->addVariation($shirt, 'Red');

        $this->actingAsUser($buyer)->post(route('cart.add', $shirt->id))
            ->assertSessionHas('error', 'Please choose a color for Tee first.');
        $this->assertEmpty(session('cart', []));
    }

    public function test_B06_cart_never_goes_past_stock(): void
    {
        $buyer = $this->buyerAt();
        $few = $this->makeProduct($this->seller, ['stock' => 3]);

        $this->actingAsUser($buyer)->post(route('cart.add', $few->id), ['quantity' => 50]);
        $this->assertSame(3, session('cart')[$this->key($few)]);

        $this->post(route('cart.update', $this->key($few)), ['action' => 'increase'])
            ->assertSessionHas('error', 'No more stock available for this product.');
        $this->assertSame(3, session('cart')[$this->key($few)]);
    }

    public function test_B07_checking_out_some_lines_leaves_the_rest_in_the_cart(): void
    {
        $buyer = $this->buyerAt();
        $otherShop = $this->sellerIn('Laguna', 'Santa Cruz');
        $bag = $this->makeProduct($otherShop, ['name' => 'Tote Bag']);
        $cart = [$this->key($this->shoes) => 1, $this->key($bag) => 1];

        $this->actingAsUser($buyer, ['cart' => $cart])->get(route('checkout', ['items' => $this->key($this->shoes)]))->assertOk();
        $this->post(route('checkout.place'), ['address' => self::LAGUNA_ADDRESS, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertRedirect();

        $this->assertSame([$this->key($bag) => 1], session('cart'));
        $this->assertSame(1, DB::table('orders')->where('buyer_id', $buyer->id)->count());
    }

    public function test_B08_voucher_rules(): void
    {
        $buyer = $this->buyerAt();
        $cart = [$this->key($this->shoes) => 1]; // ₱500
        $otherSeller = $this->makeSeller('shoes', 'Other Shop');
        $make = fn (array $over) => Voucher::create(array_merge([
            'seller_id' => $this->seller->id, 'code' => strtoupper(\Illuminate\Support\Str::random(6)), 'discount_type' => 'fixed',
            'discount_value' => 50, 'min_order_amount' => 0, 'max_uses' => null, 'used_count' => 0, 'is_active' => true,
        ], $over));
        $apply = fn (string $code) => $this->actingAsUser($buyer, ['cart' => $cart])->post(route('cart.voucher.apply'), ['voucher_code' => $code]);

        $apply('NOPE123')->assertSessionHas('error', 'Invalid voucher code.');
        $apply($make(['seller_id' => $otherSeller->id])->code)
            ->assertSessionHas('error', 'This voucher only applies to a seller whose products are not in your cart.');
        $apply($make(['min_order_amount' => 1000])->code)->assertSessionHas('error');
        $apply($make(['max_uses' => 2, 'used_count' => 2])->code)->assertSessionHas('error');
        $apply($make(['expires_at' => now()->subDay()->toDateString()])->code)->assertSessionHas('error');
        $apply($make([])->code)->assertSessionHas('success');

        // "Expires on" today still works until the day is over.
        $apply($make(['expires_at' => now()->toDateString()])->code)
            ->assertSessionHas('success');
    }

    public function test_B08b_a_voucher_is_used_once_per_buyer(): void
    {
        Voucher::create(['seller_id' => $this->seller->id, 'code' => 'ONCE50', 'discount_type' => 'fixed', 'discount_value' => 50,
            'min_order_amount' => 0, 'max_uses' => 100, 'per_buyer_limit' => 1, 'used_count' => 0, 'is_active' => true]);
        $cart = [$this->key($this->shoes) => 1];
        $buyer = $this->buyerAt();

        $this->placeOrder($buyer, $cart, self::LAGUNA_ADDRESS, 'Cash on Delivery', ['session' => ['applied_voucher' => 'ONCE50']])->assertRedirect();
        $first = $this->lastOrder($buyer);
        $this->assertEquals(50, $first->discount_amount);

        // Same buyer again: refused when applying, and at place order (e.g. a second tab).
        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => $cart])->post(route('cart.voucher.apply'), ['voucher_code' => 'ONCE50'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already used'));
        $this->flushSession();
        $this->placeOrder($buyer, $cart, self::LAGUNA_ADDRESS, 'Cash on Delivery', ['session' => ['applied_voucher' => 'ONCE50']])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already used'));

        // Another buyer still can.
        $this->flushSession();
        $this->actingAsUser($this->buyerAt(), ['cart' => $cart])->post(route('cart.voucher.apply'), ['voucher_code' => 'ONCE50'])->assertSessionHas('success');

        // Cancelling the order gives the use back.
        $this->flushSession();
        $this->actingAsUser($buyer)->post(route('buyer.order.cancel', $first->id), ['cancel_reason' => 'Changed my mind']);
        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => $cart])->post(route('cart.voucher.apply'), ['voucher_code' => 'ONCE50'])->assertSessionHas('success');
    }

    public function test_B09_buy_now_is_dropped_when_checking_out_from_the_cart(): void
    {
        $buyer = $this->buyerAt();
        $other = $this->makeProduct($this->seller, ['name' => 'Slippers']);

        $this->actingAsUser($buyer, ['cart' => [$this->key($other) => 2]])
            ->post(route('buy.now', $this->shoes->id), ['quantity' => 1])
            ->assertRedirect(route('checkout', ['buy_now' => 1]));

        $this->get(route('checkout'))->assertOk()
            ->assertViewHas('cart', [$this->key($other) => 2]);
        $this->assertNull(session('buy_now'));
    }

    public function test_B10_incomplete_profile_cannot_check_out(): void
    {
        $buyer = $this->makeUser('buyer', ['phone' => null, 'address' => null]);

        $this->actingAsUser($buyer, ['cart' => [$this->key($this->shoes) => 1]])->get(route('checkout'))
            ->assertOk()->assertSee('Complete my profile');

        $this->post(route('checkout.place'), ['address' => self::LAGUNA_ADDRESS, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertRedirect(route('buyer.profile'));
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_B11_delivery_fee_and_eta_follow_the_distance(): void
    {
        $buyer = $this->buyerAt();
        $this->actingAsUser($buyer, ['cart' => [$this->key($this->shoes) => 1]])->get(route('checkout'))->assertOk();

        $quote = fn (string $address) => $this->getJson(route('checkout.quote', ['address' => $address]))->assertOk()->json();

        $this->assertEquals(50, $quote(self::LAGUNA_ADDRESS)['delivery_fee']);
        $this->assertEquals(80, $quote('1 Real St, Calamba City, Laguna')['delivery_fee']);
        $this->assertEquals(120, $quote(self::QC_ADDRESS)['delivery_fee']);
        $far = $quote(self::CEBU_ADDRESS);
        $this->assertEquals(180, $far['delivery_fee']);
        $this->assertNotEmpty($far['eta']);
    }

    public function test_B12_pick_up_is_cheaper_and_only_where_a_center_is_near(): void
    {
        $buyer = $this->buyerAt();
        $this->actingAsUser($buyer, ['cart' => [$this->key($this->shoes) => 1]])->get(route('checkout'));

        $qc = $this->getJson(route('checkout.quote', ['address' => self::QC_ADDRESS, 'fulfillment' => 'pickup']))->json();
        $this->assertSame('pickup', $qc['fulfillment']);
        $this->assertEquals(70, $qc['delivery_fee']); // ₱120 − ₱50 rider part

        // No center anywhere in the Visayas.
        $cebu = $this->getJson(route('checkout.quote', ['address' => self::CEBU_ADDRESS, 'fulfillment' => 'pickup']))->json();
        $this->assertSame('delivery', $cebu['fulfillment']);
        $this->assertNull($cebu['pickup_center']);

        $this->placeOrder($buyer, [$this->key($this->shoes) => 1], self::CEBU_ADDRESS, 'Cash on Delivery', ['form' => ['fulfillment' => 'pickup']])
            ->assertSessionHas('error', 'There is no BoomBuy Sorting Center near that address to pick up from yet. Please choose delivery.');
    }

    public function test_B13_free_delivery_from_999_per_seller_order(): void
    {
        $buyer = $this->buyerAt();
        $pricey = $this->makeProduct($this->seller, ['price' => 999]);

        $this->placeOrder($buyer, [$this->key($pricey) => 1], self::CEBU_ADDRESS)->assertRedirect();

        $order = $this->lastOrder($buyer);
        $this->assertEquals(0, $order->delivery_fee);
        $this->assertEquals(999, $order->total_amount);
    }

    public function test_B14_three_sellers_make_three_orders_on_one_success_page(): void
    {
        $buyer = $this->buyerAt();
        $cart = [$this->key($this->shoes) => 1];
        foreach (range(1, 2) as $i) {
            $cart[$this->key($this->makeProduct($this->sellerIn('Laguna', 'Santa Cruz')))] = 1;
        }

        $response = $this->placeOrder($buyer, $cart);
        $this->assertSame(3, DB::table('orders')->where('buyer_id', $buyer->id)->count());

        $this->get($response->headers->get('Location'))->assertOk()
            ->assertViewHas('checkoutOrders', fn ($orders) => $orders->count() === 3);
    }

    public function test_B15_the_last_unit_is_sold_once(): void
    {
        $last = $this->makeProduct($this->seller, ['stock' => 1]);
        $first = $this->buyerAt();
        $second = $this->buyerAt();

        $this->placeOrder($first, [$this->key($last) => 1])->assertRedirect();
        $this->flushSession();
        $this->placeOrder($second, [$this->key($last) => 1])->assertSessionHas('error');

        $this->assertSame(0, $this->stockOf($last));
        $this->assertSame(1, DB::table('order_items')->where('product_id', $last->id)->count());
    }

    public function test_B16_three_cancellations_pause_cash_on_delivery(): void
    {
        $buyer = $this->buyerAt();
        foreach (range(1, 3) as $i) {
            $this->makeOrder($buyer, $this->shoes, 'Cancelled', ['cancelled_by' => 'buyer', 'cancelled_at' => now()->subDays($i)]);
        }

        $this->placeOrder($buyer, [$this->key($this->shoes) => 1])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Cash on Delivery is paused'));

        $this->flushSession();
        $this->placeOrder($buyer, [$this->key($this->shoes) => 1], self::LAGUNA_ADDRESS, 'GCash')->assertRedirect();
        $this->assertSame('GCash', $this->lastOrder($buyer)->payment_method);
    }

    // ---------------- Orders ----------------

    public function test_B17_cancel_rules(): void
    {
        $buyer = $this->buyerAt();
        $cancel = fn (int $id, array $data) => $this->actingAsUser($buyer)->post(route('buyer.order.cancel', $id), $data);

        $pending = $this->makeOrder($buyer, $this->shoes, 'Pending');
        DB::table('products')->where('id', $this->shoes->id)->update(['stock' => 9]);
        $cancel($pending, ['cancel_reason' => 'Other', 'cancel_details' => ''])->assertSessionHas('error', 'Please tell us why you are cancelling.');
        $cancel($pending, ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');
        $this->assertSame('Cancelled', $this->orderStatus($pending));
        $this->assertSame(10, $this->stockOf($this->shoes));

        $processing = $this->makeOrder($buyer, $this->shoes, 'Processing');
        $cancel($processing, ['cancel_reason' => 'Ordered by mistake'])->assertSessionHas('success');

        $prepaid = $this->makeOrder($buyer, $this->shoes, 'Pending', ['payment_method' => 'GCash']);
        $cancel($prepaid, ['cancel_reason' => 'Changed my mind'])->assertSessionHas('error');

        $shipped = $this->makeOrder($buyer, $this->shoes, 'Dropped Off');
        $cancel($shipped, ['cancel_reason' => 'Changed my mind'])->assertSessionHas('error');
        $this->assertSame('Dropped Off', $this->orderStatus($shipped));
    }

    public function test_B18_orders_page_has_timeline_eta_and_private_delivery_photo(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->key($this->shoes) => 1]);
        $orderId = $this->lastOrder($buyer)->id;

        $this->get(route('buyer.orders'))->assertOk()
            ->assertViewHas('timelines', fn ($t) => isset($t[$orderId]) && count($t[$orderId]) >= 1)
            ->assertViewHas('orders', fn ($orders) => !empty($orders[0]['eta']));

        Storage::disk('local')->put('delivery-proofs/p.png', 'x');
        $this->setOrder($orderId, ['status' => 'Delivered', 'delivery_proof' => 'delivery-proofs/p.png', 'delivered_at' => now()]);

        $this->get(route('orders.delivery-proof', $orderId))->assertOk();
        $this->flushSession();
        $this->actingAsUser($this->buyerAt())->get(route('orders.delivery-proof', $orderId))->assertForbidden();
    }

    public function test_B18b_buyer_sees_who_is_delivering_their_order(): void
    {
        $buyer = $this->buyerAt();
        $rider = $this->makeRider();
        $rider->forceFill(['name' => 'Jun Dela Cruz'])->save();
        $this->makeOrder($buyer, $this->shoes, 'Out for Delivery', ['delivery_rider_id' => $rider->id]);

        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders[0]['rider_name'] === 'Jun Dela Cruz')
            ->assertSee('Jun Dela Cruz');
    }

    public function test_B19_received_by_hand_or_automatically_after_three_days(): void
    {
        $buyer = $this->buyerAt();
        $manual = $this->deliveredOrder($buyer, $this->shoes, null);
        $this->actingAsUser($buyer)->post(route('buyer.order.received', $manual))->assertSessionHas('success');
        $this->assertNotNull(DB::table('orders')->where('id', $manual)->value('buyer_received_at'));

        $auto = $this->makeOrder($buyer, $this->shoes, 'Delivered', ['delivered_at' => now()->subDays(4)]);
        AutoReceive::sweep(true);
        $this->assertNotNull(DB::table('orders')->where('id', $auto)->value('buyer_received_at'));
        $this->assertTrue($this->notifiedWith($buyer, 'Order Marked as Received'));
    }

    public function test_B20_review_only_after_receiving_and_editing_updates_it(): void
    {
        $buyer = $this->buyerAt();
        $notYet = $this->deliveredOrder($buyer, $this->shoes, null);
        $review = fn (int $orderId, int $stars) => $this->actingAsUser($buyer)->post(route('buyer.product.review', [$orderId, $this->shoes->id]), ['rating' => $stars, 'review' => 'Ok']);

        $review($notYet, 5)->assertSessionHas('error', 'You can only review products after receiving the order.');

        $received = $this->deliveredOrder($buyer, $this->shoes, 1);
        $review($received, 9)->assertSessionHas('error');
        $review($received, 4)->assertSessionHas('success');
        $review($received, 5)->assertSessionHas('success', 'Your review has been updated!');

        $this->assertSame(1, DB::table('product_reviews')->where('order_id', $received)->count());
        $this->assertSame(5, (int) DB::table('product_reviews')->where('order_id', $received)->value('rating'));
    }

    public function test_B21_return_request_window_duplicates_and_photo_size(): void
    {
        $buyer = $this->buyerAt();
        $request = function (int $orderId, array $extra = []) use ($buyer) {
            $item = DB::table('order_items')->where('order_id', $orderId)->value('id');

            return $this->actingAsUser($buyer)->post(route('buyer.return-refund.store', $orderId), array_merge([
                'order_item_id' => $item, 'request_type' => 'Return', 'reason' => 'Damaged item',
            ], $extra));
        };

        $late = $this->deliveredOrder($buyer, $this->shoes, 8);
        $request($late)->assertSessionHas('error', 'The 7-day return/refund window for this order has passed.');

        $recent = $this->deliveredOrder($buyer, $this->shoes, 2);
        $request($recent, ['evidence' => \Illuminate\Http\UploadedFile::fake()->create('big.png', 5000, 'image/png')])
            ->assertSessionHasErrors('evidence');
        $request($recent)->assertSessionHas('success');
        $request($recent)->assertSessionHas('error', 'A return/refund request already exists for this item.');
    }

    public function test_B22_reorder_adds_what_is_still_available(): void
    {
        $buyer = $this->buyerAt();
        $gone = $this->makeProduct($this->seller, ['name' => 'Old Sandals']);
        $orderId = $this->makeOrder($buyer, $this->shoes, 'Delivered');
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $gone->id, 'seller_id' => $this->seller->id,
            'product_name' => $gone->name, 'price' => 300, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $gone->update(['is_archived' => true]);

        $this->actingAsUser($buyer)->post(route('buyer.order.reorder', $orderId))
            ->assertRedirect(route('cart'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 item(s) added') && str_contains($m, '1 item(s) could not be added'));
        $this->assertSame([$this->key($this->shoes) => 1], session('cart'));
    }

    public function test_B23_wishlist_toggles_and_marks_unavailable_items(): void
    {
        $buyer = $this->buyerAt();
        $this->actingAsUser($buyer)->postJson(route('wishlist.toggle', $this->shoes->id))->assertJson(['in_wishlist' => true]);

        $this->shoes->update(['is_archived' => true]);
        $this->get(route('wishlist.index'))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->first()->on_sale === false);

        $this->postJson(route('wishlist.toggle', $this->shoes->id))->assertJson(['in_wishlist' => false]);
    }

    public function test_B24_chat_from_product_page_with_auto_reply_and_order_cards(): void
    {
        $buyer = $this->buyerAt();

        $this->actingAsUser($buyer)->get(route('messages.thread', [$this->seller->id, 'product' => $this->shoes->id]))
            ->assertOk()->assertViewHas('askingAbout', fn ($card) => $card['id'] === $this->shoes->id);

        $sent = $this->postJson(route('messages.store', $this->seller->id), ['message' => 'Available pa po size 9?', 'product_id' => $this->shoes->id])
            ->assertOk()->json();
        $this->assertSame($this->shoes->id, $sent['message']['product']['id']);
        $this->assertNotNull($sent['auto_reply']);

        // A second message within 12 hours gets no second auto-reply.
        $again = $this->postJson(route('messages.store', $this->seller->id), ['message' => 'Hello?'])->json();
        $this->assertNull($again['auto_reply']);

        // Auto messages never count as unread for the buyer.
        $this->assertSame(0, Message::where('recipient_id', $buyer->id)->whereNull('read_at')->count());

        $this->placeOrder($buyer, [$this->key($this->shoes) => 1]);
        $this->assertTrue(Message::where('recipient_id', $buyer->id)->where('kind', Message::ORDER_UPDATE)->exists());
    }

    public function test_B24b_who_can_message_whom(): void
    {
        $buyer = $this->buyerAt();
        $otherBuyer = $this->buyerAt();
        $rider = $this->makeRider();

        // Not another buyer, not a rider they've never dealt with.
        $this->actingAsUser($buyer)->post(route('messages.store', $otherBuyer->id), ['message' => 'Hi'])
            ->assertRedirect(route('messages.index'))->assertSessionHas('error', \App\Http\Controllers\MessageController::NOT_ALLOWED);
        $this->get(route('messages.thread', $rider->id))->assertRedirect(route('messages.index'));
        $this->postJson(route('messages.store', $rider->id), ['message' => 'Hi'])->assertForbidden();
        $this->assertSame(0, Message::where('sender_id', $buyer->id)->count());

        // A shop, and BoomBuy Support, are fine.
        $this->postJson(route('messages.store', $this->seller->id), ['message' => 'Hello shop'])->assertOk();
        $support = \App\Support\SupportAccount::user() ?? User::create(['name' => 'Admin', 'email' => 'admin@boombuy.com', 'password' => bcrypt('x'), 'role' => 'admin']);
        $this->postJson(route('messages.store', $support->id), ['message' => 'Help'])->assertOk();

        // The rider delivering their order can message them, and they can reply.
        $this->makeOrder($buyer, $this->shoes, 'Out for Delivery', ['delivery_rider_id' => $rider->id]);
        $this->flushSession();
        $this->actingAsUser($rider)->postJson(route('messages.store', $buyer->id), ['message' => 'Nasa gate na po ako'])->assertOk();
        $this->flushSession();
        $this->actingAsUser($buyer)->postJson(route('messages.store', $rider->id), ['message' => 'Sige po'])->assertOk();
    }

    public function test_B25_notifications_filter_open_and_read_all(): void
    {
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->key($this->shoes) => 1]);
        $orderId = $this->lastOrder($buyer)->id;
        createNotification($buyer->id, 'Account Status Updated', 'x', 'account_status');

        $this->get(route('notifications', ['filter' => 'orders']))->assertOk()
            ->assertViewHas('notifications', fn ($page) => $page->total() === 1);

        $note = DB::table('notifications')->where('user_id', $buyer->id)->where('type', 'order')->first();
        $this->get(route('notifications.open', $note->id))->assertRedirect(route('buyer.orders') . '#order-' . $orderId);

        $this->post(route('notifications.read-all'));
        $this->assertSame(0, DB::table('notifications')->where('user_id', $buyer->id)->whereNull('read_at')->count());
    }

    public function test_B26_complaint_with_order_evidence_and_who_it_is_about(): void
    {
        $buyer = $this->buyerAt();
        $orderId = $this->makeOrder($buyer, $this->shoes, 'Delivered');

        $this->actingAsUser($buyer)->post(route('complaints.store'), [
            'subject' => 'Wrong size sent',
            'description' => 'I ordered size 9, got size 7.',
            'order_id' => $orderId,
            'against_user_id' => $this->seller->id,
            'evidence' => $this->png('box.png'),
        ])->assertSessionHas('success');

        $complaint = DB::table('complaints')->first();
        $this->assertSame($orderId, (int) $complaint->order_id);
        $this->assertNotNull($complaint->evidence);
        $this->assertSame($this->seller->id, (int) $complaint->against_user_id, 'The complaint does not record who it is about.');

        // Someone who isn't on that order can't be named.
        $stranger = $this->makeSeller();
        $this->post(route('complaints.store'), ['subject' => 'x', 'description' => 'y', 'order_id' => $orderId, 'against_user_id' => $stranger->id])
            ->assertSessionHas('error');

        // A rider's complaint about the buyer shows up in Admin → Compliance → Buyers.
        $rider = $this->makeRider();
        $this->setOrder($orderId, ['delivery_rider_id' => $rider->id]);
        $this->flushSession();
        $this->actingAsUser($rider)->get(route('complaints.index'))->assertOk()
            ->assertViewHas('myOrders', fn ($orders) => $orders->pluck('id')->contains($orderId));
        $this->post(route('complaints.store'), ['subject' => 'Rude buyer', 'description' => 'Shouted at me.', 'order_id' => $orderId, 'against_user_id' => $buyer->id])
            ->assertSessionHas('success');

        $this->flushSession();
        $this->actingAsAdmin()->get(route('admin.compliance.buyers'))
            ->assertViewHas('people', fn ($people) => $people->firstWhere('user_id', $buyer->id)['complaints']->count() === 1);
    }

    public function test_B27_pick_up_order_says_where_to_collect_and_how_much(): void
    {
        $buyer = $this->buyerAt();
        $staff = $this->staffAt($this->laguna);
        $this->placeOrder($buyer, [$this->key($this->shoes) => 1], self::LAGUNA_ADDRESS, 'Cash on Delivery', ['form' => ['fulfillment' => 'pickup']]);
        $orderId = $this->lastOrder($buyer)->id;

        $this->flushSession();
        $this->actingAsUser($this->seller)->post(route('seller.order.status', $orderId), ['status' => 'Processing']);
        $this->post(route('seller.order.status', $orderId), ['status' => 'Dropped Off']);
        $this->flushSession();
        $this->actingAsUser($staff)->post(route('logistics.parcels.confirm-received', $orderId))->assertSessionHas('success');

        $this->assertSame('Ready to Collect', $this->orderStatus($orderId));
        $note = DB::table('notifications')->where('user_id', $buyer->id)->where('title', 'Ready to Collect')->value('message');
        $this->assertStringContainsString('Poblacion, Santa Cruz', $note);
        $this->assertStringContainsString('₱500.00', $note);
        $this->assertStringContainsString((string) $this->lastOrder($buyer)->pickup_code, $note);

        $this->flushSession();
        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertSee($this->lastOrder($buyer)->pickup_code);
    }
}
