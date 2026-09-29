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

    public function test_buyer_pages_load(): void
    {
        $buyer = $this->makeUser();

        foreach (['buyer.dashboard', 'buyer.account', 'buyer.orders', 'buyer.profile', 'cart', 'wishlist.index', 'notifications'] as $route) {
            $this->actingAsUser($buyer)->get(route($route))->assertOk();
        }
    }
}
