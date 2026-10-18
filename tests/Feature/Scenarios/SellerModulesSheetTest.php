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
}
