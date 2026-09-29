<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class SellerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    private function productForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Classic Canvas Sneakers',
            'category' => 'shoes',
            'price' => 1500,
            'stock' => 5,
            'description' => 'Everyday canvas sneakers.',
            'image' => UploadedFile::fake()->create('shoe.jpg', 30, 'image/jpeg'),
        ], $overrides);
    }

    public function test_add_product_form_is_locked_to_the_registered_category(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('name="category" value="shoes"', false)
            ->assertDontSee('<option value="electronics"', false);
    }

    public function test_seller_cannot_list_a_product_outside_their_category(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->productForm(['category' => 'electronics']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_seller_lists_a_product_in_their_category(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->productForm());

        $this->assertDatabaseHas('products', ['seller_id' => $seller->id, 'name' => 'Classic Canvas Sneakers', 'category' => 'Shoes']);
    }

    public function test_seller_cannot_move_a_product_to_another_category(): void
    {
        $seller = $this->makeSeller('shoes');
        $product = $this->makeProduct($seller);

        $this->actingAsUser($seller)
            ->put(route('seller.products.update', $product->id), [
                'name' => $product->name, 'category' => 'automotive',
                'price' => 500, 'stock' => 10, 'description' => 'x',
            ])
            ->assertSessionHas('error');

        $this->assertSame('Shoes', $product->fresh()->category);
    }

    public function test_seller_updates_shop_name_and_description(): void
    {
        $seller = $this->makeSeller('shoes', 'Old Name');

        $this->actingAsUser($seller)
            ->post(route('seller.shop.update'), ['business_name' => 'Stride Footwear Co.', 'shop_description' => 'Comfortable shoes for every day.'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('seller_applications', ['user_id' => $seller->id, 'business_name' => 'Stride Footwear Co.']);

        $this->get(route('shop.seller', $seller->id))
            ->assertOk()
            ->assertSee('Stride Footwear Co.')
            ->assertSee('Comfortable shoes for every day.');
    }

    public function test_seller_pages_load(): void
    {
        $seller = $this->makeSeller('shoes', 'Stride Footwear Co.');

        foreach (['seller.dashboard', 'seller.orders', 'seller.products.create'] as $route) {
            $this->actingAsUser($seller)->get(route($route))->assertOk();
        }

        $this->actingAsUser($seller)
            ->get(route('seller.profile'))
            ->assertOk()
            ->assertSee('Shop Profile')
            ->assertSee('value="Stride Footwear Co."', false);
    }

    public function test_two_shops_cannot_share_a_name(): void
    {
        $this->makeSeller('electronics', 'Voltique Electronics');
        $seller = $this->makeSeller('shoes', 'Stride Footwear Co.');

        $this->actingAsUser($seller)
            ->post(route('seller.shop.update'), ['business_name' => 'voltique electronics'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('seller_applications', ['user_id' => $seller->id, 'business_name' => 'Stride Footwear Co.']);
    }
}
