<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\DeviceSpecs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Phones, tablets, laptops: storage sizes buyers pick (Color × Storage, each
 * with its own price and stock) and a Specifications table.
 */
class DeviceSpecsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'phone.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    private function phone($seller, array $overrides = []): Product
    {
        return $this->makeProduct($seller, array_merge(['name' => 'iPhone 18 Pro Max', 'category' => 'Electronics', 'price' => 89999], $overrides));
    }

    public function test_storage_sizes_sort_smallest_first(): void
    {
        $this->assertSame(['64GB', '256GB', '512GB', '1TB', '2TB'], DeviceSpecs::sortStorage(['1TB', '256GB', '2TB', '64GB', '512GB']));
        $this->assertTrue(DeviceSpecs::isStorage('storage'));
        $this->assertNull(DeviceSpecs::clean(['ram' => '8GB'], 'Shoes'));
        $this->assertSame(['device_type' => 'Phone', 'ram' => '8GB'], DeviceSpecs::clean(['device_type' => 'Phone', 'ram' => ' 8GB ', 'network' => 'Satellite', 'hack' => 'x'], 'Electronics'));
    }

    public function test_seller_adds_a_phone_with_color_by_storage_and_specs(): void
    {
        $seller = $this->makeSeller('electronics', 'Voltique Electronics');

        $this->actingAsUser($seller)
            ->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('Quick setup: colors × storage')
            ->assertSee('name="specs[ram]"', false);

        $this->actingAsUser($seller)->post(route('seller.products.store'), [
            'name' => 'Galaxy Z Test',
            'category' => 'electronics',
            'price' => 50000,
            'stock' => 10,
            'description' => 'A phone — sample product for the tests.', 'weight_kg' => 0.5,
            'photos' => [$this->photo()],
            'specs' => ['device_type' => 'Phone', 'ram' => '12GB', 'screen' => '6.8" AMOLED', 'network' => '5G'],
            'variations' => [
                ['type' => 'Color', 'value' => 'Black', 'type2' => 'Storage', 'value2' => '256GB', 'price_adjustment' => 0, 'stock' => 4],
                ['type' => 'Color', 'value' => 'Black', 'type2' => 'Storage', 'value2' => '512GB', 'price_adjustment' => 6000, 'stock' => 2],
            ],
        ])->assertRedirect();

        $product = Product::where('name', 'Galaxy Z Test')->firstOrFail();
        $this->assertSame(['device_type' => 'Phone', 'ram' => '12GB', 'screen' => '6.8" AMOLED', 'network' => '5G'], $product->specs);
        $this->assertSame(2, $product->variations()->where('option2_type', 'Storage')->count());

        // The product page shows the Specifications, with the storage sizes in order.
        $this->get(route('product.details', $product->id))
            ->assertOk()
            ->assertSee('Specifications')
            ->assertSee('12GB')
            ->assertSee('256GB / 512GB')
            ->assertSeeInOrder(['data-value="256GB"', 'data-value="512GB"'], false);
    }

    public function test_specs_are_ignored_for_other_categories(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)->post(route('seller.products.store'), [
            'name' => 'Runner',
            'category' => 'shoes',
            'price' => 1500,
            'stock' => 5,
            'description' => 'Shoes — sample product for the tests.', 'weight_kg' => 0.5,
            'photos' => [$this->photo()],
            'specs' => ['ram' => '8GB'],
        ])->assertRedirect();

        $this->assertNull(Product::where('name', 'Runner')->value('specs'));
    }

    public function test_editing_keeps_or_changes_the_specs(): void
    {
        $seller = $this->makeSeller('electronics');
        $phone = $this->phone($seller, ['specs' => ['ram' => '8GB']]);
        $form = ['name' => $phone->name, 'category' => 'electronics', 'price' => 89999, 'stock' => 10, 'description' => 'A phone — sample product for the tests.', 'weight_kg' => 0.5];

        // A form without specs (e.g. the mobile app) leaves them alone.
        $this->actingAsUser($seller)->put(route('seller.products.update', $phone->id), $form)->assertRedirect();
        $this->assertSame(['ram' => '8GB'], $phone->fresh()->specs);

        $this->actingAsUser($seller)->put(route('seller.products.update', $phone->id), $form + ['specs' => ['ram' => '12GB', 'battery' => '4,800 mAh']])->assertRedirect();
        $this->assertSame(['ram' => '12GB', 'battery' => '4,800 mAh'], $phone->fresh()->specs);
    }

    public function test_adding_storage_to_a_phone_that_only_has_colors(): void
    {
        $seller = $this->makeSeller('electronics');
        $phone = $this->phone($seller);
        $maroon = $this->addVariation($phone, 'Maroon', 8);
        $this->addVariation($phone, 'White', 3);

        $this->actingAsUser($seller)
            ->get(route('seller.products.variations', $phone->id))
            ->assertOk()
            ->assertSee('Storage (GB)');

        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), [
            'storage' => [
                '256GB' => ['on' => '1', 'extra' => '0'],
                '512GB' => ['on' => '1', 'extra' => '10000'],
                '1TB' => ['extra' => '20000'], // not ticked
            ],
            'stock' => 5,
        ])->assertSessionHas('success');

        $rows = ProductVariation::where('product_id', $phone->id)->get();
        $this->assertCount(4, $rows);

        // Maroon's own row became 256GB, keeping its stock (and photos).
        $maroon->refresh();
        $this->assertSame(['Storage', '256GB', 8], [$maroon->option2_type, $maroon->option2_value, $maroon->stock]);

        $white512 = $rows->first(fn ($v) => $v->variation_value === 'White' && $v->option2_value === '512GB');
        $this->assertSame([10000.0, 5], [(float) $white512->price_adjustment, $white512->stock]);

        // Running it again with the same sizes adds nothing.
        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), [
            'storage' => ['256GB' => ['on' => '1', 'extra' => '0']],
        ])->assertSessionHas('success', 'Every color already has those storage sizes.');
        $this->assertSame(4, ProductVariation::where('product_id', $phone->id)->count());

        // The buyer can put a specific color and storage in the cart, at its price.
        $buyer = $this->makeUser();
        $this->actingAsUser($buyer)
            ->postJson(route('cart.add', $phone->id), ['variation_id' => $white512->id], $this->ajaxHeaders())
            ->assertOk()
            ->assertSessionHas('cart', ["{$phone->id}:{$white512->id}" => 1]);
        $this->assertSame('Color: White · Storage: 512GB', $white512->label());
    }

    public function test_storage_on_a_phone_with_no_options_and_the_rules(): void
    {
        $seller = $this->makeSeller('electronics');
        $phone = $this->phone($seller);

        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), [
            'storage' => ['128GB' => ['on' => '1', 'extra' => '0'], '256GB' => ['on' => '1', 'extra' => '5000']],
            'stock' => 3,
        ])->assertSessionHas('success');
        $this->assertSame(['128GB', '256GB'], ProductVariation::where('product_id', $phone->id)->where('variation_type', 'Storage')->orderBy('id')->pluck('variation_value')->all());

        // Nothing ticked.
        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), ['storage' => []])
            ->assertSessionHas('error', 'Tick at least one storage size.');

        // An extra that brings the price to ₱0 or less.
        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), ['storage' => ['512GB' => ['on' => '1', 'extra' => '-90000']]])
            ->assertSessionHas('error');

        // Not for other categories, and not someone else's product.
        $shoes = $this->makeProduct($this->makeSeller('shoes'));
        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $shoes->id), ['storage' => ['64GB' => ['on' => '1']]])->assertNotFound();
    }

    public function test_storage_is_refused_when_the_second_option_is_something_else(): void
    {
        $seller = $this->makeSeller('electronics');
        $phone = $this->phone($seller);
        ProductVariation::create(['product_id' => $phone->id, 'variation_type' => 'Color', 'variation_value' => 'Black', 'option2_type' => 'Size', 'option2_value' => 'Large', 'price_adjustment' => 0, 'stock' => 1]);

        $this->actingAsUser($seller)->post(route('seller.products.variations.storage', $phone->id), ['storage' => ['64GB' => ['on' => '1']]])
            ->assertSessionHas('error');
        $this->assertSame(1, ProductVariation::where('product_id', $phone->id)->count());
    }
}
