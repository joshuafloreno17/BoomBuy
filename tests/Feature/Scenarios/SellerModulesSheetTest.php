<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The classmate's Seller Modules workbook (S1–S12): one test per row,
 * named after its id (SEL-PROF-001 → test_SEL_PROF_001_…), with the Test
 * Data sheet's accounts and products (see ModuleSheetWorld).
 */
class SellerModulesSheetTest extends TestCase
{
    use RefreshDatabase;
    use ModuleSheetWorld;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(10, 0));
    }

    /* ---------------------------------------------------------------
     | S1 Store Profile
     * --------------------------------------------------------------- */

    private function storeForm(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Maria Clara Handicrafts',
            'shop_description' => 'Handwoven bags and home decor.',
            'shop_phone' => '09189876543',
        ], $overrides);
    }

    private function saveStore(array $overrides = [])
    {
        return $this->as($this->seller1())->from(route('seller.profile'))->post(route('seller.shop.update'), $this->storeForm($overrides));
    }

    private function application(int $sellerId): object
    {
        return DB::table('seller_applications')->where('user_id', $sellerId)->first();
    }

    public function test_SEL_PROF_001_update_store_profile_with_all_valid_data(): void
    {
        $seller2Before = (array) $this->application($this->seller2()->id);

        $this->saveStore([
            'business_name' => 'Maria Clara Handicrafts PH',
            'shop_description' => 'Handwoven bags and home decor from Bulacan artisans.',
            'shop_phone' => '09189876543',
            'shop_logo' => $this->png('store_logo.png'),
            'shop_banner' => $this->jpegOfSize('store_banner.jpg', (int) (1.8 * 1048576)),
        ])->assertRedirect(route('seller.profile'))->assertSessionHas('success', 'Store settings saved.');

        $app = $this->application($this->seller1()->id);
        Storage::disk('public')->assertExists($app->shop_logo);
        Storage::disk('public')->assertExists($app->shop_banner);

        // After refresh every field shows the saved value.
        $this->as($this->seller1())->get(route('seller.profile'))->assertOk()
            ->assertSee('value="Maria Clara Handicrafts PH"', false)
            ->assertSee('Handwoven bags and home decor from Bulacan artisans.')
            ->assertSee('value="09189876543"', false)
            ->assertSee('storage/' . $app->shop_logo, false)
            ->assertSee('storage/' . $app->shop_banner, false);

        // The buyer's store page shows the new name, description, logo and banner.
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertOk()
            ->assertSee('Maria Clara Handicrafts PH')
            ->assertSee('Handwoven bags and home decor from Bulacan artisans.')
            ->assertSee('storage/' . $app->shop_logo, false)
            ->assertSee('storage/' . $app->shop_banner, false);

        // The store search finds the new name; the old one is gone.
        $this->get(route('products', ['search' => 'Maria Clara Handicrafts PH']))->assertOk()
            ->assertSee('Maria Clara Handicrafts PH')
            ->assertSee(route('shop.seller', $this->seller1()->id), false);
        $this->assertSame(0, DB::table('seller_applications')->where('business_name', 'Maria Clara Handicrafts')->count());

        // No other store's data changes.
        $this->assertEquals($seller2Before, (array) $this->application($this->seller2()->id));
    }

    public function test_SEL_PROF_002_each_required_field_left_blank(): void
    {
        $before = (array) $this->application($this->seller1()->id);

        $this->saveStore(['business_name' => ''])->assertSessionHasErrors(['business_name'])->assertSessionDoesntHaveErrors(['shop_phone']);
        $this->saveStore(['shop_phone' => ''])->assertSessionHasErrors(['shop_phone'])->assertSessionDoesntHaveErrors(['business_name']);
        $this->assertEquals($before, (array) $this->application($this->seller1()->id));

        // The pickup address (Profile Information), one part at a time.
        $userBefore = DB::table('users')->where('id', $this->seller1()->id)->first();
        $profile = fn (array $o) => $this->as($this->seller1())->from(route('seller.profile'))->post(route('seller.profile.update'), array_merge([
            'name' => 'Maria Clara Santos', 'phone' => '09189876543',
            'address' => 'Blk 12 Lot 8, Sampaguita St., Commonwealth, Quezon City, Metro Manila (NCR)',
        ], $o));

        $profile(['address' => ''])->assertSessionHasErrors(['address' => 'Please choose the province of your pickup address.'])->assertSessionDoesntHaveErrors(['name', 'phone']);
        $profile(['address' => 'Blk 12 Lot 8, Sampaguita St., Commonwealth, Metro Manila (NCR)'])->assertSessionHasErrors(['address' => 'Please choose the city / municipality of your pickup address.']);
        $profile(['address' => 'Quezon City, Metro Manila (NCR)'])->assertSessionHasErrors(['address' => 'Please enter the house no., street and barangay of your pickup address.']);
        $profile(['name' => ''])->assertSessionHasErrors(['name'])->assertSessionDoesntHaveErrors(['phone', 'address']);
        $profile(['phone' => ''])->assertSessionHasErrors(['phone'])->assertSessionDoesntHaveErrors(['name', 'address']);

        $this->assertEquals($userBefore, DB::table('users')->where('id', $this->seller1()->id)->first());
    }

    public function test_SEL_PROF_003_fields_containing_only_spaces(): void
    {
        $this->saveStore(['business_name' => '     '])->assertSessionHasErrors(['business_name' => 'Please enter your shop name.']);
        $this->assertSame('Maria Clara Handicrafts', $this->application($this->seller1()->id)->business_name);
    }

    public function test_SEL_PROF_004_store_name_already_used_by_another_store(): void
    {
        $this->seller2();

        foreach (['RV Gadget Hub', 'rv gadget hub', '  RV Gadget Hub  '] as $name) {
            $this->saveStore(['business_name' => $name])->assertSessionHasErrors(['business_name' => 'That store name is already taken. Please choose a different one.']);
        }

        $this->assertSame('Maria Clara Handicrafts', $this->application($this->seller1()->id)->business_name);
    }

    public function test_SEL_PROF_005_store_name_length_at_the_limits(): void
    {
        $this->saveStore(['business_name' => 'MC'])->assertSessionHasErrors(['business_name' => 'Your shop name must be 3 to 30 characters.']);

        $this->saveStore(['business_name' => 'MCH'])->assertSessionHasNoErrors();
        $this->assertSame('MCH', $this->application($this->seller1()->id)->business_name);

        $this->saveStore(['business_name' => 'Maria Clara Handicrafts Bulaca'])->assertSessionHasNoErrors();
        $this->assertSame('Maria Clara Handicrafts Bulaca', $this->application($this->seller1()->id)->business_name);

        // 31 characters: rejected, never cut to 30.
        $this->saveStore(['business_name' => 'Maria Clara Handicrafts Bulacan'])->assertSessionHasErrors(['business_name' => 'Your shop name must be 3 to 30 characters.']);
        $this->assertSame('Maria Clara Handicrafts Bulaca', $this->application($this->seller1()->id)->business_name);
    }

    public function test_SEL_PROF_006_description_length_at_the_limit(): void
    {
        $text500 = str_repeat('Handwoven ', 50);
        $this->assertSame(500, mb_strlen($text500));

        $this->saveStore(['shop_description' => $text500])->assertSessionHasNoErrors();
        $saved = $this->application($this->seller1()->id)->shop_description;
        $this->assertSame(trim($text500), $saved);
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertSee(trim($text500));

        $this->saveStore(['shop_description' => $text500 . 'x'])->assertSessionHasErrors(['shop_description' => 'The description can have at most 500 characters.']);
        $this->assertSame($saved, $this->application($this->seller1()->id)->shop_description);
    }

    public function test_SEL_PROF_007_contact_number_format(): void
    {
        $this->saveStore(['shop_phone' => '09189876543'])->assertSessionHasNoErrors();
        $this->assertSame('09189876543', $this->application($this->seller1()->id)->shop_phone);

        $this->saveStore(['shop_phone' => '+639189876543'])->assertSessionHasNoErrors();
        $this->assertSame('09189876543', $this->application($this->seller1()->id)->shop_phone);

        DB::table('seller_applications')->where('user_id', $this->seller1()->id)->update(['shop_phone' => '09180000000']);

        foreach (['0918987654', '091898765432', '0918-ABC-6543', '08189876543'] as $bad) {
            $this->saveStore(['shop_phone' => $bad])->assertSessionHasErrors(['shop_phone' => 'Please enter a valid Philippine mobile number.']);
            $this->assertSame('09180000000', $this->application($this->seller1()->id)->shop_phone);
        }
    }

    public function test_SEL_PROF_008_logo_and_banner_file_type_and_size(): void
    {
        foreach (['shop_logo' => 'logo', 'shop_banner' => 'banner'] as $field => $label) {
            foreach (['size_4.9MB.jpg' => 4.9, 'size_5.0MB.jpg' => 5.0] as $name => $mb) {
                $this->saveStore([$field => $this->jpegOfSize($name, $this->mb($mb))])->assertSessionHasNoErrors();
            }

            $kept = $this->application($this->seller1()->id)->{$field};
            Storage::disk('public')->assertExists($kept);

            $message = 'The ' . $label . ' must be a JPG or PNG image of at most 5 MB.';
            foreach ([
                $this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1)),
                $this->docx('price_list.docx'),
                $this->exe('installer.exe'),
                $this->exe('renamed_exe.jpg'),
            ] as $file) {
                $this->saveStore([$field => $file])->assertSessionHasErrors([$field => $message]);
                // The previous logo or banner is still shown.
                $this->assertSame($kept, $this->application($this->seller1()->id)->{$field});
                Storage::disk('public')->assertExists($kept);
            }
        }
    }

    public function test_SEL_PROF_009_turn_on_vacation_mode(): void
    {
        $p02 = $this->p02();
        $cart = [$p02->id . ':0' => 1];

        $this->as($this->seller1())->post(route('seller.shop.vacation'), ['vacation_mode' => 1])->assertSessionHas('success');

        // P-02 still appears in search and on the store page, labelled On vacation.
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertOk()->assertSee('Rattan Fruit Basket')->assertSee('On vacation');
        $this->as($this->buyer2())->get(route('products', ['search' => 'Rattan Fruit Basket']))->assertOk()->assertSee('Rattan Fruit Basket')->assertSee('On vacation');

        // The product page shows the label with Add to Cart and Buy Now disabled.
        $page = $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()->assertSee('On vacation.');
        $this->assertMatchesRegularExpression('/class="btn cart-btn"\s+style="width:100%;"\s+disabled/', $page->getContent());
        $this->assertMatchesRegularExpression('/class="btn buy-btn"\s+style="width:100%;"\s+disabled/', $page->getContent());

        // Add to Cart / Buy Now requests are refused.
        $this->as($this->buyer1())->post(route('cart.add', $p02->id), ['quantity' => 1])->assertSessionHas('error', Product::VACATION_MESSAGE);
        $this->as($this->buyer1())->post(route('buy.now', $p02->id), ['quantity' => 1])->assertSessionHas('error', Product::VACATION_MESSAGE);

        // Checking out the item already in the cart is blocked; no order is created.
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))
            ->assertRedirect(route('cart'))->assertSessionHas('error', 'Rattan Fruit Basket: ' . Product::VACATION_MESSAGE);
        $this->placeOrder($this->buyer1(), $cart, self::ADDR_1)->assertSessionHas('error');
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(20, $this->stockOf($p02));
    }

    public function test_SEL_PROF_010_turn_off_vacation_mode(): void
    {
        $p02 = $this->p02();
        DB::table('seller_applications')->where('user_id', $this->seller1()->id)->update(['vacation_mode' => true]);

        $this->as($this->seller1())->post(route('seller.shop.vacation'), ['vacation_mode' => 0])->assertSessionHas('success');

        $page = $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()->assertDontSee('On vacation.');
        $this->assertDoesNotMatchRegularExpression('/class="btn cart-btn"\s+style="width:100%;"\s+disabled/', $page->getContent());

        $this->placeOrder($this->buyer1(), [$p02->id . ':0' => 1], self::ADDR_1)->assertSessionDoesntHaveErrors();
        $this->assertSame('Pending', $this->lastOrder($this->buyer1())?->status);
        $this->assertSame(19, $this->stockOf($p02));
    }

    public function test_SEL_PROF_011_seller_that_is_not_approved_tries_to_publish(): void
    {
        // BoomBuy rule (A18, ERP): a seller waits for the admin's approval
        // before using the seller pages at all, so a Pending seller can't
        // reach Publish or Save as Draft and nothing reaches buyers.
        $seller3 = $this->seller3();

        $this->post(route('login.submit'), ['email' => 'seller.test01@example.com', 'password' => 'Tindahan@2026'])
            ->assertSessionHas('error', 'Your seller account is still pending verification. We will notify you once it has been approved.');
        $this->assertNull(session('user'));

        $this->as($seller3)->get(route('seller.products.create'))->assertRedirect();
        $this->as($seller3)->post(route('seller.products.store'), [
            'name' => 'Handwoven Coin Purse', 'description' => 'A small handwoven coin purse from Bulacan.',
            'category' => 'Home & Living', 'price' => '120.00', 'stock' => 10,
        ])->assertRedirect();

        $this->assertSame(0, Product::where('name', 'Handwoven Coin Purse')->count());
        $this->as($this->buyer1())->get(route('products', ['search' => 'Handwoven Coin Purse']))->assertOk()->assertDontSee('class="sp-name">Handwoven Coin Purse', false);
    }

    public function test_SEL_PROF_012_script_and_html_in_text_fields(): void
    {
        $text = "<b>Sale!</b><script>alert('x')</script>\n\"><img src=x onerror=alert(1)>";
        $this->saveStore(['shop_description' => $text])->assertSessionHasNoErrors();

        $html = $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertOk()->getContent();

        $this->assertStringContainsString(e("<b>Sale!</b><script>alert('x')</script>"), $html);
        $this->assertStringNotContainsString("<script>alert('x')</script>", $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }

    public function test_SEL_PROF_013_seller_cannot_edit_another_stores_settings(): void
    {
        $seller2Before = (array) $this->application($this->seller2()->id);

        // The save request carries no store id; adding Seller 2's ids changes only Seller 1's own store.
        $this->as($this->seller1())->post(route('seller.shop.update'), $this->storeForm([
            'business_name' => 'Taken Over Store', 'user_id' => $this->seller2()->id, 'seller_id' => $this->seller2()->id, 'id' => $this->seller2()->id,
        ]));
        $this->as($this->seller1())->post(route('seller.shop.vacation'), ['vacation_mode' => 1, 'user_id' => $this->seller2()->id]);

        $this->assertEquals($seller2Before, (array) $this->application($this->seller2()->id));
        $this->assertSame('Taken Over Store', $this->application($this->seller1()->id)->business_name);

        // Seller 2's settings page can't be opened by Seller 1: it always shows Seller 1's own store.
        $this->as($this->seller1())->get(route('seller.profile', ['user_id' => $this->seller2()->id]))->assertOk()
            ->assertDontSee('RV Gadget Hub')->assertDontSee('roberto.villanueva@example.com');
    }

    /* ---------------------------------------------------------------
     | S2 Store Categories
     * --------------------------------------------------------------- */

    /** CAT-1 Bags (P-01), CAT-2 Home Decor > CAT-3 Lamps (P-03) / CAT-4 Baskets (P-02), CAT-5 Kitchen & Dining (P-04). */
    private function sheetCategories(): array
    {
        $make = fn (string $name, ?int $parent, int $order) => \App\Models\StoreCategory::create([
            'seller_id' => $this->seller1()->id, 'parent_id' => $parent, 'name' => $name, 'sort_order' => $order,
        ]);

        $cats = ['bags' => $make('Bags', null, 1), 'decor' => $make('Home Decor', null, 2)];
        $cats['lamps'] = $make('Lamps', $cats['decor']->id, 1);
        $cats['baskets'] = $make('Baskets', $cats['decor']->id, 2);
        $cats['kitchen'] = $make('Kitchen & Dining', null, 3);

        $this->p01()->storeCategories()->sync([$cats['bags']->id]);
        $this->p03()->storeCategories()->sync([$cats['lamps']->id]);
        $this->p02()->storeCategories()->sync([$cats['baskets']->id]);
        $this->p04()->storeCategories()->sync([$cats['kitchen']->id]);

        return $cats;
    }

    private function addCategory(array $form, ?\App\Models\User $seller = null)
    {
        return $this->as($seller ?? $this->seller1())->from(route('seller.store-categories'))->post(route('seller.store-categories.store'), $form);
    }

    private function storeCatNames(\App\Models\User $seller): array
    {
        return \App\Models\StoreCategory::where('seller_id', $seller->id)->orderBy('id')->pluck('name')->all();
    }

    /** The category names on the buyer's store page, in order. */
    private function storeNav(\App\Models\User $seller): array
    {
        $html = $this->as($this->buyer1())->get(route('shop.seller', $seller->id))->assertOk()->getContent();
        preg_match_all('/data-store-category="\d+">(?:› )?([^<]+)<\/a>/', $html, $m);

        return array_map(fn ($n) => html_entity_decode(trim($n)), $m[1]);
    }

    /** Product names shown on one store category page. */
    private function inStoreCategory(\App\Models\StoreCategory $category): array
    {
        $html = $this->as($this->buyer1())->get(route('shop.seller', [$category->seller_id, 'store_category' => $category->id]))->assertOk()->getContent();
        preg_match_all('/class="sp-name">([^<]+)<\/a>/', $html, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    public function test_SEL_SCAT_001_create_a_store_category_and_assign_a_product(): void
    {
        $this->p01();
        $this->p02();

        $this->addCategory(['name' => 'Pasalubong Picks'])->assertSessionHas('success');
        $category = \App\Models\StoreCategory::where('name', 'Pasalubong Picks')->firstOrFail();

        // Edit P-01 and add the new category.
        $this->as($this->seller1())->post(route('seller.products.store-categories', $this->p01()->id), ['store_categories' => [$category->id]])->assertSessionHas('success');

        $this->as($this->seller1())->get(route('seller.store-categories'))->assertOk()->assertSee('value="Pasalubong Picks"', false)->assertSee('1 product');
        $this->assertSame(['Pasalubong Picks'], $this->storeNav($this->seller1()));
        $this->assertSame(['Abaca Woven Tote Bag'], $this->inStoreCategory($category));
    }

    public function test_SEL_SCAT_002_empty_spaces_only_and_over_length_names(): void
    {
        $this->addCategory(['name' => ''])->assertSessionHasErrors(['name' => 'Please enter a category name.']);
        $this->addCategory(['name' => '   '])->assertSessionHasErrors(['name' => 'Please enter a category name.']);
        $this->addCategory(['name' => str_repeat('B', 51)])->assertSessionHasErrors(['name' => 'A category name can have at most 50 characters.']);

        $this->addCategory(['name' => 'B'])->assertSessionHasNoErrors();
        $fifty = str_repeat('Banig Mats', 5);
        $this->assertSame(50, mb_strlen($fifty));
        $this->addCategory(['name' => $fifty])->assertSessionHasNoErrors();

        $this->assertSame(['B', $fifty], $this->storeCatNames($this->seller1()));
    }

    public function test_SEL_SCAT_003_duplicate_category_name_in_the_same_store(): void
    {
        $this->sheetCategories();

        foreach (['Bags', 'BAGS', '  Bags  '] as $name) {
            $this->addCategory(['name' => $name])->assertSessionHasErrors(['name' => 'Your store already has a category named "' . trim($name) . '".']);
        }

        $this->assertSame(1, \App\Models\StoreCategory::where('seller_id', $this->seller1()->id)->whereRaw('LOWER(name) = ?', ['bags'])->count());
    }

    public function test_SEL_SCAT_004_same_category_name_in_a_different_store(): void
    {
        $this->sheetCategories();
        $before = $this->storeCatNames($this->seller1());

        $this->addCategory(['name' => 'Bags'], $this->seller2())->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(['Bags'], $this->storeCatNames($this->seller2()));
        $this->assertSame($before, $this->storeCatNames($this->seller1()));
    }

    public function test_SEL_SCAT_005_rename_a_category_to_a_duplicate_name(): void
    {
        $cats = $this->sheetCategories();

        $this->as($this->seller1())->from(route('seller.store-categories'))->put(route('seller.store-categories.update', $cats['kitchen']->id), ['name' => 'bags'])
            ->assertSessionHasErrors(['name' => 'Your store already has a category named "bags".']);

        $this->assertSame('Kitchen & Dining', $cats['kitchen']->fresh()->name);
    }

    public function test_SEL_SCAT_006_rename_and_reorder_categories(): void
    {
        $cats = $this->sheetCategories();

        $this->as($this->seller1())->put(route('seller.store-categories.update', $cats['kitchen']->id), ['name' => 'Kitchen'])->assertSessionHas('success');
        $this->as($this->seller1())->post(route('seller.store-categories.move', $cats['kitchen']->id), ['direction' => 'top'])->assertSessionHas('success');

        // Kitchen is first; the others keep their order behind it.
        $this->assertSame(['Kitchen', 'Bags', 'Home Decor', 'Lamps', 'Baskets'], $this->storeNav($this->seller1()));
        $this->assertSame(['Kitchen', 'Bags', 'Home Decor', 'Lamps', 'Baskets'], \App\Models\StoreCategory::ordered($this->seller1()->id)->pluck('name')->all());
        $this->assertSame(['Banig Placemat Set of 4'], $cats['kitchen']->fresh()->products()->pluck('name')->all());
    }

    public function test_SEL_SCAT_007_hide_a_category(): void
    {
        $cats = $this->sheetCategories();

        $this->as($this->seller1())->post(route('seller.store-categories.toggle', $cats['bags']->id))->assertSessionHas('success');

        $this->assertNotContains('Bags', $this->storeNav($this->seller1()));
        $this->as($this->buyer1())->get(route('shop.seller', [$this->seller1()->id, 'store_category' => $cats['bags']->id]))
            ->assertRedirect(route('shop.seller', $this->seller1()->id));

        // Only the category is hidden: P-01 is still found and its page works.
        $this->as($this->buyer1())->get(route('products', ['search' => 'Abaca Tote']))->assertSee('Abaca Woven Tote Bag');
        $this->as($this->buyer1())->get(route('product.details', $this->p01()->id))->assertOk();
    }

    public function test_SEL_SCAT_008_show_a_hidden_category(): void
    {
        $cats = $this->sheetCategories();
        $cats['bags']->update(['is_hidden' => true]);

        $this->as($this->seller1())->post(route('seller.store-categories.toggle', $cats['bags']->id))->assertSessionHas('success');

        $this->assertSame(['Bags', 'Home Decor', 'Lamps', 'Baskets', 'Kitchen & Dining'], $this->storeNav($this->seller1()));
        $this->assertSame(['Abaca Woven Tote Bag'], $this->inStoreCategory($cats['bags']->fresh()));
    }

    public function test_SEL_SCAT_009_delete_a_category_that_still_has_products(): void
    {
        $cats = $this->sheetCategories();

        // The warning says 1 product moves to Uncategorized (Cancel = no request, nothing changes).
        $this->as($this->seller1())->get(route('seller.store-categories'))->assertOk()
            ->assertSee(e('Delete "Baskets"? 1 product will move to Uncategorized. No product is deleted.'), false);
        $this->assertNotNull($cats['baskets']->fresh());

        $this->as($this->seller1())->delete(route('seller.store-categories.delete', $cats['baskets']->id))->assertSessionHas('success');

        $this->assertNull($cats['baskets']->fresh());
        $p02 = $this->p02()->fresh();
        $this->assertSame(0, $p02->storeCategories()->count());
        $this->assertFalse((bool) $p02->is_archived);

        $this->as($this->seller1())->get(route('seller.dashboard'))->assertOk()
            ->assertSeeInOrder(['data-store-cats="' . $p02->id . '"', 'Uncategorized'], false);
        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()->assertSee('Add to Cart');
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertSee('Rattan Fruit Basket');
    }

    public function test_SEL_SCAT_010_delete_a_parent_category_with_subcategories(): void
    {
        $cats = $this->sheetCategories();

        $this->as($this->seller1())->get(route('seller.store-categories'))->assertOk()
            ->assertSee(e('Delete "Home Decor"? Its subcategories (Lamps, Baskets) will be deleted too. 2 products will move to Uncategorized. No product is deleted.'), false);

        $this->as($this->seller1())->delete(route('seller.store-categories.delete', $cats['decor']->id))->assertSessionHas('success');

        $this->assertSame(['Bags', 'Kitchen & Dining'], \App\Models\StoreCategory::ordered($this->seller1()->id)->pluck('name')->all());
        foreach ([$this->p02(), $this->p03()] as $product) {
            $this->assertNotNull($product->fresh());
            $this->assertSame(0, $product->storeCategories()->count());
            $this->assertFalse((bool) $product->fresh()->is_archived);
        }
    }

    public function test_SEL_SCAT_011_subcategory_nesting_at_the_depth_limit(): void
    {
        $cats = $this->sheetCategories();

        $this->addCategory(['name' => 'Wall Decor', 'parent_id' => $cats['decor']->id])->assertSessionHasNoErrors();
        $this->assertSame($cats['decor']->id, \App\Models\StoreCategory::where('name', 'Wall Decor')->value('parent_id'));

        // Lamps is not offered as a parent, and a forced request is blocked.
        $page = $this->as($this->seller1())->get(route('seller.store-categories'))->getContent();
        preg_match('/<select id="parent_id".*?<\/select>/s', $page, $select);
        $this->assertStringContainsString('>Home Decor<', $select[0]);
        $this->assertStringNotContainsString('>Lamps<', $select[0]);

        $this->addCategory(['name' => 'Table Lamps', 'parent_id' => $cats['lamps']->id])
            ->assertSessionHasErrors(['parent_id' => 'Store categories have at most 2 levels, so a subcategory can\'t have its own subcategories.']);
        $this->assertFalse(\App\Models\StoreCategory::where('name', 'Table Lamps')->exists());
    }

    public function test_SEL_SCAT_012_product_in_several_store_categories_at_the_limit(): void
    {
        $cats = $this->sheetCategories();
        $best = \App\Models\StoreCategory::create(['seller_id' => $this->seller1()->id, 'name' => 'Best Sellers', 'sort_order' => 4]);
        $gift = \App\Models\StoreCategory::create(['seller_id' => $this->seller1()->id, 'name' => 'Gift Ideas', 'sort_order' => 5]);
        $new = \App\Models\StoreCategory::create(['seller_id' => $this->seller1()->id, 'name' => 'New Arrivals', 'sort_order' => 6]);

        $this->as($this->seller1())->post(route('seller.products.store-categories', $this->p03()->id), ['store_categories' => [$cats['lamps']->id, $best->id, $gift->id]])->assertSessionHas('success');

        $this->as($this->seller1())->from(route('seller.products.edit', $this->p03()->id))
            ->post(route('seller.products.store-categories', $this->p03()->id), ['store_categories' => [$cats['lamps']->id, $best->id, $gift->id, $new->id]])
            ->assertSessionHasErrors(['store_categories' => 'A product can be in at most 3 store categories.']);

        $this->assertEqualsCanonicalizing([$cats['lamps']->id, $best->id, $gift->id], $this->p03()->storeCategories()->pluck('store_categories.id')->all());
        foreach ([$cats['lamps'], $best, $gift] as $category) {
            $this->assertSame(['Capiz Shell Lamp'], $this->inStoreCategory($category->fresh()));
        }
    }

    public function test_SEL_SCAT_013_store_category_is_separate_from_platform_category(): void
    {
        // BoomBuy rule (A7, ERP): a seller lists only in the platform
        // category the store registered for, so Seller 1's P-01 is under
        // Home & Living (the sheet's "Women's Bags").
        $cats = $this->sheetCategories();

        $this->as($this->seller1())->post(route('seller.products.store-categories', $this->p01()->id), ['store_categories' => [$cats['kitchen']->id]])->assertSessionHas('success');

        $this->assertSame('Home & Living', $this->p01()->fresh()->category);
        $this->as($this->buyer1())->get(route('products', ['category' => 'home-living']))->assertSee('Abaca Woven Tote Bag');
        $this->assertContains('Abaca Woven Tote Bag', $this->inStoreCategory($cats['kitchen']));

        // Sellers have no way to create or rename platform categories.
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())->map->uri();
        $this->assertEmpty($routes->filter(fn ($uri) => str_starts_with($uri, 'seller/') && str_contains($uri, 'platform-categor')));
        $this->assertCount(16, \App\Support\Categories::LIST);
    }

    /* ---------------------------------------------------------------
     | S3 Product Management
     * --------------------------------------------------------------- */

    /** A stored amount as the sheet writes it: 2 decimals (SQLite keeps 299.00 as 299). */
    private function money(mixed $value, string $thousands = ''): string
    {
        return number_format((float) $value, 2, '.', $thousands);
    }

    /** The fields flagged by the last request. */
    private function errorKeys(): array
    {
        $errors = session('errors');

        return is_array($errors) ? array_keys($errors['default']['messages'] ?? []) : $errors->getBag('default')->keys();
    }

    private function jpeg(string $name = 'product_front.jpg', int $kb = 40): \Illuminate\Http\UploadedFile
    {
        return $this->jpegOfSize($name, $kb * 1024);
    }

    /** The Buri Placemat Set from SEL-PROD-001, ready to Publish. */
    private function productForm(array $overrides = []): array
    {
        return array_merge([
            'action' => 'publish',
            'name' => 'Buri Placemat Set',
            'description' => 'Set of 6 placemats, handwoven buri palm.',
            'category' => 'home-living',
            'brand' => 'No brand',
            'condition' => 'New',
            'price' => '299.00',
            'stock' => '30',
            'weight_kg' => '0.6',
            'length_cm' => '35', 'width_cm' => '25', 'height_cm' => '3',
            'photos' => [$this->jpeg('product_front.jpg'), $this->png('product_side.png')],
        ], $overrides);
    }

    private function publish(array $overrides = [], ?\App\Models\User $seller = null)
    {
        return $this->as($seller ?? $this->seller1())->from(route('seller.products.create'))->post(route('seller.products.store'), $this->productForm($overrides));
    }

    private function product(string $name): ?Product
    {
        return Product::where('name', $name)->first();
    }

    /** The names on a buyer's search results page. */
    private function searchNames(string $text): array
    {
        $html = $this->as($this->buyer1())->get(route('products', ['search' => $text]))->assertOk()->getContent();
        preg_match_all('/class="sp-name">([^<]+)<\/a>/', $html, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    public function test_SEL_PROD_001_add_and_publish_a_product_with_all_valid_data(): void
    {
        $kitchen = \App\Models\StoreCategory::create(['seller_id' => $this->seller1()->id, 'name' => 'Kitchen & Dining']);

        $this->publish(['store_categories' => [$kitchen->id], 'store_categories_sent' => 1])
            ->assertRedirect(route('seller.dashboard'))->assertSessionHasNoErrors();

        $product = $this->product('Buri Placemat Set');
        $this->assertNotNull($product);
        $this->assertFalse($product->is_draft);
        $this->assertFalse($product->is_archived);
        $this->assertSame('Set of 6 placemats, handwoven buri palm.', $product->description);
        $this->assertSame('Home & Living', $product->category);
        $this->assertSame('No brand', $product->brand);
        $this->assertSame('New', $product->condition);
        $this->assertSame('299.00', $this->money($product->price));
        $this->assertSame(30, (int) $product->stock);
        $this->assertSame(['0.60', '35.00', '25.00', '3.00'], array_map([$this, 'money'], [$product->weight_kg, $product->length_cm, $product->width_cm, $product->height_cm]));

        // Images in the uploaded order.
        $paths = $product->images()->pluck('path')->all();
        $this->assertCount(2, $paths);
        $this->assertStringEndsWith('.jpg', $paths[0]);
        $this->assertStringEndsWith('.png', $paths[1]);
        $this->assertSame($paths[0], $product->image);

        $this->as($this->seller1())->get(route('seller.dashboard'))->assertSee('Buri Placemat Set');

        // Buyers find it in search, in Home & Living and in the store's Kitchen & Dining.
        $this->assertContains('Buri Placemat Set', $this->searchNames('Buri Placemat Set'));
        $this->as($this->buyer1())->get(route('products', ['category' => 'home-living']))->assertSee('Buri Placemat Set');
        $this->assertSame(['Buri Placemat Set'], $this->inStoreCategory($kitchen));
        $this->as($this->buyer1())->get(route('product.details', $product->id))->assertOk()->assertSee('299')->assertSee('30');
    }

    public function test_SEL_PROD_002_each_required_field_left_blank(): void
    {
        $fields = [
            'name' => ['name' => ''],
            'description' => ['description' => ''],
            'photos' => ['photos' => []],
            'category' => ['category' => ''],
            'price' => ['price' => ''],
            'stock' => ['stock' => ''],
            'weight_kg' => ['weight_kg' => ''],
        ];

        // A seller registered for no single category picks the platform category on the form.
        DB::table('seller_applications')->where('user_id', $this->seller1()->id)->update(['business_category' => null]);

        foreach ($fields as $field => $blank) {
            $response = $this->publish($blank)->assertRedirect(route('seller.products.create'));
            $response->assertSessionHasErrors([$field]);
            $this->assertSame([$field], $this->errorKeys(), 'Only ' . $field . ' is flagged');
            // The other values stay on the form.
            $old = session('_old_input');
            foreach (['name', 'description', 'price', 'stock', 'weight_kg'] as $kept) {
                if ($kept !== $field) {
                    $this->assertSame($this->productForm()[$kept], $old[$kept] ?? null);
                }
            }
        }

        $this->assertSame(0, Product::count());
    }

    public function test_SEL_PROD_003_product_name_and_description_length(): void
    {
        $this->publish(['name' => 'Tote'])->assertSessionHasErrors(['name' => 'The product name must be 5 to 120 characters (now 4).']);
        $this->publish(['name' => 'Totes'])->assertSessionHasNoErrors();
        $this->publish(['name' => str_repeat('T', 120)])->assertSessionHasNoErrors();
        $this->publish(['name' => str_repeat('U', 121)])->assertSessionHasErrors(['name' => 'The product name must be 5 to 120 characters (now 121).']);

        $this->publish(['name' => 'Description 19', 'description' => str_repeat('d', 19)])->assertSessionHasErrors(['description' => 'The description must be 20 to 3,000 characters (now 19).']);
        $this->publish(['name' => 'Description 20', 'description' => str_repeat('d', 20)])->assertSessionHasNoErrors();
        $this->publish(['name' => 'Description 3000', 'description' => str_repeat('d', 3000)])->assertSessionHasNoErrors();
        $this->publish(['name' => 'Description 3001', 'description' => str_repeat('d', 3001)])->assertSessionHasErrors(['description' => 'The description must be 20 to 3,000 characters (now 3,001).']);

        $this->assertEqualsCanonicalizing(['Totes', str_repeat('T', 120), 'Description 20', 'Description 3000'], Product::pluck('name')->all());
    }

    public function test_SEL_PROD_004_price_validation(): void
    {
        $accept = ['1.00' => '1.00', '1.01' => '1.01', '999,999.99' => '999999.99', '1,000,000.00' => '1000000.00'];
        $reject = ['0', '-50', '0.99', '199.999', '1,000,000.01', 'abc', ''];

        foreach ($reject as $i => $price) {
            $this->publish(['name' => 'Rejected price ' . $i, 'price' => $price])->assertSessionHasErrors(['price']);
        }
        $this->assertSame(0, Product::count());

        foreach (array_keys($accept) as $i => $price) {
            $this->publish(['name' => 'Accepted price ' . $i, 'price' => $price])->assertSessionHasNoErrors();
        }

        $this->assertSame(array_values($accept), Product::orderBy('id')->pluck('price')->map(fn ($p) => $this->money($p, ''))->all());
        $this->assertFalse(Product::where('price', 200)->exists());
    }

    public function test_SEL_PROD_005_stock_validation(): void
    {
        foreach (['-1', '100000', '2.5'] as $i => $stock) {
            $this->publish(['name' => 'Rejected stock ' . $i, 'stock' => $stock])->assertSessionHasErrors(['stock' => 'Stock must be a whole number from 0 to 99,999.']);
        }

        foreach (['0', '1', '99,999'] as $i => $stock) {
            $this->publish(['name' => 'Accepted stock ' . $i, 'stock' => $stock])->assertSessionHasNoErrors();
        }

        $this->assertSame([0, 1, 99999], Product::orderBy('id')->pluck('stock')->map(fn ($s) => (int) $s)->all());

        // Published with 0 stock: buyers see Sold out.
        $soldOut = $this->product('Accepted stock 0');
        $this->as($this->buyer1())->get(route('products', ['search' => 'Accepted stock 0']))->assertSee('Sold out');
        $this->as($this->buyer1())->get(route('product.details', $soldOut->id))->assertOk()->assertSee('Out of stock', false);
    }

    public function test_SEL_PROD_006_image_count_at_the_limits(): void
    {
        $this->publish(['photos' => []])->assertSessionHasErrors(['photos' => 'Please add at least 1 product photo (up to 9).']);
        $this->assertSame(0, Product::count());

        $this->publish(['photos' => [$this->jpeg()]])->assertSessionHasNoErrors();
        $product = $this->product('Buri Placemat Set');
        $this->assertSame(1, $product->images()->count());

        // Edit and add up to 9 images.
        $edit = fn (array $photos) => $this->as($this->seller1())->from(route('seller.products.edit', $product->id))->put(route('seller.products.update', $product->id), [
            'name' => 'Buri Placemat Set', 'description' => 'Set of 6 placemats, handwoven buri palm.', 'category' => 'home-living',
            'price' => '299.00', 'stock' => '30', 'photos' => $photos,
        ]);

        $edit(array_map(fn ($i) => $this->jpeg("extra_$i.jpg"), range(2, 9)))->assertSessionHasNoErrors();
        $this->assertSame(9, $product->images()->count());

        $edit([$this->jpeg('tenth.jpg')])->assertSessionHasErrors(['photos' => 'A product can have at most 9 photos.']);
        $this->assertSame(9, $product->images()->count());

        // The add-photo tile stops at 9.
        $this->as($this->seller1())->get(route('seller.products.create'))->assertSee('data-max="9"', false);
    }

    public function test_SEL_PROD_007_image_file_type_and_size(): void
    {
        $this->publish(['name' => 'Exactly five MB', 'photos' => [$this->jpegOfSize('size_5.0MB.jpg', $this->mb(5.0))]])->assertSessionHasNoErrors();

        foreach ([
            $this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1)),
            $this->docx('price_list.docx'),
            $this->exe('renamed_exe.jpg'),
        ] as $i => $file) {
            $this->publish(['name' => 'Bad photo ' . $i, 'photos' => [$file]])
                ->assertSessionHasErrors(['photos' => 'Each photo must be a JPG or PNG image of at most 5 MB.']);
        }

        $this->assertSame(['Exactly five MB'], Product::pluck('name')->all());
        $this->assertSame(1, \App\Models\ProductImage::count());
    }

    public function test_SEL_PROD_008_weight_and_dimensions_validation(): void
    {
        foreach (['0', '-1'] as $i => $weight) {
            $this->publish(['name' => 'Weight ' . $i, 'weight_kg' => $weight])->assertSessionHasErrors(['weight_kg' => 'Weight must be a number more than 0 kg (up to 999 kg).']);
        }

        $this->publish(['name' => 'Zero size', 'length_cm' => '0', 'width_cm' => '0', 'height_cm' => '0'])->assertSessionHasErrors(['dimensions' => 'Enter the length, width and height as numbers more than 0 cm.']);
        $this->publish(['name' => 'Letters size', 'length_cm' => 'abc', 'width_cm' => 'abc', 'height_cm' => 'abc'])->assertSessionHasErrors(['dimensions']);
        $this->assertSame(0, Product::count());

        $this->publish(['name' => 'Light Woven Fan', 'weight_kg' => '0.01'])->assertSessionHasNoErrors();
        $this->assertSame('0.01', $this->money($this->product('Light Woven Fan')->weight_kg));
        // Charged as 1 kg at checkout (whole kg, rounded up).
        $this->assertSame(1, \App\Support\DeliveryFee::chargeableKg(0.01));
    }

    public function test_SEL_PROD_009_save_as_draft(): void
    {
        $this->as($this->seller1())->from(route('seller.products.create'))->post(route('seller.products.store'), [
            'action' => 'draft', 'name' => 'Draft Woven Fan',
        ])->assertSessionHasNoErrors()->assertRedirect(route('seller.dashboard'));

        $draft = $this->product('Draft Woven Fan');
        $this->assertTrue($draft->is_draft);
        $this->as($this->seller1())->get(route('seller.dashboard'))->assertSee('data-product-status="' . $draft->id . '">Draft', false);

        $this->assertSame([], $this->searchNames('Draft Woven Fan'));
        $this->as($this->buyer1())->get(route('product.details', $draft->id))->assertNotFound();
    }

    public function test_SEL_PROD_010_publish_a_draft_with_missing_data(): void
    {
        $this->as($this->seller1())->post(route('seller.products.store'), ['action' => 'draft', 'name' => 'Draft Woven Fan']);
        $draft = $this->product('Draft Woven Fan');

        $this->as($this->seller1())->from(route('seller.products.edit', $draft->id))
            ->put(route('seller.products.update', $draft->id), ['action' => 'publish', 'name' => 'Draft Woven Fan'])
            ->assertSessionHasErrors(['description', 'price', 'stock', 'weight_kg', 'photos']);

        $this->assertTrue($draft->fresh()->is_draft);
        $this->as($this->buyer1())->get(route('product.details', $draft->id))->assertNotFound();
    }

    public function test_SEL_PROD_011_deactivate_and_reactivate_a_product(): void
    {
        $p02 = $this->p02();
        $cart = [$p02->id . ':0' => 1];
        $before = $p02->fresh()->only(['price', 'stock', 'image']);

        $this->as($this->seller1())->post(route('seller.products.archive', $p02->id))->assertSessionHas('success');

        $this->assertSame([], $this->searchNames('Rattan Fruit Basket'));
        $this->as($this->buyer1())->get(route('products', ['category' => 'home-living']))->assertDontSee('class="sp-name">Rattan Fruit Basket', false);
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertDontSee('class="sp-name">Rattan Fruit Basket', false);
        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertNotFound();

        // The cart line stays but can't be checked out.
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->assertOk()->assertSee('Rattan Fruit Basket');
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))
            ->assertRedirect(route('cart'))->assertSessionHas('error', 'Rattan Fruit Basket is no longer available. Please remove it from your selection.');

        $this->as($this->seller1())->post(route('seller.products.unarchive', $p02->id))->assertSessionHas('success');

        $this->assertSame(['Rattan Fruit Basket'], $this->searchNames('Rattan Fruit Basket'));
        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk();
        $this->assertSame($before, $p02->fresh()->only(['price', 'stock', 'image']));
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))->assertOk();
    }

    public function test_SEL_PROD_012_duplicate_a_product(): void
    {
        $p02 = $this->p02();
        $original = $p02->fresh()->toArray();

        $this->as($this->seller1())->post(route('seller.products.duplicate', $p02->id))->assertSessionHas('success');

        $copy = Product::where('id', '!=', $p02->id)->firstOrFail();
        $this->assertNotSame($p02->id, $copy->id);
        $this->assertTrue($copy->is_draft);
        foreach (['name', 'description', 'category', 'price', 'stock', 'seller_id'] as $field) {
            $this->assertEquals($original[$field], $copy->{$field}, $field);
        }

        // Saving the copy keeps it a draft.
        $this->as($this->seller1())->put(route('seller.products.update', $copy->id), ['action' => 'draft', 'name' => $copy->name, 'price' => '450.00'])->assertSessionHasNoErrors();
        $this->assertTrue($copy->fresh()->is_draft);

        $this->assertEquals($original, $p02->fresh()->toArray());
        $this->assertSame(['Rattan Fruit Basket'], $this->searchNames('Rattan Fruit Basket'));
    }

    public function test_SEL_PROD_013_edit_price_of_a_product_in_a_buyers_cart(): void
    {
        $p02 = $this->p02();
        $cart = [$p02->id . ':0' => 1];

        $this->as($this->seller1())->put(route('seller.products.update', $p02->id), [
            'name' => 'Rattan Fruit Basket', 'description' => $p02->description, 'category' => 'home-living', 'price' => '420.00', 'stock' => '20',
        ])->assertSessionHasNoErrors();

        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->assertOk()->assertSee('420.00')->assertDontSee('450.00');
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))->assertOk()->assertSee('420.00')->assertDontSee('450.00');

        $this->placeOrder($this->buyer1(), $cart, self::ADDR_1)->assertSessionDoesntHaveErrors();
        $this->assertSame('420.00', $this->money(DB::table('order_items')->value('price')));
    }

    public function test_SEL_PROD_014_delete_a_product_with_unfinished_orders(): void
    {
        $p02 = $this->p02();
        $this->makeOrder($this->buyer1(), $p02, 'Preparing'); // To Ship

        $this->as($this->seller1())->delete(route('seller.products.delete', $p02->id))
            ->assertSessionHas('error', 'Rattan Fruit Basket has unfinished orders, so it can\'t be deleted yet. You can deactivate it to hide it from buyers.');

        $this->assertNotNull(Product::find($p02->id));
        $this->assertFalse(Product::find($p02->id)->is_archived);
    }

    public function test_SEL_PROD_015_delete_a_product_with_only_finished_orders(): void
    {
        $p04 = $this->p04();
        $p04->update(['image' => 'products/banig.jpg']);
        Storage::disk('public')->put('products/banig.jpg', 'x');
        $orderId = $this->makeOrder($this->buyer1(), $p04, 'Completed');

        $this->as($this->seller1())->delete(route('seller.products.delete', $p04->id))->assertSessionHas('success');

        $this->assertNull(Product::find($p04->id));
        $this->assertNotNull(Product::withTrashed()->find($p04->id));
        $this->as($this->seller1())->get(route('seller.dashboard'))->assertDontSee('data-product-search="banig placemat set of 4', false);
        $this->assertSame([], $this->searchNames('Banig Placemat'));
        $this->as($this->buyer1())->get(route('shop.seller', $this->seller1()->id))->assertDontSee('class="sp-name">Banig Placemat Set of 4', false);

        // Both order views keep its name, photo and price paid.
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertOk()->assertSee('Banig Placemat Set of 4')->assertSee('storage/products/banig.jpg', false)->assertSee('350');
        $this->as($this->seller1())->get(route('seller.order.details', $orderId))->assertOk()->assertSee('Banig Placemat Set of 4')->assertSee('storage/products/banig.jpg', false)->assertSee('350.00');
        Storage::disk('public')->assertExists('products/banig.jpg');
    }

    public function test_SEL_PROD_016_bulk_deactivate_and_bulk_delete_with_a_mixed_selection(): void
    {
        $p02 = $this->p02();
        $p04 = $this->p04();
        $this->publish();
        $buri = $this->product('Buri Placemat Set');
        $this->makeOrder($this->buyer1(), $p02, 'Preparing');
        $ids = [$p02->id, $p04->id, $buri->id];

        $this->as($this->seller1())->post(route('seller.products.bulk'), ['bulk_action' => 'deactivate', 'product_ids' => $ids])->assertSessionHas('success', '3 products deactivated.');
        $this->assertSame(3, Product::whereIn('id', $ids)->where('is_archived', true)->count());

        $this->as($this->seller1())->post(route('seller.products.bulk'), ['bulk_action' => 'activate', 'product_ids' => $ids])->assertSessionHas('success', '3 products reactivated.');

        $this->as($this->seller1())->post(route('seller.products.bulk'), ['bulk_action' => 'delete', 'product_ids' => $ids])
            ->assertSessionHas('success', '2 products deleted. Skipped because of unfinished orders: Rattan Fruit Basket.');

        $this->assertNotNull(Product::find($p02->id));
        $this->assertNull(Product::find($p04->id));
        $this->assertNull(Product::find($buri->id));
    }

    public function test_SEL_PROD_017_double_click_publish(): void
    {
        $token = (string) Str::uuid();
        $form = ['name' => 'Double Click Test Tote', \App\Support\SubmitOnce::FIELD => $token];

        $this->publish($form)->assertRedirect(route('seller.dashboard'));
        $this->publish($form)->assertRedirect(route('seller.dashboard'));

        $this->assertSame(1, Product::where('name', 'Double Click Test Tote')->count());
        $this->as($this->seller1())->get(route('seller.products.create'))->assertSee('name="' . \App\Support\SubmitOnce::FIELD . '"', false);
    }

    public function test_SEL_PROD_018_edit_another_sellers_product(): void
    {
        $p05 = $this->p05();
        $before = $p05->fresh()->toArray();

        $this->as($this->seller1())->get(route('seller.products.edit', $p05->id))->assertNotFound()->assertDontSee('USB-C Fast Charger 20W');
        $this->as($this->seller1())->put(route('seller.products.update', $p05->id), [
            'name' => 'Hacked Charger', 'description' => 'Changed by another seller.', 'category' => 'electronics', 'price' => '1.00', 'stock' => '0',
        ])->assertForbidden();
        $this->as($this->seller1())->post(route('seller.products.duplicate', $p05->id))->assertNotFound();
        $this->as($this->seller1())->delete(route('seller.products.delete', $p05->id))->assertNotFound();

        $this->assertEquals($before, $p05->fresh()->toArray());
    }

    /* ---------------------------------------------------------------
     | S4 Variants
     * --------------------------------------------------------------- */

    /** Buri Coin Purse rows: Color (Red, Blue) × Size (S ₱99, M ₱129, L ₱149), stock 5, SKU BCP-<COLOR>-<SIZE>. */
    private function coinPurseRows(): array
    {
        $rows = [];

        foreach (['Red', 'Blue'] as $color) {
            foreach (['S' => '99.00', 'M' => '129.00', 'L' => '149.00'] as $size => $price) {
                $rows[] = [
                    'type' => 'Color', 'value' => $color, 'type2' => 'Size', 'value2' => $size,
                    'price' => $price, 'stock' => '5', 'sku' => 'BCP-' . strtoupper($color) . '-' . $size,
                ];
            }
        }

        return $rows;
    }

    private function publishVariants(array $rows, array $overrides = [])
    {
        return $this->publish(array_merge(['name' => 'Buri Coin Purse', 'price' => '', 'stock' => '', 'variations' => $rows], $overrides));
    }

    /** One line of an order already placed for a P-01 variant. */
    private function variantOrder(string $row, string $status): int
    {
        $variant = $this->variant($row);
        $orderId = $this->makeOrder($this->buyer1(), $this->p01(), $status);
        DB::table('order_items')->where('order_id', $orderId)->update([
            'variation_id' => $variant->id, 'variation_label' => $variant->label(),
            'price' => (float) $this->p01()->price + (float) $variant->price_adjustment,
        ]);

        return $orderId;
    }

    public function test_SEL_VAR_001_create_a_product_with_2_variation_types(): void
    {
        $this->publishVariants($this->coinPurseRows())->assertSessionHasNoErrors()->assertRedirect(route('seller.dashboard'));

        $product = $this->product('Buri Coin Purse');
        $rows = $product->variations()->orderBy('id')->get();

        $this->assertSame(['Red · S', 'Red · M', 'Red · L', 'Blue · S', 'Blue · M', 'Blue · L'], $rows->map->shortLabel()->all());
        $this->assertSame(['99.00', '129.00', '149.00', '99.00', '129.00', '149.00'], $rows->map(fn ($v) => $this->money((float) $product->price + (float) $v->price_adjustment))->all());
        $this->assertSame([5, 5, 5, 5, 5, 5], $rows->pluck('stock')->map(fn ($s) => (int) $s)->all());
        $this->assertSame(['BCP-RED-S', 'BCP-RED-M', 'BCP-RED-L', 'BCP-BLUE-S', 'BCP-BLUE-M', 'BCP-BLUE-L'], $rows->pluck('sku')->all());

        // The buyer sees both selectors and the price range.
        $this->as($this->buyer1())->get(route('product.details', $product->id))->assertOk()
            ->assertSee('₱99 – ₱149')->assertSee('Color')->assertSee('Size');
    }

    public function test_SEL_VAR_002_add_a_third_variation_type(): void
    {
        $this->publishVariants($this->coinPurseRows())->assertSessionHasNoErrors();
        $product = $this->product('Buri Coin Purse');
        $before = $product->variations()->orderBy('id')->get()->toArray();

        // On the Variations page…
        $this->as($this->seller1())->from(route('seller.products.variations', $product->id))->post(route('seller.products.variations.store', $product->id), [
            'variation_type' => 'Material', 'variation_value' => 'Abaca', 'stock' => 5,
        ])->assertSessionHasErrors(['variation_type' => 'A product can have at most 2 variation types, and every option uses the same ones (Color × Size).']);

        // …and on a new product's form.
        $rows = $this->coinPurseRows();
        $rows[0] += ['type3' => 'Material', 'value3' => 'Abaca'];
        $this->publishVariants($rows, ['name' => 'Three Type Purse'])->assertSessionHasErrors(['variations' => 'A product can have at most 2 variation types (for example Color and Size).']);

        $this->assertEquals($before, $product->variations()->orderBy('id')->get()->toArray());
        $this->assertNull($this->product('Three Type Purse'));
    }

    public function test_SEL_VAR_003_options_per_type_at_the_limit(): void
    {
        $colors = fn (int $n) => array_map(fn ($i) => ['type' => 'Color', 'value' => 'Color ' . $i, 'price' => '99.00', 'stock' => '1'], range(1, $n));

        $this->publishVariants($colors(21), ['name' => 'Twenty One Colors'])->assertSessionHasErrors(['variations' => 'Each variation type can have at most 20 options.']);
        $this->assertNull($this->product('Twenty One Colors'));

        $this->publishVariants($colors(20), ['name' => 'Twenty Colors'])->assertSessionHasNoErrors();
        $product = $this->product('Twenty Colors');
        $this->assertSame(20, $product->variations()->count());

        // A 21st option on the Variations page is blocked too.
        $this->as($this->seller1())->from(route('seller.products.variations', $product->id))->post(route('seller.products.variations.store', $product->id), [
            'variation_type' => 'Color', 'variation_value' => 'Color 21', 'stock' => 1,
        ])->assertSessionHasErrors(['variation_value' => 'Each variation type can have at most 20 options.']);
        $this->assertSame(20, $product->variations()->count());
    }

    public function test_SEL_VAR_004_duplicate_or_empty_option_names(): void
    {
        $rows = [
            ['type' => 'Color', 'value' => 'Red', 'price' => '99.00', 'stock' => '5'],
            ['type' => 'Color', 'value' => 'red', 'price' => '99.00', 'stock' => '5'],
            ['type' => 'Color', 'value' => '', 'price' => '99.00', 'stock' => '5'],
        ];

        $this->publishVariants($rows)->assertSessionHasErrors([
            'variations.0.value' => '"Red" and "red" are the same option. Use one name.',
            'variations.1.value' => '"Red" and "red" are the same option. Use one name.',
            'variations.2.value' => 'Enter a name for this option.',
        ]);

        $this->assertSame(0, Product::count());
    }

    public function test_SEL_VAR_005_missing_or_invalid_price_or_stock_on_one_variant(): void
    {
        foreach ([['price' => ''], ['price' => '0.00'], ['stock' => '-1']] as $bad) {
            $rows = $this->coinPurseRows();
            $rows[5] = array_merge($rows[5], $bad); // Blue-L

            $this->publishVariants($rows);
            $this->assertSame(['variations.5.' . array_key_first($bad)], $this->errorKeys(), 'Only the Blue-L row is flagged');
        }

        $this->assertSame(0, Product::count());
    }

    public function test_SEL_VAR_006_duplicate_sku(): void
    {
        $rows = $this->coinPurseRows();
        $rows[3]['sku'] = 'BCP-RED-S'; // Blue-S = Red-S
        $this->publishVariants($rows)->assertSessionHasErrors(['variations.3.sku' => 'SKU BCP-RED-S is already used by another option.']);

        $this->variant('Natural-S')->update(['sku' => 'MCH-TOTE-NAT-S']);
        $rows = $this->coinPurseRows();
        $rows[0]['sku'] = 'MCH-TOTE-NAT-S';
        $this->publishVariants($rows)->assertSessionHasErrors(['variations.0.sku' => 'SKU MCH-TOTE-NAT-S is already used by another product.']);

        $this->assertNull($this->product('Buri Coin Purse'));
    }

    public function test_SEL_VAR_007_price_range_shown_on_the_product_page_and_search(): void
    {
        $p01 = $this->p01();

        $this->as($this->buyer1())->get(route('products', ['search' => 'Abaca Woven Tote Bag']))->assertOk()->assertSee('₱199</span> – <span class="sp-amt">₱299', false);
        $this->as($this->buyer1())->get(route('product.details', $p01->id))->assertOk()->assertSee('₱199 – ₱299');

        // Picking Natural-M shows ₱249.00 only (the page swaps in that option's price).
        $natM = collect($this->as($this->buyer1())->getJson(route('product.options', $p01->id))->json('variations'))->firstWhere('id', $this->variant('Natural-M')->id);
        $this->assertEquals(249.0, $natM['price']);
    }

    public function test_SEL_VAR_008_variant_image_switching(): void
    {
        $p01 = $this->p01();
        app(\App\Services\ProductService::class)->storeFiles($p01, $this->variant('Natural-S')->id, [$this->jpeg('product_front.jpg')]);
        app(\App\Services\ProductService::class)->storeFiles($p01, $this->variant('Black-S')->id, [$this->png('product_side.png')]);
        \App\Support\ProductPhotos::sync($p01);

        $options = collect($this->as($this->buyer1())->getJson(route('product.options', $p01->id))->json('variations'));
        $natural = $options->firstWhere('id', $this->variant('Natural-S')->id)['photo'];
        $black = $options->firstWhere('id', $this->variant('Black-S')->id)['photo'];

        $this->assertNotEmpty($natural);
        $this->assertNotEmpty($black);
        $this->assertNotSame($natural, $black);
        $this->assertStringEndsWith('.jpg', $natural);
        $this->assertStringEndsWith('.png', $black);
    }

    public function test_SEL_VAR_009_variant_with_0_stock(): void
    {
        $p01 = $this->p01();

        $this->as($this->buyer1())->get(route('product.details', $p01->id))->assertOk();
        $options = collect($this->as($this->buyer1())->getJson(route('product.options', $p01->id))->json('variations'));

        $this->assertSame(0, $options->firstWhere('id', $this->variant('Black-M')->id)['stock']);
        $this->assertSame(8, $options->firstWhere('id', $this->variant('Natural-M')->id)['stock']);

        // It can't be added either.
        $this->as($this->buyer1())->post(route('cart.add', $p01->id), ['quantity' => 1, 'variation_id' => $this->variant('Black-M')->id])->assertSessionHas('error');
        $this->as($this->buyer1())->post(route('cart.add', $p01->id), ['quantity' => 1, 'variation_id' => $this->variant('Natural-M')->id])->assertSessionHas('success');
    }

    public function test_SEL_VAR_010_change_options_after_orders_exist(): void
    {
        $orderId = $this->variantOrder('Natural-M', 'Completed');
        $p01 = $this->p01();

        $rows = [];
        foreach ($p01->variations as $v) {
            $rows[$v->id] = [
                'value' => $v->variation_value === 'Natural' ? 'Beige' : $v->variation_value,
                'value2' => $v->option2_value,
                'stock' => $v->stock,
                'price' => $this->money((float) $p01->price + (float) $v->price_adjustment),
            ];
        }
        $rows[$this->variant('Natural-M')->id]['price'] = '259.00';

        $this->as($this->seller1())->put(route('seller.products.variations.update', $p01->id), ['variations' => $rows])->assertSessionHas('success');
        $this->as($this->seller1())->post(route('seller.products.variations.store', $p01->id), [
            'variation_type' => 'Color', 'variation_value' => 'Brown', 'option2_type' => 'Size', 'option2_value' => 'M', 'price' => '249.00', 'stock' => 4,
        ])->assertSessionHas('success');

        // The old order still says Natural-M at ₱249.00 for both roles.
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertSee('Natural')->assertSee('249');
        $this->as($this->seller1())->get(route('seller.order.details', $orderId))->assertSee('Color: Natural · Size: M')->assertSee('249.00');

        // The product page shows Beige and Brown with the new price.
        $options = collect($this->as($this->buyer1())->getJson(route('product.options', $p01->id))->json('variations'));
        $this->assertContains('Beige', $options->pluck('value1')->all());
        $this->assertContains('Brown', $options->pluck('value1')->all());
        $this->assertNotContains('Natural', $options->pluck('value1')->all());
        $this->assertEquals(259.0, $options->firstWhere('id', $this->variant('Natural-M')->id)['price']);
    }

    public function test_SEL_VAR_011_remove_a_variant_that_is_in_a_buyers_cart(): void
    {
        $p01 = $this->p01();
        $p02 = $this->p02();
        $blackL = $this->variant('Black-L');
        $cart = [$p01->id . ':' . $blackL->id => 1, $p02->id . ':0' => 1];

        $this->as($this->seller1())->delete(route('seller.products.variations.delete', [$p01->id, $blackL->id]))->assertSessionHas('success');

        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->assertOk()
            ->assertSee('Variation unavailable')->assertDontSee('data-cart-key="' . $p01->id . ':' . $blackL->id . '"' . "\n                                    data-unit-price", false);
        $html = $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->getContent();
        $this->assertStringNotContainsString('class="item-checkbox"' . "\n                                    data-cart-key=\"" . $p01->id . ':' . $blackL->id . '"', $html);

        // It can't be checked out; the other line still can.
        $this->placeOrder($this->buyer1(), [$p01->id . ':' . $blackL->id => 1], self::ADDR_1)->assertSessionHas('error');
        $this->placeOrder($this->buyer1(), [$p02->id . ':0' => 1], self::ADDR_1)->assertSessionDoesntHaveErrors();
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function test_SEL_VAR_012_remove_a_variant_with_unfinished_orders(): void
    {
        $orderId = $this->variantOrder('Natural-S', 'Preparing'); // To Ship
        $natS = $this->variant('Natural-S');

        $this->as($this->seller1())->delete(route('seller.products.variations.delete', [$this->p01()->id, $natS->id]))
            ->assertSessionHas('error', 'Natural · S has unfinished orders, so it can\'t be removed yet. Set its stock to 0 to stop new orders.');

        $this->assertNotNull(\App\Models\ProductVariation::find($natS->id));
        // The order can still be shipped.
        $this->as($this->seller1())->post(route('seller.order.pickup', $orderId), ['pickup_date' => now()->toDateString()])->assertSessionHas('success');
    }

    /* ---------------------------------------------------------------
     | S5 Inventory
     * --------------------------------------------------------------- */

    private function buy(\App\Models\User $buyer, array $cart, string $payment = 'Cash on Delivery')
    {
        $this->flushSession();

        return $this->placeOrder($buyer, $cart, $buyer->is($this->buyer2()) ? self::ADDR_3 : self::ADDR_1, $payment);
    }

    private function variantStock(): array
    {
        return collect(['Natural-S', 'Natural-M', 'Natural-L', 'Black-S', 'Black-M', 'Black-L'])
            ->mapWithKeys(fn ($row) => [$row => (int) $this->variant($row)->stock])->all();
    }

    public function test_SEL_INV_001_stock_deducted_when_a_cod_order_is_placed(): void
    {
        $p02 = $this->p02();

        $this->buy($this->buyer1(), [$p02->id . ':0' => 2])->assertSessionDoesntHaveErrors();

        $this->assertSame('COD', $this->lastOrder($this->buyer1())->payment_status);
        $this->assertSame(18, $this->stockOf($p02));
    }

    public function test_SEL_INV_002_stock_deducted_when_an_unpaid_e_wallet_order_is_placed(): void
    {
        $p02 = $this->p02();

        $response = $this->buy($this->buyer1(), [$p02->id . ':0' => 1], 'GCash');
        $order = $this->lastOrder($this->buyer1());

        // The buyer goes to the sandbox gateway; the order waits in To Pay.
        $response->assertRedirect(route('payment.show', $order->payment_group));
        $this->assertSame('Unpaid', $order->payment_status);
        $this->assertSame('Pending', $order->status);
        $this->assertSame(19, $this->stockOf($p02));
    }

    public function test_SEL_INV_003_stock_restored_when_the_buyer_cancels(): void
    {
        $p02 = $this->p02();
        $this->buy($this->buyer1(), [$p02->id . ':0' => 2]);
        $this->assertSame(18, $this->stockOf($p02));

        $this->as($this->buyer1())->post(route('buyer.order.cancel', $this->lastOrder($this->buyer1())->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');

        $this->assertSame('Cancelled', $this->lastOrder($this->buyer1())->status);
        $this->assertSame(20, $this->stockOf($p02));
    }

    public function test_SEL_INV_004_stock_restored_on_auto_cancel_of_an_unpaid_order(): void
    {
        $p02 = $this->p02();
        $this->buy($this->buyer1(), [$p02->id . ':0' => 1], 'GCash');
        $this->assertSame(19, $this->stockOf($p02));

        $this->travel(59)->minutes();
        $this->as($this->seller1())->get(route('seller.dashboard'))->assertOk();
        $this->assertSame('Pending', $this->lastOrder($this->buyer1())->status);

        $this->travel(2)->minutes();
        $this->as($this->seller1())->get(route('seller.dashboard'))->assertOk();

        $order = $this->lastOrder($this->buyer1());
        $this->assertSame('Cancelled', $order->status);
        $this->assertStringStartsWith('Cancelled: unpaid', $order->cancellation_reason);
        $this->assertSame(20, $this->stockOf($p02));
    }

    public function test_SEL_INV_005_stock_per_variant(): void
    {
        $before = $this->variantStock();

        $this->buy($this->buyer1(), [$this->p01()->id . ':' . $this->variant('Natural-M')->id => 1])->assertSessionDoesntHaveErrors();

        $this->assertSame(array_merge($before, ['Natural-M' => 7]), $this->variantStock());
        $this->assertSame(5, $before['Natural-L']);
    }

    public function test_SEL_INV_006_overselling_1_left_2_buyers_check_out_at_once(): void
    {
        $p03 = $this->p03();
        $cart = [$p03->id . ':0' => 1];

        $this->buy($this->buyer1(), $cart)->assertSessionDoesntHaveErrors();
        $this->buy($this->buyer2(), $cart)->assertSessionHas('error');

        $this->assertSame(1, DB::table('orders')->where('buyer_id', $this->buyer1()->id)->count());
        $this->assertSame(0, DB::table('orders')->where('buyer_id', $this->buyer2()->id)->count());
        $this->assertSame(0, $this->stockOf($p03));
    }

    public function test_SEL_INV_007_automatic_sold_out_status(): void
    {
        $p07 = $this->p07();
        DB::table('products')->where('id', $p07->id)->update(['stock' => 1]);

        $this->buy($this->buyer1(), [$p07->id . ':0' => 1])->assertSessionDoesntHaveErrors();

        $this->as($this->buyer2())->get(route('product.details', $p07->id))->assertOk()->assertSee('Out of stock', false);
        $this->as($this->buyer2())->get(route('products', ['search' => 'Power Bank 10000mAh']))->assertSee('Sold out');
        $this->as($this->buyer2())->post(route('cart.add', $p07->id), ['quantity' => 1])->assertSessionHas('error');
        $this->as($this->seller2())->get(route('seller.dashboard'))->assertSeeInOrder(['Power Bank 10000mAh', 'Out of Stock']);
    }

    public function test_SEL_INV_008_low_stock_alert_at_the_threshold(): void
    {
        $p02 = $this->p02();
        DB::table('products')->where('id', $p02->id)->update(['stock' => 7]);
        $alerts = fn () => DB::table('notifications')->where('user_id', $this->seller1()->id)->where('title', 'Low Stock Alert')->count();

        $this->buy($this->buyer1(), [$p02->id . ':0' => 1]); // 6
        $this->assertSame(6, $this->stockOf($p02));
        $this->assertSame(0, $alerts());

        $this->buy($this->buyer1(), [$p02->id . ':0' => 1]); // 5
        $this->assertSame(1, $alerts());
        $this->assertSame('Rattan Fruit Basket has only 5 left. Restock it to keep selling.', DB::table('notifications')->where('title', 'Low Stock Alert')->value('message'));

        $this->buy($this->buyer1(), [$p02->id . ':0' => 1]); // 4
        $this->assertSame(4, $this->stockOf($p02));
        $this->assertSame(1, $alerts());
    }

    public function test_SEL_INV_009_manual_stock_adjustment(): void
    {
        $p02 = $this->p02();
        $save = fn ($stock) => $this->as($this->seller1())->from(route('seller.products.edit', $p02->id))->put(route('seller.products.update', $p02->id), [
            'name' => 'Rattan Fruit Basket', 'description' => $p02->description, 'category' => 'home-living', 'price' => '450.00', 'stock' => $stock,
        ]);

        foreach (['-1', '100000', '2.5', 'abc'] as $bad) {
            $save($bad)->assertSessionHasErrors(['stock' => 'Stock must be a whole number from 0 to 99,999.']);
            $this->assertSame(20, $this->stockOf($p02));
        }

        $save('0')->assertSessionHasNoErrors();
        $this->assertSame(0, $this->stockOf($p02));

        $save('99999')->assertSessionHasNoErrors();
        $this->assertSame(99999, $this->stockOf($p02));
    }

    public function test_SEL_INV_010_reduce_stock_below_the_quantity_in_a_cart(): void
    {
        $p02 = $this->p02();
        $cart = [$p02->id . ':0' => 5];
        DB::table('products')->where('id', $p02->id)->update(['stock' => 3]);

        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->assertOk()->assertSee('Only 3 left');
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))
            ->assertRedirect(route('cart'))->assertSessionHas('error', 'Rattan Fruit Basket: only 3 left. Lower the quantity to 3 or fewer to check out.');
        $this->buy($this->buyer1(), $cart)->assertSessionHas('error');
        $this->assertSame(0, DB::table('orders')->count());

        // With 3 in the cart it goes through.
        $this->buy($this->buyer1(), [$p02->id . ':0' => 3])->assertSessionDoesntHaveErrors();
        $this->assertSame(0, $this->stockOf($p02));
    }

    /* ---------------------------------------------------------------
     | S6 Discounts (store sales)
     * --------------------------------------------------------------- */

    private function saveSale(Product $product, array $overrides = [])
    {
        return $this->as($this->seller1())->from(route('seller.promotions'))->post(route('seller.sales.store'), array_merge([
            'product_id' => $product->id,
            'discount_type' => 'percent',
            'discount_value' => '20',
            'starts_at' => now()->addMinutes(10)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
        ], $overrides));
    }

    /** What a buyer sees for P-02: product page, search card, cart and checkout. */
    private function p02Everywhere(Product $p02): array
    {
        $cart = [$p02->id . ':0' => 1];

        return [
            'page' => $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()->getContent(),
            'card' => $this->as($this->buyer1())->get(route('products', ['search' => 'Rattan Fruit Basket']))->assertOk()->getContent(),
            'cart' => $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('cart'))->assertOk()->getContent(),
            'checkout' => $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))->assertOk()->getContent(),
        ];
    }

    private function assertRegularPrice(array $views): void
    {
        $this->assertStringContainsString('<span id="pdPrice" data-base="450">₱450</span>', $views['page']);
        $this->assertStringNotContainsString('class="price-off"', $views['page']);
        $this->assertStringContainsString('<span class="sp-price"><span class="sp-amt">₱450</span> </span>', $views['card']);
        $this->assertStringContainsString('450.00', $views['cart']);
        $this->assertStringNotContainsString('360.00', $views['cart']);
        $this->assertStringContainsString('450.00', $views['checkout']);
        $this->assertStringNotContainsString('360.00', $views['checkout']);
    }

    public function test_SEL_DISC_001_create_a_scheduled_percentage_sale(): void
    {
        $p02 = $this->p02();
        $start = now()->addMinutes(10);
        $end = now()->addHours(2);

        $this->saveSale($p02)->assertSessionHasNoErrors()->assertSessionHas('success');

        $sale = \App\Models\ProductSale::firstOrFail();
        $this->assertSame('Upcoming', $sale->status());
        $this->as($this->seller1())->get(route('seller.promotions'))->assertOk()
            ->assertSee('data-sale-status="' . $sale->id . '">Upcoming', false)
            ->assertSee($start->format('M d, Y g:i A'))->assertSee($end->format('M d, Y g:i A'))
            ->assertSee('₱450.00 → <b data-sale-price="' . $sale->id . '">₱360.00</b>', false);

        // Before the start: ₱450.00 and no badge.
        $this->assertRegularPrice($this->p02Everywhere($p02));
    }

    public function test_SEL_DISC_002_sale_goes_live_at_the_exact_start_time(): void
    {
        $p02 = $this->p02();
        $this->saveSale($p02)->assertSessionHasNoErrors();

        $this->travel(9)->minutes();
        $this->assertRegularPrice($this->p02Everywhere($p02));

        $this->travel(2)->minutes();
        $views = $this->p02Everywhere($p02);
        $this->assertStringContainsString('<span id="pdPrice" data-base="450">₱360</span>', $views['page']);
        $this->assertStringContainsString('<s class="price-was" id="pdPriceWas" data-extra="90">₱450</s>', $views['page']);
        $this->assertStringContainsString('<span class="price-off">-20%</span>', $views['page']);
        $this->assertStringContainsString('<span class="sp-off">-20%</span>', $views['card']);
        $this->assertStringContainsString('₱360</span>  <s class="sp-was">₱450</s>', $views['card']);
        $this->assertStringContainsString('360.00', $views['cart']);
        $this->assertStringContainsString('360.00', $views['checkout']);
    }

    public function test_SEL_DISC_003_sale_ends_at_the_exact_end_time(): void
    {
        $p02 = $this->p02();
        $this->saveSale($p02)->assertSessionHasNoErrors();

        $this->travelTo(\App\Models\ProductSale::first()->ends_at->copy()->subMinute());
        $this->assertStringContainsString('₱360', $this->p02Everywhere($p02)['page']);

        $this->travelTo(\App\Models\ProductSale::first()->ends_at->copy()->addMinute());
        $this->assertRegularPrice($this->p02Everywhere($p02));
        $this->assertSame('Ended', \App\Models\ProductSale::first()->status());
    }

    public function test_SEL_DISC_004_invalid_sale_dates(): void
    {
        $p02 = $this->p02();
        $at = fn ($time) => $time->format('Y-m-d\TH:i');

        $this->saveSale($p02, ['starts_at' => $at(now()->addHours(2)), 'ends_at' => $at(now()->addHour())])
            ->assertSessionHasErrors(['ends_at' => 'The end time must be after the start time.']);
        $this->saveSale($p02, ['starts_at' => $at(now()->addHour()), 'ends_at' => $at(now()->addHour())])
            ->assertSessionHasErrors(['ends_at' => 'The end time must be after the start time.']);
        $this->saveSale($p02, ['starts_at' => $at(now()->subHour()), 'ends_at' => $at(now()->addHour())])
            ->assertSessionHasErrors(['starts_at' => 'The start time must be in the future.']);

        $this->assertSame(0, \App\Models\ProductSale::count());
    }

    public function test_SEL_DISC_005_discount_percentage_at_the_limits(): void
    {
        $p02 = $this->p02();

        foreach (['0', '100', '101', '-5', '10.5'] as $bad) {
            $this->saveSale($p02, ['discount_value' => $bad])->assertSessionHasErrors(['discount_value' => 'The discount must be a whole number from 1% to 99%.']);
        }
        $this->assertSame(0, \App\Models\ProductSale::count());

        foreach (['1' => '₱445.50', '99' => '₱4.50'] as $percent => $price) {
            $this->saveSale($p02, ['discount_value' => (string) $percent])->assertSessionHasNoErrors();
            $sale = \App\Models\ProductSale::latest('id')->first();
            $this->as($this->seller1())->get(route('seller.promotions'))->assertSee('<b data-sale-price="' . $sale->id . '">' . $price . '</b>', false);
            $this->as($this->seller1())->post(route('seller.sales.end', $sale->id));
        }

        $this->assertSame(2, \App\Models\ProductSale::count());
    }

    public function test_SEL_DISC_006_fixed_sale_price_at_or_above_the_original(): void
    {
        $p02 = $this->p02();

        foreach (['450.01', '450.00'] as $bad) {
            $this->saveSale($p02, ['discount_type' => 'fixed', 'discount_value' => $bad])->assertSessionHasErrors(['discount_value' => 'The sale price must be lower than the regular price of ₱450.00.']);
        }
        $this->saveSale($p02, ['discount_type' => 'fixed', 'discount_value' => '0.99'])->assertSessionHasErrors(['discount_value']);
        $this->assertSame(0, \App\Models\ProductSale::count());

        foreach (['449.99', '1.00'] as $price) {
            $this->saveSale($p02, ['discount_type' => 'fixed', 'discount_value' => $price])->assertSessionHasNoErrors();
            $sale = \App\Models\ProductSale::latest('id')->first();
            $this->assertEquals((float) $price, $sale->priceFor(450));
            $this->as($this->seller1())->post(route('seller.sales.end', $sale->id));
        }
    }

    public function test_SEL_DISC_007_overlapping_sales_on_the_same_product(): void
    {
        $p02 = $this->p02();
        $at = fn (int $hour) => now()->setTime($hour, 0)->format('Y-m-d\TH:i');

        $this->saveSale($p02, ['starts_at' => $at(13), 'ends_at' => $at(15)])->assertSessionHasNoErrors();
        $this->saveSale($p02, ['starts_at' => $at(14), 'ends_at' => $at(16)])
            ->assertSessionHasErrors(['starts_at' => 'Rattan Fruit Basket already has a sale in that period. A product can be in one store sale at a time.']);

        $this->assertSame(1, \App\Models\ProductSale::count());
    }

    public function test_SEL_DISC_008_sale_on_a_product_with_variants(): void
    {
        $p01 = $this->p01();
        $this->saveSale($p01, ['discount_value' => '10'])->assertSessionHasNoErrors();
        $this->travel(11)->minutes();

        $options = collect($this->as($this->buyer1())->getJson(route('product.options', $p01->id))->json('variations'));
        foreach (['Natural-S' => 179.10, 'Natural-M' => 224.10, 'Natural-L' => 269.10] as $row => $price) {
            $this->assertEquals($price, $options->firstWhere('id', $this->variant($row)->id)['price'], $row);
        }

        $this->as($this->buyer1())->get(route('product.details', $p01->id))->assertSee('₱179.10 – ₱269.10');
        $this->as($this->buyer1())->get(route('products', ['search' => 'Abaca Woven Tote']))->assertSee('₱179.10</span> – <span class="sp-amt">₱269.10', false);
    }

    public function test_SEL_DISC_009_edit_a_running_sale(): void
    {
        $p02 = $this->p02();
        $this->saveSale($p02)->assertSessionHasNoErrors();
        $this->travel(11)->minutes();

        $this->buy($this->buyer1(), [$p02->id . ':0' => 1])->assertSessionDoesntHaveErrors();
        $earlier = $this->lastOrder($this->buyer1());
        $this->assertSame('360.00', $this->money(DB::table('order_items')->where('order_id', $earlier->id)->value('price')));

        $sale = \App\Models\ProductSale::first();
        $this->as($this->seller1())->put(route('seller.sales.update', $sale->id), ['discount_value' => '30'])->assertSessionHas('success');

        $this->as($this->buyer2())->get(route('product.details', $p02->id))->assertSee('<span id="pdPrice" data-base="450">₱315</span>', false);
        $this->assertSame('360.00', $this->money(DB::table('order_items')->where('order_id', $earlier->id)->value('price')));
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertSee('360');
    }

    public function test_SEL_DISC_010_end_a_running_sale_now(): void
    {
        $p02 = $this->p02();
        $this->saveSale($p02)->assertSessionHasNoErrors();
        $this->travel(11)->minutes();
        $sale = \App\Models\ProductSale::first();
        $this->assertSame('Ongoing', $sale->status());

        $this->as($this->seller1())->post(route('seller.sales.end', $sale->id))->assertSessionHas('success');

        $this->assertSame('Ended', $sale->fresh()->status());
        $this->as($this->seller1())->get(route('seller.promotions'))->assertSee('data-sale-status="' . $sale->id . '">Ended', false);
        $this->assertRegularPrice($this->p02Everywhere($p02));
    }

    /* ---------------------------------------------------------------
     | S7 Store Vouchers
     * --------------------------------------------------------------- */

    /** MCH50 from the Test Data sheet, with any field changed. */
    private function voucherForm(array $overrides = []): array
    {
        return array_merge([
            'code' => 'MCH50', 'discount_type' => 'fixed', 'discount_value' => '50', 'min_order_amount' => '300',
            'max_uses' => '100', 'per_buyer_limit' => '1',
            'starts_at' => now()->format('Y-m-d\TH:i'), 'ends_at' => now()->endOfMonth()->format('Y-m-d\TH:i'),
            'display' => 'claimable', 'applies_to' => 'all',
        ], $overrides);
    }

    private function createVoucher(array $overrides = [], ?\App\Models\User $seller = null)
    {
        return $this->as($seller ?? $this->seller1())->from(route('seller.vouchers'))->post(route('seller.vouchers.store'), $this->voucherForm($overrides));
    }

    private function voucher(string $code): ?\App\Models\Voucher
    {
        return \App\Models\Voucher::where('code', $code)->first();
    }

    /** The buyer applies a code in the cart and checks out by COD; the new order(s). */
    private function checkoutWithVoucher(\App\Models\User $buyer, array $cart, string $code)
    {
        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => $cart])->from(route('cart'))->post(route('cart.voucher.apply'), ['voucher_code' => $code]);
        $applied = session(\App\Support\VoucherSelection::KEY, []);

        return $this->actingAsUser($buyer, ['cart' => $cart, \App\Support\VoucherSelection::KEY => $applied])
            ->post(route('checkout.place'), ['address' => $buyer->is($this->buyer2()) ? self::ADDR_3 : self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery']);
    }

    /** A Seller 1 product at ₱300 (two of them make the sheet's ₱600 checkout). */
    private function tray(): Product
    {
        return $this->world['tray'] ??= $this->makeProduct($this->seller1(), ['name' => 'Banig Serving Tray', 'category' => 'Home & Living', 'price' => 300, 'stock' => 10]);
    }

    public function test_SEL_VOU_001_create_a_fixed_amount_claimable_voucher(): void
    {
        $this->p02();
        $this->createVoucher()->assertSessionHasNoErrors()->assertSessionHas('success');

        $voucher = $this->voucher('MCH50');
        $this->assertSame('Ongoing', $voucher->status());
        $this->assertSame(['fixed', 50.0, 300.0, 100, 1, 'claimable', 'all'], [$voucher->discount_type, (float) $voucher->discount_value, (float) $voucher->min_order_amount, (int) $voucher->max_uses, (int) $voucher->per_buyer_limit, $voucher->display, $voucher->applies_to]);

        foreach ([route('shop.seller', $this->seller1()->id), route('product.details', $this->p02()->id)] as $page) {
            $this->as($this->buyer1())->get($page)->assertOk()
                ->assertSee('₱50 off, min. spend ₱300')
                ->assertSee(route('vouchers.claim', $voucher->id), false)
                ->assertSee('>Claim</button>', false);
        }
    }

    public function test_SEL_VOU_002_create_a_percentage_voucher_with_a_cap(): void
    {
        $this->createVoucher(['code' => 'MCH10', 'discount_type' => 'percentage', 'discount_value' => '10', 'max_discount' => '100', 'min_order_amount' => '500', 'max_uses' => '50'])->assertSessionHasNoErrors();
        $voucher = $this->voucher('MCH10');

        $this->as($this->buyer1())->post(route('vouchers.claim', $voucher->id))->assertSessionHas('success');
        $this->assertTrue($voucher->claimedBy($this->buyer1()->id));

        // ₱600 of Seller 1 items → exactly ₱60.00 off.
        $this->checkoutWithVoucher($this->buyer1(), [$this->tray()->id . ':0' => 2], 'MCH10')->assertSessionDoesntHaveErrors();
        $this->assertSame(['MCH10', '60.00'], [$this->lastOrder($this->buyer1())->voucher_code, $this->money($this->lastOrder($this->buyer1())->discount_amount)]);

        // ₱1,250 (P-03) → capped at ₱100.00, not ₱125.00 (Buyer 2: one use per buyer).
        $this->checkoutWithVoucher($this->buyer2(), [$this->p03()->id . ':0' => 1], 'MCH10')->assertSessionDoesntHaveErrors();
        $this->assertSame('100.00', $this->money($this->lastOrder($this->buyer2())->discount_amount));
    }

    public function test_SEL_VOU_003_code_only_voucher_is_hidden(): void
    {
        $p06 = $this->p06();
        $this->createVoucher(['code' => 'RVSECRET', 'discount_value' => '100', 'min_order_amount' => '1000', 'max_uses' => '20', 'display' => 'code_only'], $this->seller2())->assertSessionHasNoErrors();

        foreach ([route('shop.seller', $this->seller2()->id), route('product.details', $p06->id), route('buyer.vouchers')] as $page) {
            $this->as($this->buyer1())->get($page)->assertOk()->assertDontSee('RVSECRET')->assertDontSee('₱100 off, min. spend ₱1,000');
        }

        $black = $p06->variations()->where('variation_value', 'Black')->first();
        $this->checkoutWithVoucher($this->buyer1(), [$p06->id . ':' . $black->id => 1], 'rvsecret')->assertSessionDoesntHaveErrors();
        $this->assertSame(['RVSECRET', '100.00'], [$this->lastOrder($this->buyer1())->voucher_code, $this->money($this->lastOrder($this->buyer1())->discount_amount)]);
    }

    public function test_SEL_VOU_004_voucher_code_format(): void
    {
        foreach (['ABC', 'ABCDEFGHIJKLM', 'MCH 50', 'MCH-50'] as $bad) {
            $this->createVoucher(['code' => $bad])->assertSessionHasErrors(['code' => 'The code must be 4 to 12 letters and numbers, with no spaces or symbols.']);
        }

        foreach (['ABCD', 'ABCDEFGHIJKL', 'mch75'] as $good) {
            $this->createVoucher(['code' => $good])->assertSessionHasNoErrors();
        }

        $this->assertSame(['ABCD', 'ABCDEFGHIJKL', 'MCH75'], \App\Models\Voucher::orderBy('id')->pluck('code')->all());
    }

    public function test_SEL_VOU_005_duplicate_voucher_code(): void
    {
        $this->createVoucher()->assertSessionHasNoErrors();
        \App\Models\Voucher::create(['seller_id' => null, 'kind' => 'platform', 'code' => 'PLAT100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 1000, 'per_buyer_limit' => 1, 'is_active' => true]);

        foreach (['MCH50', 'mch50', 'PLAT100'] as $code) {
            $this->createVoucher(['code' => $code])->assertSessionHasErrors(['code' => 'The code ' . strtoupper($code) . ' is already used by another voucher.']);
        }

        $this->assertSame(2, \App\Models\Voucher::count());
    }

    public function test_SEL_VOU_006_discount_larger_than_the_minimum_spend(): void
    {
        $this->createVoucher(['code' => 'BIG500', 'discount_value' => '500', 'min_order_amount' => '100'])
            ->assertSessionHasErrors(['discount_value' => 'The discount can\'t be more than the minimum spend of ₱100.00.']);
        $this->createVoucher(['code' => 'EVEN100', 'discount_value' => '100', 'min_order_amount' => '100'])->assertSessionHasNoErrors();

        $this->assertSame(['EVEN100'], \App\Models\Voucher::pluck('code')->all());
    }

    public function test_SEL_VOU_007_field_validation(): void
    {
        foreach ([
            [['discount_value' => '0'], 'discount_value'],
            [['discount_type' => 'percentage', 'discount_value' => '0'], 'discount_value'],
            [['discount_type' => 'percentage', 'discount_value' => '100'], 'discount_value'],
            [['discount_type' => 'percentage', 'discount_value' => '10', 'max_discount' => '0'], 'max_discount'],
            [['max_uses' => '0'], 'max_uses'],
            [['max_uses' => '1', 'per_buyer_limit' => '2'], 'per_buyer_limit'],
            [['starts_at' => now()->addDays(5)->format('Y-m-d\TH:i'), 'ends_at' => now()->addDay()->format('Y-m-d\TH:i')], 'ends_at'],
        ] as $i => [$change, $field]) {
            $this->createVoucher(['code' => 'BAD' . $i . 'X'] + $change);
            $this->assertSame([$field], $this->errorKeys(), 'case ' . $i);
        }

        $this->assertSame(0, \App\Models\Voucher::count());
    }

    public function test_SEL_VOU_008_voucher_for_selected_products_only(): void
    {
        $p01 = $this->p01();
        $p02 = $this->p02();
        $this->createVoucher(['code' => 'MCHTOTE', 'discount_value' => '30', 'min_order_amount' => '100', 'applies_to' => 'selected', 'product_ids' => [$p01->id]])->assertSessionHasNoErrors();
        $natM = $this->variant('Natural-M');

        // P-02 alone: not applicable.
        $this->actingAsUser($this->buyer1(), ['cart' => [$p02->id . ':0' => 1]])->post(route('cart.voucher.apply'), ['voucher_code' => 'MCHTOTE'])
            ->assertSessionHas('error', 'Voucher MCHTOTE is not applicable to the items in your cart.');

        // P-01 Natural-M alone: ₱30 off.
        $this->checkoutWithVoucher($this->buyer1(), [$p01->id . ':' . $natM->id => 1], 'MCHTOTE')->assertSessionDoesntHaveErrors();
        $this->assertSame('30.00', $this->money($this->lastOrder($this->buyer1())->discount_amount));

        // P-01 + P-02: ₱30 off, and the minimum spend counts only P-01 (₱249).
        $voucher = $this->voucher('MCHTOTE');
        $lines = [['seller_id' => $this->seller1()->id, 'product_id' => $p01->id, 'price' => 249, 'quantity' => 1], ['seller_id' => $this->seller1()->id, 'product_id' => $p02->id, 'price' => 450, 'quantity' => 1]];
        $this->assertSame(249.0, $voucher->eligibleSubtotal($lines));
        $voucher->update(['min_order_amount' => 300]);
        $this->assertSame('Voucher MCHTOTE needs a minimum spend of ₱300.00 on the items it covers (you have ₱249.00). Spend ₱51.00 more to use it.', \App\Support\CheckoutPlan::build($lines, $voucher)['problems']['MCHTOTE']);
        $voucher->update(['min_order_amount' => 100]);

        $this->checkoutWithVoucher($this->buyer2(), [$p01->id . ':' . $natM->id => 1, $p02->id . ':0' => 1], 'MCHTOTE')->assertSessionDoesntHaveErrors();
        $this->assertSame('30.00', $this->money($this->lastOrder($this->buyer2())->discount_amount));
    }

    public function test_SEL_VOU_009_edit_a_voucher_buyers_already_claimed(): void
    {
        $this->createVoucher()->assertSessionHasNoErrors();
        $voucher = $this->voucher('MCH50');
        $this->as($this->buyer1())->post(route('vouchers.claim', $voucher->id));

        $update = fn (array $form) => $this->as($this->seller1())->from(route('seller.vouchers'))->put(route('seller.vouchers.update', $voucher->id), $form);

        $update(['min_order_amount' => '500'])->assertSessionHasErrors(['min_order_amount']);
        $update(['discount_value' => '20'])->assertSessionHasErrors(['discount_value']);
        $this->assertEquals([50.0, 300.0], [(float) $voucher->fresh()->discount_value, (float) $voucher->fresh()->min_order_amount]);

        $newEnd = $voucher->ends_at->copy()->addDays(7);
        $update(['ends_at' => $newEnd->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $update(['max_uses' => '150'])->assertSessionHasNoErrors();

        $this->assertSame($newEnd->format('Y-m-d H:i'), $voucher->fresh()->ends_at->format('Y-m-d H:i'));
        $this->assertSame(150, (int) $voucher->fresh()->max_uses);

        // The seller's form shows them locked; Buyer 1's wallet shows the new end date.
        $this->as($this->seller1())->get(route('seller.vouchers'))->assertSee('Amount and minimum spend locked');
        $this->as($this->buyer1())->get(route('buyer.vouchers'))->assertSee('data-wallet-voucher="MCH50"', false)->assertSee($newEnd->format('M d, Y g:i A'));
    }

    public function test_SEL_VOU_010_end_a_voucher_buyers_already_claimed(): void
    {
        $this->createVoucher()->assertSessionHasNoErrors();
        $voucher = $this->voucher('MCH50');
        $this->as($this->buyer1())->post(route('vouchers.claim', $voucher->id));

        // Buyer 2 used it on an order that is now To Ship.
        $this->checkoutWithVoucher($this->buyer2(), [$this->p02()->id . ':0' => 1], 'MCH50')->assertSessionDoesntHaveErrors();
        $buyer2Order = $this->lastOrder($this->buyer2());
        $this->setOrder($buyer2Order->id, ['status' => 'Preparing']);

        $this->as($this->seller1())->post(route('seller.vouchers.end', $voucher->id))->assertSessionHas('success');

        $this->as($this->buyer1())->get(route('buyer.vouchers'))->assertDontSee('data-wallet-voucher="MCH50"', false);
        $this->actingAsUser($this->buyer1(), ['cart' => [$this->p02()->id . ':0' => 1]])->post(route('cart.voucher.apply'), ['voucher_code' => 'MCH50'])
            ->assertSessionHas('error', 'Voucher MCH50 has expired.');
        $this->assertSame('50.00', $this->money(DB::table('orders')->where('id', $buyer2Order->id)->value('discount_amount')));
    }

    public function test_SEL_VOU_011_usage_counters(): void
    {
        $this->createVoucher()->assertSessionHasNoErrors();

        $this->checkoutWithVoucher($this->buyer1(), [$this->p02()->id . ':0' => 1], 'MCH50')->assertSessionDoesntHaveErrors();
        $this->as($this->seller1())->get(route('seller.vouchers'))->assertSee('Used 1 of 100');

        $this->as($this->buyer1())->post(route('buyer.order.cancel', $this->lastOrder($this->buyer1())->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');
        $this->as($this->seller1())->get(route('seller.vouchers'))->assertSee('Used 0 of 100');
    }

    /* ---------------------------------------------------------------
     | S8 Flash Sales & Bundles
     * --------------------------------------------------------------- */

    /** A flash sale on P-02 in today's 12:00–14:00 slot (slot 6). */
    private function saveFlash(array $overrides = [])
    {
        return $this->as($this->seller1())->from(route('seller.promotions'))->post(route('seller.flash.store'), array_merge([
            'product_id' => $this->p02()->id, 'date' => now()->toDateString(), 'slot' => 6, 'flash_price' => '299', 'quantity' => '3',
        ], $overrides));
    }

    private function flashLive(int $left = 3): \App\Models\FlashSale
    {
        $this->saveFlash()->assertSessionHasNoErrors();
        $flash = \App\Models\FlashSale::firstOrFail();
        $flash->update(['sold' => 3 - $left]);
        $this->travelTo(now()->setTime(12, 30));

        return $flash;
    }

    public function test_SEL_FLASH_001_create_a_flash_sale_in_a_time_slot(): void
    {
        $p02 = $this->p02();
        $this->saveFlash()->assertSessionHasNoErrors()->assertSessionHas('success');
        $flash = \App\Models\FlashSale::firstOrFail();
        $this->assertSame('Upcoming', $flash->status());
        $this->assertSame([now()->setTime(12, 0)->format('Y-m-d H:i'), now()->setTime(14, 0)->format('Y-m-d H:i')], [$flash->starts_at->format('Y-m-d H:i'), $flash->ends_at->format('Y-m-d H:i')]);

        $this->travelTo(now()->setTime(11, 59));
        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()
            ->assertSee('<span id="pdPrice" data-base="450">₱450</span>', false)->assertSee('Upcoming flash sale: ₱299 at 12:00 PM');

        $this->travelTo(now()->setTime(12, 0));
        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertOk()
            ->assertSee('<span id="pdPrice" data-base="450">₱299</span>', false)
            ->assertSee('data-countdown="' . $flash->ends_at->toIso8601String() . '"', false)
            ->assertSee('02:00:00')->assertSee('3 left');
    }

    public function test_SEL_FLASH_002_flash_quantity_and_price_validation(): void
    {
        $this->saveFlash(['quantity' => '0'])->assertSessionHasErrors(['quantity' => 'The flash quantity must be from 1 to the stock you have (20).']);
        $this->saveFlash(['quantity' => '21'])->assertSessionHasErrors(['quantity' => 'The flash quantity must be from 1 to the stock you have (20).']);
        $this->saveFlash(['flash_price' => '450'])->assertSessionHasErrors(['flash_price' => 'The flash price must be lower than the regular price of ₱450.00.']);
        $this->assertSame(0, \App\Models\FlashSale::count());

        $this->saveFlash(['quantity' => '20'])->assertSessionHasNoErrors();
        $this->saveFlash(['flash_price' => '449.99', 'slot' => 7])->assertSessionHasNoErrors();
        $this->assertSame([20, 449.99], [(int) \App\Models\FlashSale::first()->quantity, (float) \App\Models\FlashSale::latest('id')->first()->flash_price]);
    }

    public function test_SEL_FLASH_003_price_returns_to_normal_when_flash_quantity_runs_out(): void
    {
        $this->flashLive(3);
        $p02 = $this->p02();

        $this->buy($this->buyer1(), [$p02->id . ':0' => 3])->assertSessionDoesntHaveErrors();
        $order = $this->lastOrder($this->buyer1());
        $this->assertSame('897.00', $this->money(DB::table('order_items')->where('order_id', $order->id)->sum(DB::raw('price * quantity'))));
        $this->assertSame(3, (int) \App\Models\FlashSale::first()->sold);

        $this->as($this->buyer2())->get(route('product.details', $p02->id))->assertSee('<span id="pdPrice" data-base="450">₱450</span>', false)->assertDontSee('Flash sale ·');
        $this->actingAsUser($this->buyer2(), ['cart' => [$p02->id . ':0' => 1]])->get(route('cart'))->assertSee('₱450.00 each')->assertDontSee('(flash sale)');
    }

    public function test_SEL_FLASH_004_order_more_than_the_remaining_flash_quantity(): void
    {
        $this->flashLive(1);
        $p02 = $this->p02();
        $cart = [$p02->id . ':0' => 2];

        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->get(route('checkout', ['items' => $p02->id . ':0']))->assertOk()
            ->assertSee('1 × ₱299.00')->assertSee('1 × ₱450.00')->assertSee('749.00');

        $this->buy($this->buyer1(), $cart)->assertSessionDoesntHaveErrors();
        $order = $this->lastOrder($this->buyer1());
        $lines = DB::table('order_items')->where('order_id', $order->id)->orderBy('id')->get(['price', 'quantity', 'price_note']);
        $this->assertSame([['299.00', 1, 'flash'], ['450.00', 1, null]], $lines->map(fn ($l) => [$this->money($l->price), (int) $l->quantity, $l->price_note])->all());
        $this->assertSame('749.00', $this->money($order->total_amount - $order->delivery_fee));

        $this->as($this->seller1())->get(route('seller.order.details', $order->id))->assertSee('₱299.00')->assertSee('₱450.00');
    }

    public function test_SEL_FLASH_005_flash_sale_ends_at_the_slot_end(): void
    {
        $this->flashLive(3);
        $p02 = $this->p02();
        $this->assertStringContainsString('₱299', $this->p02Everywhere($p02)['page']);

        $this->travelTo(now()->setTime(14, 0));
        $this->assertSame('Ended', \App\Models\FlashSale::first()->status());
        $this->assertRegularPrice($this->p02Everywhere($p02));
    }

    public function test_SEL_FLASH_006_overlapping_store_sale_and_flash_sale(): void
    {
        $p02 = $this->p02();
        \App\Models\ProductSale::create(['seller_id' => $this->seller1()->id, 'product_id' => $p02->id, 'discount_type' => 'fixed', 'discount_value' => 360, 'starts_at' => now(), 'ends_at' => now()->addDay()]);
        $this->flashLive(1);

        $this->as($this->buyer1())->get(route('product.details', $p02->id))->assertSee('<span id="pdPrice" data-base="450">₱299</span>', false);
        $this->actingAsUser($this->buyer1(), ['cart' => [$p02->id . ':0' => 2]])->get(route('checkout', ['items' => $p02->id . ':0']))->assertOk()
            ->assertSee('1 × ₱299.00')->assertSee('1 × ₱360.00')->assertDontSee('1 × ₱450.00');
    }

    public function test_SEL_FLASH_007_bundle_deal_item_counting(): void
    {
        $p01 = $this->p01();
        $this->as($this->seller1())->post(route('seller.bundles.store'), ['name' => 'Tote trio', 'type' => 'bundle', 'product_ids' => [$p01->id], 'min_quantity' => 3, 'percent' => 15])->assertSessionHasNoErrors();
        $natS = $p01->id . ':' . $this->variant('Natural-S')->id;
        $blackL = $p01->id . ':' . $this->variant('Black-L')->id;
        $total = fn (array $cart) => array_sum(array_column(\App\Support\CartPricing::price(\App\Support\CartPricing::fromCart($cart)), 'total'));

        // 2 items: no discount.
        $this->assertEquals(398.0, $total([$natS => 2]));
        $this->actingAsUser($this->buyer1(), ['cart' => [$natS => 2]])->get(route('cart'))->assertDontSee('(bundle deal)');

        // 3 items (mixed options): 15% of ₱697 = ₱104.55 off.
        $this->assertEquals(round(697 - 104.55, 2), round($total([$natS => 2, $blackL => 1]), 2));
        $this->actingAsUser($this->buyer1(), ['cart' => [$natS => 2, $blackL => 1]])->get(route('cart'))->assertSee('(bundle deal)')->assertSee('592.45');

        // One removed: discount gone.
        $this->assertEquals(498.0, $total([$natS => 1, $blackL => 1]));
    }

    public function test_SEL_FLASH_008_buy_x_get_y(): void
    {
        $p02 = $this->p02();
        $p04 = $this->p04();
        DB::table('products')->where('id', $p04->id)->update(['stock' => 10]);
        $this->as($this->seller1())->post(route('seller.bundles.store'), [
            'name' => 'Basket + mats', 'type' => 'bxgy', 'buy_product_id' => $p02->id, 'buy_quantity' => 2, 'get_product_id' => $p04->id, 'get_quantity' => 1,
        ])->assertSessionHasNoErrors();
        $p04Line = fn (array $cart) => collect(\App\Support\CartPricing::price(\App\Support\CartPricing::fromCart($cart)))->firstWhere('key', $p04->id . ':0');

        $line = $p04Line([$p02->id . ':0' => 2, $p04->id . ':0' => 1]);
        $this->assertSame([[0.0, 1, 'free']], array_map(fn ($p) => [(float) $p['price'], $p['quantity'], $p['note']], $line['pieces']));
        $this->actingAsUser($this->buyer1(), ['cart' => [$p02->id . ':0' => 2, $p04->id . ':0' => 1]])->get(route('cart'))->assertSee('1 × ₱0 (free)');

        $line = $p04Line([$p02->id . ':0' => 1, $p04->id . ':0' => 1]);
        $this->assertEquals(350.0, $line['total']);
    }

    /* ---------------------------------------------------------------
     | S9 Order Processing
     * --------------------------------------------------------------- */

    /** The worked checkout example: Seller 1 (Natural-M + P-02, MCH50) and Seller 2 (P-05), COD to ADDR-1. */
    private function workedExample(string $note = 'Please pack it as a gift.'): array
    {
        $this->sheetRates();
        $this->createVoucher()->assertSessionHasNoErrors();
        $cart = [$this->p01()->id . ':' . $this->variant('Natural-M')->id => 1, $this->p02()->id . ':0' => 1, $this->p05()->id . ':0' => 1];

        $this->flushSession();
        $this->actingAsUser($this->buyer1(), ['cart' => $cart])->post(route('cart.voucher.apply'), ['voucher_code' => 'MCH50'])->assertSessionHas('success');
        $this->actingAsUser($this->buyer1(), ['cart' => $cart, \App\Support\VoucherSelection::KEY => ['MCH50']])->post(route('checkout.place'), [
            'address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery', 'note' => $note,
        ])->assertSessionDoesntHaveErrors();

        $orders = DB::table('orders')->where('buyer_id', $this->buyer1()->id)->orderBy('id')->get();
        $bySeller = fn (int $sellerId) => $orders->first(fn ($o) => DB::table('order_items')->where('order_id', $o->id)->value('seller_id') == $sellerId);

        return ['seller1' => $bySeller($this->seller1()->id), 'seller2' => $bySeller($this->seller2()->id)];
    }

    private function sellerTabIds(string $tab, array $query = []): array
    {
        $orders = $this->as($this->seller1())->get(route('seller.orders', ['tab' => $tab] + $query))->assertOk()->viewData('orders');

        return collect($orders?->items() ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_SEL_ORD_001_order_list_tabs_counts_and_filters(): void
    {
        $p02 = $this->p02();
        $ids = [];
        foreach (['Pending' => 'to-process', 'Preparing' => 'to-ship', 'Out for Delivery' => 'shipped', 'Completed' => 'completed', 'Cancelled' => 'cancelled'] as $status => $tab) {
            $ids[$tab] = $this->makeOrder($this->buyer1(), $p02, $status);
        }

        // Each order appears in exactly one tab (besides All), matching its status.
        $response = $this->as($this->seller1())->get(route('seller.orders'))->assertOk();
        $counts = $response->viewData('tabCounts');
        foreach ($ids as $tab => $id) {
            $this->assertSame([$id], $this->sellerTabIds($tab), $tab);
            $this->assertSame(1, $counts[$tab], $tab);
        }
        $this->assertSame(5, $counts['all']);

        // A return request is listed under Return Requests.
        $this->assertArrayHasKey('returns', $counts);

        // Date filter (both ends included) and order # search.
        DB::table('orders')->where('id', $ids['completed'])->update(['created_at' => now()->subDays(10)]);
        $this->assertSame([$ids['completed']], $this->sellerTabIds('all', ['from' => now()->subDays(10)->toDateString(), 'to' => now()->subDays(10)->toDateString()]));
        $this->assertSame([$ids['shipped']], $this->sellerTabIds('all', ['q' => '#' . $ids['shipped']]));
        $this->as($this->seller1())->get(route('seller.orders', ['from' => now()->toDateString(), 'to' => now()->subDay()->toDateString()]))
            ->assertSessionHas('error', 'The end date must be on or after the start date.');
    }

    public function test_SEL_ORD_002_accept_pack_ready_for_pickup_and_print_waybill(): void
    {
        $this->sheetRates();
        $p02 = $this->p02();
        $this->buy($this->buyer1(), [$p02->id . ':0' => 1])->assertSessionDoesntHaveErrors();
        $order = $this->lastOrder($this->buyer1());
        $this->assertSame('500.00', $this->money($order->total_amount));

        foreach (['Confirmed', 'Preparing'] as $step) {
            $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => $step])->assertSessionHas('success');
        }
        $this->as($this->seller1())->post(route('seller.order.pickup', $order->id), ['pickup_date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertSame('Ready for Pickup', $this->orderStatus($order->id));

        // Each step has a timestamp on the buyer's timeline.
        $steps = DB::table('order_events')->where('order_id', $order->id)->orderBy('id')->get();
        $this->assertSame(['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup'], $steps->pluck('status')->unique()->values()->all());
        $this->assertTrue($steps->every(fn ($e) => !empty($e->created_at)));
        $this->assertSame(['Order placed', 'Seller accepted your order', 'Seller is preparing your order'], $steps->pluck('title')->take(3)->all());
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertOk()->assertSee('#' . $order->id);

        // The waybill: tracking number, ADDR-1, Seller 1's pickup address, 0.8 kg and COD ₱500.00.
        $this->as($this->seller1())->get(route('seller.order.label', $order->id))->assertOk()
            ->assertSee(\App\Support\Waybill::number($order->id))
            ->assertSee(self::ADDR_1)->assertSee('Blk 12 Lot 8, Sampaguita St., Commonwealth, Quezon City')
            ->assertSee('0.8 kg')->assertSee('COD')->assertSee('₱500.00');

        // BoomBuy Logistics sees it waiting for a pickup rider.
        $logistics = $this->makeLogistics();
        $this->actingAsUser($logistics)->get(route('logistics.parcels'))->assertOk()->assertSee('#' . $order->id);
    }

    public function test_SEL_ORD_003_skip_a_step(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Pending');

        $this->as($this->seller1())->get(route('seller.order.details', $orderId))->assertOk()->assertSee('Accept Order')->assertDontSee('Mark as Ready for Pickup');
        $this->as($this->seller1())->post(route('seller.order.status', $orderId), ['status' => 'Preparing'])->assertSessionHas('error', 'Accept the order first.');
        $this->as($this->seller1())->post(route('seller.order.pickup', $orderId), ['pickup_date' => now()->toDateString()])->assertSessionHas('error');

        $this->assertSame('Pending', $this->orderStatus($orderId));
    }

    public function test_SEL_ORD_004_seller_cancels_a_cod_order(): void
    {
        $this->createVoucher()->assertSessionHasNoErrors();
        $p02 = $this->p02();
        $this->as($this->buyer1())->post(route('vouchers.claim', $this->voucher('MCH50')->id));
        $this->checkoutWithVoucher($this->buyer1(), [$p02->id . ':0' => 2], 'MCH50')->assertSessionDoesntHaveErrors();
        $order = $this->lastOrder($this->buyer1());
        $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Confirmed']);
        $this->assertSame(18, $this->stockOf($p02));

        $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Cancelled', 'cancellation_reason' => ''])->assertSessionHas('error', 'Please provide a reason for cancelling this order.');
        $this->assertSame('Confirmed', $this->orderStatus($order->id));

        $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Cancelled', 'cancellation_reason' => 'Out of stock'])->assertSessionHas('success');

        $this->assertSame('Cancelled', $this->orderStatus($order->id));
        $this->assertSame(20, $this->stockOf($p02));
        $this->assertSame(0, (int) $this->voucher('MCH50')->used_count);
        $this->as($this->buyer1())->get(route('buyer.vouchers'))->assertSee('data-wallet-voucher="MCH50"', false);
        $this->assertStringContainsString('Reason: Out of stock', DB::table('notifications')->where('user_id', $this->buyer1()->id)->where('title', 'Order Cancelled by Seller')->value('message'));
    }

    public function test_SEL_ORD_005_seller_cancels_a_prepaid_order(): void
    {
        $p02 = $this->p02();
        $this->buy($this->buyer1(), [$p02->id . ':0' => 1], 'GCash');
        $order = $this->lastOrder($this->buyer1());
        $this->as($this->buyer1())->post(route('payment.pay', $order->payment_group));
        $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Confirmed'])->assertSessionHas('success');

        $this->as($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Cancelled', 'cancellation_reason' => 'Out of stock'])->assertSessionHas('success');

        $order = DB::table('orders')->find($order->id);
        $this->assertSame(['Cancelled', 'Refund Pending'], [$order->status, $order->payment_status]);
        $this->assertSame($this->money($order->total_amount), $this->money($order->refund_amount));
        $this->assertStringContainsString('A refund of ₱' . number_format((float) $order->total_amount, 2) . ' to your GCash has been started.', DB::table('notifications')->where('user_id', $this->buyer1()->id)->where('title', 'Order Cancelled by Seller')->value('message'));

        // Not counted in Seller 1's sales.
        $this->assertEquals(0, $this->as($this->seller1())->get(route('seller.reports'))->viewData('totalSales'));
    }

    public function test_SEL_ORD_006_no_cancel_after_handover(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Picked Up');

        $this->as($this->seller1())->get(route('seller.order.details', $orderId))->assertOk()->assertDontSee('value="Cancelled"', false);
        $this->as($this->seller1())->post(route('seller.order.status', $orderId), ['status' => 'Cancelled', 'cancellation_reason' => 'Out of stock'])->assertSessionHas('error');

        $this->assertSame('Picked Up', $this->orderStatus($orderId));
    }

    public function test_SEL_ORD_007_order_details_accuracy(): void
    {
        $orders = $this->workedExample();

        $this->as($this->seller1())->get(route('seller.order.details', $orders['seller1']->id))->assertOk()
            ->assertSee('Abaca Woven Tote Bag')->assertSee('Color: Natural · Size: M')->assertSee('₱249.00')
            ->assertSee('Rattan Fruit Basket')->assertSee('₱450.00')
            ->assertSee('Your voucher (MCH50)')->assertSee('−₱50.00')
            ->assertSee('₱65.00')
            ->assertSee('Please pack it as a gift.')
            ->assertSee('Cash on Delivery')->assertSee(self::ADDR_1)
            ->assertDontSee('USB-C Fast Charger 20W');
    }

    public function test_SEL_ORD_008_seller_cannot_open_another_stores_order(): void
    {
        $orders = $this->workedExample();

        $this->as($this->seller1())->get(route('seller.order.details', $orders['seller2']->id))->assertNotFound()->assertDontSee('USB-C Fast Charger 20W');
        $this->as($this->seller1())->get(route('seller.order.label', $orders['seller2']->id))->assertNotFound();
    }

    /* ---------------------------------------------------------------
     | S10 Returns
     * --------------------------------------------------------------- */

    /* ---------------------------------------------------------------
     | S12 Reports & Earnings
     * ---------------------------------------------------------------
     | BoomBuy rule (A9, ERP list "Commission 10%"): the commission is
     | 10% of the items after the seller's own vouchers, so the worked
     | example's Seller 1 net is ₱649.00 − ₱64.90 = ₱584.10 (the sheet's
     | 5% gives ₱616.55).
     */

    /** The worked example with BoomBuy's PLAT100 and FREESHIP too; Seller 1's order. */
    private function workedExampleWithPlatform(): object
    {
        \App\Models\Voucher::create(['seller_id' => null, 'kind' => 'platform', 'code' => 'PLAT100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 1000, 'per_buyer_limit' => 1, 'is_active' => true, 'display' => 'claimable']);
        \App\Models\Voucher::create(['seller_id' => null, 'kind' => 'shipping', 'code' => 'FREESHIP', 'discount_type' => 'fixed', 'discount_value' => 50, 'min_order_amount' => 300, 'per_buyer_limit' => 1, 'is_active' => true, 'display' => 'claimable']);
        $this->sheetRates();
        $this->createVoucher()->assertSessionHasNoErrors();
        $cart = [$this->p01()->id . ':' . $this->variant('Natural-M')->id => 1, $this->p02()->id . ':0' => 1, $this->p05()->id . ':0' => 1];

        $this->flushSession();
        $this->actingAsUser($this->buyer1(), ['cart' => $cart, \App\Support\VoucherSelection::KEY => ['MCH50', 'PLAT100', 'FREESHIP']])->post(route('checkout.place'), [
            'address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery',
        ])->assertSessionDoesntHaveErrors();

        $orderId = DB::table('order_items')->where('seller_id', $this->seller1()->id)->value('order_id');

        return DB::table('orders')->find($orderId);
    }

    private function earnings(): array
    {
        $response = $this->as($this->seller1())->get(route('seller.earnings'))->assertOk();

        return ['page' => $response->getContent(), 'balances' => $response->viewData('balances')];
    }

    public function test_SEL_EARN_001_net_earnings_calculation(): void
    {
        $order = $this->workedExampleWithPlatform();
        $this->setOrder($order->id, ['status' => 'Completed', 'buyer_received_at' => now()]);

        // The checkout itself matches the sheet: ₱63.66 PLAT100 share for Seller 1, FREESHIP on shipping.
        $this->assertSame(['50.00', '63.66', '65.00'], [$this->money($order->discount_amount), $this->money($order->platform_discount), $this->money($order->delivery_fee)]);

        $html = $this->earnings()['page'];
        preg_match('/data-earning-order="' . $order->id . '">.*?<\/small>/s', $html, $row);
        $this->assertStringContainsString('Gross <b data-gross>₱699.00</b>', $row[0]);
        $this->assertStringContainsString('Your voucher <b data-voucher>−₱50.00</b>', $row[0]);
        $this->assertStringContainsString('Commission 10% <b data-commission>−₱64.90</b>', $row[0]);
        $this->assertStringContainsString('Net <b data-net>₱584.10</b>', $row[0]);
        $this->assertStringNotContainsString('63.66', $row[0]);
        $this->assertStringNotContainsString('65.00', $row[0]);
    }

    public function test_SEL_EARN_002_earnings_pending_until_completion(): void
    {
        $order = $this->workedExampleWithPlatform();
        $this->setOrder($order->id, ['status' => 'Delivered', 'delivered_at' => now()]);

        $this->assertSame([584.10, 0.0], [$this->earnings()['balances']['pending'], $this->earnings()['balances']['available']]);

        $this->as($this->buyer1())->post(route('buyer.order.received', $order->id))->assertSessionHas('success');

        $this->assertSame([0.0, 584.10], [$this->earnings()['balances']['pending'], $this->earnings()['balances']['available']]);
    }

    public function test_SEL_EARN_003_cancelled_and_refunded_orders_excluded(): void
    {
        $p02 = $this->p02();
        $this->makeOrder($this->buyer1(), $p02, 'Cancelled');
        $refunded = $this->returnRequest('Refund');
        DB::table('return_refund_requests')->where('id', $refunded->id)->update(['status' => 'completed', 'refunded_at' => now()]);

        $report = $this->as($this->seller1())->get(route('seller.reports'))->assertOk();
        $this->assertEquals(0, $report->viewData('totalSales'));
        $this->assertEquals(0, $report->viewData('commissionOwed'));
        $this->assertEquals(0, $report->viewData('netEarnings'));
        $this->assertSame(0, $report->viewData('deliveredOrders'));
        $this->assertSame(0.0, $this->earnings()['balances']['available']);
    }

    public function test_SEL_EARN_004_report_date_range(): void
    {
        $p02 = $this->p02();
        $today = $this->makeOrder($this->buyer1(), $p02, 'Completed');
        $fiveDays = $this->makeOrder($this->buyer1(), $p02, 'Completed', ['created_at' => now()->subDays(5)]);
        $tenDays = $this->makeOrder($this->buyer1(), $p02, 'Completed', ['created_at' => now()->subDays(10)]);
        $sales = fn (array $range) => $this->as($this->seller1())->get(route('seller.reports', $range))->assertOk()->viewData('totalSales');

        $this->assertEquals(450, $sales(['from' => now()->toDateString(), 'to' => now()->toDateString()]));
        $this->assertEquals(900, $sales(['from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()]));
        // A range that starts and ends on an order's date includes it.
        $this->assertEquals(450, $sales(['from' => now()->subDays(10)->toDateString(), 'to' => now()->subDays(10)->toDateString()]));
        $this->assertEquals(1350, $sales(['from' => now()->subDays(10)->toDateString(), 'to' => now()->toDateString()]));

        $this->as($this->seller1())->get(route('seller.reports', ['from' => now()->toDateString(), 'to' => now()->subDay()->toDateString()]))
            ->assertRedirect(route('seller.reports'))->assertSessionHas('error', 'The end date must be on or after the start date.');
        $this->assertNotEmpty([$today, $fiveDays, $tenDays]);
    }

    public function test_SEL_EARN_005_payout_request_amounts(): void
    {
        $order = $this->workedExampleWithPlatform();
        $this->setOrder($order->id, ['status' => 'Completed', 'buyer_received_at' => now()]);
        $this->assertSame(584.10, $this->earnings()['balances']['available']);
        $ask = fn (string $amount) => $this->as($this->seller1())->from(route('seller.earnings'))->post(route('seller.payouts.store'), ['amount' => $amount, 'method' => 'GCash']);

        foreach (['99.99', '584.11', '0', 'abc'] as $bad) {
            $ask($bad)->assertSessionHasErrors(['amount']);
        }
        $this->assertSame(0, DB::table('seller_payout_requests')->count());

        $ask('100.00')->assertSessionHasNoErrors();
        $this->assertSame(484.10, $this->earnings()['balances']['available']);
        $this->as($this->seller1())->get(route('seller.earnings'))->assertSee('data-payout=', false)->assertSee('Pending');
        $this->as($this->seller1())->post(route('seller.payouts.cancel', DB::table('seller_payout_requests')->value('id')))->assertSessionHas('success');

        $ask('584.10')->assertSessionHasNoErrors();
        $this->assertSame(0.0, $this->earnings()['balances']['available']);
    }

    public function test_SEL_EARN_006_second_payout_while_one_is_pending(): void
    {
        $order = $this->workedExampleWithPlatform();
        $this->setOrder($order->id, ['status' => 'Completed', 'buyer_received_at' => now()]);

        $this->as($this->seller1())->post(route('seller.payouts.store'), ['amount' => '100.00'])->assertSessionHasNoErrors();
        $this->as($this->seller1())->from(route('seller.earnings'))->post(route('seller.payouts.store'), ['amount' => '100.00'])
            ->assertSessionHasErrors(['amount' => 'You already have a payout request pending. Wait for it to be paid (or cancel it) before asking again.']);

        $this->assertSame(1, DB::table('seller_payout_requests')->count());
    }

    public function test_SEL_EARN_007_double_click_request_payout(): void
    {
        $order = $this->workedExampleWithPlatform();
        $this->setOrder($order->id, ['status' => 'Completed', 'buyer_received_at' => now()]);
        $form = ['amount' => '100.00', \App\Support\SubmitOnce::FIELD => (string) Str::uuid()];

        $this->as($this->seller1())->post(route('seller.payouts.store'), $form);
        $this->as($this->seller1())->post(route('seller.payouts.store'), $form)->assertSessionHas('success', 'Your payout request was already sent.');

        $this->assertSame(1, DB::table('seller_payout_requests')->count());
        $this->assertSame(484.10, $this->earnings()['balances']['available']);
    }

    /** Buyer 1's pending return for P-02 (₱450.00) with 2 photos. */
    private function returnRequest(string $type = 'Return'): object
    {
        Storage::disk('local')->put('evidence/photo1.jpg', 'one');
        Storage::disk('local')->put('evidence/photo2.jpg', 'two');
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Completed', ['buyer_received_at' => now(), 'delivered_at' => now()]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        $id = DB::table('return_refund_requests')->insertGetId([
            'order_id' => $orderId, 'order_item_id' => $itemId, 'buyer_id' => $this->buyer1()->id, 'seller_id' => $this->seller1()->id,
            'request_type' => $type, 'reason' => 'Damaged item', 'message' => 'The basket arrived with a broken handle.',
            'evidence' => 'evidence/photo1.jpg', 'evidence_photos' => json_encode(['evidence/photo2.jpg']),
            'refund_amount' => 450, 'refund_method' => 'GCash', 'refund_account_name' => 'Juan Dela Cruz', 'refund_account_number' => '09171234567',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('return_refund_requests')->find($id);
    }

    private function returnStatus(object $request): string
    {
        return (string) DB::table('return_refund_requests')->where('id', $request->id)->value('status');
    }

    public function test_SEL_RET_001_view_a_return_request_with_evidence(): void
    {
        $request = $this->returnRequest();

        $page = $this->as($this->seller1())->get(route('seller.orders', ['tab' => 'returns']))->assertOk()
            ->assertSee('Damaged item')->assertSee('The basket arrived with a broken handle.')
            ->assertSee('Rattan Fruit Basket')->assertSee('Amount ₱450.00')
            ->assertSee('Respond by ' . now()->addDays(3)->format('M d, Y • h:i A'));

        foreach ([0, 1] as $photo) {
            $page->assertSee(route('return-refund.evidence', [$request->id, 'photo' => $photo]), false);
        }

        // Full size on click: each photo opens.
        $this->as($this->seller1())->get(route('return-refund.evidence', [$request->id, 'photo' => 1]))->assertOk();
    }

    public function test_SEL_RET_002_approve_a_return_request(): void
    {
        $request = $this->returnRequest();

        $this->as($this->seller1())->post(route('seller.return-refund.approve', $request->id))->assertSessionHas('success');

        $this->assertSame('approved', $this->returnStatus($request));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Return Approved'));
        $this->assertStringContainsString('bring it to', DB::table('notifications')->where('user_id', $this->buyer1()->id)->where('title', 'Return Approved')->value('message'));
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertSee('Approved — bring the item to the Sorting Center');
        $this->as($this->seller1())->get(route('seller.orders', ['tab' => 'returns']))->assertSee('Approved — bring the item to the Sorting Center');
    }

    public function test_SEL_RET_003_reject_without_a_reason(): void
    {
        $request = $this->returnRequest();

        foreach (['', 'Item used'] as $note) {
            $this->as($this->seller1())->post(route('seller.return-refund.reject', $request->id), ['seller_note' => $note])->assertSessionHas('error');
        }

        $this->assertSame('pending', $this->returnStatus($request));
    }

    public function test_SEL_RET_004_reject_with_a_reason(): void
    {
        $request = $this->returnRequest();
        $reason = 'Item shows signs of use and the tag is removed.';

        $this->as($this->seller1())->post(route('seller.return-refund.reject', $request->id), ['seller_note' => $reason])->assertSessionHas('success');

        $this->assertSame('rejected', $this->returnStatus($request));
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertSee('Rejected')->assertSee($reason)->assertSee('Escalate to a dispute');
        $this->as($this->seller1())->get(route('seller.orders', ['tab' => 'returns']))->assertSee('Rejected');
    }

    public function test_SEL_RET_005_escalate_to_a_dispute(): void
    {
        $request = $this->returnRequest();
        $this->as($this->seller1())->post(route('seller.return-refund.reject', $request->id), ['seller_note' => 'Item shows signs of use and the tag is removed.']);
        $this->travel(2)->days();

        $this->as($this->buyer1())->post(route('buyer.return-refund.escalate', $request->id))->assertSessionHas('success');

        $this->assertSame('disputed', $this->returnStatus($request));
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertSee('In Dispute');
        $this->as($this->seller1())->get(route('seller.orders', ['tab' => 'returns']))->assertSee('In Dispute')
            ->assertDontSee(route('seller.return-refund.approve', $request->id), false);
        $this->as($this->seller1())->post(route('seller.return-refund.approve', $request->id))->assertSessionHas('error');
        $this->as($this->seller1())->post(route('seller.return-refund.reject', $request->id), ['seller_note' => 'Still rejecting this one.'])->assertSessionHas('error');

        // Only once.
        $this->as($this->buyer1())->post(route('buyer.return-refund.escalate', $request->id))->assertSessionHas('error');
        $this->as($this->buyer1())->get(route('buyer.orders'))->assertDontSee('Escalate to a dispute');
    }

    public function test_SEL_RET_006_no_response_within_3_days(): void
    {
        // The checks have run before (the first run only notes when they started).
        \App\Support\StaleOrders::sweep(true);
        $request = $this->returnRequest();

        $this->travelTo(\Illuminate\Support\Carbon::parse($request->created_at)->addDays(3)->subMinute());
        \App\Support\StaleOrders::sweep(true);
        $this->assertSame('pending', $this->returnStatus($request));

        $this->travelTo(\Illuminate\Support\Carbon::parse($request->created_at)->addDays(3)->addMinute());
        \App\Support\StaleOrders::sweep(true);

        $this->assertSame('approved', $this->returnStatus($request));
        $this->assertTrue((bool) DB::table('return_refund_requests')->where('id', $request->id)->value('auto_approved'));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Return Approved'));
    }

    public function test_SEL_RET_007_refunded_amount_removed_from_earnings(): void
    {
        $request = $this->returnRequest();
        $before = $this->as($this->seller1())->get(route('seller.reports'))->viewData('totalSales');
        $this->assertEquals(450, $before);

        DB::table('return_refund_requests')->where('id', $request->id)->update(['status' => 'completed', 'refunded_at' => now()]);

        $this->assertEquals(0, $this->as($this->seller1())->get(route('seller.reports'))->viewData('totalSales'));
    }

    /* ---------------------------------------------------------------
     | S11 Reviews & Chat
     * --------------------------------------------------------------- */

    /** P-02 reviews: 5, 3 and 1 star. */
    private function p02Reviews(): array
    {
        $ids = [];

        foreach ([5 => 'Sturdy and beautiful basket.', 3 => 'Okay, a bit smaller than expected.', 1 => 'The handle came loose.'] as $stars => $text) {
            $buyer = $stars === 5 ? $this->buyer1() : $this->makeUser('buyer');
            $orderId = $this->makeOrder($buyer, $this->p02(), 'Completed');
            Storage::disk('public')->put("reviews/r{$stars}.jpg", 'x');
            $ids[$stars] = DB::table('product_reviews')->insertGetId([
                'product_id' => $this->p02()->id, 'buyer_id' => $buyer->id, 'order_id' => $orderId, 'rating' => $stars, 'review' => $text,
                'photos' => json_encode(["reviews/r{$stars}.jpg"]), 'variation_label' => null,
                'created_at' => now()->subDays($stars), 'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    private function reviewCards(?int $rating = null): array
    {
        $html = $this->as($this->seller1())->get(route('seller.reviews', array_filter(['rating' => $rating])))->assertOk()->getContent();
        preg_match_all('/data-review="\d+" data-rating="(\d)"/', $html, $m);

        return array_map('intval', $m[1]);
    }

    private function chat(\App\Models\User $from, \App\Models\User $to, array $form)
    {
        return $this->as($from)->post(route('messages.store', $to->id), $form, $this->ajaxHeaders());
    }

    public function test_SEL_REV_001_view_and_filter_reviews(): void
    {
        $this->p02Reviews();

        // Newest first.
        $this->assertSame([1, 3, 5], $this->reviewCards());
        $this->as($this->seller1())->get(route('seller.reviews'))->assertSee('Sturdy and beautiful basket.')->assertSee('storage/reviews/r5.jpg', false)->assertSee(now()->subDays(5)->format('M d, Y'));
        $this->assertSame([1], $this->reviewCards(1));
        $this->assertSame([5], $this->reviewCards(5));
    }

    public function test_SEL_REV_002_reply_to_a_review(): void
    {
        $ids = $this->p02Reviews();

        $this->as($this->seller1())->post(route('seller.reviews.reply', $ids[5]), ['seller_reply' => 'Salamat po! Enjoy your basket.'])->assertSessionHas('success');

        $this->as($this->buyer1())->get(route('product.details', $this->p02()->id))->assertOk()
            ->assertSeeInOrder(['Sturdy and beautiful basket.', "Seller's Response", 'Salamat po! Enjoy your basket.']);
    }

    public function test_SEL_REV_003_second_reply_and_edit_reply(): void
    {
        $ids = $this->p02Reviews();
        $this->as($this->seller1())->post(route('seller.reviews.reply', $ids[5]), ['seller_reply' => 'Salamat po! Enjoy your basket.']);

        $this->as($this->seller1())->post(route('seller.reviews.reply', $ids[5]), ['seller_reply' => 'Another reply'])->assertSessionHas('error', 'You already replied to this review. Edit your reply instead.');
        $this->as($this->seller1())->put(route('seller.reviews.reply.update', $ids[5]), ['seller_reply' => 'Salamat po sa tiwala!'])->assertSessionHas('success');

        $this->assertSame('Salamat po sa tiwala!', DB::table('product_reviews')->where('id', $ids[5])->value('seller_reply'));
        $this->as($this->buyer1())->get(route('product.details', $this->p02()->id))->assertSee('Salamat po sa tiwala!')->assertDontSee('Enjoy your basket.');
    }

    public function test_SEL_REV_004_seller_cannot_delete_or_hide_a_review(): void
    {
        $ids = $this->p02Reviews();

        $this->as($this->seller1())->get(route('seller.reviews'))->assertDontSee('>Delete<', false)->assertDontSee('>Hide<', false);
        $this->as($this->seller1())->delete(route('seller.reviews.delete', $ids[1]))->assertForbidden();
        $this->as($this->seller1())->post(route('seller.reviews.hide', $ids[1]))->assertForbidden();

        $this->as($this->buyer1())->get(route('product.details', $this->p02()->id))->assertSee('The handle came loose.');
    }

    public function test_SEL_REV_005_chat_with_a_buyer_with_attachment(): void
    {
        $this->chat($this->buyer1(), $this->seller1(), ['message' => 'Available pa po ba ang Rattan Fruit Basket?', 'product_id' => $this->p02()->id])->assertOk();

        $this->chat($this->seller1(), $this->buyer1(), ['message' => 'Opo, available pa.'])->assertOk();
        $this->travel(1)->minutes();
        $photo = $this->chat($this->seller1(), $this->buyer1(), ['attachment' => $this->jpegOfSize('review_photo.jpg', (int) (1.5 * 1048576))])->assertOk()->json('message');

        // (The shop's automatic reply aside.)
        $messages = \App\Models\Message::where('kind', '!=', \App\Models\Message::AUTO_REPLY)->orderBy('id')->get()->values();
        $this->assertSame(['Available pa po ba ang Rattan Fruit Basket?', 'Opo, available pa.', ''], $messages->pluck('message')->all());
        $this->assertNotNull($messages->last()->attachment);
        $this->assertTrue($messages[1]->created_at->lt($messages[2]->created_at));

        // Buyer 1 sees them in order, with times, and can open the photo.
        $this->as($this->buyer1())->get(route('messages.thread', $this->seller1()->id))->assertOk()
            ->assertSeeInOrder(['Opo, available pa.', route('messages.attachment', $messages->last()->id)], false)
            ->assertSee($messages[1]->created_at->format('g:i A'));
        $this->as($this->buyer1())->get($photo['attachment'])->assertOk();
        $this->as($this->makeUser('buyer'))->get($photo['attachment'])->assertForbidden();
    }

    public function test_SEL_REV_006_invalid_chat_attachments(): void
    {
        foreach ([$this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1)), $this->exe('installer.exe'), $this->exe('renamed_exe.jpg')] as $file) {
            $this->chat($this->seller1(), $this->buyer1(), ['attachment' => $file])->assertStatus(422)->assertJsonValidationErrors(['attachment' => 'Attach a JPG or PNG photo of at most 5 MB.']);
        }

        $this->assertSame(0, \App\Models\Message::count());
    }

    public function test_SEL_REV_007_message_length_and_empty_message(): void
    {
        // Buyer 1 wrote first (a seller answers buyers).
        $this->chat($this->buyer1(), $this->seller1(), ['message' => 'Hello po!'])->assertOk();
        $sent = fn () => \App\Models\Message::where('sender_id', $this->seller1()->id)->where('kind', '!=', \App\Models\Message::AUTO_REPLY);

        $this->chat($this->seller1(), $this->buyer1(), ['message' => ''])->assertStatus(422);
        $this->chat($this->seller1(), $this->buyer1(), ['message' => '      '])->assertStatus(422);
        $this->assertSame(0, $sent()->count());

        $thousand = str_repeat('Salamat! ', 111) . 'x';
        $this->assertSame(1000, mb_strlen($thousand));
        $this->chat($this->seller1(), $this->buyer1(), ['message' => $thousand])->assertOk();
        $this->assertSame($thousand, $sent()->first()->message);

        $this->chat($this->seller1(), $this->buyer1(), ['message' => $thousand . 'y'])->assertStatus(422)->assertJsonValidationErrors(['message' => 'A message can have at most 1,000 characters.']);
        $this->assertSame(1, $sent()->count());
    }
}
