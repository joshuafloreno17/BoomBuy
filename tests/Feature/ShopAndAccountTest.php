<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class ShopAndAccountTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_landing_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_shop_filters_by_category_and_price(): void
    {
        $shoes = $this->makeSeller('shoes');
        $tech = $this->makeSeller('electronics');
        $this->makeProduct($shoes, ['name' => 'Budget Slippers', 'price' => 300]);
        $this->makeProduct($shoes, ['name' => 'Leather Boots', 'price' => 4500]);
        $this->makeProduct($tech, ['name' => 'USB Charger', 'category' => 'Electronics', 'price' => 400]);

        $this->get(route('products', ['category' => 'shoes', 'max' => 1000]))
            ->assertOk()
            ->assertSee('Budget Slippers')
            ->assertDontSee('Leather Boots')
            ->assertDontSee('USB Charger');
    }

    public function test_shop_filters_by_rating(): void
    {
        $seller = $this->makeSeller('shoes');
        $buyer = $this->makeUser();
        $loved = $this->makeProduct($seller, ['name' => 'Loved Loafers']);
        $meh = $this->makeProduct($seller, ['name' => 'Meh Mules']);
        $this->makeProduct($seller, ['name' => 'Unrated Clogs']);

        $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId(['buyer_id' => $buyer->id, 'status' => 'Delivered', 'created_at' => now(), 'updated_at' => now()]);
        \App\Models\ProductReview::create(['buyer_id' => $buyer->id, 'order_id' => $orderId, 'product_id' => $loved->id, 'rating' => 5]);
        \App\Models\ProductReview::create(['buyer_id' => $buyer->id, 'order_id' => $orderId, 'product_id' => $meh->id, 'rating' => 2]);

        $this->get(route('products', ['rating' => 4]))
            ->assertOk()
            ->assertSee('Loved Loafers')
            ->assertDontSee('Meh Mules')
            ->assertDontSee('Unrated Clogs');
    }

    public function test_shop_cards_count_variation_stock_and_show_shop_and_sales(): void
    {
        $seller = $this->makeSeller('shoes', 'Stride Footwear Co.');
        $buyer = $this->makeUser();
        $sized = $this->makeProduct($seller, ['name' => 'Sized Sneakers', 'stock' => 0]);
        $this->addVariation($sized, 'Red', 3);
        $this->addVariation($sized, 'Blue', 4);
        $this->makeProduct($seller, ['name' => 'Gone Galoshes', 'stock' => 0]);
        $this->makeOrder($buyer, $sized, 'Delivered');

        // Stock lives in the variations, so the base stock of 0 must not hide it.
        $this->get(route('products', ['in_stock' => 1]))
            ->assertOk()
            ->assertSee('Sized Sneakers')
            ->assertSee('Only 7 left')
            ->assertSee('Stride Footwear Co.')
            ->assertSee('1 sold')
            ->assertDontSee('Gone Galoshes');
    }

    public function test_shop_page_shows_only_that_sellers_products(): void
    {
        $shoes = $this->makeSeller('shoes', 'Stride Footwear Co.');
        $tech = $this->makeSeller('electronics');
        $this->makeProduct($shoes, ['name' => 'Canvas Sneakers']);
        $this->makeProduct($tech, ['name' => 'USB Charger', 'category' => 'Electronics']);

        $this->get(route('shop.seller', $shoes->id))
            ->assertOk()
            ->assertSee('Stride Footwear Co.')
            ->assertSee('Canvas Sneakers')
            ->assertDontSee('USB Charger');

        $this->get('/shop/999999')->assertNotFound();
    }

    public function test_address_book_keeps_exactly_one_default(): void
    {
        $buyer = $this->makeUser();

        $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['label' => 'Home', 'phone' => '09171234567', 'address' => '1 Rizal St']);
        $this->actingAsUser($buyer)->post(route('buyer.addresses.store'), ['label' => 'Work', 'phone' => '09998887777', 'address' => '99 IT Park']);

        $home = \App\Models\BuyerAddress::where('label', 'Home')->first();
        $work = \App\Models\BuyerAddress::where('label', 'Work')->first();
        $this->assertTrue($home->is_default);
        $this->assertFalse($work->is_default);

        $this->actingAsUser($buyer)->post(route('buyer.addresses.default', $work->id));
        $this->assertTrue($work->fresh()->is_default);
        $this->assertFalse($home->fresh()->is_default);

        // Deleting the default hands it to the remaining address.
        $this->actingAsUser($buyer)->delete(route('buyer.addresses.delete', $work->id));
        $this->assertTrue($home->fresh()->is_default);
    }

    public function test_buyer_cannot_touch_someone_elses_address(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $address = \App\Models\BuyerAddress::create(['user_id' => $owner->id, 'phone' => '0917', 'address' => 'Mine', 'is_default' => true]);

        $this->actingAsUser($other)
            ->delete(route('buyer.addresses.delete', $address->id))
            ->assertNotFound();

        $this->assertDatabaseHas('buyer_addresses', ['id' => $address->id]);
    }

    public function test_notifications_filter_by_kind_and_unread(): void
    {
        $buyer = $this->makeUser();
        $other = $this->makeUser();
        createNotification($buyer->id, 'Order Shipped', 'On its way.', 'order_status', 1);
        createNotification($buyer->id, 'Return Approved', 'Refund coming.', 'return_refund', 1);
        createNotification($other->id, 'Someone Elses', 'Not yours.', 'order', 1);
        \App\Models\Notification::where('title', 'Order Shipped')->update(['read_at' => now()]);

        // The navbar's bell menu lists recent notifications too, so check the page's own list.
        $titles = fn (array $query) => $this->actingAsUser($buyer)->get(route('notifications', $query))
            ->assertOk()
            ->viewData('notifications')->pluck('title')->sort()->values()->all();

        $this->assertSame(['Order Shipped', 'Return Approved'], $titles([]));
        $this->assertSame(['Return Approved'], $titles(['filter' => 'returns']));
        $this->assertSame(['Return Approved'], $titles(['filter' => 'unread']));
        $this->assertSame(['Order Shipped'], $titles(['filter' => 'orders']));

        $this->actingAsUser($buyer)->get(route('notifications'))->assertSee('1 unread');
    }

    public function test_opening_a_notification_goes_to_its_order_or_says_it_is_gone(): void
    {
        $buyer = $this->makeUser();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()));
        createNotification($buyer->id, 'Order Placed', 'Placed.', 'order', $orderId);
        createNotification($buyer->id, 'Old Order', 'Gone.', 'order', 999999);
        $live = \App\Models\Notification::where('title', 'Order Placed')->value('id');
        $gone = \App\Models\Notification::where('title', 'Old Order')->value('id');

        $this->actingAsUser($buyer)->get(route('notifications.open', $live))
            ->assertRedirect(route('buyer.orders') . '#order-' . $orderId);

        $this->actingAsUser($buyer)->get(route('notifications.open', $gone))
            ->assertRedirect(route('buyer.orders'))
            ->assertSessionHas('error', 'Order #999999 is no longer available.');
    }

    public function test_complaints_page_loads_for_every_role(): void
    {
        // Buyers get the shop-style header with a breadcrumb; panel roles keep their own.
        $this->actingAsUser($this->makeUser())->get(route('complaints.index'))
            ->assertOk()->assertSee('bb-page-crumbs', false)->assertSee('bb-file-input', false);

        foreach ([$this->makeSeller(), $this->makeRider(), $this->makeLogistics()] as $user) {
            $this->actingAsUser($user)->get(route('complaints.index'))
                ->assertOk()->assertDontSee('bb-page-crumbs', false)->assertSee('bb-file-input', false);
        }
    }

    public function test_buyer_pages_load(): void
    {
        $buyer = $this->makeUser();

        foreach (['buyer.dashboard', 'buyer.account', 'buyer.orders', 'buyer.profile', 'cart', 'wishlist.index', 'notifications'] as $route) {
            $this->actingAsUser($buyer)->get(route($route))->assertOk();
        }
    }
}
