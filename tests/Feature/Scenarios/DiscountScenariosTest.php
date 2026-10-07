<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/** A seller's "% off" on a product (ERP: "set prices, discounts"). */
class DiscountScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    public function test_D01_seller_sets_a_discount_and_buyers_pay_the_lower_price(): void
    {
        $this->center('Laguna', 'Santa Cruz');
        $seller = $this->sellerIn('Laguna', 'Santa Cruz');
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 5]);

        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), [
            'name' => $product->name, 'category' => 'shoes', 'price' => 1000,
            'discount_percent' => 20, 'stock' => 5, 'description' => 'A shoe.',
        ])->assertSessionMissing('error');

        $product->refresh();
        $this->assertEquals(800, $product->price);
        $this->assertEquals(1000, $product->original_price);
        $this->assertSame(20, $product->discount_percent);

        // Shop card + product page show the crossed-out price and the badge.
        $this->get(route('products'))->assertSee('-20%')->assertSee('₱1,000', false)->assertSee('₱800', false);
        $this->get(route('product.details', Str::slug($product->name) . '-' . $product->id))
            ->assertOk()->assertSee('price-was', false)->assertSee('-20%');

        // The edit form shows the regular price again, with the discount.
        $this->actingAsUser($seller)->get(route('seller.products.edit', $product->id))
            ->assertSee('value="1000"', false)->assertSee('value="20"', false);

        // The buyer pays ₱800.
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$product->id . ':0' => 1])->assertRedirect();
        $this->assertEquals(800, DB::table('order_items')->where('product_id', $product->id)->value('price'));
    }

    public function test_D02_removing_the_discount_and_bad_values(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);

        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), [
            'name' => $product->name, 'category' => 'shoes', 'price' => 1000, 'discount_percent' => 95, 'stock' => 5, 'description' => 'A shoe.',
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'Discount'));
        $this->assertEquals(1000, $product->fresh()->price);

        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), [
            'name' => $product->name, 'category' => 'shoes', 'price' => 1000, 'discount_percent' => 10, 'stock' => 5, 'description' => 'A shoe.',
        ]);
        $this->assertEquals(900, $product->fresh()->price);

        // 0 / empty = no discount.
        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), [
            'name' => $product->name, 'category' => 'shoes', 'price' => 1000, 'discount_percent' => '', 'stock' => 5, 'description' => 'A shoe.',
        ]);
        $product->refresh();
        $this->assertEquals(1000, $product->price);
        $this->assertNull($product->original_price);
        $this->assertFalse($product->isDiscounted());
    }

    public function test_D03_an_option_cannot_become_free_after_the_discount(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 500]);
        $this->addVariation($product, 'Red')->update(['price_adjustment' => -300]);

        // 50% off makes the base ₱250, and the -₱300 option would be below ₱0.
        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), [
            'name' => $product->name, 'category' => 'shoes', 'price' => 500, 'discount_percent' => 50, 'stock' => 5, 'description' => 'A shoe.',
        ])->assertSessionHas('error');
        $this->assertEquals(500, $product->fresh()->price);
    }

    public function test_D04_admin_price_edit_keeps_the_sellers_discount(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, Product::pricing(1000, 20));

        $this->actingAsAdmin()->get(route('admin.products.edit', $product->id))->assertSee('value="1000"', false);
    }
}
