<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The classmate's Buyer Modules workbook (B1–B14): one test per row, named
 * after its id (BUY-ACCT-001 → test_BUY_ACCT_001_…), with the Test Data
 * sheet's accounts, products, vouchers and rates (see ModuleSheetWorld).
 */
class BuyerModulesSheetTest extends TestCase
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
     | Shared helpers
     * --------------------------------------------------------------- */

    /** A new session with no saved cart either (the cart is kept per account). */
    private function freshCart(): void
    {
        $this->flushSession();
        DB::table('saved_carts')->delete();
    }

    private function key(Product $product, int $variationId = 0): string
    {
        return $product->id . ':' . $variationId;
    }

    /** Log in through the real login form; the session user's id, or null. */
    private function login(string $email, string $password): ?int
    {
        $this->flushSession();
        $this->post(route('login.submit'), ['email' => $email, 'password' => $password]);
        $id = session('user.id');
        $this->flushSession();

        return $id;
    }

    /** A voucher from the Test Data sheet. */
    private function sheetVoucher(string $code, array $overrides = []): Voucher
    {
        $base = ['is_active' => true, 'display' => 'claimable', 'discount_type' => 'fixed', 'per_buyer_limit' => 1, 'starts_at' => now()->subDay(), 'ends_at' => now()->endOfMonth()];

        $rules = match ($code) {
            'MCH50' => ['seller_id' => $this->seller1()->id, 'discount_value' => 50, 'min_order_amount' => 300, 'max_uses' => 100],
            'MCH10' => ['seller_id' => $this->seller1()->id, 'discount_type' => 'percentage', 'discount_value' => 10, 'max_discount' => 100, 'min_order_amount' => 500, 'max_uses' => 50],
            'RVSECRET' => ['seller_id' => $this->seller2()->id, 'discount_value' => 100, 'min_order_amount' => 1000, 'max_uses' => 20, 'display' => 'code'],
            'MCHOLD' => ['seller_id' => $this->seller1()->id, 'discount_value' => 30, 'min_order_amount' => 200, 'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()],
            'MCHSOON' => ['seller_id' => $this->seller1()->id, 'discount_value' => 30, 'min_order_amount' => 200, 'starts_at' => now()->addDay(), 'ends_at' => now()->addMonth()],
            'MCHMAX' => ['seller_id' => $this->seller1()->id, 'discount_value' => 20, 'min_order_amount' => 100, 'max_uses' => 1, 'used_count' => 1],
            'PLAT100' => ['seller_id' => null, 'kind' => 'platform', 'discount_value' => 100, 'min_order_amount' => 1000],
            'FREESHIP' => ['seller_id' => null, 'kind' => 'shipping', 'discount_value' => 50, 'min_order_amount' => 300],
            'MCHT100' => ['seller_id' => $this->seller1()->id, 'discount_value' => 100, 'min_order_amount' => 100],
        };

        return Voucher::create(array_merge($base, $rules, $overrides, ['code' => $code]));
    }

    /** The sheet's zones and rates (Within NCR ₱50+₱15, NCR ↔ Visayas ₱120+₱40, Within Visayas ₱60+₱20). */
    private function sheetZones(): array
    {
        $this->sheetRates();
        $ncr = DB::table('shipping_zones')->insertGetId(['name' => 'NCR', 'provinces' => json_encode(['Metro Manila (NCR)']), 'created_at' => now(), 'updated_at' => now()]);
        $vis = DB::table('shipping_zones')->insertGetId(['name' => 'Visayas', 'provinces' => json_encode(\App\Support\PhLocations::REGIONS['central-visayas']['provinces'] ?? ['Cebu', 'Bohol', 'Negros Oriental', 'Siquijor']), 'created_at' => now(), 'updated_at' => now()]);

        foreach ([[$ncr, $ncr, 50, 15], [$vis, $ncr, 120, 40], [$ncr, $vis, 120, 40], [$vis, $vis, 60, 20]] as [$from, $to, $first, $extra]) {
            DB::table('shipping_rates')->insert(['from_zone_id' => $from, 'to_zone_id' => $to, 'first_kg' => $first, 'per_kg' => $extra, 'created_at' => now(), 'updated_at' => now()]);
        }

        return ['ncr' => $ncr, 'visayas' => $vis];
    }

    /** The checkout page's plan for a cart, to an address. */
    private function checkoutPlan(User $buyer, array $cart, array $vouchers = [], ?string $address = null): array
    {
        $this->flushSession();
        $plan = \App\Support\CheckoutPlan::build(
            \App\Support\CartPricing::planLines(\App\Support\CartPricing::price($this->asLines($cart))),
            Voucher::whereIn('code', $vouchers)->get(),
            \App\Support\PhLocations::locate($address ?? ($buyer->is($this->buyer2()) ? self::ADDR_3 : self::ADDR_1))
        );

        return $plan;
    }

    private function asLines(array $cart): array
    {
        session()->put('cart', $cart);

        return \App\Support\CartPricing::fromCart($cart);
    }

    /** Place an order (COD unless told) for a cart, with vouchers in the session. */
    private function order(User $buyer, array $cart, array $vouchers = [], string $payment = 'Cash on Delivery', array $form = [])
    {
        $this->flushSession();

        return $this->actingAsUser($buyer, ['cart' => $cart, \App\Support\VoucherSelection::KEY => $vouchers])
            ->post(route('checkout.place'), array_merge([
                'address' => $buyer->is($this->buyer2()) ? self::ADDR_3 : self::ADDR_1,
                'phone' => '09171234567',
                'payment' => $payment,
            ], $form));
    }

    private function orders(User $buyer)
    {
        return DB::table('orders')->where('buyer_id', $buyer->id)->orderBy('id')->get();
    }

    /** The buyer's My Orders page data. */
    private function myOrders(User $buyer, array $query = []): array
    {
        $this->flushSession();

        return collect($this->actingAsUser($buyer)->get(route('buyer.orders', $query))->assertOk()->viewData('orders'))->all();
    }

    /** Address-book form for ADDR-2 (Makati office). */
    private function addr2Form(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Office', 'recipient_name' => 'Juan Miguel Dela Cruz', 'phone' => '09171234567',
            'region' => 'National Capital Region (NCR)', 'province' => 'Metro Manila (NCR)', 'city_municipality' => 'Makati',
            'barangay' => 'San Lorenzo', 'street' => '12F Pacific Tower, 6750 Ayala Ave.',
        ], $overrides);
    }

    private function addAddress(array $form, ?User $buyer = null)
    {
        $this->flushSession();

        return $this->actingAsUser($buyer ?? $this->buyer1())->from(route('buyer.profile'))->post(route('buyer.addresses.store'), $form);
    }

    private function names($response, string $key = 'products'): array
    {
        return collect($response->viewData($key))->pluck('name')->all();
    }

    /* ---------------------------------------------------------------
     | B1 Account & Security
     * --------------------------------------------------------------- */

    public function test_BUY_ACCT_001_edit_profile_with_valid_data(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->from(route('buyer.profile'))
            ->post(route('buyer.profile.update'), ['first_name' => 'Juan Miguel Jr.', 'last_name' => 'Dela Cruz'])
            ->assertSessionHas('success', 'Profile updated successfully.');
        $this->post(route('buyer.profile.photo'), ['profile_photo' => $this->jpegOfSize('review_photo.jpg', 40000)])->assertSessionHas('success');

        $fresh = $buyer->fresh();
        $this->assertSame('Juan Miguel Jr. Dela Cruz', $fresh->name);
        $this->assertNotNull($fresh->profile_photo);
        $this->assertSame('juanmiguel.delacruz@example.com', $fresh->email);
        $this->assertSame('09171234567', $fresh->phone);

        // After logging in again the profile and the header show the new name.
        $this->assertSame($buyer->id, $this->login('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
        $this->actingAsUser($fresh)->get(route('buyer.profile'))->assertOk()->assertSee('Juan Miguel Jr.');
    }

    public function test_BUY_ACCT_002_profile_field_validation(): void
    {
        $buyer = $this->buyer1();
        $before = $buyer->fresh()->only(['name', 'profile_photo']);

        foreach (['', 'Juan123', 'J'] as $first) {
            $this->flushSession();
            $this->actingAsUser($buyer)->from(route('buyer.profile'))
                ->post(route('buyer.profile.update'), ['first_name' => $first, 'last_name' => 'Dela Cruz'])
                ->assertSessionHasErrors('first_name');
        }

        foreach ([$this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1)), $this->exe('renamed_exe.jpg')] as $photo) {
            $this->flushSession();
            $this->actingAsUser($buyer)->from(route('buyer.profile'))->post(route('buyer.profile.photo'), ['profile_photo' => $photo])
                ->assertSessionHasErrors(['profile_photo' => 'The profile photo must be a JPG or PNG image of at most 5 MB.']);
        }

        $this->assertSame($before, $buyer->fresh()->only(['name', 'profile_photo']));
    }

    public function test_BUY_ACCT_003_change_password_with_the_correct_old_password(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->post(route('buyer.profile.password'), [
            'current_password' => 'Mamimili@2026', 'new_password' => 'Mamimili@2027', 'new_password_confirmation' => 'Mamimili@2027',
        ])->assertSessionHas('success', 'Password changed successfully.');

        $this->assertNull($this->login('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
        $this->assertSame($buyer->id, $this->login('juanmiguel.delacruz@example.com', 'Mamimili@2027'));
    }

    public function test_BUY_ACCT_004_change_password_with_a_wrong_old_password(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->post(route('buyer.profile.password'), [
            'current_password' => 'Wrong@2026', 'new_password' => 'Mamimili@2027', 'new_password_confirmation' => 'Mamimili@2027',
        ])->assertSessionHas('error', 'Current password is incorrect.');

        $this->assertSame($buyer->id, $this->login('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
    }

    public function test_BUY_ACCT_005_new_password_breaks_the_rule(): void
    {
        $buyer = $this->buyer1();
        $cases = [
            ['Mami@26', 'Mami@26', 'The new password must be at least 8 characters.'],
            ['mamimili@2027', 'mamimili@2027', 'The new password needs at least one uppercase letter.'],
            ['MAMIMILI@2027', 'MAMIMILI@2027', 'The new password needs at least one lowercase letter.'],
            ['Mamimili@abcd', 'Mamimili@abcd', 'The new password needs at least one number.'],
            ['Mamimili2027', 'Mamimili2027', 'The new password needs at least one special character (e.g. @ # !).'],
            ['Mamimili@2027', 'Mamimili@2028', 'The new passwords do not match.'],
            ['Mamimili@2026', 'Mamimili@2026', 'The new password must be different from your current password.'],
        ];

        foreach ($cases as [$new, $confirm, $message]) {
            $this->flushSession();
            $this->actingAsUser($buyer)->post(route('buyer.profile.password'), [
                'current_password' => 'Mamimili@2026', 'new_password' => $new, 'new_password_confirmation' => $confirm,
            ])->assertSessionHas('error', $message);
        }

        $this->assertTrue(Hash::check('Mamimili@2026', $buyer->fresh()->password));
    }

    public function test_BUY_ACCT_006_change_email_with_verification(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->from(route('buyer.profile'))
            ->post(route('buyer.email.request'), ['email' => 'buyer.test01@example.com'])->assertSessionHas('success');
        $code = \App\Http\Controllers\ContactChangeController::pending($buyer->id, 'email')['code'];

        // Before the code: the old email still logs in and stays on the profile.
        $this->assertSame($buyer->id, $this->login('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
        $this->assertSame('juanmiguel.delacruz@example.com', $buyer->fresh()->email);

        $this->actingAsUser($buyer)->from(route('buyer.profile'))->post(route('buyer.contact.verify', 'email'), ['otp' => $code])
            ->assertSessionHas('success', 'Email changed. Log in with buyer.test01@example.com from now on.');

        $this->assertNull($this->login('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
        $this->assertSame($buyer->id, $this->login('buyer.test01@example.com', 'Mamimili@2026'));
    }

    public function test_BUY_ACCT_007_change_email_wrong_or_expired_otp(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->post(route('buyer.email.request'), ['email' => 'buyer.test01@example.com']);
        $code = \App\Http\Controllers\ContactChangeController::pending($buyer->id, 'email')['code'];

        $this->post(route('buyer.contact.verify', 'email'), ['otp' => $code === '000000' ? '111111' : '000000'])
            ->assertSessionHasErrors(['otp' => 'That code is not correct.']);

        $this->travel(5)->minutes();
        $this->travel(1)->seconds();
        $this->post(route('buyer.contact.verify', 'email'), ['otp' => $code])
            ->assertSessionHasErrors(['otp' => 'That code has expired. Request a new one.']);

        $this->assertSame('juanmiguel.delacruz@example.com', $buyer->fresh()->email);
    }

    public function test_BUY_ACCT_008_change_email_to_one_already_used(): void
    {
        $buyer = $this->buyer1();
        $this->buyer2();
        $this->seller1();

        foreach (['anamarie.reyes@example.com', 'MARIACLARA.SANTOS@example.com'] as $email) {
            $this->flushSession();
            $this->actingAsUser($buyer)->from(route('buyer.profile'))->post(route('buyer.email.request'), ['email' => $email])
                ->assertSessionHasErrors(['email' => 'That email is already in use.']);
        }

        Mail::assertNothingSent();
        $this->assertNull(\App\Http\Controllers\ContactChangeController::pending($buyer->id, 'email'));
    }

    public function test_BUY_ACCT_009_change_phone_with_verification_and_format(): void
    {
        $buyer = $this->buyer1();
        $this->buyer2();

        $this->actingAsUser($buyer)->post(route('buyer.phone.request'), ['phone' => '0917123'])
            ->assertSessionHasErrors(['phone' => 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).']);
        $this->post(route('buyer.phone.request'), ['phone' => '09173456789'])
            ->assertSessionHasErrors(['phone' => 'That mobile number is already in use.']);
        Mail::assertNothingSent();

        $this->post(route('buyer.phone.request'), ['phone' => '09170000001'])->assertSessionHas('success');
        $this->assertSame('09171234567', $buyer->fresh()->phone);

        $code = \App\Http\Controllers\ContactChangeController::pending($buyer->id, 'phone')['code'];
        $this->post(route('buyer.contact.verify', 'phone'), ['otp' => $code])->assertSessionHas('success');
        $this->assertSame('09170000001', $buyer->fresh()->phone);
    }

    /* ---------------------------------------------------------------
     | B2 Address Book
     * --------------------------------------------------------------- */

    public function test_BUY_ADDR_001_add_an_address_with_valid_data(): void
    {
        $this->addAddress($this->addr2Form())->assertSessionHas('success', 'Address saved.');

        $addresses = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->orderBy('id')->get();
        $this->assertCount(2, $addresses);
        $office = $addresses->last();
        $this->assertSame(['Office', 'Juan Miguel Dela Cruz', '09171234567', 'Metro Manila (NCR)', 'Makati', 'San Lorenzo', '12F Pacific Tower, 6750 Ayala Ave.'],
            [$office->label, $office->recipient_name, $office->phone, $office->province, $office->city_municipality, $office->barangay, $office->street]);
        $this->assertFalse((bool) $office->is_default);
        $this->assertTrue((bool) $addresses->first()->is_default);
    }

    public function test_BUY_ADDR_002_region_city_barangay_cascade(): void
    {
        $regions = \App\Support\PhLocations::provincesByRegionName();
        $this->assertSame(['Metro Manila (NCR)'], $regions['National Capital Region (NCR)']);
        $this->assertNotContains('Cebu', $regions['National Capital Region (NCR)']);
        $this->assertContains('Cebu', $regions['Region VII (Central Visayas)']);
        $this->assertContains('San Lorenzo', \App\Support\AddressRules::barangays('Metro Manila (NCR)', 'Makati'));

        // The server refuses a city or barangay from another place too.
        $this->addAddress($this->addr2Form(['city_municipality' => 'Cebu City']))->assertSessionHasErrors('city_municipality');
        $this->addAddress($this->addr2Form(['barangay' => 'Capitol Site (Pob.)']))->assertSessionHasErrors('barangay');
        $this->addAddress($this->addr2Form(['region' => 'Region VII (Central Visayas)']))->assertSessionHasErrors('province');

        // The form clears City and Barangay when the Region changes.
        $this->actingAsUser($this->buyer1())->get(route('buyer.profile'))->assertOk()->assertSee('BB_REGIONS', false);
    }

    public function test_BUY_ADDR_003_each_required_field_left_blank(): void
    {
        foreach (['recipient_name', 'phone', 'region', 'city_municipality', 'barangay', 'street'] as $field) {
            $this->addAddress($this->addr2Form([$field => '']))->assertSessionHasErrors($field);
        }

        $this->assertSame(1, \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->count());
    }

    public function test_BUY_ADDR_004_phone_number_format(): void
    {
        $this->addAddress($this->addr2Form(['phone' => '0917123']))->assertSessionHasErrors('phone');
        $this->addAddress($this->addr2Form(['phone' => '091712345678']))->assertSessionHasErrors('phone');
        $this->addAddress($this->addr2Form(['phone' => '09171234567']))->assertSessionHasNoErrors();
        $this->addAddress($this->addr2Form(['phone' => '+639171234567', 'label' => 'Office 2']))->assertSessionHasNoErrors();

        $this->assertSame(3, \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->count());
    }

    public function test_BUY_ADDR_005_address_book_limit(): void
    {
        for ($i = 2; $i <= 9; $i++) {
            $this->addAddress($this->addr2Form(['label' => 'Spot ' . $i]))->assertSessionHas('success');
        }

        $this->addAddress($this->addr2Form(['label' => 'Tenth']))->assertSessionHas('success', 'Address saved.');
        $this->addAddress($this->addr2Form(['label' => 'Eleventh']))->assertSessionHas('error', 'You can save up to 10 addresses. Delete one first.');
        $this->assertSame(10, \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->count());
    }

    public function test_BUY_ADDR_006_set_a_different_default_address(): void
    {
        $this->addAddress($this->addr2Form());
        $office = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('label', 'Office')->first();

        $this->actingAsUser($this->buyer1())->post(route('buyer.addresses.default', $office->id))->assertSessionHas('success');
        $defaults = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('is_default', true)->pluck('id')->all();
        $this->assertSame([$office->id], $defaults);

        $this->flushSession();
        $this->actingAsUser($this->buyer1(), ['cart' => [$this->key($this->p02()) => 1]])->get(route('checkout'))
            ->assertOk()->assertViewHas('savedAddress', $office->fresh()->address);
    }

    public function test_BUY_ADDR_007_delete_the_default_address(): void
    {
        $this->addAddress($this->addr2Form());
        $home = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('is_default', true)->first();
        $office = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('label', 'Office')->first();

        $this->actingAsUser($this->buyer1())->delete(route('buyer.addresses.delete', $home->id))
            ->assertSessionHas('error', 'This is your default address. Set another address as default first, then delete this one.');
        $this->assertNotNull($home->fresh());

        $this->post(route('buyer.addresses.default', $office->id));
        $this->delete(route('buyer.addresses.delete', $home->id))->assertSessionHas('success', 'Address deleted.');
        $this->assertSame(1, \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('is_default', true)->count());
    }

    public function test_BUY_ADDR_008_edit_or_delete_an_address_used_by_an_active_order(): void
    {
        $this->addAddress($this->addr2Form());
        $office = \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->where('label', 'Office')->first();
        $original = $office->address;

        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'Cash on Delivery', ['address' => $original]);
        $order = $this->orders($this->buyer1())->last();
        $this->setOrder($order->id, ['status' => 'Confirmed']);

        $this->actingAsUser($this->buyer1())->put(route('buyer.addresses.update', $office->id), ['street' => '14F Pacific Tower, 6750 Ayala Ave.'])->assertSessionHas('success');
        $this->delete(route('buyer.addresses.delete', $office->id))->assertSessionHas('success');

        $this->assertSame($original, DB::table('orders')->where('id', $order->id)->value('shipping_address'));
        $this->flushSession();
        $this->actingAsUser($this->seller1())->get(route('seller.order.details', $order->id))->assertOk()->assertSee('12F Pacific Tower')->assertDontSee('14F Pacific Tower');
        $this->get(route('seller.order.waybill', $order->id))->assertOk()->assertSee('12F Pacific Tower');
    }

    public function test_BUY_ADDR_009_cannot_open_another_buyers_address(): void
    {
        $addr3 = \App\Models\BuyerAddress::where('user_id', $this->buyer2()->id)->first();

        $this->actingAsUser($this->buyer1())->get(route('buyer.addresses.edit', $addr3->id))->assertNotFound()->assertDontSee('Osmeña');
        $this->put(route('buyer.addresses.update', $addr3->id), ['street' => 'Hacked'])->assertNotFound();
        $this->assertStringContainsString('Osmeña', $addr3->fresh()->address);
    }

    /* ---------------------------------------------------------------
     | B3 Browse, Search & Filter
     * --------------------------------------------------------------- */

    private function shop(array $query = [], ?User $as = null)
    {
        $this->flushSession();

        return $this->actingAsUser($as ?? $this->buyer1())->get(route('products', $query))->assertOk();
    }

    public function test_BUY_SRCH_001_browse_a_platform_category(): void
    {
        $this->p02();
        $this->p03();
        $this->p05();

        $names = $this->names($this->shop(['category' => \App\Support\Categories::slug('Home & Living')]));
        $this->assertContains('Rattan Fruit Basket', $names);
        $this->assertContains('Capiz Shell Lamp', $names);
        $this->assertNotContains('USB-C Fast Charger 20W', $names);
    }

    public function test_BUY_SRCH_002_store_page_with_store_categories(): void
    {
        $seller = $this->seller1();
        $make = fn (string $name, ?int $parent = null, int $order = 0) => \App\Models\StoreCategory::create(['seller_id' => $seller->id, 'parent_id' => $parent, 'name' => $name, 'sort_order' => $order]);
        $bags = $make('Bags', null, 1);
        $decor = $make('Home Decor', null, 2);
        $lamps = $make('Lamps', $decor->id, 1);
        $baskets = $make('Baskets', $decor->id, 2);
        $kitchen = $make('Kitchen & Dining', null, 3);
        $this->p01()->storeCategories()->attach($bags->id);
        $this->p03()->storeCategories()->attach($lamps->id);
        $this->p02()->storeCategories()->attach($baskets->id);
        $this->p04()->storeCategories()->attach($kitchen->id);

        $this->flushSession();
        $page = $this->actingAsUser($this->buyer1())->get(route('shop.seller', $seller->id))->assertOk();
        $this->assertSame(['Bags', 'Home Decor', 'Lamps', 'Baskets', 'Kitchen & Dining'], collect($page->viewData('storeCategories'))->pluck('name')->all());

        $this->assertSame(['Capiz Shell Lamp'], $this->names($this->get(route('shop.seller', [$seller->id, 'store_category' => $lamps->id]))));
    }

    public function test_BUY_SRCH_003_search_exact_and_partial_match(): void
    {
        $this->p02();

        foreach (['Rattan Fruit Basket', 'rattan', 'RATTAN', '  rattan  '] as $term) {
            $this->assertContains('Rattan Fruit Basket', $this->names($this->shop(['search' => $term])), $term);
        }
    }

    public function test_BUY_SRCH_004_search_typo_and_no_results(): void
    {
        $this->p02();

        $typo = $this->shop(['search' => 'Ratan baskit']);
        $this->assertSame('rattan basket', $typo->viewData('didYouMean'));
        $typo->assertSee('Did you mean')->assertSee('rattan basket');

        $none = $this->shop(['search' => 'zzqxw']);
        $this->assertSame([], $this->names($none));
        $this->assertNull($none->viewData('didYouMean'));
        $none->assertSee('No “zzqxw”', false);
    }

    public function test_BUY_SRCH_005_search_special_characters_and_injection(): void
    {
        $this->p02();
        $this->p05();

        foreach (['%', "' OR '1'='1", '<script>alert(1)</script>', '"><img src=x onerror=alert(1)>'] as $term) {
            $page = $this->shop(['search' => $term]);
            $this->assertSame([], $this->names($page), $term);
            $page->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
        }
    }

    public function test_BUY_SRCH_006_empty_search(): void
    {
        $this->p02();
        $this->p05();

        foreach (['', '   '] as $term) {
            $names = $this->names($this->shop(['search' => $term]));
            $this->assertContains('Rattan Fruit Basket', $names);
            $this->assertContains('USB-C Fast Charger 20W', $names);
        }
    }

    public function test_BUY_SRCH_007_price_range_filter(): void
    {
        $this->p02();
        $this->p05();
        $this->p03();

        $this->assertContains('Rattan Fruit Basket', $this->names($this->shop(['min' => 300, 'max' => 450])));
        $this->assertNotContains('Rattan Fruit Basket', $this->names($this->shop(['min' => 451, 'max' => 1000])));
        $this->assertSame(['Rattan Fruit Basket'], $this->names($this->shop(['min' => 450, 'max' => 450])));

        $swapped = $this->shop(['min' => 1000, 'max' => 100]);
        $this->assertSame('The minimum price can\'t be more than the maximum price.', $swapped->viewData('priceError'));
        $this->assertCount(3, $this->names($swapped));

        $negative = $this->shop(['min' => -1]);
        $this->assertSame('Prices can\'t be negative.', $negative->viewData('priceError'));
        $this->assertCount(3, $this->names($negative));
    }

    public function test_BUY_SRCH_008_combined_filters(): void
    {
        $p02 = $this->p02();
        $p03 = $this->p03();
        $p05 = $this->p05();
        $reviewer = $this->buyer2();

        foreach ([[$p02, 5], [$p03, 3], [$p05, 5]] as [$product, $stars]) {
            $orderId = $this->makeOrder($reviewer, $product, 'Completed');
            DB::table('product_reviews')->insert(['buyer_id' => $reviewer->id, 'order_id' => $orderId, 'product_id' => $product->id, 'rating' => $stars, 'created_at' => now(), 'updated_at' => now()]);
        }

        $home = \App\Support\Categories::slug('Home & Living');
        $all = ['rating' => 4, 'category' => $home, 'location' => 'Metro Manila (NCR)'];
        $this->assertSame(['Rattan Fruit Basket'], $this->names($this->shop($all)));

        $noRating = $this->names($this->shop(['category' => $home, 'location' => 'Metro Manila (NCR)']));
        $this->assertEqualsCanonicalizing(['Rattan Fruit Basket', 'Capiz Shell Lamp'], $noRating);

        $noCategory = $this->names($this->shop(['location' => 'Metro Manila (NCR)']));
        $this->assertNotContains('USB-C Fast Charger 20W', $noCategory);
        $this->assertContains('USB-C Fast Charger 20W', $this->names($this->shop([])));
    }

    public function test_BUY_SRCH_009_sort_and_pagination(): void
    {
        $p02 = $this->p02();
        $p03 = $this->p03();
        $p05 = $this->p05();
        $this->travel(1)->minutes();
        $p07 = $this->p07();

        $this->assertSame(['USB-C Fast Charger 20W', 'Rattan Fruit Basket', 'Power Bank 10000mAh', 'Capiz Shell Lamp'], $this->names($this->shop(['sort' => 'price_low'])));
        $this->assertSame(['Capiz Shell Lamp', 'Power Bank 10000mAh', 'Rattan Fruit Basket', 'USB-C Fast Charger 20W'], $this->names($this->shop(['sort' => 'price_high'])));
        $this->assertSame('Power Bank 10000mAh', $this->names($this->shop(['sort' => 'newest']))[0]);

        $orderId = $this->makeOrder($this->buyer2(), $p05, 'Completed');
        DB::table('order_items')->where('order_id', $orderId)->update(['quantity' => 5]);
        $this->assertSame('USB-C Fast Charger 20W', $this->names($this->shop(['sort' => 'best_selling']))[0]);

        // Pages: no product twice, none missing.
        for ($i = 1; $i <= 30; $i++) {
            $this->makeProduct($this->seller1(), ['name' => 'Filler ' . $i, 'category' => 'Home & Living', 'price' => 100 + $i]);
        }

        $page1 = $this->names($this->shop(['sort' => 'price_low']));
        $page2 = $this->names($this->shop(['sort' => 'price_low', 'page' => 2]));
        $this->assertSame($page1, $this->names($this->shop(['sort' => 'price_low', 'page' => 1])));
        $this->assertEmpty(array_intersect($page1, $page2));
        $total = $this->shop(['sort' => 'price_low'])->viewData('paginator')->total();
        $seen = [];
        for ($page = 1; $page <= (int) ceil($total / count($page1)); $page++) {
            $seen = array_merge($seen, $this->names($this->shop(['sort' => 'price_low', 'page' => $page])));
        }
        $this->assertCount(34, array_unique($seen));
    }

    public function test_BUY_SRCH_010_inactive_deleted_and_draft_products_never_appear(): void
    {
        $inactive = $this->makeProduct($this->seller1(), ['name' => 'Archived Abaca Fan', 'category' => 'Home & Living', 'is_archived' => true]);
        $deleted = $this->makeProduct($this->seller1(), ['name' => 'Deleted Abaca Hat', 'category' => 'Home & Living']);
        $deleted->delete();
        $draft = $this->makeProduct($this->seller1(), ['name' => 'Draft Abaca Mat', 'category' => 'Home & Living', 'is_draft' => true]);

        foreach (['Archived Abaca Fan', 'Deleted Abaca Hat', 'Draft Abaca Mat'] as $name) {
            $this->assertSame([], $this->names($this->shop(['search' => $name])), $name);
        }

        $category = $this->names($this->shop(['category' => \App\Support\Categories::slug('Home & Living')]));
        $store = $this->names($this->get(route('shop.seller', $this->seller1()->id)));
        foreach ([$category, $store] as $list) {
            $this->assertEmpty(array_intersect(['Archived Abaca Fan', 'Deleted Abaca Hat', 'Draft Abaca Mat'], $list));
        }

        foreach ([$inactive, $deleted, $draft] as $product) {
            $status = $this->get(route('product.details', $product->id))->status();
            $this->assertContains($status, [302, 404], $product->name);
        }
    }

    public function test_BUY_SRCH_011_products_of_a_vacation_store(): void
    {
        $p02 = $this->p02();
        DB::table('seller_applications')->where('user_id', $this->seller1()->id)->update(['vacation_mode' => true]);

        $card = collect($this->shop(['search' => 'Rattan'])->viewData('products'))->firstWhere('name', 'Rattan Fruit Basket');
        $this->assertTrue($card['vacation']);

        $this->post(route('cart.add', $p02->id))->assertSessionHas('error', Product::VACATION_MESSAGE);
        $this->assertArrayNotHasKey($this->key($p02), session('cart', []));
    }

    /* ---------------------------------------------------------------
     | B4 Product Detail Page
     * --------------------------------------------------------------- */

    public function test_BUY_PDP_001_all_variation_types_must_be_selected(): void
    {
        $p01 = $this->p01();
        $buyer = $this->buyer1();

        $this->actingAsUser($buyer)->post(route('cart.add', $p01->id))->assertSessionHas('error', 'Please choose a color and size for Abaca Woven Tote Bag first.');
        $this->post(route('buy.now', $p01->id))->assertSessionHas('error', 'Please choose a color and size for Abaca Woven Tote Bag first.');
        $this->assertSame([], session('cart', []));
        $this->assertNull(session('buy_now'));

        $this->post(route('cart.add', $p01->id), ['variation_id' => $this->variant('Natural-M')->id])->assertSessionHas('success');
        $this->assertSame([$this->key($p01, $this->variant('Natural-M')->id) => 1], session('cart'));
    }

    public function test_BUY_PDP_002_selecting_a_variant_updates_price_stock_and_image(): void
    {
        $p01 = $this->p01();
        $rows = ['Natural-S' => [199, 10], 'Natural-L' => [299, 5], 'Black-S' => [199, 10]];

        foreach ($rows as $row => [$price, $stock]) {
            $variant = $this->variant($row);
            $this->assertSame((float) $price, $p01->unitPrice($variant), $row);
            $this->assertSame($stock, (int) $variant->stock, $row);
        }

        $this->actingAsUser($this->buyer1())->get(route('product.details', $p01->id))->assertOk()
            ->assertSee('₱199')->assertSee('₱299')->assertSee('data-stock="5"', false);
    }

    public function test_BUY_PDP_003_out_of_stock_variant_disabled(): void
    {
        $p01 = $this->p01();
        $blackM = $this->variant('Black-M');

        $this->actingAsUser($this->buyer1())->get(route('product.details', $p01->id))->assertOk();
        $this->post(route('cart.add', $p01->id), ['variation_id' => $blackM->id])->assertSessionHas('error', 'This product is currently out of stock.');
        $this->assertSame([], session('cart', []));
    }

    public function test_BUY_PDP_004_quantity_limits(): void
    {
        $p02 = $this->p02();

        foreach (['0' => false, '-1' => false, '1' => true, '20' => true, '21' => false, '1.5' => false, 'abc' => false] as $quantity => $accepted) {
            $this->freshCart();
            $response = $this->actingAsUser($this->buyer1())->post(route('cart.add', $p02->id), ['quantity' => (string) $quantity]);

            if ($accepted) {
                $response->assertSessionHas('success');
                $this->assertSame((int) $quantity, session('cart')[$this->key($p02)], (string) $quantity);
            } else {
                $response->assertSessionHas('error');
                $this->assertArrayNotHasKey($this->key($p02), session('cart', []), (string) $quantity);
            }
        }

        // The + button stops at 20.
        $this->freshCart();
        $this->actingAsUser($this->buyer1(), ['cart' => [$this->key($p02) => 20]])
            ->post(route('cart.update', $this->key($p02)), ['action' => 'increase'])->assertSessionHas('error', 'Only 20 available.');
        $this->assertSame(20, session('cart')[$this->key($p02)]);
    }

    public function test_BUY_PDP_005_per_buyer_purchase_limit(): void
    {
        $p07 = $this->p07();
        $p07->update(['purchase_limit' => 2]);

        $this->actingAsUser($this->buyer1())->post(route('cart.add', $p07->id), ['quantity' => 2])->assertSessionHas('success');
        $this->order($this->buyer1(), [$this->key($p07) => 2])->assertSessionDoesntHaveErrors();
        $this->assertSame(1, $this->orders($this->buyer1())->count());

        $message = 'You can buy at most 2 of Power Bank 10000mAh per buyer (you already bought 2).';
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('cart.add', $p07->id), ['quantity' => 1])->assertSessionHas('error', $message);
        $this->post(route('buy.now', $p07->id), ['quantity' => 1])->assertSessionHas('error', $message);

        // Even with the item put in a new cart some other way, checkout refuses it.
        $this->order($this->buyer1(), [$this->key($p07) => 1]);
        $this->assertSame(1, $this->orders($this->buyer1())->count());

        // Buyer 2 has their own limit.
        $this->flushSession();
        $this->actingAsUser($this->buyer2())->post(route('cart.add', $p07->id), ['quantity' => 2])->assertSessionHas('success');
    }

    public function test_BUY_PDP_006_sale_price_countdown_and_vouchers_shown(): void
    {
        $p02 = $this->p02();
        \App\Models\FlashSale::create(['seller_id' => $this->seller1()->id, 'product_id' => $p02->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'flash_price' => 299, 'quantity' => 10, 'sold' => 2]);
        $this->sheetVoucher('MCH50');

        $html = $this->actingAsUser($this->buyer1())->get(route('product.details', $p02->id))->assertOk()->getContent();

        foreach (['₱299', '₱450', 'data-countdown', '8 left', 'MCH50', 'Claim'] as $needle) {
            $this->assertStringContainsString($needle, $html, 'Missing: ' . $needle);
        }
    }

    public function test_BUY_PDP_007_buy_now_checks_out_only_that_item(): void
    {
        $p01 = $this->p01();
        $p05 = $this->p05();
        $naturalM = $this->variant('Natural-M');

        $this->actingAsUser($this->buyer1(), ['cart' => [$this->key($p05) => 1]])
            ->post(route('buy.now', $p01->id), ['variation_id' => $naturalM->id])->assertRedirect(route('checkout', ['buy_now' => 1]));
        $this->assertSame([$this->key($p01, $naturalM->id) => 1], session('buy_now'));

        $checkout = $this->get(route('checkout', ['buy_now' => 1]))->assertOk()->assertSee('Abaca Woven Tote Bag');
        $this->assertSame([$this->key($p01, $naturalM->id)], array_keys($checkout->viewData('cart')));
        $this->assertSame([$this->key($p05) => 1], session('cart'));
    }

    public function test_BUY_PDP_008_product_page_of_an_unavailable_product(): void
    {
        $deleted = $this->makeProduct($this->seller1(), ['name' => 'Deleted Abaca Hat']);
        $deleted->delete();
        $inactive = $this->makeProduct($this->seller1(), ['name' => 'Archived Abaca Fan', 'is_archived' => true]);

        foreach ([$deleted, $inactive] as $product) {
            $this->flushSession();
            $response = $this->actingAsUser($this->buyer1())->get(route('product.details', $product->id))->assertNotFound()->assertSee('This product is unavailable');
            $this->assertStringNotContainsString('Add to Cart', $response->getContent());
        }
    }

    /* ---------------------------------------------------------------
     | B5 Wishlist & Follow Store
     * --------------------------------------------------------------- */

    private function wishlistIds(User $buyer): array
    {
        return DB::table('wishlists')->where('user_id', $buyer->id)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function test_BUY_WISH_001_add_and_remove_a_wishlist_item(): void
    {
        $p02 = $this->p02();
        $buyer = $this->buyer1();

        $this->actingAsUser($buyer)->postJson(route('wishlist.toggle', $p02->id))->assertJson(['in_wishlist' => true]);
        $this->assertSame([$p02->id], $this->wishlistIds($buyer));
        $this->assertSame(['Rattan Fruit Basket'], collect($this->get(route('wishlist.index'))->assertOk()->viewData('products'))->pluck('name')->all());
        $this->assertTrue(collect($this->shop(['search' => 'Rattan'])->viewData('products'))->first()['in_wishlist']);

        $this->actingAsUser($buyer)->postJson(route('wishlist.toggle', $p02->id))->assertJson(['in_wishlist' => false]);
        $this->assertSame([], $this->wishlistIds($buyer));
        $this->assertFalse(collect($this->shop(['search' => 'Rattan'])->viewData('products'))->first()['in_wishlist']);
    }

    public function test_BUY_WISH_002_add_the_same_item_twice_quickly(): void
    {
        $p02 = $this->p02();
        $buyer = $this->buyer1();

        $first = $this->actingAsUser($buyer)->postJson(route('wishlist.toggle', $p02->id))->json('in_wishlist');
        $second = $this->postJson(route('wishlist.toggle', $p02->id))->json('in_wishlist');

        $this->assertLessThanOrEqual(1, count($this->wishlistIds($buyer)));
        $this->assertSame($second, in_array($p02->id, $this->wishlistIds($buyer), true));
        $this->assertTrue($first);
    }

    public function test_BUY_WISH_003_wishlist_item_goes_out_of_stock_or_is_deleted(): void
    {
        $p02 = $this->p02();
        $p05 = $this->p05();
        $buyer = $this->buyer1();
        foreach ([$p02, $p05] as $product) {
            DB::table('wishlists')->insert(['user_id' => $buyer->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        $p02->update(['stock' => 0]);
        $p05->delete();

        $page = $this->actingAsUser($buyer)->get(route('wishlist.index'))->assertOk();
        $html = $page->getContent();
        $this->assertSame(2, collect($page->viewData('products'))->count());
        $this->assertStringContainsString('Sold out', $html);
        $this->assertStringContainsString('Unavailable', $html);
        $this->assertStringNotContainsString(route('cart.add', $p02->id), $html);
        $this->assertStringNotContainsString(route('cart.add', $p05->id), $html);
    }

    public function test_BUY_WISH_004_follow_and_unfollow_a_store(): void
    {
        $seller = $this->seller1();
        $n = \App\Http\Controllers\StoreFollowController::followers($seller->id);

        $this->actingAsUser($this->buyer1())->post(route('store.follow', $seller->id), ['action' => 'follow'])->assertSessionHas('success', 'You are now following this store.');
        $this->assertSame($n + 1, \App\Http\Controllers\StoreFollowController::followers($seller->id));
        $this->get(route('buyer.following'))->assertOk()->assertSee('Maria Clara Handicrafts');
        $this->get(route('shop.seller', $seller->id))->assertSee(($n + 1) . ' follower')->assertSee('Following');

        $this->post(route('store.follow', $seller->id), ['action' => 'unfollow'])->assertSessionHas('success', 'You unfollowed this store.');
        $this->assertSame($n, \App\Http\Controllers\StoreFollowController::followers($seller->id));
        $this->get(route('buyer.following'))->assertDontSee('Maria Clara Handicrafts');
    }

    public function test_BUY_WISH_005_wishlist_and_follow_require_login(): void
    {
        $p02 = $this->p02();
        $seller = $this->seller1();

        $this->post(route('wishlist.toggle', $p02->id))->assertRedirect(route('login'));
        $this->post(route('store.follow', $seller->id))->assertRedirect(route('login'));
        $this->assertSame(0, DB::table('wishlists')->count());
        $this->assertSame(0, DB::table('store_follows')->count());
    }

    /* ---------------------------------------------------------------
     | B6 Cart
     * --------------------------------------------------------------- */

    private function cartPage(User $buyer, array $cart)
    {
        $this->freshCart();

        return $this->actingAsUser($buyer, ['cart' => $cart])->get(route('cart'))->assertOk();
    }

    public function test_BUY_CART_001_same_variant_added_twice_merges(): void
    {
        $p01 = $this->p01();
        $naturalM = $this->variant('Natural-M');

        $this->actingAsUser($this->buyer1())->post(route('cart.add', $p01->id), ['variation_id' => $naturalM->id, 'quantity' => 1]);
        $this->post(route('cart.add', $p01->id), ['variation_id' => $naturalM->id, 'quantity' => 2]);

        $this->assertSame([$this->key($p01, $naturalM->id) => 3], session('cart'));
        $this->get(route('cart'))->assertOk()->assertSee('₱747.00');
    }

    public function test_BUY_CART_002_different_variants_stay_separate(): void
    {
        $p01 = $this->p01();

        $this->actingAsUser($this->buyer1())->post(route('cart.add', $p01->id), ['variation_id' => $this->variant('Natural-M')->id]);
        $this->post(route('cart.add', $p01->id), ['variation_id' => $this->variant('Black-S')->id]);

        $this->assertCount(2, session('cart'));
        $this->get(route('cart'))->assertSee('₱249.00 each')->assertSee('₱199.00 each');
    }

    public function test_BUY_CART_003_items_grouped_by_store(): void
    {
        $html = $this->cartPage($this->buyer1(), [$this->key($this->p02()) => 1, $this->key($this->p05()) => 1])->getContent();

        $this->assertStringContainsString('Maria Clara Handicrafts', $html);
        $this->assertStringContainsString('RV Gadget Hub', $html);
        // In the cart list: Seller 1's group (with P-02), then Seller 2's (with P-05).
        $list = substr($html, strpos($html, 'id="cart-item-') - 3000);
        $this->assertLessThan(strpos($list, 'RV Gadget Hub'), strpos($list, 'Rattan Fruit Basket'));
        $this->assertGreaterThan(strpos($list, 'RV Gadget Hub'), strpos($list, 'USB-C Fast Charger 20W'));
    }

    public function test_BUY_CART_004_select_and_deselect_items_for_checkout(): void
    {
        $tote = $this->key($this->p01(), $this->variant('Natural-M')->id);
        $basket = $this->key($this->p02());
        $charger = $this->key($this->p05());
        $cart = [$tote => 1, $basket => 1, $charger => 1];
        $html = $this->cartPage($this->buyer1(), $cart)->getContent();

        // Each line's checkbox carries its price; the page adds up the ticked ones.
        $price = fn (string $key) => (float) (preg_match('/data-cart-key="' . preg_quote($key, '/') . '"\s+data-unit-price="([0-9.]+)"/', $html, $m) ? $m[1] : 0);
        $this->assertSame(399.0, $price($charger));
        $this->assertSame(1098.0, $price($tote) + $price($basket) + $price($charger));
        $this->assertSame(699.0, $price($tote) + $price($basket));

        $this->get(route('checkout', ['items' => $tote . ',' . $basket]))->assertOk();
        $this->assertEqualsCanonicalizing([$tote, $basket], session('checkout_selection'));
        $this->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])->assertSessionDoesntHaveErrors();

        $this->assertSame([$charger => 1], session('cart'));
        $this->assertSame(1, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CART_005_change_quantity_at_the_limits(): void
    {
        $p02 = $this->p02();
        $key = $this->key($p02);

        foreach (['0' => null, '1' => 1, '20' => 20, '21' => null] as $quantity => $saved) {
            $this->freshCart();
            $response = $this->actingAsUser($this->buyer1(), ['cart' => [$key => 5]])->post(route('cart.update', $key), ['action' => 'set', 'quantity' => (string) $quantity]);

            if ($saved === null) {
                $response->assertSessionHas('error', $quantity === 0 ? 'Enter a quantity of at least 1, or remove the item.' : 'Only 20 available.');
                $this->assertSame(5, session('cart')[$key]);
            } else {
                $this->assertSame($saved, session('cart')[$key]);
            }
        }
    }

    public function test_BUY_CART_006_price_changes_while_in_the_cart(): void
    {
        $p02 = $this->p02();
        $this->cartPage($this->buyer1(), [$this->key($p02) => 1])->assertSee('₱450.00');

        $p02->update(['price' => 420]);
        $this->get(route('cart'))->assertSee('₱420.00')->assertDontSee('₱450.00 each');
    }

    public function test_BUY_CART_007_item_goes_out_of_stock_or_is_deleted_while_in_the_cart(): void
    {
        $p02 = $this->p02();
        $p05 = $this->p05();
        $p06 = $this->p06();
        $black = DB::table('product_variations')->where('product_id', $p06->id)->where('variation_value', 'Black')->value('id');
        $cart = [$this->key($p02) => 1, $this->key($p05) => 1, $this->key($p06, $black) => 1];

        $p02->update(['stock' => 0]);
        $p05->delete();

        $html = $this->cartPage($this->buyer1(), $cart)->getContent();
        $this->assertStringContainsString('Sold out', $html);
        $this->assertStringContainsString('No longer available', $html);
        $this->assertStringNotContainsString('class="item-checkbox"' . "\n" . '                                    data-cart-key="' . $this->key($p02) . '"', $html);
        $this->assertSame(1, substr_count($html, 'class="item-checkbox"'));

        // Picking them anyway is refused; P-06 checks out.
        $this->get(route('checkout', ['items' => $this->key($p02)]))->assertRedirect(route('cart'));
        $this->get(route('checkout', ['items' => $this->key($p06, $black)]))->assertOk();
        $this->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery']);
        $this->assertSame(1, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CART_008_cart_persists_after_logout_and_on_another_device(): void
    {
        $buyer = $this->buyer1();
        $this->actingAsUser($buyer)->post(route('cart.add', $this->p02()->id), ['quantity' => 2]);
        $this->post(route('cart.add', $this->p05()->id), ['quantity' => 1]);
        $expected = session('cart');
        $this->get(route('cart'));

        // A new session (logged in again, or another browser) gets the same cart.
        $this->flushSession();
        $this->actingAsUser($buyer)->get(route('cart'))->assertOk();
        $this->assertSame($expected, session('cart'));
    }

    public function test_BUY_CART_009_remove_an_item(): void
    {
        $p02 = $this->p02();
        $p05 = $this->p05();
        $this->cartPage($this->buyer1(), [$this->key($p02) => 1, $this->key($p05) => 1]);

        $this->post(route('cart.remove', $this->key($p02)));
        $this->assertSame([$this->key($p05) => 1], session('cart'));
        $this->get(route('cart'))->assertDontSee('Rattan Fruit Basket')->assertSee('₱399.00');
    }

    public function test_BUY_CART_010_cannot_change_another_buyers_cart(): void
    {
        $p05 = $this->p05();
        $this->cartPage($this->buyer2(), [$this->key($p05) => 1]);
        $buyer2Cart = session('cart');
        $this->get(route('cart'));

        // Cart lines are keyed in the buyer's own session: Buyer 2's line isn't reachable.
        $this->freshCart();
        DB::table('saved_carts')->insert(['user_id' => $this->buyer2()->id, 'items' => json_encode($buyer2Cart)]);
        $this->actingAsUser($this->buyer1())->post(route('cart.update', $this->key($p05)), ['action' => 'set', 'quantity' => 9]);
        $this->assertArrayNotHasKey($this->key($p05), session('cart', []));
        $this->assertSame($buyer2Cart, json_decode(DB::table('saved_carts')->where('user_id', $this->buyer2()->id)->value('items'), true));
    }

    /* ---------------------------------------------------------------
     | B7 Vouchers & Promotions
     * --------------------------------------------------------------- */

    /** Apply codes in the cart (as the buyer does), then open checkout; [response, plan, applied]. */
    private function checkoutWith(User $buyer, array $cart, array $codes, ?string $address = null): array
    {
        $this->freshCart();
        $this->actingAsUser($buyer, ['cart' => $cart]);
        $messages = [];

        foreach ($codes as $code) {
            $this->post(route('cart.voucher.apply'), ['voucher_code' => $code]);
            $messages[$code] = session('error') ?? session('success');
        }

        if ($address) {
            \App\Models\BuyerAddress::where('user_id', $buyer->id)->update(['is_default' => false]);
            \App\Models\BuyerAddress::create(['user_id' => $buyer->id, 'label' => 'Other', 'phone' => '09171234567', 'address' => $address, 'is_default' => true]);
        }

        $page = $this->get(route('checkout'))->assertOk();

        return [$page, $page->viewData('checkoutPlan'), $messages];
    }

    private function workedCart(): array
    {
        return [$this->key($this->p01(), $this->variant('Natural-M')->id) => 1, $this->key($this->p02()) => 1, $this->key($this->p05()) => 1];
    }

    public function test_BUY_VOU_001_claim_a_voucher_into_the_wallet(): void
    {
        $mch50 = $this->sheetVoucher('MCH50');

        $this->actingAsUser($this->buyer1())->post(route('vouchers.claim', $mch50->id))->assertSessionHas('success');
        $this->post(route('vouchers.claim', $mch50->id));
        $this->assertSame(1, DB::table('voucher_claims')->where('voucher_id', $mch50->id)->where('user_id', $this->buyer1()->id)->count());

        $this->get(route('buyer.vouchers'))->assertOk()->assertSee('MCH50')->assertSee('300')->assertSee($mch50->ends_at->format('M'));
        $this->get(route('shop.seller', $this->seller1()->id))->assertSee('Claimed');
    }

    public function test_BUY_VOU_002_apply_a_store_voucher(): void
    {
        $this->sheetZones();
        $this->sheetVoucher('MCH50');

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame(50.0, $plan['discount']);
        $this->assertSame(50.0, $plan['delivery_fee']);
        $this->assertSame(450.0, $plan['total']);
    }

    public function test_BUY_VOU_003_stacking_per_the_agreed_rule(): void
    {
        $this->sheetZones();
        foreach (['MCH50', 'MCH10', 'PLAT100', 'FREESHIP', 'RVSECRET'] as $code) {
            $this->sheetVoucher($code);
        }

        [, $plan, $messages] = $this->checkoutWith($this->buyer1(), $this->workedCart(), ['MCH50', 'PLAT100', 'FREESHIP']);
        $this->assertSame(1083.0, $plan['total']);
        $this->assertEqualsCanonicalizing(['MCH50', 'PLAT100', 'FREESHIP'], $plan['applied']);

        [, $plan, $messages] = $this->checkoutWith($this->buyer1(), $this->workedCart(), ['MCH50', 'PLAT100', 'FREESHIP', 'MCH10']);
        $this->assertStringContainsString('replaced MCH50', $messages['MCH10']);
        $this->assertSame(69.9, $plan['discount']);
        $this->assertSame(1063.1, $plan['total']);
        $this->assertNotContains('MCH50', $plan['applied']);

        [, , $messages] = $this->checkoutWith($this->buyer1(), $this->workedCart(), ['RVSECRET']);
        $this->assertStringContainsString('needs a minimum spend of ₱1,000.00', $messages['RVSECRET']);
    }

    public function test_BUY_VOU_004_minimum_spend_at_the_boundary(): void
    {
        $this->sheetZones();
        $this->sheetVoucher('MCH50');
        $p02 = $this->p02();

        $p02->update(['price' => 299.99]);
        [, , $messages] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCH50']);
        $this->assertStringContainsString('Spend ₱0.01 more', $messages['MCH50']);

        foreach ([300.00, 300.01] as $price) {
            $p02->update(['price' => $price]);
            [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCH50']);
            $this->assertSame(50.0, $plan['discount'], (string) $price);
        }

        $this->assertSame(0, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_VOU_005_expired_not_started_limit_reached_invalid_code(): void
    {
        foreach (['MCHOLD', 'MCHSOON', 'MCHMAX'] as $code) {
            $this->sheetVoucher($code);
        }

        [, $plan, $messages] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCHOLD', 'MCHSOON', 'MCHMAX', 'NOSUCHCODE']);
        $this->assertSame('Voucher MCHOLD has expired.', $messages['MCHOLD']);
        $this->assertStringStartsWith('Voucher MCHSOON is not available yet. It starts', $messages['MCHSOON']);
        $this->assertSame('Voucher MCHMAX has reached its usage limit.', $messages['MCHMAX']);
        $this->assertSame('Invalid voucher code.', $messages['NOSUCHCODE']);
        $this->assertSame(0.0, $plan['discount']);
    }

    public function test_BUY_VOU_006_per_buyer_limit_reached(): void
    {
        $this->sheetVoucher('MCH50');
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->setOrder($this->orders($this->buyer1())->last()->id, ['status' => 'Completed']);

        [, , $messages] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame('You have already used voucher "MCH50".', $messages['MCH50']);

        [, $plan] = $this->checkoutWith($this->buyer2(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame(50.0, $plan['discount']);
    }

    public function test_BUY_VOU_007_removing_an_item_drops_below_the_minimum_spend(): void
    {
        $this->sheetZones();
        $this->sheetVoucher('MCH50');
        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame(50.0, $plan['discount']);

        // Back to the cart: P-02 out, P-01 Natural-S in, then checkout again (MCH50 still selected).
        $this->post(route('cart.remove', $this->key($this->p02())));
        $this->post(route('cart.add', $this->p01()->id), ['variation_id' => $this->variant('Natural-S')->id]);
        $page = $this->get(route('checkout'))->assertOk();

        $this->assertNotEmpty($page->viewData('voucherNotices'));
        $this->assertStringContainsString('MCH50', implode(' ', (array) $page->viewData('voucherNotices')));
        $this->assertSame(249.0, $page->viewData('checkoutPlan')['total']);
    }

    public function test_BUY_VOU_008_percentage_voucher_respects_its_cap(): void
    {
        $this->sheetVoucher('MCH10');
        $p02 = $this->p02();

        $p02->update(['price' => 499.99]);
        [, $plan, $messages] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCH10']);
        $this->assertStringContainsString('needs a minimum spend', $messages['MCH10']);
        $this->assertSame(0.0, $plan['discount']);

        foreach ([999.00 => 99.9, 1000.00 => 100.0] as $price => $off) {
            $p02->update(['price' => $price]);
            [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCH10']);
            $this->assertSame($off, $plan['discount'], (string) $price);
        }

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p03()) => 1], ['MCH10']);
        $this->assertSame(100.0, $plan['discount']);
    }

    public function test_BUY_VOU_009_shipping_voucher_larger_than_shipping(): void
    {
        $this->sheetZones();
        $this->sheetVoucher('FREESHIP');

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['FREESHIP']);
        $this->assertSame([50.0, 50.0, 450.0], [$plan['delivery_fee'], $plan['shipping_discount'], $plan['subtotal']]);
        $this->assertSame(450.0, $plan['total']);

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1, $this->key($this->p01(), $this->variant('Natural-M')->id) => 1], ['FREESHIP']);
        $this->assertSame([65.0, 50.0, 699.0], [$plan['delivery_fee'], $plan['shipping_discount'], $plan['subtotal']]);
        $this->assertSame(714.0, $plan['total']);
    }

    public function test_BUY_VOU_010_total_never_negative(): void
    {
        $this->sheetZones();
        $p02 = $this->p02();
        $p02->update(['price' => 100]);
        $this->sheetVoucher('MCHT100');

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCHT100']);
        $this->assertSame(100.0, $plan['discount']);
        $this->assertSame(50.0, $plan['delivery_fee']);
        $this->assertSame(50.0, $plan['total']);
    }

    public function test_BUY_VOU_011_voucher_on_items_already_on_sale(): void
    {
        $this->sheetZones();
        $p02 = $this->p02();
        \App\Models\ProductSale::create(['seller_id' => $this->seller1()->id, 'product_id' => $p02->id, 'discount_type' => 'fixed', 'discount_value' => 360, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay()]);
        $this->sheetVoucher('MCH50');

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], ['MCH50']);
        $this->assertSame(360.0, $plan['subtotal']);
        $this->assertSame(50.0, $plan['discount']);
        $this->assertSame(360.0, $plan['total']);
    }

    public function test_BUY_VOU_012_voucher_returned_after_cancellation(): void
    {
        $mch50 = $this->sheetVoucher('MCH50');
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $order = $this->orders($this->buyer1())->last();
        $this->assertSame('MCH50', $order->voucher_code);
        $this->assertSame(1, (int) $mch50->fresh()->used_count);

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('buyer.order.cancel', $order->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');
        $this->assertSame(0, (int) $mch50->fresh()->used_count);
        $this->assertFalse($mch50->fresh()->usedUpBy($this->buyer1()->id));

        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame(50.0, $plan['discount']);
    }

    /* ---------------------------------------------------------------
     | B8 Checkout
     * --------------------------------------------------------------- */

    public function test_BUY_CHK_001_checkout_with_the_worked_example(): void
    {
        $this->sheetZones();
        foreach (['MCH50', 'PLAT100', 'FREESHIP'] as $code) {
            $this->sheetVoucher($code);
        }

        [, $plan] = $this->checkoutWith($this->buyer1(), $this->workedCart(), ['MCH50', 'PLAT100', 'FREESHIP']);
        $this->assertSame([1098.0, 185.0, 50.0, 100.0, 50.0, 1083.0],
            [$plan['subtotal'], $plan['delivery_fee'], $plan['discount'], $plan['platform_discount'], $plan['shipping_discount'], $plan['total']]);

        $this->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])->assertSessionDoesntHaveErrors();
        $orders = $this->orders($this->buyer1());
        $this->assertCount(2, $orders);
        $this->assertSame(1083.0, round($orders->sum('total_amount'), 2));
        $this->assertSame([], session('cart', []));
    }

    public function test_BUY_CHK_002_shipping_fee_per_store_and_per_address(): void
    {
        $this->sheetZones();
        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], []);
        $this->assertSame(50.0, $plan['delivery_fee']);

        $quote = $this->getJson(route('checkout.quote', ['address' => self::ADDR_3]))->assertOk();
        $this->assertSame(120, (int) $quote->json('delivery_fee'));
        $this->assertSame(570, (int) $quote->json('total'));
    }

    public function test_BUY_CHK_003_shipping_weight_at_the_1_kg_boundary(): void
    {
        $this->sheetZones();
        [, $five] = $this->checkoutWith($this->buyer2(), [$this->key($this->p05()) => 5], []);
        [, $six] = $this->checkoutWith($this->buyer2(), [$this->key($this->p05()) => 6], []);

        $this->assertSame(60.0, $five['delivery_fee']);
        $this->assertSame(80.0, $six['delivery_fee']);
    }

    public function test_BUY_CHK_004_unserviceable_address(): void
    {
        $this->sheetZones();
        $addr4 = 'Purok 2, Brgy. Raele, Itbayat, Batanes';

        [$page, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], [], $addr4);
        $this->assertSame('Sorry, we don\'t deliver to Batanes yet — this area is not serviceable. Choose another address.', $plan['unserviceable']);
        $page->assertSee('not serviceable');
        $this->assertMatchesRegularExpression('/id="placeOrderButton"\s+disabled/', $page->getContent());

        $this->post(route('checkout.place'), ['address' => $addr4, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertSessionHas('error', $plan['unserviceable']);
        $this->assertSame(0, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CHK_005_note_to_seller_length(): void
    {
        $note = Str::padRight('Paki-wrap po as gift ', 200, 'x');
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'Cash on Delivery', ['note' => $note])->assertSessionDoesntHaveErrors();
        $order = $this->orders($this->buyer1())->last();
        $this->assertSame($note, $order->buyer_note);
        $this->flushSession();
        $this->actingAsUser($this->seller1())->get(route('seller.order.details', $order->id))->assertSee($note);

        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'Cash on Delivery', ['note' => $note . 'x'])
            ->assertSessionHas('error', 'The note to the seller can have at most 200 characters (now 201).');
        $this->assertSame(1, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CHK_006_no_payment_method_selected(): void
    {
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], '')->assertSessionHas('error', 'Please choose a payment method.');
        $this->assertSame(0, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CHK_007_double_click_place_order(): void
    {
        $p02 = $this->p02();
        $token = (string) Str::uuid();
        $form = ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery', \App\Support\SubmitOnce::FIELD => $token];

        $this->actingAsUser($this->buyer1(), ['cart' => [$this->key($p02) => 1]])->post(route('checkout.place'), $form);
        $this->actingAsUser($this->buyer1(), ['cart' => [$this->key($p02) => 1]])->post(route('checkout.place'), $form)
            ->assertSessionHas('success', 'Your order was already placed.');

        $this->assertSame(1, $this->orders($this->buyer1())->count());
        $this->assertSame(19, $this->stockOf($p02));
    }

    public function test_BUY_CHK_008_price_changed_during_checkout(): void
    {
        $this->sheetZones();
        $p02 = $this->p02();
        [$page, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($p02) => 1], []);
        $this->assertSame(500.0, $plan['total']);
        $page->assertSee('name="expected_total"', false);

        $p02->update(['price' => 480]);
        $this->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery', 'expected_total' => '500.00'])
            ->assertSessionHas('error', 'A price in your order changed while you were checking out. The total is now ₱530.00 (was ₱500.00). Please review it and place the order again.');
        $this->assertSame(0, $this->orders($this->buyer1())->count());
        $this->assertSame(530.0, $this->get(route('checkout'))->viewData('checkoutPlan')['total']);
    }

    public function test_BUY_CHK_009_stock_ran_out_during_checkout(): void
    {
        $p03 = $this->p03();
        $this->checkoutWith($this->buyer1(), [$this->key($p03) => 1], []);

        $this->order($this->buyer2(), [$this->key($p03) => 1])->assertSessionDoesntHaveErrors();
        $this->order($this->buyer1(), [$this->key($p03) => 1])->assertSessionHas('error');
        $this->assertStringContainsString('stock', strtolower((string) session('error')));

        $this->assertSame(0, $this->orders($this->buyer1())->count());
        $this->assertSame(0, $this->stockOf($p03));
    }

    public function test_BUY_CHK_010_voucher_becomes_invalid_during_checkout(): void
    {
        $mch50 = $this->sheetVoucher('MCH50');
        [, $plan] = $this->checkoutWith($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50']);
        $this->assertSame(50.0, $plan['discount']);

        $mch50->update(['ended_at' => now()]);
        $this->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertSessionHas('error', 'Voucher MCH50 has expired. It has been removed — please review your total and place the order again.');
        $this->assertSame(0, $this->orders($this->buyer1())->count());
    }

    public function test_BUY_CHK_011_tampered_price_or_total_in_the_request(): void
    {
        $this->sheetZones();
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'Cash on Delivery', ['price' => '1.00', 'total' => '1.00', 'total_amount' => '1.00', 'subtotal' => '1.00']);

        $order = $this->orders($this->buyer1())->last();
        $this->assertSame(500.0, (float) $order->total_amount);
        $this->assertSame(450.0, (float) DB::table('order_items')->where('order_id', $order->id)->value('price'));
    }

    /* ---------------------------------------------------------------
     | B9 Payment
     * --------------------------------------------------------------- */

    private function tabOf(object $order): string
    {
        return \App\Support\BuyerOrderTabs::group((array) $order, false);
    }

    public function test_BUY_PAY_001_cod_order(): void
    {
        $this->sheetZones();
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1])->assertRedirect();
        $order = $this->orders($this->buyer1())->last();

        $this->assertSame('to-ship', $this->tabOf($order));
        $this->assertSame('COD', $order->payment_status);
        $this->assertSame(500.0, (float) $order->total_amount);
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee('Cash on Delivery')->assertSee('₱500.00');
    }

    public function test_BUY_PAY_002_e_wallet_or_card_payment_succeeds(): void
    {
        $this->sheetZones();
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'GCash');
        $order = $this->orders($this->buyer1())->last();
        $this->assertSame('to-pay', $this->tabOf($order));

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('payment.show', $order->payment_group))->assertOk()->assertViewHas('amount', 500.0);
        $this->post(route('payment.pay', $order->payment_group))->assertSessionHas('success');

        $order = DB::table('orders')->find($order->id);
        $this->assertSame('Paid', $order->payment_status);
        $this->assertStringStartsWith('BBPAY-', $order->payment_reference);
        $this->assertSame('to-ship', $this->tabOf($order));
    }

    public function test_BUY_PAY_003_payment_fails(): void
    {
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'Maya');
        $order = $this->orders($this->buyer1())->last();

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.fail', $order->payment_group))
            ->assertSessionHas('error', 'Payment failed. You were not charged. You can try again with Pay Again.');
        $order = DB::table('orders')->find($order->id);
        $this->assertSame('Failed', $order->payment_status);
        $this->assertSame('to-pay', $this->tabOf($order));
        $this->get(route('payment.show', $order->payment_group))->assertSee('Pay Again');

        $this->flushSession();
        $this->actingAsUser($this->seller1())->post(route('seller.order.status', $order->id), ['status' => 'Confirmed'])->assertSessionHas('error');
        $this->assertSame('Pending', DB::table('orders')->where('id', $order->id)->value('status'));
    }

    public function test_BUY_PAY_004_buyer_cancels_at_the_gateway(): void
    {
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'GCash');
        $order = $this->orders($this->buyer1())->last();

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.cancel', $order->payment_group))->assertRedirect(route('buyer.orders', ['tab' => 'to-pay']));
        $this->assertSame(1, $this->orders($this->buyer1())->count());
        $this->assertSame('to-pay', $this->tabOf(DB::table('orders')->find($order->id)));
    }

    public function test_BUY_PAY_005_browser_closed_on_the_gateway(): void
    {
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'GCash');
        $order = $this->orders($this->buyer1())->last();

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.pay', $order->payment_group));
        $reference = DB::table('orders')->where('id', $order->id)->value('payment_reference');

        // The gateway's confirmation comes back again (closed tab reopened).
        $this->post(route('payment.pay', $order->payment_group))->assertSessionHas('success', 'This order is already paid.');
        $this->assertSame($reference, DB::table('orders')->where('id', $order->id)->value('payment_reference'));
        $this->assertSame('to-ship', $this->tabOf(DB::table('orders')->find($order->id)));
    }

    public function test_BUY_PAY_006_unpaid_order_auto_cancels(): void
    {
        $p02 = $this->p02();
        $mch50 = $this->sheetVoucher('MCH50');
        $this->order($this->buyer1(), [$this->key($p02) => 1], ['MCH50'], 'GCash');
        $order = $this->orders($this->buyer1())->last();
        $this->assertSame(19, $this->stockOf($p02));
        $this->assertSame(1, (int) $mch50->fresh()->used_count);

        $this->travel(61)->minutes();
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('payment.show', $order->payment_group));

        $order = DB::table('orders')->find($order->id);
        $this->assertSame('Cancelled', $order->status);
        $this->assertStringStartsWith('Cancelled: unpaid', $order->cancellation_reason);
        $this->assertSame(20, $this->stockOf($p02));
        $this->assertSame(0, (int) $mch50->fresh()->used_count);
    }

    public function test_BUY_PAY_007_pay_just_before_vs_after_the_auto_cancel_time(): void
    {
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], [], 'GCash');
        $early = $this->orders($this->buyer1())->last();
        $this->travel(59)->minutes();
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.pay', $early->payment_group))->assertSessionHas('success');
        $this->assertSame('Paid', DB::table('orders')->where('id', $early->id)->value('payment_status'));

        $this->order($this->buyer1(), [$this->key($this->p05()) => 1], [], 'GCash');
        $late = $this->orders($this->buyer1())->last();
        $this->travel(61)->minutes();
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.pay', $late->payment_group))
            ->assertSessionHas('error', 'This order can no longer be paid. It was cancelled because it was not paid within 60 minutes.');
        $this->assertSame('Cancelled', DB::table('orders')->where('id', $late->id)->value('status'));
        $this->get(route('payment.show', $late->payment_group))->assertViewHas('amount', 0.0);
    }

    /* ---------------------------------------------------------------
     | B10 Order Tracking & History
     * --------------------------------------------------------------- */

    /** The tab counts My Orders shows (key => count). */
    private function tabCounts(User $buyer): array
    {
        $this->flushSession();
        $html = $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk()->getContent();
        preg_match_all('/data-group="([a-z\-]+)">\s*[^<]+<small>(\d+)<\/small>/', $html, $m);

        return array_map('intval', array_combine($m[1], $m[2]));
    }

    /** A Quezon City Sorting Center with its staff and rider (Logistics 1 / Rider 1). */
    private function qcHub(): array
    {
        $center = $this->world['qc'] ??= $this->center('Metro Manila (NCR)', 'Quezon City');

        return [$center, $this->world['qcStaff'] ??= $this->staffAt($center), $this->world['qcRider'] ??= $this->riderFor($center, 'Quezon City')];
    }

    private function deliveredOrder(User $buyer, Product $product, array $columns = []): int
    {
        return $this->makeOrder($buyer, $product, 'Delivered', array_merge(['delivered_at' => now(), 'payment_status' => 'COD'], $columns));
    }

    public function test_BUY_TRK_001_orders_appear_in_the_correct_tab(): void
    {
        $buyer = $this->buyer1();
        $p02 = $this->p02();
        $this->makeOrder($buyer, $p02, 'Pending', ['payment_method' => 'GCash', 'payment_status' => 'Unpaid', 'pay_by' => now()->addHour()]);
        $this->makeOrder($buyer, $p02, 'Confirmed', ['payment_status' => 'COD']);
        $this->makeOrder($buyer, $p02, 'Out for Delivery', ['payment_status' => 'COD']);
        $this->makeOrder($buyer, $p02, 'Completed', ['payment_status' => 'COD', 'buyer_received_at' => now(), 'delivered_at' => now()]);
        $this->makeOrder($buyer, $p02, 'Cancelled', ['payment_status' => 'COD']);
        $returned = $this->deliveredOrder($buyer, $p02);
        DB::table('return_refund_requests')->insert([
            'order_id' => $returned, 'order_item_id' => DB::table('order_items')->where('order_id', $returned)->value('id'), 'buyer_id' => $buyer->id,
            'seller_id' => $this->seller1()->id, 'request_type' => 'Return', 'reason' => 'Damaged item', 'message' => 'Broken rim on arrival.', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(['all' => 6, 'to-pay' => 1, 'to-ship' => 1, 'to-receive' => 1, 'completed' => 1, 'cancelled' => 1, 'returns' => 1], $this->tabCounts($buyer));
    }

    public function test_BUY_TRK_002_timeline_matches_seller_rider_and_logistics_updates(): void
    {
        [, $staff, $rider] = $this->qcHub();
        $buyer = $this->buyer1();
        $this->order($buyer, [$this->key($this->p02()) => 1])->assertRedirect();
        $order = $this->orders($buyer)->last();

        $this->shipFromSeller($this->seller1(), $order->id);
        $this->assertSame('to-receive', \App\Support\BuyerOrderTabs::group(DB::table('orders')->find($order->id)));

        $this->flushSession();
        $this->actingAsUser($staff)->post(route('logistics.parcels.confirm-received', $order->id))->assertSessionHas('success');
        $this->post(route('logistics.parcels.assign', $this->sorted($order->id)), ['rider_id' => $rider->id])->assertSessionHas('success');
        $this->flushSession();
        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order->id), ['status' => 'Out for Delivery'])->assertSessionHas('success');

        $events = DB::table('order_events')->where('order_id', $order->id)->orderBy('id')->get();
        $statuses = $events->pluck('status')->all();
        foreach (['Confirmed', 'Preparing', 'Ready for Pickup', 'Picked Up', 'Out for Delivery'] as $status) {
            $this->assertSame(1, count(array_keys($statuses, $status)), $status . ' appears once');
        }
        $this->assertSame($events->pluck('id')->sort()->values()->all(), $events->pluck('id')->all());
        $this->assertTrue($events->every(fn ($e) => $e->created_at !== null));
        $this->assertSame('to-receive', \App\Support\BuyerOrderTabs::group(DB::table('orders')->find($order->id)));
    }

    public function test_BUY_TRK_003_order_received_button(): void
    {
        $buyer = $this->buyer1();
        $orderId = $this->deliveredOrder($buyer, $this->p02(), ['total_amount' => 500, 'delivery_fee' => 50]);
        $this->assertSame(0.0, \App\Support\SellerBalance::balances($this->seller1()->id)['available']);

        $this->actingAsUser($buyer)->post(route('buyer.order.received', $orderId))->assertSessionHas('success');
        $this->assertSame('Completed', $this->orderStatus($orderId));
        $this->assertSame('completed', \App\Support\BuyerOrderTabs::group(DB::table('orders')->find($orderId)));
        $this->get(route('buyer.orders'))->assertSee('Rate Product');
        $this->assertGreaterThan(0, \App\Support\SellerBalance::balances($this->seller1()->id)['available']);
    }

    public function test_BUY_TRK_004_order_received_not_available_before_delivery(): void
    {
        $buyer = $this->buyer1();
        $orderId = $this->makeOrder($buyer, $this->p02(), 'Out for Delivery', ['payment_status' => 'COD']);

        $html = $this->actingAsUser($buyer)->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString(route('buyer.order.received', $orderId), $html);
        $this->post(route('buyer.order.received', $orderId))->assertSessionHas('error', 'You can only confirm an order after it has been delivered.');
        $this->assertSame('Out for Delivery', $this->orderStatus($orderId));
    }

    public function test_BUY_TRK_005_automatic_completion(): void
    {
        $orderId = $this->deliveredOrder($this->buyer1(), $this->p02());

        $this->travel(7)->days();
        $this->travel(-1)->minutes();
        \App\Support\AutoReceive::sweep(true);
        $this->assertSame('Delivered', $this->orderStatus($orderId));

        $this->travel(2)->minutes();
        \App\Support\AutoReceive::sweep(true);
        $this->assertSame('Completed', $this->orderStatus($orderId));
        $this->assertGreaterThan(0, \App\Support\SellerBalance::balances($this->seller1()->id)['available']);
    }

    public function test_BUY_TRK_006_cannot_open_another_buyers_order(): void
    {
        $theirs = $this->makeOrder($this->buyer2(), $this->p05(), 'Confirmed', ['shipping_address' => self::ADDR_3]);

        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString('Osmeña', $html);
        $this->post(route('buyer.order.cancel', $theirs), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('error', 'Order not found.');
        $this->post(route('buyer.order.received', $theirs))->assertSessionHas('error');
        $this->get(route('orders.success', $theirs))->assertStatus(404);
        $this->assertSame('Confirmed', $this->orderStatus($theirs));
    }

    /* ---------------------------------------------------------------
     | B11 Cancellation
     * --------------------------------------------------------------- */

    public function test_BUY_CAN_001_cancel_a_cod_order_before_shipping(): void
    {
        $p02 = $this->p02();
        $this->order($this->buyer1(), [$this->key($p02) => 2]);
        $order = $this->orders($this->buyer1())->last();
        $this->setOrder($order->id, ['status' => 'Confirmed']);
        $this->assertSame(18, $this->stockOf($p02));

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('buyer.order.cancel', $order->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');

        $this->assertSame('Cancelled', $this->orderStatus($order->id));
        $this->assertSame(20, $this->stockOf($p02));
        $note = DB::table('notifications')->where('user_id', $this->seller1()->id)->where('title', 'Order Cancelled by Buyer')->value('message');
        $this->assertStringContainsString('Reason: Changed my mind', $note);
        $this->flushSession();
        $this->actingAsUser($this->seller1())->get(route('seller.order.details', $order->id))->assertSee('Cancelled');
    }

    public function test_BUY_CAN_002_reason_is_required(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Confirmed');

        $this->actingAsUser($this->buyer1())->post(route('buyer.order.cancel', $orderId), ['cancel_reason' => ''])->assertSessionHas('error', 'Please choose a reason for cancelling.');
        $this->assertSame('Confirmed', $this->orderStatus($orderId));
    }

    public function test_BUY_CAN_003_cancel_a_prepaid_order(): void
    {
        $mch50 = $this->sheetVoucher('MCH50');
        $this->order($this->buyer1(), [$this->key($this->p02()) => 1], ['MCH50'], 'GCash');
        $order = $this->orders($this->buyer1())->last();
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('payment.pay', $order->payment_group));
        $this->setOrder($order->id, ['status' => 'Confirmed']);

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('buyer.order.cancel', $order->id), ['cancel_reason' => 'Ordered by mistake'])->assertSessionHas('success');

        $order = DB::table('orders')->find($order->id);
        $this->assertSame('Cancelled', $order->status);
        $this->assertSame('Refund Pending', $order->payment_status);
        $this->assertSame((float) $order->total_amount, (float) $order->refund_amount);
        $this->assertSame(0, (int) $mch50->fresh()->used_count);
    }

    public function test_BUY_CAN_004_no_cancel_after_ready_for_pickup(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Ready for Pickup', ['payment_status' => 'COD']);

        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString(route('buyer.order.cancel', $orderId), $html);
        $this->post(route('buyer.order.cancel', $orderId), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('error');
        $this->assertSame('Ready for Pickup', $this->orderStatus($orderId));
    }

    public function test_BUY_CAN_005_no_cancel_after_shipping(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Picked Up', ['payment_status' => 'COD']);

        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString(route('buyer.order.cancel', $orderId), $html);
        $this->assertSame('Picked Up', $this->orderStatus($orderId));
    }

    public function test_BUY_CAN_006_double_click_cancel(): void
    {
        $p02 = $this->p02();
        $this->order($this->buyer1(), [$this->key($p02) => 1]);
        $order = $this->orders($this->buyer1())->last();
        $this->assertSame(19, $this->stockOf($p02));

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->post(route('buyer.order.cancel', $order->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('success');
        $this->post(route('buyer.order.cancel', $order->id), ['cancel_reason' => 'Changed my mind'])->assertSessionHas('error');

        $this->assertSame(20, $this->stockOf($p02));
        $this->assertSame(1, DB::table('order_events')->where('order_id', $order->id)->where('status', 'Cancelled')->count());
    }

    /* ---------------------------------------------------------------
     | B12 Return & Refund Request
     * --------------------------------------------------------------- */

    private function returnForm(int $orderId, array $overrides = []): array
    {
        return array_merge([
            'order_item_id' => DB::table('order_items')->where('order_id', $orderId)->value('id'),
            'request_type' => 'Return', 'reason' => 'Damaged item', 'message' => 'Basket arrived with a broken rim.',
            'photos' => [$this->jpegOfSize('review_photo.jpg', 40000), $this->jpegOfSize('product_front.jpg', 40000)],
            'refund_method' => 'GCash', 'refund_account_name' => 'Juan Miguel Dela Cruz', 'refund_account_number' => '09171234567',
        ], $overrides);
    }

    private function askReturn(int $orderId, array $overrides = [])
    {
        $this->flushSession();

        return $this->actingAsUser($this->buyer1())->post(route('buyer.return-refund.store', $orderId), $this->returnForm($orderId, $overrides));
    }

    public function test_BUY_RET_001_request_a_return_within_the_window(): void
    {
        $orderId = $this->deliveredOrder($this->buyer1(), $this->p02(), ['delivered_at' => now()->subDays(2)]);

        $this->askReturn($orderId)->assertSessionHas('success');
        $request = DB::table('return_refund_requests')->where('order_id', $orderId)->first();
        $this->assertSame('pending', $request->status);
        $this->assertCount(2, array_merge([$request->evidence], json_decode($request->evidence_photos ?? '[]', true)));
        $this->assertSame('returns', $this->myOrdersGroup($orderId));
        $this->assertTrue(DB::table('notifications')->where('user_id', $this->seller1()->id)->where('type', 'return_refund')->exists());

        $this->travel(8)->days();
        \App\Support\AutoReceive::sweep(true);
        $this->assertSame('Delivered', $this->orderStatus($orderId));
    }

    private function myOrdersGroup(int $orderId): string
    {
        $hasRequest = DB::table('return_refund_requests')->where('order_id', $orderId)->where('status', '!=', 'cancelled')->exists();

        return \App\Support\BuyerOrderTabs::group(DB::table('orders')->find($orderId), $hasRequest);
    }

    public function test_BUY_RET_002_return_not_allowed_before_delivery(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Confirmed', ['payment_status' => 'COD']);

        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString("openReturnRefund('" . $orderId . "')", $html);
        $this->askReturn($orderId)->assertSessionHas('error', 'Only delivered orders can be returned or refunded.');
    }

    public function test_BUY_RET_003_return_window_boundary(): void
    {
        $day7 = $this->deliveredOrder($this->buyer1(), $this->p02(), ['delivered_at' => now()->subDays(7)->addMinute()]);
        $this->askReturn($day7)->assertSessionHas('success');

        $late = $this->makeOrder($this->buyer1(), $this->p02(), 'Completed', ['delivered_at' => now()->subDays(7)->subHour(), 'buyer_received_at' => now()->subDays(7)->subHour()]);
        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString("openReturnRefund('" . $late . "')", $html);
        $this->askReturn($late)->assertSessionHas('error', 'The 7-day return/refund window for this order has passed.');
    }

    public function test_BUY_RET_004_evidence_and_description_rules(): void
    {
        $orderId = $this->deliveredOrder($this->buyer1(), $this->p02());
        $six = array_map(fn ($i) => $this->jpegOfSize('photo' . $i . '.jpg', 20000), range(1, 6));

        $this->askReturn($orderId, ['photos' => []])->assertSessionHas('error', 'Please add 1 to 5 photos of the item (JPG or PNG, up to 5 MB each).');
        $this->askReturn($orderId, ['photos' => $six])->assertSessionHas('error', 'Please add 1 to 5 photos of the item (JPG or PNG, up to 5 MB each).');
        $this->askReturn($orderId, ['photos' => [$this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1))]])->assertSessionHas('error', 'Each photo must be a JPG or PNG image of at most 5 MB.');
        $this->askReturn($orderId, ['message' => 'Broken it'])->assertSessionHas('error', 'Please describe the problem in 10 to 500 characters (now 9).');
        $this->assertSame(0, DB::table('return_refund_requests')->count());

        $this->askReturn($orderId, ['message' => 'Broken rim', 'photos' => [$this->jpegOfSize('one.jpg', 20000)]])->assertSessionHas('success');
    }

    public function test_BUY_RET_005_track_request_status(): void
    {
        $orderId = $this->deliveredOrder($this->buyer1(), $this->p02());
        $this->askReturn($orderId);
        $request = DB::table('return_refund_requests')->where('order_id', $orderId)->first();
        $reason = 'Item shows signs of use and the tag is removed.';

        $this->flushSession();
        $this->actingAsUser($this->seller1())->post(route('seller.return-refund.reject', $request->id), ['seller_note' => $reason])->assertSessionHas('success');

        $this->assertSame('rejected', DB::table('return_refund_requests')->where('id', $request->id)->value('status'));
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee($reason)->assertSee(route('buyer.return-refund.escalate', $request->id), false);
    }

    /* ---------------------------------------------------------------
     | B13 Ratings & Reviews
     * --------------------------------------------------------------- */

    private function completedOrder(User $buyer, Product $product, array $columns = []): int
    {
        return $this->makeOrder($buyer, $product, 'Completed', array_merge(['delivered_at' => now(), 'buyer_received_at' => now(), 'payment_status' => 'COD'], $columns));
    }

    private function review(User $buyer, int $orderId, int $productId, array $form, string $method = 'post')
    {
        $this->flushSession();
        $route = $method === 'put' ? 'buyer.product.review.update' : 'buyer.product.review';

        return $this->actingAsUser($buyer)->{$method}(route($route, [$orderId, $productId]), $form);
    }

    public function test_BUY_RATE_001_rate_a_completed_order(): void
    {
        $p01 = $this->p01();
        $naturalM = $this->variant('Natural-M');
        $orderId = $this->completedOrder($this->buyer1(), $p01);
        DB::table('order_items')->where('order_id', $orderId)->update(['variation_id' => $naturalM->id, 'variation_label' => 'Natural, M']);

        $this->review($this->buyer1(), $orderId, $p01->id, ['rating' => 5, 'review' => 'Matibay at maganda!', 'photos' => [$this->jpegOfSize('review_photo.jpg', 40000)]])
            ->assertSessionHas('success', 'Thank you! Your product review has been submitted.');

        $this->flushSession();
        $this->actingAsUser($this->buyer2())->get(route('product.details', $p01->id))->assertOk()
            ->assertSee('Matibay at maganda!')->assertSee('Natural, M')->assertSee('J*** M*** D*** C***')->assertDontSee('Juan Miguel Dela Cruz')
            ->assertSee('reviews/', false);
        $this->assertSame(5, (int) DB::table('product_reviews')->where('order_id', $orderId)->value('rating'));
    }

    public function test_BUY_RATE_002_rating_not_allowed_before_completion(): void
    {
        $orderId = $this->makeOrder($this->buyer1(), $this->p02(), 'Out for Delivery', ['payment_status' => 'COD']);

        $html = $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->getContent();
        $this->assertStringNotContainsString('/buyer/orders/' . $orderId . '/review/', $html);
        $this->review($this->buyer1(), $orderId, $this->p02()->id, ['rating' => 5])->assertSessionHas('error', 'You can only review products after receiving the order.');
        $this->assertSame(0, DB::table('product_reviews')->count());
    }

    public function test_BUY_RATE_003_one_review_per_item(): void
    {
        $orderId = $this->completedOrder($this->buyer1(), $this->p02());
        $this->review($this->buyer1(), $orderId, $this->p02()->id, ['rating' => 4]);

        $this->review($this->buyer1(), $orderId, $this->p02()->id, ['rating' => 1])->assertSessionHas('error', 'You already reviewed this item. You can edit your review instead.');
        $this->assertSame(1, DB::table('product_reviews')->count());
        $this->get(route('buyer.orders'))->assertSee('Edit review');
    }

    public function test_BUY_RATE_004_stars_text_and_photo_limits(): void
    {
        $p02 = $this->p02();
        $one = $this->completedOrder($this->buyer1(), $p02);
        $five = array_map(fn ($i) => $this->jpegOfSize('p' . $i . '.jpg', 20000), range(1, 5));

        $this->review($this->buyer1(), $one, $p02->id, ['rating' => 0])->assertSessionHas('error', 'Please select a rating from 1 to 5 stars.');
        $this->review($this->buyer1(), $one, $p02->id, ['rating' => 5, 'review' => str_repeat('a', 501)])->assertSessionHas('error', 'A review can have at most 500 characters (now 501).');
        $this->review($this->buyer1(), $one, $p02->id, ['rating' => 5, 'photos' => array_merge($five, [$this->jpegOfSize('p6.jpg', 20000)])])->assertSessionHas('error', 'You can add up to 5 photos.');
        $this->assertSame(0, DB::table('product_reviews')->count());

        $this->review($this->buyer1(), $one, $p02->id, ['rating' => 5, 'review' => str_repeat('a', 500), 'photos' => $five])->assertSessionHas('success');
        $this->assertCount(5, json_decode(DB::table('product_reviews')->value('photos'), true));
    }

    public function test_BUY_RATE_005_edit_once_within_the_window(): void
    {
        $p02 = $this->p02();
        $orderId = $this->completedOrder($this->buyer1(), $p02);
        $this->review($this->buyer1(), $orderId, $p02->id, ['rating' => 3, 'review' => 'Okay lang.']);

        $this->review($this->buyer1(), $orderId, $p02->id, ['rating' => 5, 'review' => 'Mas maganda pala!'], 'put')->assertSessionHas('success', 'Your review has been updated!');
        $this->review($this->buyer1(), $orderId, $p02->id, ['rating' => 1, 'review' => 'Again'], 'put')->assertSessionHas('error', 'You already edited this review. A review can be edited once.');
        $this->assertSame(['5', 'Mas maganda pala!'], array_values(array_map('strval', (array) DB::table('product_reviews')->first(['rating', 'review']))));

        $old = $this->completedOrder($this->buyer1(), $p02, ['buyer_received_at' => now()->subDays(29)]);
        $this->review($this->buyer1(), $old, $p02->id, ['rating' => 4]);
        $this->travel(2)->days();
        $this->review($this->buyer1(), $old, $p02->id, ['rating' => 2], 'put')->assertSessionHas('error', 'Reviews can be written or edited within 30 days of completing the order.');
    }

    public function test_BUY_RATE_006_average_rating_updates(): void
    {
        $p02 = $this->p02();
        $this->review($this->buyer1(), $this->completedOrder($this->buyer1(), $p02), $p02->id, ['rating' => 4]);
        $this->review($this->buyer2(), $this->completedOrder($this->buyer2(), $p02), $p02->id, ['rating' => 2]);

        $card = collect($this->shop(['search' => 'Rattan'])->viewData('products'))->firstWhere('name', 'Rattan Fruit Basket');
        $this->assertSame([3.0, 2], [(float) $card['rating'], $card['reviews']]);
        $this->assertEquals(3, $this->get(route('product.details', $p02->id))->assertOk()->viewData('averageRating'));
    }

    /* ---------------------------------------------------------------
     | B14 Chat & Notifications
     * --------------------------------------------------------------- */

    private function chat(User $from, User $to, array $form)
    {
        $this->flushSession();

        return $this->actingAsUser($from)->post(route('messages.store', $to->id), $form, $this->ajaxHeaders());
    }

    public function test_BUY_CHAT_001_chat_with_a_seller_from_a_product(): void
    {
        $this->chat($this->buyer1(), $this->seller1(), ['message' => 'Hi, available pa po ba?', 'product_id' => $this->p02()->id])->assertOk();
        $this->travel(1)->seconds();
        $this->chat($this->buyer1(), $this->seller1(), ['attachment' => $this->jpegOfSize('review_photo.jpg', 40000)])->assertOk();

        $mine = \App\Models\Message::where('sender_id', $this->buyer1()->id)->orderBy('id')->get();
        $this->assertSame('Hi, available pa po ba?', $mine[0]->message);
        $this->assertSame($this->p02()->id, (int) $mine[0]->product_id);
        $this->assertNotNull($mine[1]->attachment);
        $this->assertSame((int) $this->seller1()->id, (int) $mine[1]->recipient_id);
    }

    public function test_BUY_CHAT_002_invalid_attachments_and_message_length(): void
    {
        foreach ([$this->exe('installer.exe'), $this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1))] as $file) {
            $this->chat($this->buyer1(), $this->seller1(), ['attachment' => $file])->assertStatus(422);
        }
        $this->chat($this->buyer1(), $this->seller1(), ['message' => str_repeat('a', 1001)])->assertStatus(422)
            ->assertJsonValidationErrors(['message' => 'A message can have at most 1,000 characters.']);

        $this->assertSame(0, \App\Models\Message::where('sender_id', $this->buyer1()->id)->count());
    }

    public function test_BUY_CHAT_003_order_status_notifications(): void
    {
        [, $staff, $rider] = $this->qcHub();
        $buyer = $this->buyer1();
        $this->order($buyer, [$this->key($this->p02()) => 1]);
        $order = $this->orders($buyer)->last();

        $this->shipFromSeller($this->seller1(), $order->id);
        $this->flushSession();
        $this->actingAsUser($staff)->post(route('logistics.parcels.confirm-received', $order->id));
        $this->post(route('logistics.parcels.assign', $this->sorted($order->id)), ['rider_id' => $rider->id]);
        $this->flushSession();
        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order->id), ['status' => 'Out for Delivery']);
        $this->post(route('rider.delivery.status', $order->id), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()]);

        foreach (['Order Placed', 'Order Confirmed', 'Order Being Prepared', 'Order Shipped', 'Order Out for Delivery', 'Order Delivered'] as $title) {
            $this->assertSame(1, DB::table('notifications')->where('user_id', $buyer->id)->where('title', $title)->count(), $title);
        }

        $note = DB::table('notifications')->where('user_id', $buyer->id)->where('title', 'Order Delivered')->first();
        $this->flushSession();
        $this->actingAsUser($buyer)->get(route('notifications.open', $note->id))->assertRedirect(route('buyer.orders') . '#order-' . $order->id);
    }

    public function test_BUY_CHAT_004_flash_sale_reminder(): void
    {
        $p02 = $this->p02();
        $flash = \App\Models\FlashSale::create(['seller_id' => $this->seller1()->id, 'product_id' => $p02->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'flash_price' => 299, 'quantity' => 10]);

        $this->actingAsUser($this->buyer1())->get(route('product.details', $p02->id))->assertSee('Remind me');
        $this->post(route('flash.remind', $flash->id))->assertSessionHas('success');
        $this->get(route('product.details', $p02->id))->assertSee('Reminder set');

        $this->travel(50)->minutes();
        $this->assertSame(0, \App\Support\FlashReminders::sweep());
        $this->travel(6)->minutes();
        $this->assertSame(1, \App\Support\FlashReminders::sweep());
        $this->assertSame(0, \App\Support\FlashReminders::sweep());
        $this->assertTrue(now()->lt($flash->starts_at));

        $note = DB::table('notifications')->where('user_id', $this->buyer1()->id)->where('type', 'flash_reminder')->first();
        $this->assertStringContainsString('Rattan Fruit Basket', $note->message);
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('notifications.open', $note->id))->assertRedirect(\App\Support\FlashReminders::productUrl($p02->id));
    }

    public function test_BUY_CHAT_005_mark_notifications_as_read(): void
    {
        $buyer = $this->buyer1();
        foreach (['One', 'Two', 'Three'] as $title) {
            createNotification($buyer->id, $title, 'Test note', 'order');
        }
        $unread = fn () => DB::table('notifications')->where('user_id', $buyer->id)->whereNull('read_at')->count();
        $this->assertSame(3, $unread());

        $first = DB::table('notifications')->where('user_id', $buyer->id)->orderBy('id')->first();
        $this->actingAsUser($buyer)->get(route('notifications.open', $first->id));
        $this->assertSame(2, $unread());

        $this->post(route('notifications.read-all'))->assertSessionHas('success', 'All notifications marked as read.');
        $this->assertSame(0, $unread());
    }
}
