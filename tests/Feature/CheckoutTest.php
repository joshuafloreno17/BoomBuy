<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_cod_checkout_places_the_order_and_updates_stock_and_cart(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['stock' => 10, 'price' => 1200]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 2]])
            ->post('/checkout/place-order', [
                'address' => '1 Rizal St, Cebu City',
                'phone' => '09171234567',
                'payment' => 'Cash on Delivery',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'payment_method' => 'Cash on Delivery',
            'shipping_address' => '1 Rizal St, Cebu City',
        ]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'seller_id' => $seller->id, 'quantity' => 2]);
        $this->assertSame(8, (int) $product->fresh()->stock);

        // The seller hears about it, and the ordered line leaves the cart.
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'title' => 'New Order Received']);
        $this->assertArrayNotHasKey("{$product->id}:0", session('cart', []));
    }

    public function test_checkout_needs_the_delivery_details(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller());

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->post('/checkout/place-order', ['address' => '', 'phone' => '', 'payment' => 'Cash on Delivery'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_prefills_the_default_saved_address(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller());

        \App\Models\BuyerAddress::create([
            'user_id' => $buyer->id, 'label' => 'Home', 'phone' => '09998887777',
            'address' => '99 IT Park, Cebu City', 'is_default' => true,
        ]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('value="99 IT Park, Cebu City"', false);
    }
}
