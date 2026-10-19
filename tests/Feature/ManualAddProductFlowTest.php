<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StoreCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * A seller adds a phone by hand the way the Add Product form sends it
 * (Quick setup Color × Storage, one photo on one row per color, a photo of
 * all colors for all options) and the product page comes out like the demo
 * iPhone: every photo in the row, color photos first, the all-colors one last.
 */
class ManualAddProductFlowTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==') . $name
        );
    }

    public function test_a_phone_added_by_hand_comes_out_like_the_demo_iphone(): void
    {
        $seller = $this->makeSeller('electronics');
        $phones = StoreCategory::create(['seller_id' => $seller->id, 'name' => 'Phones', 'sort_order' => 1]);

        // What "Create options" makes: every color × every storage, one row each.
        $rows = [];
        foreach (['Black', 'Silver', 'Glacier', 'Burgundy'] as $color) {
            foreach (['256GB' => 0, '512GB' => 16000, '1TB' => 48000] as $size => $extra) {
                $rows[] = [
                    'type' => 'Color', 'type2' => 'Storage', 'value' => $color, 'value2' => $size,
                    'price_adjustment' => $extra, 'stock' => 5,
                    // The seller adds the color's photo on its first row only.
                    'photos' => $size === '256GB' ? [$this->photo(strtolower($color) . '.png')] : [],
                ];
            }
        }

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), [
                'action' => 'publish',
                'name' => 'Apple iPhone 18 Pro',
                'description' => 'The newest iPhone with the A20 Pro chip and a 48MP triple camera.',
                'price' => 94990, 'stock' => 1, 'category' => 'electronics', 'brand' => 'Apple', 'condition' => 'New', 'weight_kg' => 0.4,
                'specs' => ['device_type' => 'Phone', 'brand' => 'Apple', 'model' => 'iPhone 18 Pro', 'processor' => 'A20 Pro', 'network' => '5G'],
                'variations' => $rows,
                'photos' => [$this->photo('all-colors.png')],
                'store_categories' => [$phones->id], 'store_categories_sent' => 1,
            ])
            ->assertSessionHas('success');

        $product = Product::sole();
        $this->assertSame(12, $product->variations()->count());
        $this->assertSame(60, (int) $product->variations()->sum('stock'), 'each option keeps its own stock');
        $this->assertSame([$phones->id], $product->storeCategories()->pluck('store_categories.id')->all());
        $this->assertSame('A20 Pro', $product->specs['processor']);

        $black = $product->variations()->where('variation_value', 'Black')->where('option2_value', '256GB')->first();
        $this->assertSame($black->image, $product->image, 'the first color\'s photo is the cover');

        // The page: 5 photos in the row — 4 colors in order, the all-colors photo last.
        $this->flushSession();
        $html = $this->get(route('product.details', $product->id))->assertOk()->getContent();
        preg_match_all('/class="pd-thumb [^"]*"\s+data-src="([^"]+)"\s+data-group="([^"]+)"/', $html, $m);
        $this->assertCount(5, $m[1]);
        $this->assertSame('all', end($m[2]), 'the photo for all options comes last');
        $this->assertNotContains('all', array_slice($m[2], 0, 4));

        // Each color chip shows its own photo; 512GB/1TB rows borrow their color's.
        foreach (['Black', 'Silver', 'Glacier', 'Burgundy'] as $color) {
            $this->assertMatchesRegularExpression('/pd-part1" data-value="' . $color . '"[^>]*>\s*<img src="[^"]+"/', $html, $color . ' chip has a photo');
        }
        $this->assertStringContainsString('function followThumb', $html, 'a photo click picks its color');
        $this->assertStringContainsString('id="description"', $html, 'the full description has its own section');
        $this->assertStringContainsString('id="pdLightbox"', $html, 'tapping a photo opens the viewer');
    }

    public function test_clothes_added_by_hand_come_out_the_same_way(): void
    {
        $seller = $this->makeSeller('womens-fashion');

        // Typed by hand: Variation type Color, Second type Size, one row per color × size.
        $rows = [];
        foreach (['White', 'Beige', 'Sage'] as $color) {
            foreach (['S', 'M', 'L', 'XL'] as $size) {
                $rows[] = [
                    'type' => 'Color', 'type2' => 'Size', 'value' => $color, 'value2' => $size,
                    'price_adjustment' => $size === 'XL' ? 50 : 0, 'stock' => $color === 'Sage' && $size === 'XL' ? 0 : 6,
                    'photos' => $size === 'S' ? [$this->photo(strtolower($color) . '-blouse.png')] : [],
                ];
            }
        }

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), [
                'action' => 'publish', 'name' => 'Linen Button Blouse', 'category' => 'womens-fashion',
                'description' => 'Light, breathable linen-blend blouse that stays cool on hot days.',
                'price' => 599, 'stock' => 1, 'weight_kg' => 0.25,
                'variations' => $rows,
                'photos' => [$this->photo('flat-lay-all-colors.png')],
            ])
            ->assertSessionHas('success');

        $product = Product::sole();
        $this->flushSession();
        $html = $this->get(route('product.details', $product->id))->assertOk()->getContent();
        preg_match_all('/class="pd-thumb [^"]*"\s+data-src="([^"]+)"\s+data-group="([^"]+)"/', $html, $m);
        $this->assertCount(4, $m[1], '3 colors + the photo of all colors');
        $this->assertSame('all', end($m[2]));
        foreach (['White', 'Beige', 'Sage'] as $color) {
            $this->assertMatchesRegularExpression('/pd-part1" data-value="' . $color . '"[^>]*>\s*<img src="[^"]+"/', $html, $color . ' chip has a photo');
        }
        foreach (['S', 'M', 'L', 'XL'] as $size) {
            $this->assertStringContainsString('pd-part2" data-value="' . $size . '"', $html);
        }
    }
}
