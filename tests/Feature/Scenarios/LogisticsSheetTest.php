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
 * The classmate's sheet, Logistics tab: TC-LOG-001 to TC-LOG-044, one test
 * each, with the sheet's own test data (Account 1 = Bilis Hatid Express Inc.).
 * The form's "business_name" is the Company Name, first/last name the contact
 * person, "id_photo" the representative's ID and "business_permit" the
 * Mayor's / Business Permit.
 */
class LogisticsSheetTest extends TestCase
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

    /** Account 1 from the Test Data sheet, plus the ERP fields (sex, birthday). */
    private function account1(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Bilis Hatid Express Inc.',
            'sec_registration_no' => 'CS202012345',
            'tin' => '234-567-890-000',
            'region' => 'National Capital Region (NCR)',
            'province' => 'Metro Manila (NCR)',
            'city_municipality' => 'Pasig',
            'barangay' => 'Kapitolyo',
            'street_address' => 'Unit 5B, 88 West Capitol Drive',
            'postal_code' => '1603',
            'first_name' => 'Carlos Antonio',
            'last_name' => 'Mendoza',
            'position' => 'Operations Manager',
            'email' => 'operations@bilishatid.example.com',
            'phone' => '09285551234',
            'service_areas' => ['National Capital Region (NCR)', 'Region IV-A (CALABARZON)'],
            'sec_certificate' => $this->pdf('sec_certificate.pdf'),
            'bir_2303' => $this->pdf('bir_2303.pdf'),
            'business_permit' => $this->pdf('mayors_permit.pdf'),
            'id_photo' => UploadedFile::fake()->create('govt_id_front.jpg', 1200, 'image/jpeg'),
            'password' => 'Hatid@2026',
            'password_confirmation' => 'Hatid@2026',
            'terms' => '1',
            'sex' => 'Male',
            'birthdate' => '1988-01-15',
        ], $overrides);
    }

    /** Account 2 (Agila Cargo Movers Corp.). */
    private function account2(array $overrides = []): array
    {
        return $this->account1(array_merge([
            'business_name' => 'Agila Cargo Movers Corp.', 'sec_registration_no' => 'CS201998765', 'tin' => '567-890-123-000',
            'region' => 'Region XI (Davao Region)', 'province' => 'Davao del Sur', 'city_municipality' => 'Davao City',
            'barangay' => 'Buhangin', 'street_address' => 'Door 3, 210 Sampaguita St.', 'postal_code' => '8000',
            'first_name' => 'Liza Mae', 'last_name' => 'Fernandez', 'position' => 'General Manager',
            'email' => 'admin@agilacargo.example.com', 'phone' => '09396667890',
            'service_areas' => ['Region XI (Davao Region)'], 'sex' => 'Female',
            'id_photo' => $this->png('govt_id_front.png'), 'password' => 'Agila#Cargo26', 'password_confirmation' => 'Agila#Cargo26',
        ], $overrides));
    }

    private function register(array $form)
    {
        return $this->post(route('logistics.register.submit'), $form);
    }

    private function accepted(array $overrides): bool
    {
        $this->flushSession();
        $this->register($this->account1($overrides));
        $ok = session()->has('otp_code');
        $this->flushSession();

        return $ok;
    }

    private function pendingCompany(?array $form = null): User
    {
        $form ??= $this->account1();
        $this->register($form);
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
        $this->flushSession();

        return User::where('email', trim($form['email']))->firstOrFail();
    }

    private function applicationId(User $user): int
    {
        return (int) DB::table('logistics_applications')->where('user_id', $user->id)->latest('id')->value('id');
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

    /** The registration form has an input with this name. */
    private function formHas(string $name): bool
    {
        $html = $this->get(route('logistics.register'))->getContent();

        return str_contains($html, 'name="' . $name . '"') || str_contains($html, 'name="' . $name . '[]"');
    }

    private function assertFormHas(array $names): void
    {
        $missing = array_values(array_filter($names, fn ($n) => !$this->formHas($n)));
        $this->assertSame([], $missing, 'The registration form has no field for: ' . implode(', ', $missing));
    }

    public function test_TC_LOG_001_register_with_all_valid_data(): void
    {
        $this->register($this->account1())->assertSessionHasNoErrors()->assertRedirect(route('otp.show'));
        Mail::assertSent(OtpMail::class, fn ($m) => $m->hasTo('operations@bilishatid.example.com'));

        // Every field of the sheet's form must be on the page.
        $this->assertFormHas(['sec_registration_no', 'tin', 'region', 'postal_code', 'position', 'service_areas', 'sec_certificate', 'bir_2303']);
    }

    public function test_TC_LOG_002_submit_a_completely_empty_form(): void
    {
        $this->register([])->assertSessionHasErrors(['business_name', 'first_name', 'last_name', 'email', 'phone', 'province', 'id_photo', 'business_permit', 'password', 'terms']);
        Mail::assertNothingSent();
        $this->assertSame(0, User::count());
    }

    public function test_TC_LOG_003_leave_one_required_field_blank_at_a_time(): void
    {
        $fields = ['business_name', 'sec_registration_no', 'tin', 'region', 'province', 'city_municipality', 'barangay', 'street_address', 'postal_code', 'first_name', 'last_name', 'position', 'email', 'phone', 'service_areas', 'sec_certificate', 'bir_2303', 'business_permit', 'id_photo', 'password'];
        $notBlocked = [];

        foreach ($fields as $field) {
            $this->flushSession();
            $this->register($this->account1([$field => '']));
            if (session()->has('otp_code')) {
                $notBlocked[] = $field;
            }
        }
        $this->assertSame([], $notBlocked, 'Registration went through with these left blank: ' . implode(', ', $notBlocked));
    }

    public function test_TC_LOG_004_name_with_numbers_or_symbols(): void
    {
        $this->register($this->account1(['first_name' => 'Carlos Antonio7', 'last_name' => 'Dela Cruz@#']))
            ->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertStringContainsString('letters', session('errors')->first('first_name'));
    }

    public function test_TC_LOG_005_name_with_enye_and_allowed_punctuation(): void
    {
        $user = $this->pendingCompany($this->account1(['first_name' => 'Iñigo Rafael', 'last_name' => "Muñoz-D'Angelo"]));
        $this->assertSame('Iñigo Rafael', $user->first_name);
        $this->assertSame("Muñoz-D'Angelo", $user->last_name);
    }

    public function test_TC_LOG_006_name_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['first_name' => 'C']), '1 character');
        $this->assertTrue($this->accepted(['first_name' => 'Co']), '2 characters');
        $this->assertTrue($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonor']), '50 characters');
        $this->assertFalse($this->accepted(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonora']), '51 characters');
    }

    public function test_TC_LOG_007_email_in_an_invalid_format(): void
    {
        foreach (['operations.bilishatidexample.com', 'operations.bilishatid@', '@example.com', 'operations.bilishatid@example', 'operations bilishatid@example.com'] as $email) {
            $this->assertFalse($this->accepted(['email' => $email]), $email);
        }
        Mail::assertNothingSent();
    }

    public function test_TC_LOG_008_email_that_is_already_registered(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['email' => 'operations@bilishatid.example.com']))
            ->assertSessionHas('error', 'Email is already registered.');
    }

    public function test_TC_LOG_009_registered_email_typed_in_capital_letters(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['email' => 'OPERATIONS@BILISHATID.EXAMPLE.COM']))
            ->assertSessionHas('error', 'Email is already registered.');
    }

    public function test_TC_LOG_010_email_with_spaces_before_and_after(): void
    {
        $this->pendingCompany($this->account1(['email' => '  logistics.test01@example.com  ']));
        $this->assertTrue(User::where('email', 'logistics.test01@example.com')->exists());
    }

    public function test_TC_LOG_011_mobile_number_in_an_invalid_format(): void
    {
        foreach (['0917ABC4567', '0917-123-456', '12345678901', '02-8123-4567'] as $phone) {
            $this->flushSession();
            $this->register($this->account1(['phone' => $phone]))->assertSessionHasErrors('phone');
        }
    }

    public function test_TC_LOG_012_mobile_number_length_at_the_limits(): void
    {
        $this->assertFalse($this->accepted(['phone' => '0917123456']), '10 digits');
        $this->assertTrue($this->accepted(['phone' => '09170000001']), '11 digits');
        $this->assertFalse($this->accepted(['phone' => '091712345678']), '12 digits');
    }

    public function test_TC_LOG_013_mobile_number_that_is_already_registered(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['phone' => '09285551234']))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_LOG_014_registered_number_entered_in_plus63_format(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['phone' => '+639285551234']))
            ->assertSessionHas('error', 'This phone number is already registered.');
    }

    public function test_TC_LOG_015_password_length_at_the_limit(): void
    {
        $this->register($this->account1(['password' => 'Hati@26', 'password_confirmation' => 'Hati@26']))
            ->assertSessionHasErrors(['password' => 'Password must be at least 8 characters.']);
        $this->assertTrue($this->accepted(['password' => 'Hatid@26', 'password_confirmation' => 'Hatid@26']), '8 characters');
    }

    public function test_TC_LOG_016_password_missing_a_required_character_type(): void
    {
        foreach (['hatid@2026', 'HATID@2026', 'Hatid@abcd', 'Hatid2026'] as $password) {
            $this->flushSession();
            $this->register($this->account1(['password' => $password, 'password_confirmation' => $password]))->assertSessionHasErrors('password');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_LOG_017_confirm_password_does_not_match(): void
    {
        $this->register($this->account1(['password_confirmation' => 'Hatid@2027']))
            ->assertSessionHasErrors(['password' => 'Passwords do not match.']);
    }

    public function test_TC_LOG_018_password_is_hidden_while_typing(): void
    {
        $this->get(route('logistics.register'))->assertSee('type="password"', false)->assertSee('class="password-toggle"', false);
    }

    public function test_TC_LOG_019_register_without_accepting_the_terms(): void
    {
        $this->register($this->account1(['terms' => null]))
            ->assertSessionHasErrors(['terms' => 'Please agree to the Terms & Conditions and Privacy Policy.']);
    }

    public function test_TC_LOG_020_click_register_several_times_quickly(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->register($this->account2());
        }
        Mail::assertSentCount(1);
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));
        $this->assertSame(1, User::where('email', 'admin@agilacargo.example.com')->count());
    }

    public function test_TC_LOG_021_company_name_already_registered(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['business_name' => 'Bilis Hatid Express Inc.']))
            ->assertSessionHas('error', 'This company is already registered.');
    }

    public function test_TC_LOG_022_sec_registration_number_in_an_invalid_format(): void
    {
        foreach (['CS20201234', '202012345CS', 'CS2020-12345', '12345678901'] as $sec) {
            $this->assertFalse($this->accepted(['sec_registration_no' => $sec]), "SEC No. $sec was accepted");
        }
    }

    public function test_TC_LOG_023_sec_registration_number_already_registered(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['sec_registration_no' => 'CS202012345']));
        $this->assertNull(session('otp_code'), 'A second company registered with the same SEC No.');
    }

    public function test_TC_LOG_024_select_more_than_one_service_area(): void
    {
        $user = $this->pendingCompany($this->account1(['service_areas' => ['National Capital Region (NCR)', 'Region III (Central Luzon)', 'Region IV-A (CALABARZON)']]));
        $this->assertFormHas(['service_areas']);
        $saved = (string) DB::table('logistics_applications')->where('user_id', $user->id)->value('service_areas');
        $this->assertStringContainsString('Region III (Central Luzon)', $saved);
    }

    public function test_TC_LOG_025_tin_in_an_invalid_format_or_length(): void
    {
        $this->assertFalse($this->accepted(['tin' => '123-456-789-00']), '11 digits accepted');
        $this->assertFalse($this->accepted(['tin' => '123-456-789-0001']), '13 digits accepted');
        $this->assertFalse($this->accepted(['tin' => '123-456-78A-000']), 'letters accepted');
    }

    public function test_TC_LOG_026_tin_that_is_already_registered(): void
    {
        $this->pendingCompany();
        $this->register($this->account2(['tin' => '234-567-890-000']));
        $this->assertNull(session('otp_code'), 'A second company registered with the same TIN.');
    }

    public function test_TC_LOG_027_address_dropdowns_depend_on_each_other(): void
    {
        $this->assertFormHas(['region']);
        $this->assertFalse($this->accepted(['region' => 'Region VII (Central Visayas)', 'province' => 'Metro Manila (NCR)']), 'A Metro Manila address was accepted under Region VII.');
    }

    public function test_TC_LOG_028_postal_code_length_and_format(): void
    {
        $this->assertFalse($this->accepted(['postal_code' => '160']), '3 digits accepted');
        $this->assertFalse($this->accepted(['postal_code' => '16030']), '5 digits accepted');
        $this->assertFalse($this->accepted(['postal_code' => '16A3']), 'letters accepted');
    }

    public function test_TC_LOG_029_upload_documents_in_each_accepted_file_type(): void
    {
        foreach ([UploadedFile::fake()->create('govt_id_front.jpg', 1200, 'image/jpeg'), $this->png('govt_id_front.png')] as $id) {
            $this->assertTrue($this->accepted(['id_photo' => $id]), $id->getClientOriginalName());
        }
        $this->assertFormHas(['sec_certificate', 'bir_2303', 'business_permit', 'id_photo']);
    }

    public function test_TC_LOG_030_upload_a_file_type_that_is_not_accepted(): void
    {
        foreach ([['price_list.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], ['store_banner.gif', 'image/gif'], ['installer.exe', 'application/x-msdownload']] as [$name, $mime]) {
            $this->flushSession();
            $this->register($this->account1(['id_photo' => UploadedFile::fake()->create($name, 400, $mime)]))
                ->assertSessionHasErrors(['id_photo' => 'The valid ID must be a JPG, PNG or PDF file.']);
        }
    }

    public function test_TC_LOG_031_file_size_at_the_limit(): void
    {
        $this->assertTrue($this->accepted(['id_photo' => UploadedFile::fake()->create('size_4.9MB.jpg', 5017, 'image/jpeg')]), '4.9 MB');
        $this->assertTrue($this->accepted(['id_photo' => UploadedFile::fake()->create('size_5.0MB.jpg', 5120, 'image/jpeg')]), '5.0 MB');
        $this->register($this->account1(['id_photo' => UploadedFile::fake()->create('size_5.1MB.jpg', 5222, 'image/jpeg')]))
            ->assertSessionHasErrors(['id_photo' => 'The valid ID must not be larger than 5 MB.']);
    }

    public function test_TC_LOG_032_verify_with_the_correct_otp(): void
    {
        $this->register($this->account1());
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', fn ($m) => str_contains(strtolower($m), 'approv'));
        $user = User::where('email', 'operations@bilishatid.example.com')->first();
        $this->assertSame('Pending Verification', DB::table('logistics_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_TC_LOG_033_verify_with_an_incorrect_otp(): void
    {
        $this->register($this->account1());
        $wrong = session('otp_code') === '000000' ? '111111' : '000000';
        $this->post(route('otp.verify'), ['otp_code' => $wrong])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
    }

    public function test_TC_LOG_034_otp_in_the_wrong_format(): void
    {
        $this->register($this->account1());
        foreach (['48291', '4829137', '48A91B', ''] as $code) {
            $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error');
        }
        $this->assertSame(0, User::count());
    }

    public function test_TC_LOG_035_verify_with_an_expired_otp(): void
    {
        $this->register($this->account1());
        $code = session('otp_code');
        $this->travel(5 * 60 + 1)->seconds();
        $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error', 'Invalid or expired code.');
        $this->assertSame(0, User::count());
        $this->get(route('otp.show'))->assertSee('Resend');
    }

    public function test_TC_LOG_036_resend_otp(): void
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
    }

    public function test_TC_LOG_037_log_in_before_verifying_the_email(): void
    {
        $this->register($this->account1());
        $this->post(route('login.submit'), ['email' => 'operations@bilishatid.example.com', 'password' => 'Hatid@2026'])->assertSessionHas('error');
        $this->assertNull(session('user'));
    }

    public function test_TC_LOG_038_pending_logistics_logs_in(): void
    {
        $this->pendingCompany();
        $this->login('operations@bilishatid.example.com', 'Hatid@2026')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));
        $this->assertNull(session('user'));
        $this->get(route('logistics.riders.create'))->assertRedirect(route('login'));
    }

    public function test_TC_LOG_039_new_application_appears_in_the_admins_pending_list(): void
    {
        $user = $this->pendingCompany();
        $id = $this->applicationId($user);

        $this->asAdmin()->followingRedirects()->get(route('admin.applications', ['type' => 'logistics']))
            ->assertOk()->assertSee('Bilis Hatid Express Inc.')->assertSee('operations@bilishatid.example.com')->assertSee('Pending')
            // All details of the sheet's form, as entered.
            ->assertSee('CS202012345')->assertSee('234-567-890-000')->assertSee('Operations Manager');

        foreach (['id_photo', 'business_permit', 'sec_certificate', 'bir_2303'] as $document) {
            $this->asAdmin()->get(route('admin.applications.document', ['logistics', $id, $document]))->assertOk();
        }
    }

    public function test_TC_LOG_040_admin_approves_the_application(): void
    {
        $user = $this->pendingCompany();
        $this->asAdmin()->post(route('admin.applications.approve', ['logistics', $this->applicationId($user)]))->assertRedirect();

        $this->assertSame('Approved', DB::table('logistics_applications')->where('user_id', $user->id)->value('status'));
        Mail::assertSent(ApplicationStatusMail::class, fn ($m) => $m->hasTo('operations@bilishatid.example.com'));

        $this->login('operations@bilishatid.example.com', 'Hatid@2026')->assertSessionMissing('error');
        $this->get(route('logistics.dashboard'))->assertOk();
    }

    public function test_TC_LOG_041_admin_rejects_the_application_with_a_reason(): void
    {
        $reason = "Mayor's permit expired on 12/31/2025.";
        $user = $this->pendingCompany($this->account2());
        $this->asAdmin()->post(route('admin.applications.reject', ['logistics', $this->applicationId($user)]), ['admin_remarks' => $reason])->assertRedirect();

        $this->assertSame('Rejected', DB::table('logistics_applications')->where('user_id', $user->id)->value('status'));
        Mail::assertSent(ApplicationStatusMail::class, fn ($m) => $m->hasTo('admin@agilacargo.example.com') && str_contains(html_entity_decode($m->render(), ENT_QUOTES), $reason));

        $this->login('admin@agilacargo.example.com', 'Agila#Cargo26')->assertSessionHas('error', fn ($m) => str_contains($m, $reason));
        $this->assertNull(session('user'));
    }

    public function test_TC_LOG_042_rejected_logistics_corrects_and_resubmits(): void
    {
        $user = $this->pendingCompany($this->account2());
        $this->asAdmin()->post(route('admin.applications.reject', ['logistics', $this->applicationId($user)]), ['admin_remarks' => "Mayor's permit expired."]);
        $this->flushSession();
        $this->travel(1)->minutes();

        // ERP flow: apply again from the Register page with the same email and the new permit.
        $this->register($this->account2(['business_permit' => $this->pdf('mayors_permit_2026.pdf')]))->assertSessionHasNoErrors();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $this->assertSame(1, User::where('email', 'admin@agilacargo.example.com')->count());
        $id = $this->applicationId($user);
        $this->assertSame('Pending Verification', DB::table('logistics_applications')->where('id', $id)->value('status'));
        $this->asAdmin()->followingRedirects()->get(route('admin.applications', ['type' => 'logistics']))->assertSee('Agila Cargo Movers Corp.');
        $this->asAdmin()->get(route('admin.applications.document', ['logistics', $id, 'business_permit']))->assertOk();
    }

    public function test_TC_LOG_043_non_admin_opens_the_approval_page(): void
    {
        $user = $this->pendingCompany();
        $buyer = $this->makeUser('buyer');
        $session = ['user' => ['id' => $buyer->id, 'name' => $buyer->name, 'email' => $buyer->email, 'role' => 'buyer']];

        $this->withSession($session)->get(route('admin.applications', ['type' => 'logistics']))->assertRedirect()->assertDontSee('Bilis Hatid Express Inc.');
        $this->withSession($session)->post(route('admin.applications.approve', ['logistics', $this->applicationId($user)]))->assertRedirect();
        $this->assertSame('Pending Verification', DB::table('logistics_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_TC_LOG_044_open_another_roles_pages(): void
    {
        $company = $this->makeUser('logistics');
        foreach (['admin.dashboard', 'seller.dashboard', 'rider.dashboard'] as $page) {
            $response = $this->withSession(['user' => ['id' => $company->id, 'name' => $company->name, 'email' => $company->email, 'role' => 'logistics']])->get(route($page));
            $this->assertTrue($response->isRedirect() || $response->status() === 403, $page);
        }
    }
}
