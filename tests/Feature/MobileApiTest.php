<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The BoomBuy mobile app's API (/api/mobile/v1): who may sign in, and the
 * buyer, seller and rider flows going through the website's own rules.
 */
class MobileApiTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private const API = '/api/mobile/v1';

    private function login(User $user, string $password = 'password123')
    {
        return $this->postJson(self::API . '/login', ['email' => $this->lowercaseEmail($user), 'password' => $password]);
    }

    /** Emails are stored lowercase (as registration does); the builders make mixed-case ones. */
    private function lowercaseEmail(User $user): string
    {
        $user->forceFill(['email' => strtolower($user->email)])->save();

        return $user->email;
    }

    private function tokenFor(User $user): string
    {
        return $this->login($user)->assertOk()->json('token');
    }

    private function as(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    // ---- Who may sign in -------------------------------------------------

    public function test_buyers_sellers_and_riders_can_sign_in(): void
    {
        foreach ([$this->makeUser('buyer'), $this->makeSeller(), $this->makeRider()] as $user) {
            $this->login($user)
                ->assertOk()
                ->assertJsonPath('user.id', $user->id)
                ->assertJsonPath('user.role', $user->role)
                ->assertJsonStructure(['token']);
        }

        $this->assertDatabaseCount('api_tokens', 3);
    }

    public function test_logistics_and_admin_are_told_to_use_the_website_on_a_computer(): void
    {
        $logistics = $this->makeLogistics();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@boombuy.com', 'password' => bcrypt('password123'), 'role' => 'admin']);

        foreach ([$logistics, $admin] as $user) {
            $this->login($user)
                ->assertForbidden()
                ->assertJsonPath('code', 'desktop_only')
                ->assertJsonPath('message', MobileAccess::DESKTOP_ONLY_MESSAGE);
        }

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_a_wrong_password_never_reveals_the_kind_of_account(): void
    {
        $this->login($this->makeLogistics(), 'wrong-password')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid email or password.')
            ->assertJsonMissing(['code' => 'desktop_only']);
    }

    public function test_unapproved_or_suspended_accounts_cannot_sign_in(): void
    {
        $this->login($this->makeRider('Pending Verification'))->assertForbidden()->assertJsonPath('code', 'blocked');

        $buyer = $this->makeUser('buyer');
        DB::table('users')->where('id', $buyer->id)->update(['status' => 'Suspended']);
        $this->login($buyer->fresh())->assertForbidden()->assertJsonPath('code', 'blocked');
    }

    public function test_each_role_only_reaches_its_own_screens(): void
    {
        $seller = $this->tokenFor($this->makeSeller());
        $rider = $this->tokenFor($this->makeRider());
        $buyer = $this->tokenFor($this->makeUser('buyer'));

        $this->as($seller)->getJson(self::API . '/buyer/cart')->assertForbidden();
        $this->as($rider)->getJson(self::API . '/seller/dashboard')->assertForbidden();
        $this->as($buyer)->getJson(self::API . '/rider/deliveries')->assertForbidden();

        $this->as($seller)->getJson(self::API . '/seller/dashboard')->assertOk();
        $this->as($rider)->getJson(self::API . '/rider/dashboard')->assertOk();
        $this->as($buyer)->getJson(self::API . '/buyer/home')->assertOk();
        $this->as($buyer)->getJson(self::API . '/me')->assertOk()->assertJsonPath('user.role', 'buyer');
    }

    public function test_a_token_stops_working_when_the_role_moves_to_the_website_or_the_account_is_suspended(): void
    {
        $user = $this->makeUser('buyer');
        $token = $this->tokenFor($user);

        DB::table('users')->where('id', $user->id)->update(['role' => 'logistics']);
        $this->as($token)->getJson(self::API . '/me')->assertStatus(401);

        $other = $this->makeUser('buyer');
        $otherToken = $this->tokenFor($other);
        DB::table('users')->where('id', $other->id)->update(['status' => 'Suspended']);
        $this->as($otherToken)->getJson(self::API . '/me')->assertUnauthorized();
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $other->id]);
    }

    public function test_logout_and_password_change_revoke_tokens(): void
    {
        $user = $this->makeUser('buyer');
        $token = $this->tokenFor($user);

        $this->as($token)->postJson(self::API . '/profile/password', [
            'current_password' => 'password123',
            'new_password' => 'new-password-1',
            'new_password_confirmation' => 'new-password-1',
        ])->assertOk();

        // The old token is gone (every device signed out); this phone got a new one.
        $this->assertDatabaseCount('api_tokens', 1);
        $this->login($user, 'new-password-1')->assertOk();
    }

    public function test_the_website_login_is_unchanged_for_sellers_and_logistics(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->post(route('login.submit'), ['email' => $this->lowercaseEmail($this->makeSeller()), 'password' => 'password123'])
            ->assertRedirect(route('seller.dashboard'));

        $this->flushSession();
        $this->post(route('logistics.login.submit'), ['email' => $this->lowercaseEmail($this->makeLogistics()), 'password' => 'password123'])
            ->assertRedirect(route('logistics.dashboard'));
    }

    public function test_the_app_download_page_is_public(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('BoomBuy for Android')
            ->assertSee('logistics', false);
    }

    // ---- Guests -----------------------------------------------------------

    public function test_guests_see_the_landing_page_and_browse_the_shop(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['name' => 'Running Shoes']);
        $hidden = $this->makeProduct($this->makeSeller(), ['name' => 'Archived Item', 'is_archived' => true]);

        $this->getJson(self::API . '/shop/landing')
            ->assertOk()
            ->assertJsonPath('hero.0.id', $product->id)
            ->assertJsonPath('just_listed.0.name', 'Running Shoes')
            ->assertJsonCount(8, 'categories')
            ->assertJsonPath('categories.0.note', 'Gadgets & devices');

        $this->getJson(self::API . '/shop/products?search=shoes')->assertOk()->assertJsonCount(1, 'products');
        $this->getJson(self::API . "/shop/products/{$product->id}")->assertOk()->assertJsonPath('product.in_wishlist', false);
        $this->getJson(self::API . "/shop/products/{$hidden->id}")->assertNotFound();

        // Buying still needs an account.
        $this->getJson(self::API . '/buyer/cart')->assertUnauthorized();
        $this->postJson(self::API . '/buyer/cart', ['product_id' => $product->id])->assertUnauthorized();
    }

    public function test_a_signed_in_buyer_sees_their_wishlist_on_the_public_shop(): void
    {
        $buyer = $this->makeUser('buyer');
        $product = $this->makeProduct($this->makeSeller());
        DB::table('wishlists')->insert(['user_id' => $buyer->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->as($this->tokenFor($buyer))->getJson(self::API . "/shop/products/{$product->id}")->assertOk()->assertJsonPath('product.in_wishlist', true);

        // A bad token is simply a guest here, never an error.
        $this->withHeader('Authorization', 'Bearer 1|nope')->getJson(self::API . '/shop/landing')->assertOk();
    }

    // ---- Buyer -------------------------------------------------------------

    public function test_a_buyer_browses_fills_the_cart_and_checks_out(): void
    {
        $buyer = $this->makeUser('buyer', ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567']);
        $seller = $this->makeSeller();
        $shoes = $this->makeProduct($seller, ['name' => 'Running Shoes', 'price' => 1200, 'stock' => 5]);
        $shirt = $this->makeProduct($seller, ['name' => 'Plain Shirt', 'price' => 300]);
        $red = $this->addVariation($shirt, 'Red', 4);
        $token = $this->tokenFor($buyer);

        $this->as($token)->getJson(self::API . '/buyer/products?search=running')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Running Shoes');

        $this->as($token)->getJson(self::API . '/buyer/products/' . $shirt->id)
            ->assertOk()
            ->assertJsonPath('product.variations.0.label', 'Red');

        // A product with options needs one picked.
        $this->as($token)->postJson(self::API . '/buyer/cart', ['product_id' => $shirt->id])->assertStatus(422);
        $this->as($token)->postJson(self::API . '/buyer/cart', ['product_id' => $shirt->id, 'variation_id' => $red->id])->assertOk();
        $this->as($token)->postJson(self::API . '/buyer/cart', ['product_id' => $shoes->id, 'quantity' => 2])->assertOk()->assertJsonPath('cart_count', 3);

        // The cart is the one the website keeps for the account.
        $this->assertEquals(
            ["{$shirt->id}:{$red->id}" => 1, "{$shoes->id}:0" => 2],
            json_decode(DB::table('saved_carts')->where('user_id', $buyer->id)->value('items'), true)
        );

        $this->as($token)->postJson(self::API . '/buyer/checkout/preview', ['items' => ["{$shoes->id}:0"]])
            ->assertOk()
            ->assertJsonPath('subtotal', 2400)
            ->assertJsonPath('missing_info', []);

        $this->as($token)->postJson(self::API . '/buyer/checkout', [
            'items' => ["{$shoes->id}:0"],
            'address' => '1 Rizal St, Cebu City',
            'phone' => '09171234567',
            'payment' => 'Cash on Delivery',
        ])->assertOk()->assertJsonCount(1, 'order_ids');

        $this->assertSame(3, (int) $shoes->fresh()->stock);
        $this->assertDatabaseHas('orders', ['buyer_id' => $buyer->id, 'status' => 'Pending']);

        // Only the ordered line left the cart.
        $this->as($token)->getJson(self::API . '/buyer/cart')->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.option', 'Red');
    }

    public function test_a_buyer_tracks_and_cancels_a_cod_order(): void
    {
        $buyer = $this->makeUser('buyer');
        $order = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()));
        $token = $this->tokenFor($buyer);

        $this->as($token)->getJson(self::API . '/buyer/orders?tab=to_ship')
            ->assertOk()
            ->assertJsonPath('orders.0.id', $order)
            ->assertJsonPath('orders.0.can.cancel', true);

        // The whole trip, as on the website: placed (reached) first, delivered still ahead last.
        $trip = $this->as($token)->getJson(self::API . "/buyer/orders/{$order}")->assertOk()->json('order.trip');
        $this->assertSame('Order placed', $trip[0]['title']);
        $this->assertTrue($trip[0]['done'] && $trip[0]['current']);
        $this->assertSame('Delivered', end($trip)['title']);
        $this->assertFalse(end($trip)['done']);

        $this->as($token)->postJson(self::API . "/buyer/orders/{$order}/cancel", ['reason' => 'Changed my mind'])
            ->assertOk()
            ->assertJsonPath('order.status', 'Cancelled')
            ->assertJsonPath('order.trip', null);

        // Someone else's order is not found.
        $this->as($this->tokenFor($this->makeUser('buyer')))->getJson(self::API . "/buyer/orders/{$order}")->assertNotFound();
    }

    public function test_a_buyer_manages_addresses_with_the_website_rules(): void
    {
        $token = $this->tokenFor($this->makeUser('buyer'));

        $this->as($token)->postJson(self::API . '/buyer/addresses', ['phone' => '09171234567', 'address' => 'Somewhere'])
            ->assertStatus(422);

        $this->as($token)->postJson(self::API . '/buyer/addresses', ['label' => 'Home', 'phone' => '09171234567', 'address' => '123 Rizal St., Poblacion, Santa Cruz, Laguna'])
            ->assertOk()
            ->assertJsonPath('addresses.0.is_default', true);
    }

    // ---- Seller ------------------------------------------------------------

    public function test_a_seller_moves_an_order_to_ready_for_pickup(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makeOrder($this->makeUser('buyer'), $this->makeProduct($seller));
        $token = $this->tokenFor($seller);

        $this->as($token)->getJson(self::API . '/seller/orders?tab=to_process')->assertOk()->assertJsonPath('orders.0.can.accept', true);

        $this->as($token)->postJson(self::API . "/seller/orders/{$order}/status", ['status' => 'Confirmed'])->assertOk()->assertJsonPath('order.status', 'Confirmed');
        $this->as($token)->postJson(self::API . "/seller/orders/{$order}/status", ['status' => 'Preparing'])->assertOk()->assertJsonPath('order.can.request_pickup', true);
        $this->as($token)->postJson(self::API . "/seller/orders/{$order}/pickup", ['pickup_date' => now()->toDateString()])
            ->assertOk()
            ->assertJsonPath('order.status', 'Ready for Pickup');

        // Another seller can't touch it.
        $this->as($this->tokenFor($this->makeSeller()))->postJson(self::API . "/seller/orders/{$order}/status", ['status' => 'Cancelled', 'reason' => 'x'])
            ->assertStatus(422);
    }

    public function test_a_seller_updates_option_stock_and_archives_a_product(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        $red = $this->addVariation($product, 'Red', 2);
        $token = $this->tokenFor($seller);

        $this->as($token)->postJson(self::API . "/seller/products/{$product->id}/variations", ['variations' => [['id' => $red->id, 'stock' => 9, 'price_adjustment' => 50]]])
            ->assertOk()
            ->assertJsonPath('product.variations.0.stock', 9);

        $this->as($token)->postJson(self::API . "/seller/products/{$product->id}/archive")->assertOk();
        $this->assertTrue((bool) $product->fresh()->is_archived);

        $this->as($this->tokenFor($this->makeSeller()))->getJson(self::API . "/seller/products/{$product->id}")->assertNotFound();
    }

    // ---- Rider -------------------------------------------------------------

    public function test_a_rider_delivers_an_assigned_parcel(): void
    {
        $rider = $this->makeRider();
        $buyer = $this->makeUser('buyer');
        $order = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()), 'Assigned for Delivery', ['delivery_rider_id' => $rider->id]);
        $token = $this->tokenFor($rider);

        $this->as($token)->getJson(self::API . '/rider/deliveries')->assertOk()->assertJsonPath('deliveries.0.can.start', true);

        $this->as($token)->postJson(self::API . "/rider/deliveries/{$order}/out-for-delivery")->assertOk()->assertJsonPath('delivery.status', 'Out for Delivery');

        $this->as($token)->post(self::API . "/rider/deliveries/{$order}/delivered", ['photo' => $this->deliveryPhoto()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('delivery.status', 'Delivered');

        // Another rider can't see it.
        $this->as($this->tokenFor($this->makeRider()))->getJson(self::API . "/rider/deliveries/{$order}")->assertNotFound();
    }
}
