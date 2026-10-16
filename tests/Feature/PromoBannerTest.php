<?php

namespace Tests\Feature;

use App\Models\PromoBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** Admin → Promo Banners and the promo hero on the landing page and buyer home. */
class PromoBannerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('public');
    }

    private function banner(array $overrides = []): PromoBanner
    {
        return PromoBanner::create(array_merge([
            'placement' => 'main',
            'title' => 'Mega Sale ' . uniqid(),
            'link_type' => 'products',
            'style' => 'violet',
            'is_active' => true,
        ], $overrides));
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'placement' => 'main',
            'title' => '11.11 Mega Sale',
            'subtitle' => 'Up to 50% off.',
            'big_text' => '11.11',
            'button_label' => 'Shop the sale',
            'link_type' => 'deals',
            'style' => 'plum',
        ], $overrides);
    }

    public function test_only_the_admin_can_open_or_change_banners(): void
    {
        $this->get(route('admin.banners'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.banners.store'), $this->form())->assertRedirect(route('admin.login'));

        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->post(route('admin.banners.store'), $this->form())->assertRedirect(route('admin.login'));

        $this->assertSame(0, PromoBanner::count());
    }

    public function test_admin_adds_a_banner_with_a_picture_and_it_shows_on_the_landing_page(): void
    {
        $this->actingAsAdmin()->get(route('admin.banners'))->assertOk()->assertSee('Promo Banners');

        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form([
            'image' => UploadedFile::fake()->createWithContent('promo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')),
        ]))->assertRedirect(route('admin.banners'))->assertSessionHas('success');

        $banner = PromoBanner::sole();
        $this->assertSame('deals', $banner->link_type);
        $this->assertNotNull($banner->image);
        Storage::disk('public')->assertExists($banner->image);

        $this->get(route('home'))->assertOk()
            ->assertSee('11.11 Mega Sale')
            ->assertSee('Shop the sale')
            ->assertSee(route('products', ['deals' => 1]), false);
    }

    public function test_link_targets_are_required_and_must_exist(): void
    {
        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form(['link_type' => 'category']))
            ->assertSessionHasErrors('link_category');
        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form(['link_type' => 'product', 'link_product' => 99999]))
            ->assertSessionHasErrors('link_product');

        $buyer = $this->makeUser('buyer');
        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form(['link_type' => 'shop', 'link_shop' => $buyer->id]))
            ->assertSessionHasErrors('link_shop');

        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form(['starts_at' => '2026-12-10', 'ends_at' => '2026-12-01']))
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, PromoBanner::count());

        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        $this->actingAsAdmin()->post(route('admin.banners.store'), $this->form(['link_type' => 'product', 'link_product' => $product->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame(route('product.details', $product->id), PromoBanner::sole()->url());
    }

    public function test_scheduled_ended_and_switched_off_banners_stay_hidden(): void
    {
        $this->banner(['title' => 'Live One']);
        $this->banner(['title' => 'Later One', 'starts_at' => now()->addDays(3)->toDateString()]);
        $this->banner(['title' => 'Old One', 'ends_at' => now()->subDay()->toDateString()]);
        $this->banner(['title' => 'Off One', 'is_active' => false]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Live One')
            ->assertDontSee('Later One')
            ->assertDontSee('Old One')
            ->assertDontSee('Off One');

        $statuses = PromoBanner::orderBy('id')->get()->map->status()->all();
        $this->assertSame(['Live', 'Scheduled', 'Ended', 'Off'], $statuses);
    }

    public function test_side_tiles_show_their_own_banner_and_the_buyer_home_uses_the_hero(): void
    {
        $this->banner(['title' => 'Main Banner']);
        $this->banner(['title' => 'Top Tile Banner', 'placement' => 'side_top', 'style' => 'plum']);
        $this->banner(['title' => 'Bottom Tile Banner', 'placement' => 'side_bottom', 'style' => 'lavender', 'big_text' => '-40%']);

        $buyer = $this->makeUser('buyer');

        $this->actingAsUser($buyer)->get(route('buyer.dashboard'))->assertOk()
            ->assertSee('Main Banner')
            ->assertSee('Top Tile Banner')
            ->assertSee('Bottom Tile Banner')
            ->assertSee('-40%');
    }

    public function test_without_banners_the_hero_falls_back_to_products_with_photos(): void
    {
        $seller = $this->makeSeller();
        $this->makeProduct($seller, ['name' => 'Photo Watch', 'image' => 'products/watch.png']);
        $this->makeProduct($seller, ['name' => 'No Photo Mug', 'image' => null]);

        $page = $this->get(route('home'))->assertOk();
        $page->assertSee('Photo Watch');
        $page->assertSee('Free shipping from');

        // The mug may be in "Featured products" lower on the page, but never as a hero slide.
        $hero = \App\Support\PromoHero::build();
        $this->assertSame(['Photo Watch'], array_column($hero['main'], 'title'));
    }

    public function test_admin_edits_toggles_and_deletes_a_banner(): void
    {
        $banner = $this->banner([
            'title' => 'Before',
            'image' => UploadedFile::fake()->create('x.png', 1, 'image/png')->store('promo-banners', 'public'),
        ]);

        $this->actingAsAdmin()->get(route('admin.banners', ['edit' => $banner->id]))->assertOk()->assertSee('Edit banner');

        $this->actingAsAdmin()->post(route('admin.banners.update', $banner->id), $this->form([
            'title' => 'After',
            'link_type' => 'category',
            'link_category' => 'electronics',
            'remove_image' => 1,
        ]))->assertRedirect(route('admin.banners'));

        $old = $banner->image;
        $banner->refresh();
        $this->assertSame('After', $banner->title);
        $this->assertSame('electronics', $banner->link_value);
        $this->assertNull($banner->image);
        Storage::disk('public')->assertMissing($old);

        $this->actingAsAdmin()->post(route('admin.banners.toggle', $banner->id));
        $this->assertFalse($banner->refresh()->is_active);

        $this->actingAsAdmin()->delete(route('admin.banners.delete', $banner->id))->assertRedirect(route('admin.banners'));
        $this->assertSame(0, PromoBanner::count());
    }

    public function test_the_shop_on_sale_filter_lists_only_discounted_products(): void
    {
        $seller = $this->makeSeller();
        $this->makeProduct($seller, ['name' => 'Cut Price Shoe', 'price' => 600, 'original_price' => 1000, 'discount_percent' => 40]);
        $this->makeProduct($seller, ['name' => 'Full Price Shoe', 'price' => 900]);

        $this->get(route('products', ['deals' => 1]))->assertOk()
            ->assertSee('Cut Price Shoe')
            ->assertDontSee('Full Price Shoe')
            ->assertSee('On sale only');
    }
}
