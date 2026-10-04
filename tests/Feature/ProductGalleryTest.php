<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** Several photos per product: added on the seller forms, shown as thumbnails. */
class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    /** A real 1x1 PNG (this machine has no GD, so fake()->image() can't draw one). */
    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Car Phone Holder',
            'category' => 'shoes',
            'price' => 349,
            'stock' => 10,
            'description' => 'Holds your phone.',
            'image' => $this->photo('cover.png'),
        ], $overrides);
    }

    public function test_seller_adds_a_product_with_extra_photos_and_buyers_see_thumbnails(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('name="photos[]"', false);

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->form(['photos' => [$this->photo('a.png'), $this->photo('b.png')]]))
            ->assertSessionHas('success');

        $product = Product::sole();
        $this->assertCount(2, $product->images);
        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }

        // Cover + 2 extra photos = 3 thumbnails on the product page.
        $this->flushSession();
        $html = $this->get(route('product.details', $product->id))->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'class="pd-thumb '));
    }

    public function test_a_product_holds_at_most_eight_photos(): void
    {
        $seller = $this->makeSeller('shoes');
        $eight = array_map(fn ($i) => $this->photo("p{$i}.png"), range(1, 8));

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->form(['photos' => $eight]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_seller_removes_adds_and_picks_a_new_cover(): void
    {
        $seller = $this->makeSeller('shoes');
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form(['photos' => [$this->photo('a.png'), $this->photo('b.png')]]));

        $product = Product::sole();
        [$first, $second] = $product->images->all();
        $oldCover = $product->image;

        $this->actingAsUser($seller)
            ->get(route('seller.products.edit', $product->id))
            ->assertOk()
            ->assertSee('name="remove_photos[]"', false)
            ->assertSee('Make cover');

        $this->actingAsUser($seller)
            ->put(route('seller.products.update', $product->id), [
                'name' => $product->name, 'category' => 'shoes', 'price' => 349, 'stock' => 10, 'description' => 'Holds your phone.',
                'remove_photos' => [$first->id],
                'cover_photo' => $second->id,
                'photos' => [$this->photo('c.png')],
            ])
            ->assertSessionHas('success');

        $product->refresh();
        Storage::disk('public')->assertMissing($first->path);
        $this->assertSame($second->path, $product->image, 'the chosen photo is the cover now');
        $this->assertTrue($product->images->pluck('path')->contains($oldCover), 'the old cover became an extra photo');
        $this->assertCount(2, $product->images);
    }

    public function test_deleting_a_product_deletes_its_photos(): void
    {
        $seller = $this->makeSeller('shoes');
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form(['photos' => [$this->photo('a.png')]]));

        $product = Product::sole();
        $paths = [$product->image, $product->images->first()->path];

        $this->actingAsUser($seller)->delete(route('seller.products.delete', $product->id))->assertSessionHas('success');

        $this->assertSame(0, ProductImage::count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }
}
