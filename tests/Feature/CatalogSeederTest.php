<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StoreCategory;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SellerShopsSeeder;
use Database\Seeders\SortingCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The demo catalog: store categories, options, a photo per color. */
class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_fills_the_shops_with_photos_and_can_run_twice(): void
    {
        Storage::fake('public');
        $this->seed([SortingCenterSeeder::class, SellerShopsSeeder::class, CatalogSeeder::class]);

        $this->assertSame(30, Product::count());
        $this->seed(CatalogSeeder::class);
        $this->assertSame(30, Product::count());

        // Every product is on one of its shop's shelves; a product whose photos are in
        // database/seeders/catalog/ gets a cover that exists (the rest wait for their photos).
        foreach (Product::with('storeCategories')->get() as $product) {
            if ($product->image) {
                Storage::disk('public')->assertExists($product->image);
            }
            $this->assertCount(1, $product->storeCategories, $product->name);
            $this->assertSame($product->seller_id, $product->storeCategories->first()->seller_id);
        }

        // Shelves: Phones, Chargers & Cables, Audio › Earbuds & Headphones / Speakers.
        $voltique = User::where('email', 'voltique@boombuy.test')->first();
        $this->assertSame(5, StoreCategory::where('seller_id', $voltique->id)->count());
        $audio = StoreCategory::where('seller_id', $voltique->id)->where('name', 'Audio')->first();
        $this->assertSame(2, $audio->children()->count());

        // iPhone 18 Pro: 4 colors × 3 storage, a photo per color, all in stock.
        $iphone = Product::where('name', 'Apple iPhone 18 Pro')->firstOrFail();
        $this->assertSame(12, $iphone->variations()->count());
        $this->assertSame(0, $iphone->variations()->where('stock', 0)->count());
        $this->assertSame(4, ProductImage::where('product_id', $iphone->id)->whereNotNull('product_variation_id')->count());
        $this->assertSame('A20 Pro (6-core CPU, 7-core GPU)', $iphone->specs['processor']);
        $this->assertEquals(94990, $iphone->price);

        // Demo sold-outs: Nova X5 Mint and 512GB, Linen Blouse Sage/XL.
        $nova = Product::where('name', 'Nova X5 5G Smartphone')->firstOrFail();
        $this->assertSame(0, (int) $nova->variations()->where('variation_value', 'Mint')->sum('stock'));
        $this->assertSame(0, (int) $nova->variations()->where('option2_value', '512GB')->sum('stock'));
        $blouse = Product::where('name', 'Linen Button Blouse')->firstOrFail();
        $this->assertSame(0, $blouse->variations()->where('variation_value', 'Sage')->where('option2_value', 'XL')->value('stock'));
        $this->assertGreaterThan(0, $blouse->variations()->where('variation_value', 'Sage')->where('option2_value', 'M')->value('stock'));

        // The product page shows the color chips with their photos.
        $this->get(route('product.details', Str::slug($iphone->name) . '-' . $iphone->id))
            ->assertOk()->assertSee('Burgundy')->assertSee('1TB')->assertSee('seed-voltique-iphone-18-pro-burgundy-');
        $this->get(route('products', ['category' => 'womens-fashion']))->assertOk()->assertSee('Satin Slip Dress');
    }
}
