<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Product;
use App\Support\ProhibitedItems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Seller compliance auto-flag: a listing that mentions a prohibited item is
 * hidden on save and waits for the admin; the admin edits the word list in
 * Settings, and a newly added word is checked against products already listed.
 */
class ProhibitedItemsTest extends TestCase
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
        return UploadedFile::fake()->createWithContent('item.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function addProduct($seller, string $name, string $description): Product
    {
        $this->actingAsUser($seller)->post(route('seller.products.store'), [
            'action' => 'publish',
            'name' => $name,
            'description' => $description,
            'price' => 500, 'stock' => 5, 'category' => 'sports-outdoors', 'weight_kg' => 0.5,
            'photos' => [$this->photo()],
        ])->assertSessionHasNoErrors();
        $this->flushSession();

        return Product::where('name', $name)->sole();
    }

    public function test_whole_words_only(): void
    {
        $words = ['pistol', 'class a', 'ammo'];

        $this->assertSame('pistol', ProhibitedItems::find($words, 'Water PISTOL for kids'));
        $this->assertSame('class a', ProhibitedItems::find($words, 'Shoes', 'Class  A quality copy'));
        $this->assertNull(ProhibitedItems::find($words, 'Pistols-free', 'Ammonia cleaner, first class all around'));
    }

    public function test_a_listing_with_a_prohibited_word_is_hidden_on_save(): void
    {
        $seller = $this->makeSeller('sports-outdoors');
        $this->actingAsAdmin();
        $admin = \App\Support\SupportAccount::user();
        $this->flushSession();

        $bad = $this->addProduct($seller, 'Airsoft Rifle Scope', 'Fits any rifle rail, 4x zoom.');
        $ok = $this->addProduct($seller, 'Yoga Mat', 'Non-slip yoga mat, 6 mm thick.');

        $this->assertTrue((bool) $bad->is_flagged);
        $this->assertStringContainsString('"rifle"', $bad->flag_reason);
        $this->assertFalse((bool) $ok->is_flagged);
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'type' => 'compliance_warning', 'reference_id' => $bad->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => 'Product Auto-flagged', 'reference_id' => $bad->id]);

        // The seller is told right away, and buyers can't open it.
        $this->actingAsUser($seller)->post(route('seller.products.store'), [
            'action' => 'publish', 'name' => 'Ammo Box', 'description' => 'Metal ammo box with a rubber seal.',
            'price' => 300, 'stock' => 2, 'category' => 'sports-outdoors', 'weight_kg' => 0.5, 'photos' => [$this->photo()],
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'hidden from the Shop'));
        $this->flushSession();

        $this->actingAsAdmin()->get(route('admin.compliance', ['view' => 'issues']))->assertOk()->assertSee('Airsoft Rifle Scope');
    }

    public function test_cleared_by_admin_stays_cleared_until_the_words_change(): void
    {
        $seller = $this->makeSeller('sports-outdoors');
        $product = $this->addProduct($seller, 'Replica Jersey', 'Home kit for the 2026 season.');
        $this->assertTrue((bool) $product->is_flagged);

        $this->actingAsAdmin()->post(route('admin.compliance.unflag', $product->id))->assertSessionHas('success');
        $this->flushSession();

        $edit = fn (array $changes) => $this->actingAsUser($seller)->put(route('seller.products.update', $product->id), array_merge([
            'name' => 'Replica Jersey', 'description' => 'Home kit for the 2026 season.', 'price' => 500, 'stock' => 5, 'category' => 'sports-outdoors', 'weight_kg' => 0.5, 'weight_kg' => 0.5,
        ], $changes));

        // Price change only: stays visible.
        $edit(['price' => 450])->assertSessionHas('success');
        $this->assertFalse((bool) $product->fresh()->is_flagged);

        // New description that still mentions it: checked again.
        $this->flushSession();
        $edit(['description' => 'Home kit for the 2026 season, replica.'])->assertSessionHas('error');
        $this->assertTrue((bool) $product->fresh()->is_flagged);
    }

    public function test_admin_edits_the_list_and_new_words_hit_listed_products(): void
    {
        $seller = $this->makeSeller('sports-outdoors');
        $vape = $this->makeProduct($seller, ['name' => 'Vape Pen Kit', 'category' => 'Sports & Outdoors']);
        $cleared = $this->makeProduct($seller, ['name' => 'Replica Ball', 'category' => 'Sports & Outdoors']);

        $list = implode("\n", array_merge(ProhibitedItems::DEFAULT, ['Vape']));

        $this->actingAsAdmin()->get(route('admin.settings'))->assertOk()->assertSee('Prohibited Items')->assertSee('counterfeit');
        $this->post(route('admin.settings.prohibited.update'), ['prohibited_words' => $list])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 listed product'));

        $this->assertContains('vape', ProhibitedItems::words());
        $this->assertTrue((bool) $vape->fresh()->is_flagged);
        // "replica" was already on the list: products already listed aren't re-checked for it.
        $this->assertFalse((bool) $cleared->fresh()->is_flagged);

        // An empty list is refused; the saved one stays.
        $this->post(route('admin.settings.prohibited.update'), ['prohibited_words' => " \n , "])->assertSessionHas('error');
        $this->assertCount(count(ProhibitedItems::DEFAULT) + 1, ProhibitedItems::parse(PlatformSetting::get(ProhibitedItems::SETTING)));
    }
}
