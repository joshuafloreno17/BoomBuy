<?php

namespace Tests\Feature\Scenarios;

use App\Mail\ApplicationStatusMail;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The classmate's sheet, Seller tab: TC-SEL-001 to TC-SEL-044, one test each,
 * with the sheet's own test data (Account 1 = Maria Clara Santos).
 */
class SellerSheetTest extends TestCase
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

    /** Account 1 from the Test Data sheet (Sole Proprietorship), plus the ERP fields (sex, birthday). */
    private function account1(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria Clara',
            'last_name' => 'Santos',
            'email' => 'mariaclara.santos@example.com',
            'phone' => '09189876543',
            'business_name' => 'Maria Clara Handicrafts',
            'business_category' => 'home-living',
            'seller_type' => 'Sole Proprietorship',
            'tin' => '123-456-789-000',
            'region' => 'National Capital Region (NCR)',
            'province' => 'Metro Manila (NCR)',
            'city_municipality' => 'Quezon City',
            'barangay' => 'Commonwealth',
            'street_address' => 'Blk 12 Lot 8, Sampaguita St.',
            'postal_code' => '1121',
            'national_id' => UploadedFile::fake()->create('govt_id_front.jpg', 1200, 'image/jpeg'),
            'dti_certificate' => $this->pdf('dti_certificate.pdf'),
            'bir_2303' => $this->pdf('bir_2303.pdf'),
            'password' => 'Tindahan@2026',
            'password_confirmation' => 'Tindahan@2026',
            'terms' => '1',
            'sex' => 'Female',
            'birthdate' => '1995-03-08',
        ], $overrides);
    }

    /** Account 2 (Roberto Villanueva, RV Gadget Hub, Corporation). */
    private function account2(array $overrides = []): array
    {
        return $this->account1(array_merge([
            'first_name' => 'Roberto', 'last_name' => 'Villanueva',
            'email' => 'roberto.villanueva@example.com', 'phone' => '09194567890',
            'business_name' => 'RV Gadget Hub', 'business_category' => 'electronics',
            'seller_type' => 'Corporation', 'tin' => '456-789-012-000',
            'region' => 'Region VII (Central Visayas)', 'province' => 'Cebu', 'city_municipality' => 'Cebu City', 'barangay' => 'Lahug',
            'street_address' => '2F, 45 Gorordo Ave.', 'postal_code' => '6000', 'sex' => 'Male',
            'national_id' => $this->png('govt_id_front.png'), 'dti_certificate' => null,
            'sec_certificate' => $this->pdf('sec_certificate.pdf'), 'bir_2303' => $this->pdf('bir_2303.pdf'),
            'password' => 'Gadget#Hub26', 'password_confirmation' => 'Gadget#Hub26',
        ], $overrides));
    }

    private function register(array $form)
    {
        return $this->post(route('seller.register.submit'), $form);
    }

    private function accepted(array $overrides): bool
    {
        $this->flushSession();
        $this->register($this->account1($overrides));
        $ok = session()->has('otp_code');
        $this->flushSession();

        return $ok;
    }

    /** Registers and verifies an account; it is then Pending Approval. */
    private function pendingSeller(?array $form = null): User
    {
        $form ??= $this->account1();
        $this->register($form);
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
        $this->flushSession();

        return User::where('email', trim($form['email']))->firstOrFail();
    }

    private function applicationId(User $user): int
    {
        return (int) DB::table('seller_applications')->where('user_id', $user->id)->latest('id')->value('id');
    }

    private function asAdmin(): static
    {
        return $this->withSession(['admin_logged_in' => true]);
    }

    private function login(string $email, string $password)
    {
        $this->flushSession();

        return $this->post(route('login.submit'), ['email' => $email, 'password' => $password]);
    }

    public function test_TC_SEL_001_register_with_all_valid_data(): void
    {
        $this->register($this->account1())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('otp.show'))
            ->assertSessionHas('success');
        $this->assertMatchesRegularExpression('/^\d{6}$/', session('otp_code'));
        Mail::assertSent(OtpMail::class, fn ($m) => $m->hasTo('mariaclara.santos@example.com'));
    }

    public function test_TC_SEL_002_submit_a_completely_empty_form(): void
    {
        $this->register([])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'business_name', 'business_category', 'seller_type', 'tin', 'region', 'province', 'postal_code', 'national_id', 'password', 'terms']);
        Mail::assertNothingSent();
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_003_leave_one_required_field_blank_at_a_time(): void
    {
        $fields = ['first_name', 'last_name', 'email', 'phone', 'business_name', 'business_category', 'seller_type', 'tin', 'region', 'province', 'city_municipality', 'barangay', 'street_address', 'postal_code', 'national_id', 'dti_certificate', 'bir_2303', 'password', 'password_confirmation'];

        foreach ($fields as $field) {
            $this->flushSession();
            $response = $this->register($this->account1([$field => '']));
            $expected = $field === 'password_confirmation' ? 'password' : $field;
            $response->assertSessionHasErrors($expected);
            $response->assertSessionDoesntHaveErrors(array_diff($fields, [$expected, 'password_confirmation']));
            if ($field !== 'business_name') {
                $this->assertSame('Maria Clara Handicrafts', old('business_name'), "values kept when $field is blank");
            }
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_004_name_with_numbers_or_symbols(): void
    {
        $this->register($this->account1(['first_name' => 'Maria Clara99', 'last_name' => 'Dela Cruz@#']))
            ->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertStringContainsString('letters', session('errors')->first('first_name'));
    }

    public function test_TC_SEL_005_name_with_enye_and_allowed_punctuation(): void
    {
        $user = $this->pendingSeller($this->account1(['first_name' => 'Niña Corazon', 'last_name' => "Peñaranda-D'Souza"]));
        $this->assertSame('Niña Corazon', $user->first_name);
        $this->assertSame("Peñaranda-D'Souza", $user->last_name);
    }

    public function test_TC_SEL_006_name_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['first_name' => 'M']), '1 character');
        $this->assertTrue($this->accepted(['first_name' => 'Mo']), '2 characters');
        $this->assertTrue($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonor']), '50 characters');
        $this->assertFalse($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonora']), '51 characters');
    }

    public function test_TC_SEL_007_email_in_an_invalid_format(): void
    {
        foreach (['mariaclara.santosexample.com', 'mariaclara.santos@', '@example.com', 'mariaclara.santos@example', 'mariaclara santos@example.com'] as $email) {
            $this->assertFalse($this->accepted(['email' => $email]), $email);
        }
        Mail::assertNothingSent();
    }

    public function test_TC_SEL_008_email_that_is_already_registered(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['email' => 'mariaclara.santos@example.com']))
            ->assertSessionHas('error', 'Email is already registered.');
    }

    public function test_TC_SEL_009_registered_email_typed_in_capital_letters(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['email' => 'MARIACLARA.SANTOS@EXAMPLE.COM']))
            ->assertSessionHas('error', 'Email is already registered.');
    }

    public function test_TC_SEL_010_email_with_spaces_before_and_after(): void
    {
        $this->pendingSeller($this->account1(['email' => '  seller.test01@example.com  ']));
        $this->assertTrue(User::where('email', 'seller.test01@example.com')->exists());
    }

    public function test_TC_SEL_011_mobile_number_in_an_invalid_format(): void
    {
        foreach (['0917ABC4567', '0917-123-456', '12345678901', '02-8123-4567'] as $phone) {
            $this->flushSession();
            $this->register($this->account1(['phone' => $phone]))->assertSessionHasErrors('phone');
        }
    }

    public function test_TC_SEL_012_mobile_number_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['phone' => '0917123456']), '10 digits');
        $this->assertTrue($this->accepted(['phone' => '09170000001']), '11 digits');
        $this->assertFalse($this->accepted(['phone' => '091712345678']), '12 digits');
    }

    public function test_TC_SEL_013_mobile_number_that_is_already_registered(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['phone' => '09189876543']))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_SEL_014_registered_number_entered_in_plus63_format(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['phone' => '+639189876543']))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_SEL_015_password_length_at_the_limit(): void
    {
        $this->register($this->account1(['password' => 'Tind@26', 'password_confirmation' => 'Tind@26']))
            ->assertSessionHasErrors(['password' => 'Password must be at least 8 characters.']);
        $this->assertTrue($this->accepted(['password' => 'Tinda@26', 'password_confirmation' => 'Tinda@26']), '8 characters');
    }

    public function test_TC_SEL_016_password_missing_a_required_character_type(): void
    {
        foreach (['tindahan@2026', 'TINDAHAN@2026', 'Tindahan@abcd', 'Tindahan2026'] as $password) {
            $this->flushSession();
            $this->register($this->account1(['password' => $password, 'password_confirmation' => $password]))->assertSessionHasErrors('password');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_017_confirm_password_does_not_match(): void
    {
        $this->register($this->account1(['password_confirmation' => 'Tindahan@2027']))
            ->assertSessionHasErrors(['password' => 'Passwords do not match.']);
    }

    public function test_TC_SEL_018_password_is_hidden_while_typing(): void
    {
        $this->get(route('seller.register'))
            ->assertSee('type="password"', false)
            ->assertSee('class="password-toggle"', false);
    }

    public function test_TC_SEL_019_register_without_accepting_the_terms(): void
    {
        $this->register($this->account1(['terms' => null]))
            ->assertSessionHasErrors(['terms' => 'Please agree to the Terms & Conditions and Privacy Policy.']);
    }

    public function test_TC_SEL_020_click_register_several_times_quickly(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->register($this->account2());
        }
        Mail::assertSentCount(1);

        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));
        $this->assertSame(1, User::where('email', 'roberto.villanueva@example.com')->count());
    }

    public function test_TC_SEL_021_shop_name_already_used_by_another_seller(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['business_name' => 'Maria Clara Handicrafts']))
            ->assertSessionHas('error', 'Another shop already uses that name. Please choose a different one.');
    }

    public function test_TC_SEL_022_shop_name_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['business_name' => 'MC']), '2 characters');
        $this->assertTrue($this->accepted(['business_name' => 'MCH']), '3 characters');
        $this->assertTrue($this->accepted(['business_name' => 'Maria Clara Handicrafts Manila']), '30 characters');
        $this->assertFalse($this->accepted(['business_name' => 'Maria Clara Handicrafts, Manila']), '31 characters');
    }

    public function test_TC_SEL_023_required_documents_change_with_the_seller_type(): void
    {
        $id = fn () => UploadedFile::fake()->create('govt_id_front.jpg', 1200, 'image/jpeg');

        $this->assertTrue($this->accepted(['seller_type' => 'Individual', 'national_id' => $id(), 'dti_certificate' => null, 'sec_certificate' => null, 'bir_2303' => null]), 'Individual: ID only');
        $this->assertTrue($this->accepted(['seller_type' => 'Sole Proprietorship', 'national_id' => $id(), 'dti_certificate' => $this->pdf('dti_certificate.pdf'), 'bir_2303' => $this->pdf('bir_2303.pdf'), 'sec_certificate' => null]), 'Sole Proprietorship');
        $this->assertTrue($this->accepted(['seller_type' => 'Corporation', 'national_id' => $id(), 'sec_certificate' => $this->pdf('sec_certificate.pdf'), 'bir_2303' => $this->pdf('bir_2303.pdf'), 'dti_certificate' => null]), 'Corporation');

        // The form marks the documents per seller type.
        $this->get(route('seller.register'))
            ->assertSee('data-seller-doc="Sole Proprietorship"', false)
            ->assertSee('data-seller-doc="Corporation"', false)
            ->assertSee('data-seller-doc="Sole Proprietorship|Corporation"', false);
    }

    public function test_TC_SEL_024_corporation_registers_without_the_sec_certificate(): void
    {
        $this->register($this->account2(['sec_certificate' => null]))
            ->assertSessionHasErrors(['sec_certificate' => 'The SEC Certificate is required for a Corporation.']);
        $this->assertNull(session('otp_code'));
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_025_tin_in_an_invalid_format_or_length(): void
    {
        $this->assertFalse($this->accepted(['tin' => '123-456-789-00']), '11 digits');
        $this->assertTrue($this->accepted(['tin' => '345-678-901-000']), '12 digits');
        $this->assertFalse($this->accepted(['tin' => '123-456-789-0001']), '13 digits');
        $this->assertFalse($this->accepted(['tin' => '123-456-78A-000']), 'letters');

        $this->register($this->account1(['tin' => '123-456-78A-000']))
            ->assertSessionHasErrors(['tin' => 'The TIN must be 12 digits in the form 000-000-000-000.']);
    }

    public function test_TC_SEL_026_tin_that_is_already_registered(): void
    {
        $this->pendingSeller();
        $this->register($this->account2(['tin' => '123-456-789-000']))
            ->assertSessionHas('error', 'This TIN is already registered.');
        // Typed without dashes it is still the same TIN.
        $this->register($this->account2(['tin' => '123456789000']))
            ->assertSessionHas('error', 'This TIN is already registered.');
    }

    public function test_TC_SEL_027_address_dropdowns_depend_on_each_other(): void
    {
        // The Region list and each region's provinces are on the form...
        $this->get(route('seller.register'))
            ->assertSee('<select id="region" name="region" required>', false)
            ->assertSee('initRegionFilter(', false)
            ->assertSee('Region VII (Central Visayas)');

        // ...and the server refuses a province from another region.
        $this->register($this->account1(['region' => 'Region VII (Central Visayas)', 'province' => 'Metro Manila (NCR)']))
            ->assertSessionHasErrors(['province' => 'Please choose a province in Region VII (Central Visayas).']);
        $this->assertTrue($this->accepted(['region' => 'Region VII (Central Visayas)', 'province' => 'Cebu', 'city_municipality' => 'Cebu City', 'barangay' => 'Lahug']));
    }

    public function test_TC_SEL_028_postal_code_length_and_format(): void
    {
        $this->assertFalse($this->accepted(['postal_code' => '112']), '3 digits');
        $this->assertTrue($this->accepted(['postal_code' => '1121']), '4 digits');
        $this->assertFalse($this->accepted(['postal_code' => '11210']), '5 digits');
        $this->assertFalse($this->accepted(['postal_code' => '11A1']), 'letters');
    }

    public function test_TC_SEL_029_upload_documents_in_each_accepted_file_type(): void
    {
        foreach ([UploadedFile::fake()->create('govt_id_front.jpg', 1200, 'image/jpeg'), $this->png('govt_id_front.png')] as $id) {
            $this->assertTrue($this->accepted(['national_id' => $id, 'dti_certificate' => $this->pdf('dti_certificate.pdf'), 'bir_2303' => $this->pdf('bir_2303.pdf')]), $id->getClientOriginalName());
        }
        // The form shows the chosen file's name.
        $this->get(route('seller.register'))->assertSee('accept=".jpg,.jpeg,.png,.pdf"', false);
    }

    public function test_TC_SEL_030_upload_a_file_type_that_is_not_accepted(): void
    {
        foreach ([['price_list.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], ['store_banner.gif', 'image/gif'], ['installer.exe', 'application/x-msdownload']] as [$name, $mime]) {
            $this->flushSession();
            $this->register($this->account1(['national_id' => UploadedFile::fake()->create($name, 400, $mime)]))
                ->assertSessionHasErrors(['national_id' => 'The government-issued ID must be a JPG, PNG or PDF file.']);
        }
    }

    public function test_TC_SEL_031_file_size_at_the_limit(): void
    {
        $this->assertTrue($this->accepted(['national_id' => UploadedFile::fake()->create('size_4.9MB.jpg', 5017, 'image/jpeg')]), '4.9 MB');
        $this->assertTrue($this->accepted(['national_id' => UploadedFile::fake()->create('size_5.0MB.jpg', 5120, 'image/jpeg')]), '5.0 MB');
        $this->register($this->account1(['national_id' => UploadedFile::fake()->create('size_5.1MB.jpg', 5222, 'image/jpeg')]))
            ->assertSessionHasErrors(['national_id' => 'The government-issued ID must not be larger than 5 MB.']);
    }

    public function test_TC_SEL_032_verify_with_the_correct_otp(): void
    {
        $this->register($this->account1());
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', fn ($m) => str_contains(strtolower($m), 'approv'));

        $user = User::where('email', 'mariaclara.santos@example.com')->first();
        $this->assertSame('Pending Verification', DB::table('seller_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_TC_SEL_033_verify_with_an_incorrect_otp(): void
    {
        $this->register($this->account1());
        $wrong = session('otp_code') === '000000' ? '111111' : '000000';
        $this->post(route('otp.verify'), ['otp_code' => $wrong])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_034_otp_in_the_wrong_format(): void
    {
        $this->register($this->account1());
        foreach (['48291', '4829137', '48A91B', ''] as $code) {
            $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_SEL_035_verify_with_an_expired_otp(): void
    {
        $this->register($this->account1());
        $code = session('otp_code');

        $this->travel(5 * 60 + 1)->seconds();
        $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
        $this->get(route('otp.show'))->assertSee('Resend');
    }

    public function test_TC_SEL_036_resend_otp(): void
    {
        $this->register($this->account1());
        $first = session('otp_code');

        $this->post(route('otp.resend'))->assertSessionHas('success');
        $second = session('otp_code');
        Mail::assertSentCount(2);

        if ($first !== $second) {
            $this->post(route('otp.verify'), ['otp_code' => $first])->assertSessionHas('error');
        }
        $this->post(route('otp.verify'), ['otp_code' => $second])->assertRedirect(route('login'));
        $this->assertTrue(User::where('email', 'mariaclara.santos@example.com')->exists());
    }

    public function test_TC_SEL_037_log_in_before_verifying_the_email(): void
    {
        $this->register($this->account1());
        $this->post(route('login.submit'), ['email' => 'mariaclara.santos@example.com', 'password' => 'Tindahan@2026'])->assertSessionHas('error');
        $this->assertNull(session('user'));
    }

    public function test_TC_SEL_038_pending_seller_logs_in(): void
    {
        $this->pendingSeller();
        $this->login('mariaclara.santos@example.com', 'Tindahan@2026')
            ->assertSessionHas('error', 'Your seller account is still pending verification. We will notify you once it has been approved.');
        $this->assertNull(session('user'));
        $this->get(route('seller.dashboard'))->assertRedirect();
    }

    public function test_TC_SEL_039_new_application_appears_in_the_admins_pending_list(): void
    {
        $user = $this->pendingSeller();
        $id = $this->applicationId($user);

        $this->asAdmin()->get(route('admin.applications', ['type' => 'seller']))
            ->assertOk()
            ->assertSee('Maria Clara Handicrafts')
            ->assertSee('mariaclara.santos@example.com')
            ->assertSee('Pending');

        foreach (['national_id', 'dti_certificate', 'bir_2303'] as $document) {
            $this->asAdmin()->get(route('admin.applications.document', ['seller', $id, $document]))->assertOk();
        }
    }

    public function test_TC_SEL_040_admin_approves_the_application(): void
    {
        $user = $this->pendingSeller();
        $this->asAdmin()->post(route('admin.applications.approve', ['seller', $this->applicationId($user)]))->assertRedirect();

        $this->assertSame('Approved', DB::table('seller_applications')->where('user_id', $user->id)->value('status'));
        Mail::assertSent(ApplicationStatusMail::class, fn ($m) => $m->hasTo('mariaclara.santos@example.com'));

        $this->login('mariaclara.santos@example.com', 'Tindahan@2026')->assertRedirect(route('seller.dashboard'));
        $this->get(route('seller.dashboard'))->assertOk();
    }

    public function test_TC_SEL_041_admin_rejects_the_application_with_a_reason(): void
    {
        $reason = 'DTI certificate is blurred and the business name cannot be read.';
        $user = $this->pendingSeller($this->account2());
        $this->asAdmin()->post(route('admin.applications.reject', ['seller', $this->applicationId($user)]), ['admin_remarks' => $reason])->assertRedirect();

        $this->assertSame('Rejected', DB::table('seller_applications')->where('user_id', $user->id)->value('status'));
        Mail::assertSent(ApplicationStatusMail::class, fn ($m) => $m->hasTo('roberto.villanueva@example.com') && str_contains($m->render(), $reason));

        // On login the reason is shown and the dashboard stays closed.
        $this->login('roberto.villanueva@example.com', 'Gadget#Hub26')->assertSessionHas('error', fn ($m) => str_contains($m, $reason));
        $this->assertNull(session('user'));
    }

    public function test_TC_SEL_042_rejected_seller_corrects_and_resubmits(): void
    {
        $user = $this->pendingSeller($this->account2());
        $this->asAdmin()->post(route('admin.applications.reject', ['seller', $this->applicationId($user)]), ['admin_remarks' => 'DTI certificate is blurred.']);
        $this->flushSession();
        $this->travel(1)->minutes();

        // ERP flow: the rejected seller applies again from the Register page with the same email.
        $this->register($this->account2(['sec_certificate' => $this->pdf('sec_certificate_clear.pdf')]))->assertSessionHasNoErrors();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $this->assertSame(1, User::where('email', 'roberto.villanueva@example.com')->count());
        $id = $this->applicationId($user);
        $this->assertSame('Pending Verification', DB::table('seller_applications')->where('id', $id)->value('status'));
        $this->asAdmin()->get(route('admin.applications', ['type' => 'seller']))->assertSee('RV Gadget Hub');
        $this->asAdmin()->get(route('admin.applications.document', ['seller', $id, 'sec_certificate']))->assertOk();
    }

    public function test_TC_SEL_043_non_admin_opens_the_approval_page(): void
    {
        $user = $this->pendingSeller();
        $buyer = $this->makeUser('buyer');
        $session = ['user' => ['id' => $buyer->id, 'name' => $buyer->name, 'email' => $buyer->email, 'role' => 'buyer']];

        $this->withSession($session)->get(route('admin.applications', ['type' => 'seller']))->assertRedirect()->assertDontSee('Maria Clara Handicrafts');
        $this->withSession($session)->post(route('admin.applications.approve', ['seller', $this->applicationId($user)]))->assertRedirect();
        $this->assertSame('Pending Verification', DB::table('seller_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_TC_SEL_044_open_another_roles_pages(): void
    {
        $seller = $this->makeUser('seller');
        foreach (['admin.dashboard', 'logistics.dashboard', 'rider.dashboard'] as $page) {
            $response = $this->withSession(['user' => ['id' => $seller->id, 'name' => $seller->name, 'email' => $seller->email, 'role' => 'seller']])->get(route($page));
            $this->assertTrue($response->isRedirect() || $response->status() === 403, $page);
        }
    }
}
