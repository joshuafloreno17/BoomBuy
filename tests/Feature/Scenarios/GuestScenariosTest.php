<?php

namespace Tests\Feature\Scenarios;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\LoginGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Test Plan → "Guest at Login" (G01–G18). */
class GuestScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function registrationBase(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_initial' => 'P',
            'sex' => 'Male',
            'birthdate' => '2000-05-10',
            'email' => 'juan' . strtolower(Str::random(4)) . '@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '0917' . random_int(1000000, 9999999),
            'province' => 'Laguna',
            'city_municipality' => 'Santa Cruz',
            'barangay' => 'Poblacion',
            'street_address' => '15 P. Guevarra St',
            'terms' => '1',
        ], $overrides);
    }

    // ---------------- Landing and shop ----------------

    public function test_G01_home_shows_landing_to_guests_and_sends_each_role_to_its_dashboard(): void
    {
        $this->get('/')->assertOk();

        foreach (['buyer' => 'buyer.dashboard', 'rider' => 'rider.dashboard', 'logistics' => 'logistics.dashboard'] as $role => $route) {
            $user = $role === 'rider' ? $this->makeRider() : ($role === 'logistics' ? $this->makeLogistics() : $this->makeUser($role));
            $this->actingAsUser($user)->get('/')->assertRedirect(route($route));
        }

        $this->actingAsUser($this->makeSeller())->get('/')->assertRedirect(route('seller.dashboard'));
    }

    public function test_G02_shop_search_needs_every_word_and_keeps_filters_in_the_url(): void
    {
        $seller = $this->makeSeller('electronics');
        $match = $this->makeProduct($seller, ['name' => 'iPhone 18 Pro Max', 'category' => 'Electronics', 'price' => 500, 'description' => 'Latest phone.']);
        $this->makeProduct($seller, ['name' => 'iPhone Case', 'category' => 'Electronics', 'description' => 'Clear case.']);
        $this->makeProduct($seller, ['name' => 'Pro Speaker', 'category' => 'Electronics', 'description' => 'Loud.']);
        $empty = $this->makeProduct($seller, ['name' => 'iPhone 18 Pro Sold Out', 'category' => 'Electronics', 'stock' => 0]);

        $this->get(route('products', ['search' => 'iphone pro']))
            ->assertOk()
            ->assertSee('iPhone 18 Pro Max')
            ->assertDontSee('iPhone Case')
            ->assertDontSee('Pro Speaker');

        // min > max is swapped instead of showing nothing; in_stock hides sold-out items.
        $this->get(route('products', ['search' => 'iphone pro', 'min' => 1000, 'max' => 100, 'in_stock' => 1]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($empty->name);

        // "Load more" returns just the next cards.
        $this->getJson(route('products', ['partial' => 1]), $this->ajaxHeaders())
            ->assertOk()
            ->assertJsonStructure(['html', 'next', 'shown', 'total']);
    }

    public function test_G03_search_suggestions_show_categories_first_then_products(): void
    {
        $seller = $this->makeSeller('electronics');
        $this->makeProduct($seller, ['name' => 'iPhone 18', 'category' => 'Electronics']);
        $shoeSeller = $this->makeSeller('shoes');
        $this->makeProduct($shoeSeller, ['name' => 'iPhone Shoe Charm', 'category' => 'Shoes']);

        $empty = $this->getJson(route('search.suggestions'))->assertOk()->json();
        $this->assertNotEmpty($empty['categories']);
        $this->assertEmpty($empty['products']);

        $typed = $this->getJson(route('search.suggestions', ['q' => 'iph']))->assertOk()->json();
        $this->assertCount(2, $typed['products']);
        $this->assertLessThanOrEqual(5, count($typed['products']));

        $scoped = $this->getJson(route('search.suggestions', ['q' => 'iph', 'category' => 'electronics']))->json();
        $this->assertSame(['iPhone 18'], array_column($scoped['products'], 'name'));
    }

    public function test_G04_archived_product_is_hidden_from_guests_but_not_its_seller_or_admin(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['is_archived' => true]);
        $url = route('product.details', Str::slug($product->name) . '-' . $product->id);

        $this->get($url)->assertNotFound();
        $this->actingAsUser($seller)->get($url)->assertOk();

        $this->flushSession();
        $this->actingAsAdmin()->get($url)->assertOk();
    }

    public function test_G05_guest_add_to_cart_goes_to_login_then_back_to_the_product(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $productUrl = route('product.details', Str::slug($product->name) . '-' . $product->id);
        $buyer = $this->makeUser('buyer');

        $this->from($productUrl)->post(route('cart.add', $product->id))->assertRedirect(route('login'));

        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123'])
            ->assertRedirect($productUrl);
    }

    public function test_G06_guest_cart_goes_to_login_then_back_to_cart(): void
    {
        $buyer = $this->makeUser('buyer');

        $this->get(route('cart'))->assertRedirect(route('login'));

        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123'])
            ->assertRedirect(route('cart'));
    }

    // ---------------- Registration ----------------

    public function test_G07_buyer_registers_verifies_email_and_is_logged_in_right_away(): void
    {
        $form = $this->registrationBase(['id_photo' => $this->png('id.png')]);

        $this->post(route('buyer.register.submit'), $form)->assertRedirect(route('otp.show'));
        Mail::assertSent(\App\Mail\OtpMail::class);

        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])
            ->assertRedirect(route('buyer.dashboard'));

        $user = User::where('email', $form['email'])->first();
        $this->assertNotNull($user);
        $this->assertSame('buyer', $user->role);
        $this->assertSame($user->id, session('user.id'));
    }

    public function test_G08_otp_wrong_five_times_resend_throttle_and_ten_minute_expiry(): void
    {
        $form = $this->registrationBase(['id_photo' => $this->png('id.png')]);
        $this->post(route('buyer.register.submit'), $form);
        $code = session('otp_code');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('otp.verify'), ['otp_code' => '000000' === $code ? '111111' : '000000']);
        }
        $this->post(route('otp.verify'), ['otp_code' => '000000' === $code ? '111111' : '000000'])
            ->assertSessionHas('error', 'Too many wrong codes. Please tap "Resend code" to get a new one.');

        // The right code no longer works once it was thrown away.
        $this->post(route('otp.verify'), ['otp_code' => $code]);
        $this->assertFalse(User::where('email', $form['email'])->exists());

        // Resend: 3 per 5 minutes.
        $this->post(route('otp.resend'))->assertRedirect();
        $this->post(route('otp.resend'))->assertRedirect();
        $this->post(route('otp.resend'))->assertRedirect();
        $this->post(route('otp.resend'))->assertStatus(429);

        // A fresh code expires after 10 minutes.
        $fresh = session('otp_code');
        $this->travel(11)->minutes();
        $this->post(route('otp.verify'), ['otp_code' => $fresh])->assertSessionHas('error');
        $this->assertFalse(User::where('email', $form['email'])->exists());
    }

    public function test_G09_existing_email_or_phone_and_bad_passwords_keep_input_but_not_the_password(): void
    {
        $existing = $this->makeUser('buyer');

        $this->post(route('buyer.register.submit'), $this->registrationBase(['email' => $existing->email]))
            ->assertSessionHas('error', 'Email is already registered.')
            ->assertSessionHasInput('first_name', 'Juan');

        $this->post(route('buyer.register.submit'), $this->registrationBase(['phone' => $existing->phone]))
            ->assertSessionHas('error', 'This phone number is already registered.');

        $this->post(route('buyer.register.submit'), $this->registrationBase(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHas('error', 'Password must be at least 8 characters.');

        $this->post(route('buyer.register.submit'), $this->registrationBase(['password_confirmation' => 'different1']))
            ->assertSessionHas('error', 'Passwords do not match.');

        // What was typed comes back — the password should not be kept in the session.
        $this->assertNull(session()->getOldInput('password'), 'The password is flashed back into the session as old input.');
    }

    public function test_G09b_birthdays_in_the_future_or_too_young_are_refused(): void
    {
        $this->post(route('buyer.register.submit'), $this->registrationBase(['birthdate' => now()->addYear()->toDateString()]))
            ->assertSessionHas('error', 'Please enter a valid birthday.');
        $this->post(route('buyer.register.submit'), $this->registrationBase(['birthdate' => now()->subYears(10)->toDateString()]))
            ->assertSessionHas('error', 'You must be at least 13 years old to register.');

        $sellerForm = ['business_name' => 'Teen Shop', 'business_category' => 'shoes', 'national_id' => $this->png(), 'business_permit' => $this->pdf()];
        $this->post(route('seller.register.submit'), $this->registrationBase($sellerForm + ['birthdate' => now()->subYears(16)->toDateString()]))
            ->assertSessionHas('error', 'You must be at least 18 years old to register.');

        $this->post(route('rider.apply.submit'), $this->registrationBase(['birthdate' => now()->subYears(17)->toDateString()]))
            ->assertSessionHasErrors(['birthdate' => 'You must be at least 18 years old to apply as a rider.']);

        // A 20-year-old buyer is fine.
        $this->post(route('buyer.register.submit'), $this->registrationBase(['birthdate' => now()->subYears(20)->toDateString(), 'id_photo' => $this->png()]))
            ->assertRedirect(route('otp.show'));
    }

    public function test_G10_seller_registers_and_cannot_log_in_before_approval(): void
    {
        $form = $this->registrationBase([
            'business_name' => 'Juan Shoes',
            'business_category' => 'shoes',
            'national_id' => $this->png('id.png'),
            'business_permit' => $this->pdf('permit.pdf'),
        ]);

        $this->post(route('seller.register.submit'), $form)->assertRedirect(route('otp.show'));
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $this->post(route('login.submit'), ['email' => $form['email'], 'password' => 'secret123'])
            ->assertSessionHas('error', 'Your seller account is still pending verification. We will notify you once it has been approved.');
        $this->assertNull(session('user'));
    }

    public function test_G11_rider_applies_with_documents_and_checks_status_by_email(): void
    {
        $form = $this->registrationBase([
            'vehicle_type' => 'Motorcycle',
            'vehicle_model' => 'Honda Click',
            'plate_number' => 'ABC 1234',
            'national_id' => $this->png('id.png'),
            'drivers_license' => $this->png('license.png'),
            'profile_selfie' => $this->png('selfie.png'),
            'proof_of_address' => $this->pdf('bill.pdf'),
            'or_cr' => $this->pdf('orcr.pdf'),
        ]);

        $this->post(route('rider.apply.submit'), $form)->assertRedirect();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $user = User::where('email', $form['email'])->first();
        $this->assertSame('rider', $user->role);
        $this->assertSame('Pending Verification', DB::table('rider_applications')->where('user_id', $user->id)->value('status'));

        $this->get(route('rider.apply', ['email' => $form['email']]))->assertOk()->assertSee('Pending');
    }

    public function test_G12_logistics_registers_and_waits_for_admin_approval(): void
    {
        $form = $this->registrationBase([
            'business_name' => 'Laguna Hub',
            'id_photo' => $this->png('id.png'),
            'business_permit' => $this->pdf('permit.pdf'),
        ]);

        $this->post(route('logistics.register.submit'), $form)->assertRedirect(route('otp.show'));
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $this->post(route('login.submit'), ['email' => $form['email'], 'password' => 'secret123'])
            ->assertSessionHas('error');
        $this->assertNull(session('user'));
    }

    // ---------------- Login ----------------

    public function test_G13_eleventh_wrong_password_in_a_minute_is_throttled(): void
    {
        $buyer = $this->makeUser('buyer');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'wrong-password'])->assertRedirect();
        }

        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_G13_typing_in_the_search_box_does_not_use_up_the_login_limit(): void
    {
        $buyer = $this->makeUser('buyer');

        // Each letter typed in the navbar search box asks for suggestions.
        foreach (str_split('rubber shoes') as $i => $letter) {
            $this->getJson(route('search.suggestions', ['q' => substr('rubber shoes', 0, $i + 1)]))->assertOk();
        }

        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123'])
            ->assertRedirect(route('buyer.dashboard'));
    }

    public function test_G14_forgot_password_does_not_reveal_accounts_and_signs_out_remembered_devices(): void
    {
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);
        $unknown->assertRedirect(route('password.reset'));
        $unknownMessage = session('success');
        Mail::assertNothingSent();

        $buyer = $this->makeUser('buyer');
        DB::table('users')->where('id', $buyer->id)->update(['remember_token' => hash('sha256', 'old-device')]);

        $this->post(route('password.email'), ['email' => $buyer->email])->assertRedirect(route('password.reset'));
        $this->assertSame($unknownMessage, session('success'));
        Mail::assertSent(\App\Mail\OtpMail::class);

        $this->post(route('password.update'), [
            'otp_code' => session('password_reset_otp'),
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('login'));

        $fresh = $buyer->fresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brandnew123', $fresh->password));
        $this->assertNull($fresh->remember_token);
    }

    public function test_G15_remember_me_logs_back_in_unless_the_account_is_suspended(): void
    {
        $buyer = $this->makeUser('buyer');

        $login = $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123', 'remember' => '1']);
        $cookie = collect($login->headers->getCookies())->first(fn ($c) => $c->getName() === LoginGate::REMEMBER_COOKIE);
        $this->assertNotNull($cookie, 'No remember-me cookie was set.');
        // The response cookie is encrypted; the test client encrypts again when sending.
        $cookie = new \Symfony\Component\HttpFoundation\Cookie(
            $cookie->getName(),
            \Illuminate\Cookie\CookieValuePrefix::remove(\Illuminate\Support\Facades\Crypt::decryptString($cookie->getValue()))
        );

        // A new visit after the session ended.
        $this->flushSession();
        $this->withCookie(LoginGate::REMEMBER_COOKIE, $cookie->getValue())->get(route('buyer.dashboard'))->assertOk();
        $this->assertSame($buyer->id, session('user.id'));

        $this->flushSession();
        DB::table('users')->where('id', $buyer->id)->update(['status' => 'Suspended']);
        $this->withCookie(LoginGate::REMEMBER_COOKIE, $cookie->getValue())->get(route('buyer.dashboard'))->assertRedirect(route('login'));
        $this->assertNull(session('user'));
    }

    public function test_G15b_remember_me_on_two_devices_keeps_both(): void
    {
        $buyer = $this->makeUser('buyer');
        $rememberOn = function () use ($buyer): string {
            $this->flushSession();
            $login = $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123', 'remember' => '1']);
            $cookie = collect($login->headers->getCookies())->first(fn ($c) => $c->getName() === LoginGate::REMEMBER_COOKIE);

            return \Illuminate\Cookie\CookieValuePrefix::remove(\Illuminate\Support\Facades\Crypt::decryptString($cookie->getValue()));
        };
        $comesBack = function (string $cookie) {
            $this->flushSession();
            $this->withCookie(LoginGate::REMEMBER_COOKIE, $cookie)->get(route('buyer.dashboard'));

            return session('user.id');
        };

        $phone = $rememberOn();
        $laptop = $rememberOn();

        $this->assertSame($buyer->id, $comesBack($phone), 'The phone was signed out when the laptop was remembered.');
        $this->assertSame($buyer->id, $comesBack($laptop));

        // Logging out on the laptop leaves the phone signed in.
        $this->flushSession();
        $this->withCookie(LoginGate::REMEMBER_COOKIE, $laptop)->actingAsUser($buyer)->post(route('logout'));
        $this->assertNull($comesBack($laptop));
        $this->assertSame($buyer->id, $comesBack($phone));

        // A password reset signs out every device.
        LoginGate::passwordChanged($buyer);
        $this->assertNull($comesBack($phone));
    }

    public function test_G17_user_suspended_while_logged_in_is_logged_out_on_the_next_click(): void
    {
        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->get(route('buyer.dashboard'))->assertOk();

        DB::table('users')->where('id', $buyer->id)->update(['status' => 'Suspended']);

        $this->get(route('buyer.orders'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account has been suspended. Please contact BoomBuy support for assistance.');
        $this->assertNull(session('user'));
    }

    public function test_G18_policies_page_shows_the_admins_text(): void
    {
        $this->get(route('policies'))->assertOk();

        PlatformSetting::set('terms_policy', 'Bawal ang pekeng order sa BoomBuy.');

        $this->get(route('policies'))->assertOk()->assertSee('Bawal ang pekeng order sa BoomBuy.');
    }
}
