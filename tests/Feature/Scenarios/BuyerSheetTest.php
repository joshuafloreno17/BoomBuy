<?php

namespace Tests\Feature\Scenarios;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The classmate's sheet, Buyer tab: TC-BUY-001 to TC-BUY-030, one test each,
 * with the sheet's own test data (Account 1 = Juan Miguel Dela Cruz).
 */
class BuyerSheetTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(10, 0));
    }

    /** Account 1 from the Test Data sheet, plus the fields the ERP form also asks for. */
    private function account1(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan Miguel',
            'last_name' => 'Dela Cruz',
            'email' => 'juanmiguel.delacruz@example.com',
            'phone' => '09171234567',
            'birthdate' => '1998-05-14',
            'password' => 'Mamimili@2026',
            'password_confirmation' => 'Mamimili@2026',
            'terms' => '1',
            'sex' => 'Male',
            'province' => 'Metro Manila',
            'city_municipality' => 'Quezon City',
            'barangay' => 'Commonwealth',
            'street_address' => 'Blk 12 Lot 8, Sampaguita St.',
            'id_photo' => $this->png('govt_id_front.png'),
        ], $overrides);
    }

    private function register(array $form)
    {
        return $this->post(route('buyer.register.submit'), $form);
    }

    /** Tries one value; true when the form went through to the OTP step. */
    private function accepted(array $overrides): bool
    {
        $this->flushSession();
        $this->register($this->account1($overrides));
        $ok = session()->has('otp_code');
        $this->flushSession();

        return $ok;
    }

    /** Registers Account 1 and verifies the OTP. */
    private function registeredBuyer(): User
    {
        $this->register($this->account1());
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
        $this->flushSession();

        return User::where('email', 'juanmiguel.delacruz@example.com')->firstOrFail();
    }

    public function test_TC_BUY_001_register_with_all_valid_data(): void
    {
        $this->register($this->account1())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('otp.show'))
            ->assertSessionHas('success');
        $this->assertMatchesRegularExpression('/^\d{6}$/', session('otp_code'));
        Mail::assertSent(OtpMail::class, fn ($m) => $m->hasTo('juanmiguel.delacruz@example.com'));
    }

    public function test_TC_BUY_002_submit_a_completely_empty_form(): void
    {
        $this->register([])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'birthdate', 'password', 'terms']);
        $this->assertNull(session('otp_code'));
        Mail::assertNothingSent();
        $this->assertSame(0, User::count());
    }

    public function test_TC_BUY_003_leave_one_required_field_blank_at_a_time(): void
    {
        foreach (['first_name', 'last_name', 'email', 'phone', 'birthdate', 'password', 'password_confirmation'] as $field) {
            $this->flushSession();
            $response = $this->register($this->account1([$field => '']));
            // password_confirmation blank is reported on the password ("does not match").
            $expected = $field === 'password_confirmation' ? 'password' : $field;
            $response->assertSessionHasErrors($expected);
            $others = array_diff(['first_name', 'last_name', 'email', 'phone', 'birthdate', 'password', 'terms', 'id_photo'], [$expected]);
            $response->assertSessionDoesntHaveErrors($others);
            if ($field !== 'last_name') {
                $this->assertSame('Dela Cruz', old('last_name'), "values kept when $field is blank");
            }
            $this->assertNull(session('otp_code'));
        }
    }

    public function test_TC_BUY_004_name_with_numbers_or_symbols(): void
    {
        $this->register($this->account1(['first_name' => 'Juan Miguel123', 'last_name' => 'Dela Cruz@#']))
            ->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertStringContainsString('letters', session('errors')->first('first_name'));
    }

    public function test_TC_BUY_005_name_with_enye_and_allowed_punctuation(): void
    {
        $this->register($this->account1(['first_name' => 'Niño Angelo', 'last_name' => "Peñaflor-O'Hara"]))->assertSessionHasNoErrors();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);

        $user = User::where('email', 'juanmiguel.delacruz@example.com')->first();
        $this->assertSame('Niño Angelo', $user->first_name);
        $this->assertSame("Peñaflor-O'Hara", $user->last_name);
    }

    public function test_TC_BUY_006_name_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['first_name' => 'J']), '1 character');
        $this->assertTrue($this->accepted(['first_name' => 'Jo']), '2 characters');
        $this->assertTrue($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonor']), '50 characters');
        $this->assertFalse($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonora']), '51 characters');
    }

    public function test_TC_BUY_007_age_at_the_18_year_limit(): void
    {
        $this->register($this->account1(['birthdate' => '2008-10-13']))->assertSessionHas('error', 'You must be at least 18 years old to register.');
        $this->assertTrue($this->accepted(['birthdate' => '2008-10-12']), 'turns 18 today');
        $this->assertTrue($this->accepted(['birthdate' => '2008-10-11']), '18 years and 1 day');
    }

    public function test_TC_BUY_008_date_of_birth_in_the_future(): void
    {
        $this->register($this->account1(['birthdate' => '2026-10-13']))->assertSessionHas('error', 'Please enter a valid birthday.');
        // The date picker itself stops at 18 years ago.
        $this->get(route('buyer.register'))->assertSee('max="2008-10-12"', false);
    }

    public function test_TC_BUY_009_email_in_an_invalid_format(): void
    {
        foreach (['juanmiguel.delacruzexample.com', 'juanmiguel.delacruz@', '@example.com', 'juanmiguel.delacruz@example', 'juanmiguel delacruz@example.com'] as $email) {
            $this->assertFalse($this->accepted(['email' => $email]), $email);
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_BUY_010_email_that_is_already_registered(): void
    {
        $this->registeredBuyer();
        $this->register($this->account1(['phone' => '09170000001', 'id_photo' => $this->png()]))
            ->assertSessionHas('error', 'Email is already registered.');
        $this->assertSame(1, User::count());
    }

    public function test_TC_BUY_011_registered_email_typed_in_capital_letters(): void
    {
        $this->registeredBuyer();
        $this->register($this->account1(['email' => 'JUANMIGUEL.DELACRUZ@EXAMPLE.COM', 'phone' => '09170000001', 'id_photo' => $this->png()]))
            ->assertSessionHas('error', 'Email is already registered.');
    }

    public function test_TC_BUY_012_email_with_spaces_before_and_after(): void
    {
        $this->register($this->account1(['email' => '  buyer.test01@example.com  ']))->assertSessionHasNoErrors();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
        $this->assertTrue(User::where('email', 'buyer.test01@example.com')->exists());
    }

    public function test_TC_BUY_013_mobile_number_in_an_invalid_format(): void
    {
        foreach (['0917ABC4567', '0917-123-456', '12345678901', '02-8123-4567'] as $phone) {
            $this->flushSession();
            $this->register($this->account1(['phone' => $phone]))->assertSessionHasErrors('phone');
            $this->assertStringContainsString('valid Philippine mobile number', session('errors')->first('phone'), $phone);
        }
    }

    public function test_TC_BUY_014_mobile_number_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['phone' => '0917123456']), '10 digits');
        $this->assertTrue($this->accepted(['phone' => '09170000001']), '11 digits');
        $this->assertFalse($this->accepted(['phone' => '091712345678']), '12 digits');
    }

    public function test_TC_BUY_015_mobile_number_that_is_already_registered(): void
    {
        $this->registeredBuyer();
        $this->register($this->account1(['email' => 'buyer.test02@example.com', 'id_photo' => $this->png()]))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_BUY_016_registered_number_entered_in_plus63_format(): void
    {
        $this->registeredBuyer();
        $this->register($this->account1(['email' => 'buyer.test03@example.com', 'phone' => '+639171234567', 'id_photo' => $this->png()]))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_BUY_017_password_length_at_the_limit(): void
    {
        $this->register($this->account1(['password' => 'Bili@26', 'password_confirmation' => 'Bili@26']))
            ->assertSessionHasErrors(['password' => 'Password must be at least 8 characters.']);
        $this->assertTrue($this->accepted(['password' => 'Bili@202', 'password_confirmation' => 'Bili@202']), '8 characters');
    }

    public function test_TC_BUY_018_password_missing_a_required_character_type(): void
    {
        foreach (['mamimili@2026', 'MAMIMILI@2026', 'Mamimili@abcd', 'Mamimili2026'] as $password) {
            $this->flushSession();
            $this->register($this->account1(['password' => $password, 'password_confirmation' => $password]))->assertSessionHasErrors('password');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_BUY_019_confirm_password_does_not_match(): void
    {
        $this->register($this->account1(['password_confirmation' => 'Mamimili@2027']))
            ->assertSessionHasErrors(['password' => 'Passwords do not match.']);
    }

    public function test_TC_BUY_020_password_is_hidden_while_typing(): void
    {
        $this->get(route('buyer.register'))
            ->assertSee('type="password"', false)
            ->assertSee('class="password-toggle"', false);
    }

    public function test_TC_BUY_021_register_without_accepting_the_terms(): void
    {
        $this->register($this->account1(['terms' => null]))
            ->assertSessionHasErrors(['terms' => 'Please agree to the Terms & Conditions and Privacy Policy.']);
    }

    public function test_TC_BUY_022_click_register_several_times_quickly(): void
    {
        $account2 = $this->account1(['first_name' => 'Ana Marie', 'last_name' => 'Reyes', 'email' => 'anamarie.reyes@example.com', 'phone' => '09173456789', 'birthdate' => '2001-11-02', 'password' => 'Suki#2026Ana', 'password_confirmation' => 'Suki#2026Ana']);

        // Three quick clicks reach the server as three submits.
        for ($i = 0; $i < 3; $i++) {
            $this->register(array_merge($account2, ['id_photo' => $this->png()]));
        }

        Mail::assertSentCount(1);
    }

    public function test_TC_BUY_023_verify_with_the_correct_otp(): void
    {
        $this->register($this->account1());
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user = User::where('email', 'juanmiguel.delacruz@example.com')->first();
        $this->assertNotNull($user);
        // ERP list: the buyer then waits for the admin's approval.
        $this->assertSame('Pending Verification', DB::table('buyer_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_TC_BUY_024_verify_with_an_incorrect_otp(): void
    {
        $this->register($this->account1());
        $wrong = session('otp_code') === '000000' ? '111111' : '000000';
        $this->post(route('otp.verify'), ['otp_code' => $wrong])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
    }

    public function test_TC_BUY_025_otp_in_the_wrong_format(): void
    {
        $this->register($this->account1());
        foreach (['48291', '4829137', '48A91B', ''] as $code) {
            $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_BUY_026_verify_with_an_expired_otp(): void
    {
        $this->register($this->account1());
        $code = session('otp_code');

        $this->travel(5 * 60 + 1)->seconds();
        $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
        $this->get(route('otp.show'))->assertSee('Resend');
    }

    public function test_TC_BUY_027_resend_otp(): void
    {
        $this->register($this->account1());
        $first = session('otp_code');

        $this->post(route('otp.resend'))->assertSessionHas('success', 'A new code has been sent to your email.');
        $second = session('otp_code');
        Mail::assertSentCount(2);

        if ($first !== $second) {
            $this->post(route('otp.verify'), ['otp_code' => $first])->assertSessionHas('error');
        }
        $this->post(route('otp.verify'), ['otp_code' => $second])->assertRedirect(route('login'));
        $this->assertTrue(User::where('email', 'juanmiguel.delacruz@example.com')->exists());
    }

    public function test_TC_BUY_028_log_in_before_verifying_the_email(): void
    {
        $this->register($this->account1());
        $this->post(route('login.submit'), ['email' => 'juanmiguel.delacruz@example.com', 'password' => 'Mamimili@2026'])
            ->assertSessionHas('error');
        $this->assertNull(session('user'));
    }

    public function test_TC_BUY_029_log_in_with_the_new_verified_account(): void
    {
        $user = $this->registeredBuyer();
        $appId = DB::table('buyer_applications')->where('user_id', $user->id)->value('id');
        $this->withSession(['admin_logged_in' => true])->post(route('admin.applications.approve', ['buyer', $appId]));
        $this->flushSession();

        $this->post(route('login.submit'), ['email' => 'juanmiguel.delacruz@example.com', 'password' => 'Mamimili@2026'])
            ->assertSessionMissing('error');
        $this->assertSame($user->id, session('user.id'));
        $this->get(route('buyer.dashboard'))->assertOk()->assertSee('Juan Miguel');
    }

    public function test_TC_BUY_030_open_another_roles_pages(): void
    {
        $buyer = $this->makeUser('buyer');
        foreach (['seller.dashboard', 'logistics.dashboard', 'rider.dashboard', 'admin.dashboard'] as $page) {
            $response = $this->withSession(['user' => ['id' => $buyer->id, 'name' => $buyer->name, 'email' => $buyer->email, 'role' => 'buyer']])->get(route($page));
            $this->assertTrue($response->isRedirect() || $response->status() === 403, $page);
            $this->assertStringNotContainsString('Dashboard', $response->getContent(), $page);
        }
    }
}
