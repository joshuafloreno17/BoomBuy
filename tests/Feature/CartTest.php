<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_guest_gets_a_login_answer_instead_of_a_fake_success(): void
    {
        $product = $this->makeProduct($this->makeSeller());

        $this->postJson(route('cart.add', $product->id), [], $this->ajaxHeaders())
            ->assertStatus(401)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['login']);
    }

    public function test_buyer_adds_a_product_without_options(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller());

        $this->actingAsUser($buyer)
            ->postJson(route('cart.add', $product->id), [], $this->ajaxHeaders())
            ->assertOk()
            ->assertJson(['ok' => true, 'cart_count' => 1])
            ->assertSessionHas('cart', ["{$product->id}:0" => 1]);
    }

    public function test_product_with_options_is_refused_until_one_is_picked(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller());
        $red = $this->addVariation($product, 'Red');

        // The shop used to show "Added!" here even though nothing was added.
        $this->actingAsUser($buyer)
            ->postJson(route('cart.add', $product->id), [], $this->ajaxHeaders())
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->actingAsUser($buyer)
            ->postJson(route('cart.add', $product->id), ['variation_id' => $red->id], $this->ajaxHeaders())
            ->assertOk()
            ->assertSessionHas('cart', ["{$product->id}:{$red->id}" => 1]);
    }

    public function test_cannot_add_more_than_the_stock(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['stock' => 1]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->postJson(route('cart.add', $product->id), [], $this->ajaxHeaders())
            ->assertStatus(422);
    }

    public function test_cart_is_kept_with_the_account_across_sessions(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['name' => 'Trail Runner Shoes']);

        $this->actingAsUser($buyer)
            ->postJson(route('cart.add', $product->id), [], $this->ajaxHeaders())
            ->assertOk();

        $this->assertDatabaseHas('saved_carts', ['user_id' => $buyer->id]);

        // A fresh session (expired + "remember me", or a new login) gets the cart back.
        $this->flushSession();
        $this->actingAsUser($buyer)
            ->get(route('cart'))
            ->assertOk()
            ->assertSee('Trail Runner Shoes')
            ->assertDontSee('Your cart is empty');
    }

    public function test_cart_groups_items_by_seller(): void
    {
        $buyer = $this->makeUser();
        $a = $this->makeProduct($this->makeSeller('shoes', 'Stride Footwear Co.'));
        $b = $this->makeProduct($this->makeSeller('electronics', 'Voltique Electronics'), ['category' => 'Electronics']);

        $this->actingAsUser($buyer, ['cart' => ["{$a->id}:0" => 1, "{$b->id}:0" => 1]])
            ->get(route('cart'))
            ->assertOk()
            ->assertSee('Stride Footwear Co.')
            ->assertSee('Voltique Electronics');
    }
}
