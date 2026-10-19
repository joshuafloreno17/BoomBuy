<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use App\Models\Voucher;
use App\Services\TestSheetData;
use App\Support\DeliveryFee;
use App\Support\ShippingZones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The test sheets' Test Data on a real database (Admin → Settings → Test
 * Sheet Data, `php artisan boombuy:test-sheets`) and the /mailbox catcher
 * for the @example.com accounts.
 */
class TestSheetDataTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function seed_(): array
    {
        return app(TestSheetData::class)->seed();
    }

    private function loginId(string $email, string $password): ?int
    {
        $this->flushSession();
        // The logistics accounts (…@bilishatid.example.com) sign in on the Logistics portal.
        $this->post(route(str_contains($email, '.example.com') ? 'logistics.login.submit' : 'login.submit'), ['email' => $email, 'password' => $password]);
        $id = session('user.id');
        $this->flushSession();

        return $id;
    }

    private function product(string $name): \App\Models\Product
    {
        return \App\Models\Product::where('name', $name)->firstOrFail();
    }

    public function test_seed_makes_the_test_data_accounts_and_they_can_log_in(): void
    {
        $this->seed_();

        foreach ([
            'juanmiguel.delacruz@example.com' => 'Mamimili@2026',
            'anamarie.reyes@example.com' => 'Suki#2026Ana',
            'mariaclara.santos@example.com' => 'Tindahan@2026',
            'roberto.villanueva@example.com' => 'Gadget#Hub26',
            'operations@bilishatid.example.com' => 'Hatid@2026',
            'admin@agilacargo.example.com' => 'Agila#Cargo26',
        ] as $email => $password) {
            $this->assertSame(User::where('email', $email)->value('id'), $this->loginId($email, $password), $email . ' should log in');
        }

        $this->assertSame('Pending Verification', DB::table('seller_applications')->where('user_id', User::where('email', 'seller.test01@example.com')->value('id'))->value('status'));
        $this->assertNull($this->loginId('seller.test01@example.com', 'Tindahan@2026'));
        $this->assertSame('Suspended', User::where('email', 'rider.test01@example.com')->value('status'));
        $this->assertSame(
            User::where('email', 'operations@bilishatid.example.com')->value('id'),
            (int) DB::table('rider_applications')->where('user_id', User::where('email', 'pedrojose.garcia@example.com')->value('id'))->value('created_by')
        );
    }

    public function test_seed_twice_puts_the_values_back_without_duplicates(): void
    {
        $this->seed_();
        $users = User::count();

        // A tester changes things during a module…
        $this->product('Capiz Shell Lamp')->update(['stock' => 0, 'price' => 999]);
        Voucher::where('code', 'MCH50')->update(['used_count' => 7]);
        User::where('email', 'juanmiguel.delacruz@example.com')->update(['password' => bcrypt('Changed@2026')]);

        $this->seed_();

        $this->assertSame($users, User::count());
        $this->assertSame(1, $this->product('Capiz Shell Lamp')->stock);
        $this->assertEquals(1250, $this->product('Capiz Shell Lamp')->price);
        $this->assertSame(0, (int) Voucher::where('code', 'MCH50')->value('used_count'));
        $this->assertSame(1, Voucher::where('code', 'MCH50')->count());
        $this->assertNotNull($this->loginId('juanmiguel.delacruz@example.com', 'Mamimili@2026'));
        $this->assertSame(6, DB::table('product_variations')->where('product_id', $this->product('Abaca Woven Tote Bag')->id)->whereNull('deleted_at')->count());
        $this->assertSame(5, DB::table('store_categories')->count());
    }

    public function test_seed_keeps_an_account_made_through_account_creation(): void
    {
        $made = $this->makeUser('buyer', ['email' => 'juanmiguel.delacruz@example.com', 'name' => 'Juan Miguel Dela Cruz']);

        $this->seed_();

        $this->assertSame($made->id, User::where('email', 'juanmiguel.delacruz@example.com')->value('id'));
        $this->assertSame(1, User::where('email', 'juanmiguel.delacruz@example.com')->count());
    }

    public function test_the_worked_checkout_example_totals_1083(): void
    {
        $this->seed_();
        $buyer = User::where('email', 'juanmiguel.delacruz@example.com')->first();
        $tote = DB::table('product_variations')->where('product_id', $this->product('Abaca Woven Tote Bag')->id)
            ->where('variation_value', 'Natural')->where('option2_value', 'M')->value('id');
        $cart = [
            $this->product('Abaca Woven Tote Bag')->id . ':' . $tote => 1,
            $this->product('Rattan Fruit Basket')->id . ':0' => 1,
            $this->product('USB-C Fast Charger 20W')->id . ':0' => 1,
        ];

        $this->actingAsUser($buyer, ['cart' => $cart]);
        foreach (['MCH50', 'PLAT100', 'FREESHIP'] as $code) {
            $this->post(route('cart.voucher.apply'), ['voucher_code' => $code]);
        }
        $plan = $this->get(route('checkout'))->assertOk()->viewData('checkoutPlan');

        $this->assertSame([1098.0, 185.0, 50.0, 100.0, 50.0, 1083.0],
            [$plan['subtotal'], $plan['delivery_fee'], $plan['discount'], $plan['platform_discount'], $plan['shipping_discount'], $plan['total']]);
    }

    public function test_batanes_is_the_only_unserviceable_province(): void
    {
        $this->seed_();

        $this->assertFalse(ShippingZones::serviceable('Batanes'));
        foreach (['Metro Manila (NCR)', 'Cebu', 'Laguna', 'Davao del Sur', 'Iloilo', 'Benguet'] as $province) {
            $this->assertTrue(ShippingZones::serviceable($province), $province);
        }
    }

    public function test_clear_removes_only_the_test_accounts_and_puts_the_settings_back(): void
    {
        \App\Models\PlatformSetting::set(DeliveryFee::FREE_SHIPPING_KEY, '999');
        $realSeller = $this->sellerIn('Laguna', 'Santa Cruz');
        $realProduct = $this->makeProduct($realSeller, ['name' => 'Real Product', 'price' => 100, 'stock' => 5]);
        $realBuyer = $this->buyerAt();
        $this->seed_();
        $this->assertSame('1000000', \App\Models\PlatformSetting::get(DeliveryFee::FREE_SHIPPING_KEY));

        // A sheet buyer orders from a real seller and a sheet seller; a real buyer orders from a sheet seller.
        $buyer1 = User::where('email', 'juanmiguel.delacruz@example.com')->first();
        $this->placeOrder($buyer1, [$realProduct->id . ':0' => 1, $this->product('Rattan Fruit Basket')->id . ':0' => 1], self::QC_ADDRESS);
        $this->placeOrder($realBuyer, [$this->product('USB-C Fast Charger 20W')->id . ':0' => 1]);
        $this->assertGreaterThanOrEqual(3, DB::table('orders')->count());

        $log = app(TestSheetData::class)->clear();

        $this->assertSame(0, TestSheetData::testAccounts()->count());
        $this->assertGreaterThan(0, (int) $log['Test accounts removed']);
        $this->assertNotNull($realSeller->fresh());
        $this->assertNotNull($realBuyer->fresh());
        $this->assertNotNull($realProduct->fresh());
        $this->assertSame(0, Voucher::whereIn('code', TestSheetData::VOUCHER_CODES)->count(), 'vouchers');
        $this->assertSame(0, DB::table('shipping_zones')->count(), 'zones');
        $this->assertSame(0, DB::table('products')->where('name', 'Rattan Fruit Basket')->count(), 'basket');
        $this->assertSame('999', \App\Models\PlatformSetting::get(DeliveryFee::FREE_SHIPPING_KEY));

        // …and Account Creation can make the same account again.
        $this->seed_();
        $this->assertSame(1, User::where('email', 'juanmiguel.delacruz@example.com')->count());
    }

    public function test_mail_to_example_com_goes_to_the_mailbox_not_out(): void
    {
        Mail::raw('Your code is 123456', fn ($m) => $m->to('juanmiguel.delacruz@example.com')->subject('Your BoomBuy code'));
        Mail::raw('Hello', fn ($m) => $m->to('someone@gmail.com')->subject('Real mail'));

        $this->assertSame(1, DB::table('test_mails')->count());
        $this->assertSame('juanmiguel.delacruz@example.com', DB::table('test_mails')->value('to'));
        $sent = collect(app('mailer')->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage()->getSubject())->all();
        $this->assertSame(['Real mail'], $sent);

        $id = DB::table('test_mails')->value('id');
        $this->get(route('mailbox'))->assertOk()->assertSee('Your BoomBuy code')->assertSee('juanmiguel.delacruz@example.com');
        $this->get(route('mailbox', ['to' => 'nobody']))->assertOk()->assertDontSee('Your BoomBuy code');
        $this->get(route('mailbox.show', $id))->assertOk()->assertSee('123456');
    }

    public function test_registration_otp_for_an_example_com_address_is_read_in_the_mailbox(): void
    {
        $this->post(route('buyer.register.submit'), [
            'first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'email' => 'juanmiguel.delacruz@example.com',
            'phone' => '09171234567', 'birthdate' => '1998-05-14', 'sex' => 'Male',
            'password' => 'Mamimili@2026', 'password_confirmation' => 'Mamimili@2026', 'terms' => '1',
            'province' => 'Metro Manila', 'city_municipality' => 'Quezon City', 'barangay' => 'Commonwealth', 'street_address' => '45 Kalayaan Ave.',
            'id_photo' => $this->png('govt_id_front.png'),
        ]);

        $mail = DB::table('test_mails')->where('to', 'juanmiguel.delacruz@example.com')->first();
        $this->assertNotNull($mail, 'the OTP email should be in the mailbox');
        $this->get(route('otp.show'))->assertSee('Test Mailbox');
    }

    public function test_the_admin_buttons_and_the_command(): void
    {
        $this->withSession(['admin_logged_in' => true])->post(route('admin.settings.testsheet.seed'))->assertSessionHas('success');
        $this->assertSame(1, User::where('email', 'mariaclara.santos@example.com')->count());
        $this->get(route('admin.settings'))->assertOk()->assertSee('Test Sheet Data');

        $this->withSession(['admin_logged_in' => true])->post(route('admin.settings.testsheet.clear'))->assertSessionHas('success');
        $this->assertSame(0, TestSheetData::testAccounts()->count());

        $this->flushSession();
        $this->post(route('admin.settings.testsheet.seed'))->assertRedirect(route('admin.login'));
        $this->assertSame(0, TestSheetData::testAccounts()->count());

        $this->artisan('boombuy:test-sheets', ['action' => 'seed'])->assertSuccessful();
        $this->assertSame(1, User::where('email', 'mariaclara.santos@example.com')->count());
        $this->artisan('boombuy:test-sheets', ['action' => 'clear', '--force' => true])->assertSuccessful();
        $this->assertSame(0, TestSheetData::testAccounts()->count());
    }
}
