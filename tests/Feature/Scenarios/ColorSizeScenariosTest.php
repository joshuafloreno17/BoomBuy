<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Options in two parts: Color × Size. */
class ColorSizeScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
        Storage::fake('local');
        $this->center('Laguna', 'Santa Cruz');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Kicks');
    }

    private function teeWithSizes(): Product
    {
        $this->actingAsUser($this->seller)->post(route('seller.products.store'), [
            'name' => 'Running Shoe', 'category' => 'shoes', 'price' => 1000, 'stock' => 1, 'description' => 'Light.',
            'photos' => [$this->png()],
            'variations' => [
                ['type' => 'Color', 'value' => 'Red', 'type2' => 'Size', 'value2' => '9', 'price_adjustment' => 0, 'stock' => 3, 'photos' => [$this->png('red.png')]],
                ['type' => 'Color', 'value' => 'Red', 'type2' => 'Size', 'value2' => '10', 'price_adjustment' => 50, 'stock' => 0],
                ['type' => 'Color', 'value' => 'Blue', 'type2' => 'Size', 'value2' => '9', 'price_adjustment' => 0, 'stock' => 2],
            ],
        ])->assertRedirect(route('seller.dashboard'));

        return Product::where('name', 'Running Shoe')->firstOrFail();
    }

    public function test_CS01_seller_adds_color_by_size_options(): void
    {
        $product = $this->teeWithSizes();

        $this->assertSame(3, $product->variations()->count());
        $red10 = $product->variations()->where('variation_value', 'Red')->where('option2_value', '10')->first();
        $this->assertSame('Size', $red10->option2_type);
        $this->assertSame('Color: Red · Size: 10', $red10->label());
        $this->assertSame('Red · 10', $red10->shortLabel());

        // A row missing its second value is refused.
        $this->post(route('seller.products.store'), [
            'name' => 'Half Shoe', 'category' => 'shoes', 'price' => 500, 'stock' => 1, 'description' => 'x', 'photos' => [$this->png()],
            'variations' => [['type' => 'Color', 'value' => 'Red', 'type2' => 'Size', 'value2' => '', 'stock' => 1]],
        ])->assertSessionHas('error', 'Enter the Size for "Red".');

        // The Variations page: same combination twice is refused, a new one is added.
        $this->post(route('seller.products.variations.store', $product->id), ['variation_type' => 'Color', 'variation_value' => 'Blue', 'option2_type' => 'Size', 'option2_value' => '9', 'stock' => 1])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already has that option'));
        $this->post(route('seller.products.variations.store', $product->id), ['variation_type' => 'Color', 'variation_value' => 'Blue', 'option2_type' => 'Size', 'option2_value' => '10', 'stock' => 4])
            ->assertSessionHas('success');
        $this->get(route('seller.products.variations', $product->id))->assertOk()->assertSee('Blue · 10')->assertSee('Color × Size');
    }

    public function test_CS02_buyer_picks_a_color_and_size_and_checks_out(): void
    {
        $product = $this->teeWithSizes();
        $red9 = ProductVariation::where('variation_value', 'Red')->where('option2_value', '9')->first();
        $red10 = ProductVariation::where('variation_value', 'Red')->where('option2_value', '10')->first();
        $buyer = $this->buyerAt();

        $this->flushSession();
        $page = $this->actingAsUser($buyer)->get(route('product.details', Str::slug($product->name) . '-' . $product->id))->assertOk();
        $page->assertSee('Choose a color and size')->assertSee('class="variation-swatch-part pd-part1"', false)->assertSee('data-v2="10"', false);

        $this->post(route('cart.add', $product->id))->assertSessionHas('error', 'Please choose a color and size for Running Shoe first.');
        $this->post(route('cart.add', $product->id), ['variation_id' => $red10->id])->assertSessionHas('error'); // none left
        $this->post(route('cart.add', $product->id), ['variation_id' => $red9->id, 'quantity' => 2])->assertSessionHas('success');

        $this->get(route('cart'))->assertOk()->assertSee('Color: Red · Size: 9');

        $this->post(route('checkout.place'), ['address' => self::LAGUNA_ADDRESS, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])->assertRedirect();
        $item = DB::table('order_items')->first();
        $this->assertSame('Color: Red · Size: 9', $item->variation_label);
        $this->assertSame($red9->id, (int) $item->variation_id);
        $this->assertSame(1, (int) $red9->fresh()->stock);
    }

    public function test_CS03_cancel_restocks_and_buy_again_finds_the_same_combination(): void
    {
        $product = $this->teeWithSizes();
        $blue9 = ProductVariation::where('variation_value', 'Blue')->where('option2_value', '9')->first();
        $buyer = $this->buyerAt();

        $this->placeOrder($buyer, [$product->id . ':' . $blue9->id => 1])->assertRedirect();
        $orderId = $this->lastOrder($buyer)->id;
        $this->assertSame(1, (int) $blue9->fresh()->stock);

        $this->flushSession();
        $this->actingAsUser($buyer)->post(route('buyer.order.cancel', $orderId), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');
        $this->assertSame(2, (int) $blue9->fresh()->stock);

        $this->post(route('buyer.order.reorder', $orderId))->assertRedirect(route('cart'));
        $this->assertSame([$product->id . ':' . $blue9->id => 1], session('cart'));

        // An older order line with only a label still finds its option.
        $line = (object) ['product_id' => $product->id, 'variation_id' => null, 'variation_label' => 'color: blue · size: 9', 'quantity' => 1];
        $this->assertTrue(\App\Support\OrderStock::restockItem($line));
        $this->assertSame(3, (int) $blue9->fresh()->stock);
    }

    public function test_CS04_single_part_options_still_work(): void
    {
        $product = $this->makeProduct($this->seller, ['name' => 'Plain Tee']);
        $this->addVariation($product, 'Green', 4);

        $this->get(route('product.details', Str::slug($product->name) . '-' . $product->id))->assertOk()
            ->assertSee('Choose a color')->assertDontSee('class="variation-swatch-part pd-part1"', false);
    }
}
