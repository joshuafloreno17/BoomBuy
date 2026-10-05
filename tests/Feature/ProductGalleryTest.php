<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Product photos: each option can have its own, plus photos for all options.
 * The cover and each option's button photo are picked automatically, and the
 * chosen option's photo follows the item to the cart and the order.
 */
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
            // Bytes after the PNG end differ per name, so each name is a different picture.
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==') . $name
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
        ], $overrides);
    }

    private function thumbs(string $html): int
    {
        return substr_count($html, 'class="pd-thumb ');
    }

    public function test_photos_without_options_the_first_is_the_cover(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('name="photos[]"', false);

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->form(['photos' => [$this->photo('a.png'), $this->photo('b.png'), $this->photo('c.png')]]))
            ->assertSessionHas('success');

        $product = Product::sole();
        $this->assertCount(3, $product->images);
        $this->assertSame($product->images->first()->path, $product->image);

        $this->flushSession();
        $html = $this->get(route('product.details', $product->id))->assertOk()->getContent();
        $this->assertSame(3, $this->thumbs($html));
    }

    public function test_a_product_needs_a_photo_and_has_limits(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form())->assertSessionHas('error');

        $nine = array_map(fn ($i) => $this->photo("p{$i}.png"), range(1, 9));
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form(['photos' => $nine]))->assertSessionHas('error');

        $six = array_map(fn ($i) => $this->photo("o{$i}.png"), range(1, 6));
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form([
            'variations' => [['type' => 'Color', 'value' => 'Red', 'price_adjustment' => 0, 'stock' => 5, 'photos' => $six]],
        ]))->assertSessionHas('error');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_each_option_has_its_own_photos_and_the_page_groups_them(): void
    {
        $seller = $this->makeSeller('shoes');

        $this->actingAsUser($seller)
            ->post(route('seller.products.store'), $this->form([
                'photos' => [$this->photo('box.png')],
                'variations' => [
                    ['type' => 'Mount', 'value' => 'Air Vent', 'price_adjustment' => 0, 'stock' => 5, 'photos' => [$this->photo('vent1.png'), $this->photo('vent2.png')]],
                    ['type' => 'Mount', 'value' => 'Dashboard', 'price_adjustment' => 80, 'stock' => 5, 'photos' => [$this->photo('dash1.png')]],
                    ['type' => 'Mount', 'value' => 'Windshield', 'price_adjustment' => 50, 'stock' => 5],
                ],
            ]))
            ->assertSessionHas('success');

        $product = Product::sole();
        [$vent, $dash, $wind] = $product->variations()->orderBy('id')->get()->all();

        $this->assertCount(2, $vent->images);
        $this->assertSame($vent->images->first()->path, $vent->image, 'an option\'s first photo is its button photo');
        $this->assertSame($vent->image, $product->image, 'the first option\'s first photo is the cover');
        $this->assertSame($dash->images->first()->path, $dash->image);
        $this->assertNull($wind->image, 'an option without photos keeps none');

        $this->flushSession();
        $html = $this->get(route('product.details', $product->id))->assertOk()->getContent();
        $this->assertSame(4, $this->thumbs($html));
        $this->assertSame(2, substr_count($html, 'data-group="' . $vent->id . '"'));
        $this->assertSame(1, substr_count($html, 'data-group="' . $dash->id . '"'));
        $this->assertSame(1, substr_count($html, 'data-group="all"'));
    }

    public function test_seller_edits_photos_per_group(): void
    {
        $seller = $this->makeSeller('shoes');
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form([
            'photos' => [$this->photo('a.png'), $this->photo('b.png')],
            'variations' => [['type' => 'Color', 'value' => 'Red', 'price_adjustment' => 0, 'stock' => 5]],
        ]));

        $product = Product::sole();
        $red = $product->variations()->first();
        [$a] = $product->images->all();
        $this->assertSame($a->path, $product->image, 'no option photos yet: the first photo for all options is the cover');

        $this->actingAsUser($seller)
            ->get(route('seller.products.edit', $product->id))
            ->assertOk()
            ->assertSee('Photos for Red')
            ->assertSee('Photos for all options')
            ->assertSee('name="option_photos[' . $red->id . '][]"', false);

        $this->actingAsUser($seller)
            ->put(route('seller.products.update', $product->id), $this->form([
                'remove_photos' => [$a->id],
                'option_photos' => [$red->id => [$this->photo('red.png')]],
            ]))
            ->assertSessionHas('success');

        $product->refresh();
        $red->refresh();
        Storage::disk('public')->assertMissing($a->path);
        $this->assertNotNull($red->image);
        $this->assertSame($red->image, $product->image, 'now the first option has a photo, it is the cover');
        $this->assertCount(2, $product->images);

        // Removing every photo is refused.
        $this->actingAsUser($seller)
            ->put(route('seller.products.update', $product->id), $this->form(['remove_photos' => $product->images->pluck('id')->all()]))
            ->assertSessionHas('error');
        $this->assertCount(2, $product->fresh()->images);
    }

    public function test_deleting_an_option_or_the_product_removes_its_photos(): void
    {
        $seller = $this->makeSeller('shoes');
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form([
            'photos' => [$this->photo('all.png')],
            'variations' => [
                ['type' => 'Color', 'value' => 'Red', 'price_adjustment' => 0, 'stock' => 5, 'photos' => [$this->photo('red.png')]],
                ['type' => 'Color', 'value' => 'Blue', 'price_adjustment' => 0, 'stock' => 5, 'photos' => [$this->photo('blue.png')]],
            ],
        ]));

        $product = Product::sole();
        [$red, $blue] = $product->variations()->orderBy('id')->get()->all();
        $redPath = $red->image;

        $this->actingAsUser($seller)->delete(route('seller.products.variations.delete', [$product->id, $red->id]))->assertSessionHas('success');
        Storage::disk('public')->assertMissing($redPath);
        $this->assertSame($blue->fresh()->image, $product->fresh()->image, 'the next option\'s photo becomes the cover');

        $paths = ProductImage::pluck('path')->all();
        $this->actingAsUser($seller)->delete(route('seller.products.delete', $product->id))->assertSessionHas('success');
        $this->assertSame(0, ProductImage::count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_the_chosen_option_photo_follows_to_cart_and_order(): void
    {
        $seller = $this->makeSeller('shoes');
        $buyer = $this->makeUser();
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form([
            'photos' => [$this->photo('all.png')],
            'variations' => [
                ['type' => 'Mount', 'value' => 'Air Vent', 'price_adjustment' => 0, 'stock' => 5, 'photos' => [$this->photo('vent.png')]],
                ['type' => 'Mount', 'value' => 'Dashboard', 'price_adjustment' => 80, 'stock' => 5, 'photos' => [$this->photo('dash.png')]],
            ],
        ]));

        $product = Product::sole();
        $dash = $product->variations()->where('variation_value', 'Dashboard')->first();
        $cart = ["{$product->id}:{$dash->id}" => 1];

        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => $cart])
            ->get(route('cart'))
            ->assertOk()
            ->assertSee($dash->image, false);

        $this->actingAsUser($buyer, ['cart' => $cart])
            ->post('/checkout/place-order', ['address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertRedirect();

        $this->assertSame($dash->image, DB::table('order_items')->value('variation_image'));

        $this->actingAsUser($buyer)
            ->get(route('buyer.orders'))
            ->assertOk()
            ->assertSee($dash->image, false);
    }
    public function test_seller_picks_any_photo_as_the_shop_cover(): void
    {
        $seller = $this->makeSeller('shoes');

        // On Add Product: the 2nd photo for all options, although an option has photos.
        $this->actingAsUser($seller)->post(route('seller.products.store'), $this->form([
            'photos' => [$this->photo('box.png'), $this->photo('size.png')],
            'variations' => [['type' => 'Color', 'value' => 'Red', 'price_adjustment' => 0, 'stock' => 5, 'photos' => [$this->photo('red.png')]]],
            'cover_ref' => 'all:1',
        ]))->assertSessionHas('success');

        $product = Product::sole();
        $size = $product->images()->whereNull('product_variation_id')->get()[1];
        $this->assertSame($size->path, $product->image);

        // Saving Edit with the current cover still ticked changes nothing.
        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), $this->form(['cover_photo' => $size->id]))->assertSessionHas('success');
        $this->assertSame($size->path, $product->fresh()->image);

        // On Edit: pick the option photo instead.
        $red = $product->variations()->first()->images()->first();
        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), $this->form(['cover_photo' => $red->id]))->assertSessionHas('success');
        $this->assertSame($red->path, $product->fresh()->image);
        $this->assertSame(1, ProductImage::where('is_cover', true)->count());

        // Removing the picked cover falls back to the automatic one.
        $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), $this->form(['remove_photos' => [$red->id]]))->assertSessionHas('success');
        $this->assertSame($product->images()->whereNull('product_variation_id')->first()->path, $product->fresh()->image);
    }
}
