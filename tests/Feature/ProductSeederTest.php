<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariation;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SellerShopsSeeder;
use Database\Seeders\SortingCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The sample products for a demo / system check. */
class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_products_fill_every_shop_and_can_run_twice(): void
    {
        $this->seed([SortingCenterSeeder::class, SellerShopsSeeder::class, ProductSeeder::class]);

        $this->assertSame(64, Product::count());
        $this->assertSame(16, Product::distinct()->count('category'));

        // Running it again adds nothing.
        $this->seed(ProductSeeder::class);
        $this->assertSame(64, Product::count());

        // Color × Size, discounts and the shop pages.
        $dress = Product::where('name', 'Floral Wrap Dress')->firstOrFail();
        $this->assertSame(6, $dress->variations()->count());
        $this->assertTrue($dress->isDiscounted());
        $this->assertEquals(719.2, $dress->price);
        $this->assertSame('Size', ProductVariation::where('product_id', $dress->id)->value('option2_type'));

        $this->get(route('products', ['category' => 'womens-fashion']))->assertOk()->assertSee('Floral Wrap Dress')->assertSee('-20%');
        $this->get(route('product.details', Str::slug($dress->name) . '-' . $dress->id))->assertOk()->assertSee('Blush');
    }
}
