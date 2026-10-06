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

    public function test_an_incomplete_account_cannot_check_out(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $cart = ['cart' => ["{$product->id}:0" => 1]];
        $form = ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'Cash on Delivery'];

        // No phone, and an address with no town ("N/A").
        $buyer = $this->makeUser('buyer', ['phone' => null, 'address' => 'N/A']);

        $this->actingAsUser($buyer, $cart)->get(route('checkout'))
            ->assertOk()
            ->assertSee('Complete your account to place an order')
            ->assertSee('A mobile number the rider can call')
            ->assertSee('A delivery address with your province and city/municipality')
            ->assertSee('disabled', false);

        // Even posting the form directly is refused.
        $this->actingAsUser($buyer, $cart)->post(route('checkout.place'), $form)
            ->assertRedirect(route('buyer.profile'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);

        // Once the profile is complete, it goes through.
        $buyer->forceFill(['phone' => '09171234567', 'address' => '1 Rizal St, Cebu City'])->save();

        $this->actingAsUser($buyer, $cart)->get(route('checkout'))->assertDontSee('Complete your account to place an order');
        $this->actingAsUser($buyer, $cart)->post(route('checkout.place'), $form)->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_a_saved_default_address_counts_for_a_complete_account(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $buyer = $this->makeUser('buyer', ['address' => 'N/A']);

        \App\Models\BuyerAddress::create([
            'user_id' => $buyer->id, 'label' => 'Home', 'phone' => '09998887777',
            'address' => '99 IT Park, Cebu City', 'is_default' => true,
        ]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])->get(route('checkout'))
            ->assertDontSee('Complete your account to place an order');
    }

    public function test_deleted_products_leave_the_cart_and_the_badge(): void
    {
        $buyer = $this->makeUser();
        $kept = $this->makeProduct($this->makeSeller(), ['name' => 'Still Here']);
        $withOption = $this->makeProduct($this->makeSeller('electronics'), ['category' => 'Electronics']);
        $option = $this->addVariation($withOption, 'Blue');
        $goneOption = $this->addVariation($withOption, 'Red');
        $goneOptionId = $goneOption->id;
        $goneOption->delete();

        // A product deleted after it went into the cart (e.g. the shop was emptied).
        $cart = [
            '999999:0' => 1,
            "{$withOption->id}:{$goneOptionId}" => 2,
            "{$kept->id}:0" => 1,
            "{$withOption->id}:{$option->id}" => 1,
        ];

        $this->actingAsUser($buyer, ['cart' => $cart])->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertSee('id="cartCount"', false)
            ->assertSeeInOrder(['id="cartCount"', '>2</span>'], false);

        $this->assertSame(["{$kept->id}:0" => 1, "{$withOption->id}:{$option->id}" => 1], session('cart'));

        // Only deleted things: badge hidden, cart page empty-handed but fine.
        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => ['999999:0' => 1]])->get(route('cart'))->assertOk();
        $this->assertSame([], session('cart'));
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

    public function test_checkout_starts_on_cash_on_delivery(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller());

        $html = $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->get(route('checkout'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="payment-cod"\s+value="Cash on Delivery"\s+checked/', $html);
    }

    public function test_product_page_says_free_delivery_when_the_item_reaches_the_minimum(): void
    {
        $seller = $this->makeSeller();
        $pricey = $this->makeProduct($seller, ['price' => 2500]);
        $cheap = $this->makeProduct($seller, ['price' => 300]);

        $this->get(route('product.details', $pricey->id))->assertOk()->assertSee('this item alone already reaches');
        $this->get(route('product.details', $cheap->id))->assertOk()->assertDontSee('this item alone already reaches');
    }
}
