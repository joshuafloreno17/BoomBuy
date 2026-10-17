<?php

namespace Tests\Feature\Scenarios;

use App\Mail\RiderActivationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The classmate's sheet, Rider tab: TC-RID-001 to TC-RID-032, one test each.
 * Riders here are added by an approved logistics company (Bilis Hatid
 * Express Inc.) and activate their account from an emailed link. The public
 * rider application (ERP) stays too, so TC-RID-029 is not met by design.
 */
class RiderSheetTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private User $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(10, 0));

        $this->company = $this->logisticsCompany('operations@bilishatid.example.com', 'Hatid@2026', 'Bilis Hatid Express Inc.');
    }

    private function logisticsCompany(string $email, string $password, string $business, string $status = 'Approved'): User
    {
        $user = $this->makeUser('logistics', ['email' => $email, 'password' => Hash::make($password), 'name' => $business . ' Ops']);
        DB::table('logistics_applications')->insert([
            'user_id' => $user->id, 'full_name' => $user->name, 'business_name' => $business,
            'phone' => $user->phone, 'address' => 'x', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user;
    }

    private function login(string $email, string $password)
    {
        $this->flushSession();

        return $this->post(route('logistics.login.submit'), ['email' => $email, 'password' => $password]);
    }

    private function asCompany(): static
    {
        $this->login('operations@bilishatid.example.com', 'Hatid@2026');

        return $this;
    }

    /** Account 1 (Pedro Jose Garcia) from the Test Data sheet, plus the ERP home address and sex. */
    private function pedro(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Pedro Jose',
            'last_name' => 'Garcia',
            'birthdate' => '1995-03-22',
            'email' => 'pedrojose.garcia@example.com',
            'phone' => '09451239876',
            'drivers_license_no' => 'N01-23-456789',
            'license_expiry' => '2030-03-22',
            'vehicle_type' => 'Motorcycle',
            'plate_number' => '123ABC',
            'drivers_license' => UploadedFile::fake()->create('drivers_license.jpg', 1500, 'image/jpeg'),
            'or_cr' => $this->pdf('or_cr.pdf'),
            'sex' => 'Male',
            'province' => 'Laguna',
            'city_municipality' => 'Santa Cruz',
            'barangay' => 'Poblacion',
            'street_address' => '15 P. Guevarra St',
        ], $overrides);
    }

    /** Account 2 (Mark Anthony Bautista). */
    private function mark(array $overrides = []): array
    {
        return $this->pedro(array_merge([
            'first_name' => 'Mark Anthony', 'last_name' => 'Bautista', 'birthdate' => '1999-08-09',
            'email' => 'markanthony.bautista@example.com', 'phone' => '09662345678',
            'drivers_license_no' => 'D05-19-123456', 'license_expiry' => '2031-08-09',
            'vehicle_type' => 'Van', 'plate_number' => 'NBC1234',
            'drivers_license' => $this->png('drivers_license.png'),
        ], $overrides));
    }

    private function save(array $form)
    {
        return $this->post(route('logistics.riders.store'), $form);
    }

    /** One try with fresh unique values; true when the rider was created. */
    private function added(array $overrides): bool
    {
        static $n = 0;
        $n++;
        $fresh = [
            'email' => "rider.test{$n}@example.com",
            'phone' => '0917' . str_pad((string) (1000000 + $n), 7, '0', STR_PAD_LEFT),
            'drivers_license_no' => sprintf('N%02d-23-%06d', $n % 100, 100000 + $n),
            'plate_number' => 'TST' . $n,
        ];
        $form = $this->pedro(array_merge($fresh, $overrides));
        $before = User::where('role', 'rider')->count();
        $this->save($form);

        return User::where('role', 'rider')->count() === $before + 1;
    }

    private function addPedro(): User
    {
        $this->asCompany()->save($this->pedro())->assertSessionHasNoErrors();

        return User::where('email', 'pedrojose.garcia@example.com')->firstOrFail();
    }

    /** The activation link in the latest email to this address. */
    private function activationUrl(string $email): string
    {
        return Mail::sent(RiderActivationMail::class, fn ($m) => $m->hasTo($email))->last()->url;
    }

    private function activationPath(string $url): string
    {
        return parse_url($url, PHP_URL_PATH);
    }

    private function activatePedro(): User
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $this->post($this->activationPath($this->activationUrl($rider->email)), ['password' => 'Rider@2026', 'password_confirmation' => 'Rider@2026'])
            ->assertRedirect(route('login'));

        return $rider->fresh();
    }

    // ---------------- Add Rider form ----------------

    public function test_TC_RID_001_create_a_rider_with_all_valid_data(): void
    {
        $this->asCompany()->save($this->pedro())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('logistics.riders', ['status' => 'all']));

        $rider = User::where('email', 'pedrojose.garcia@example.com')->first();
        $this->assertSame('rider', $rider->role);
        $this->assertSame('Pending Activation', $rider->status);
        $this->assertSame($this->company->id, (int) DB::table('rider_applications')->where('user_id', $rider->id)->value('created_by'));
        Mail::assertSent(RiderActivationMail::class, fn ($m) => $m->hasTo('pedrojose.garcia@example.com'));

        $this->get(route('logistics.riders', ['status' => 'all']))
            ->assertSee('Pedro Jose Garcia')
            ->assertSee('Pending Activation');
    }

    public function test_TC_RID_002_submit_a_completely_empty_form(): void
    {
        $this->asCompany()->save([])->assertSessionHasErrors(['first_name', 'last_name', 'birthdate', 'email', 'phone', 'drivers_license_no', 'license_expiry', 'vehicle_type', 'plate_number', 'drivers_license', 'or_cr']);
        $this->assertSame(0, User::where('role', 'rider')->count());
        Mail::assertNothingSent();
    }

    public function test_TC_RID_003_leave_one_required_field_blank_at_a_time(): void
    {
        $fields = ['first_name', 'last_name', 'birthdate', 'email', 'phone', 'drivers_license_no', 'license_expiry', 'vehicle_type', 'plate_number', 'drivers_license', 'or_cr'];
        $this->asCompany();

        foreach ($fields as $field) {
            $response = $this->save($this->pedro([$field => '']));
            $response->assertSessionHasErrors($field);
            $response->assertSessionDoesntHaveErrors(array_diff($fields, [$field]));
            if ($field !== 'plate_number') {
                $this->assertSame('123ABC', old('plate_number'), "values kept when $field is blank");
            }
        }
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_004_name_with_numbers_or_symbols(): void
    {
        $this->asCompany()->save($this->pedro(['first_name' => 'Pedro Jose99', 'last_name' => 'Dela Cruz@#']))
            ->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertStringContainsString('letters', session('errors')->first('first_name'));
    }

    public function test_TC_RID_005_name_length_at_the_limits(): void
    {
        $this->asCompany();
        $this->assertFalse($this->added(['first_name' => 'P']), '1 character');
        $this->assertTrue($this->added(['first_name' => 'Po']), '2 characters');
        $this->assertTrue($this->added(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonor']), '50 characters');
        $this->assertFalse($this->added(['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonora']), '51 characters');
    }

    public function test_TC_RID_006_age_at_the_18_year_limit(): void
    {
        $this->asCompany();
        $this->save($this->pedro(['birthdate' => '2008-10-13']))->assertSessionHasErrors(['birthdate' => 'The rider must be at least 18 years old.']);
        $this->assertTrue($this->added(['birthdate' => '2008-10-12']), 'turns 18 today');
        $this->assertTrue($this->added(['birthdate' => '2008-10-11']), '18 years and 1 day');
    }

    public function test_TC_RID_007_email_in_an_invalid_format(): void
    {
        $this->asCompany();
        foreach (['pedrojose.garciaexample.com', 'pedrojose.garcia@', '@example.com', 'pedrojose.garcia@example', 'pedrojose garcia@example.com'] as $email) {
            $this->save($this->pedro(['email' => $email]))->assertSessionHasErrors('email');
        }
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_008_email_already_used_by_an_account_of_another_role(): void
    {
        $this->makeUser('buyer', ['email' => 'juanmiguel.delacruz@example.com']);
        $this->asCompany()->save($this->pedro(['email' => 'juanmiguel.delacruz@example.com']))
            ->assertSessionHasErrors(['email' => 'This email is already in use.']);
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_009_mobile_number_in_an_invalid_format(): void
    {
        $this->asCompany();
        foreach (['0917ABC4567', '0917-123-456', '12345678901', '02-8123-4567'] as $phone) {
            $this->save($this->pedro(['phone' => $phone]))->assertSessionHasErrors('phone');
            $this->assertStringContainsString('valid Philippine mobile number', session('errors')->first('phone'));
        }
    }

    public function test_TC_RID_010_mobile_number_length_at_the_limits(): void
    {
        $this->asCompany();
        $this->assertFalse($this->added(['phone' => '0917123456']), '10 digits');
        $this->assertTrue($this->added(['phone' => '09170000001']), '11 digits');
        $this->assertFalse($this->added(['phone' => '091712345678']), '12 digits');
    }

    public function test_TC_RID_011_mobile_number_that_is_already_registered(): void
    {
        $this->makeUser('buyer', ['phone' => '09171234567']);
        $this->asCompany()->save($this->pedro(['phone' => '09171234567']))
            ->assertSessionHasErrors(['phone' => 'This mobile number is already in use.']);
    }

    public function test_TC_RID_012_drivers_license_number_in_an_invalid_format(): void
    {
        $this->asCompany();
        foreach (['N01-23-45678', 'N01-23-4567890', '101-23-456789', 'N0123456789X'] as $license) {
            $this->save($this->pedro(['drivers_license_no' => $license]))
                ->assertSessionHasErrors(['drivers_license_no' => "The driver's license number must be in the form A00-00-000000."]);
        }
    }

    public function test_TC_RID_013_drivers_license_number_already_registered(): void
    {
        $this->addPedro();
        $this->save($this->mark(['drivers_license_no' => 'N01-23-456789']))
            ->assertSessionHasErrors(['drivers_license_no' => "This driver's license number is already registered."]);
    }

    public function test_TC_RID_014_license_expiry_date_at_the_limit(): void
    {
        $this->asCompany();
        $this->save($this->pedro(['license_expiry' => '2026-10-11']))
            ->assertSessionHasErrors(['license_expiry' => "The driver's license is expired. Enter a license that is valid today or later."]);
        $this->assertTrue($this->added(['license_expiry' => '2026-10-12']), 'today');
        $this->assertTrue($this->added(['license_expiry' => '2026-10-13']), 'tomorrow');
    }

    public function test_TC_RID_015_plate_number_already_registered(): void
    {
        $this->addPedro();
        $this->save($this->mark(['plate_number' => '123ABC']))
            ->assertSessionHasErrors(['plate_number' => 'This plate number is already registered.']);
        // Typed with a space and in small letters it is still the same plate.
        $this->save($this->mark(['plate_number' => '123 abc']))->assertSessionHasErrors('plate_number');
    }

    public function test_TC_RID_016_upload_documents_in_each_accepted_file_type(): void
    {
        $this->asCompany();
        foreach ([['drivers_license.jpg', 'image/jpeg', 1500], ['drivers_license.png', 'image/png', 2000], ['drivers_license.pdf', 'application/pdf', 700]] as [$name, $mime, $kb]) {
            $this->assertTrue($this->added(['drivers_license' => UploadedFile::fake()->create($name, $kb, $mime)]), $name);
        }
    }

    public function test_TC_RID_017_upload_a_file_type_that_is_not_accepted(): void
    {
        $this->asCompany();
        foreach ([['price_list.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], ['store_banner.gif', 'image/gif'], ['installer.exe', 'application/x-msdownload']] as [$name, $mime]) {
            $this->save($this->pedro(['drivers_license' => UploadedFile::fake()->create($name, 400, $mime)]))
                ->assertSessionHasErrors(['drivers_license' => "The driver's license must be a JPG, PNG or PDF file."]);
        }
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_018_file_size_at_the_limit(): void
    {
        $this->asCompany();
        $this->assertTrue($this->added(['drivers_license' => UploadedFile::fake()->create('size_4.9MB.jpg', 5017, 'image/jpeg')]), '4.9 MB');
        $this->assertTrue($this->added(['drivers_license' => UploadedFile::fake()->create('size_5.0MB.jpg', 5120, 'image/jpeg')]), '5.0 MB');
        $this->save($this->pedro(['drivers_license' => UploadedFile::fake()->create('size_5.1MB.jpg', 5222, 'image/jpeg')]))
            ->assertSessionHasErrors(['drivers_license' => "The driver's license must not be larger than 5 MB."]);
    }

    public function test_TC_RID_019_click_save_several_times_quickly(): void
    {
        $this->asCompany();
        for ($i = 0; $i < 3; $i++) {
            $this->save($this->mark());
        }

        $this->assertSame(1, User::where('email', 'markanthony.bautista@example.com')->count());
        Mail::assertSentCount(1);
        // The form also locks its Save button after the first click.
        $this->get(route('logistics.riders.create'))->assertSee('enctype="multipart/form-data"', false)->assertSee('submit-btn', false)->assertSee('registration-fields.js', false);
    }

    // ---------------- Activation ----------------

    public function test_TC_RID_020_rider_activates_the_account_with_a_valid_password(): void
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $path = $this->activationPath($this->activationUrl($rider->email));

        $this->get($path)->assertOk()->assertSee('Activate your rider account');
        $this->post($path, ['password' => 'Rider@2026', 'password_confirmation' => 'Rider@2026'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertSame('Active', $rider->fresh()->status);
        $this->assertTrue(Hash::check('Rider@2026', $rider->fresh()->password));
    }

    public function test_TC_RID_021_set_a_password_that_breaks_the_password_rule(): void
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $path = $this->activationPath($this->activationUrl($rider->email));

        foreach (['Ride@26', 'rider@2026', 'RIDER@2026', 'Rider@abcd', 'Rider2026'] as $password) {
            $this->post($path, ['password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        }
        $this->assertSame('Pending Activation', $rider->fresh()->status);
    }

    public function test_TC_RID_022_confirm_password_does_not_match(): void
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $this->post($this->activationPath($this->activationUrl($rider->email)), ['password' => 'Rider@2026', 'password_confirmation' => 'Rider@2027'])
            ->assertSessionHasErrors(['password' => 'Passwords do not match.']);
        $this->assertSame('Pending Activation', $rider->fresh()->status);
    }

    public function test_TC_RID_023_open_an_expired_activation_link(): void
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $path = $this->activationPath($this->activationUrl($rider->email));

        $this->travel(24 * 60 + 1)->minutes();
        $this->get($path)->assertOk()->assertSee('This activation link has expired.')->assertDontSee('name="password"', false);
        $this->post($path, ['password' => 'Rider@2026', 'password_confirmation' => 'Rider@2026']);
        $this->assertSame('Pending Activation', $rider->fresh()->status);
    }

    public function test_TC_RID_024_logistics_resends_the_activation_email(): void
    {
        $rider = $this->addPedro();
        $first = $this->activationPath($this->activationUrl($rider->email));

        $this->get(route('logistics.riders', ['status' => 'all']))->assertSee('Resend Activation');
        $this->post(route('logistics.riders.resend-activation', $rider->id))->assertSessionHas('success');
        Mail::assertSentCount(2);
        $second = $this->activationPath($this->activationUrl($rider->email));

        $this->flushSession();
        $this->get($first)->assertSee('This activation link is invalid or has already been used.');
        $this->get($second)->assertSee('Activate your rider account');
    }

    public function test_TC_RID_025_reuse_the_activation_link_after_activating(): void
    {
        $rider = $this->addPedro();
        $this->flushSession();
        $path = $this->activationPath($this->activationUrl($rider->email));
        $this->post($path, ['password' => 'Rider@2026', 'password_confirmation' => 'Rider@2026']);

        $this->get($path)->assertSee('This activation link is invalid or has already been used.');
        $this->post($path, ['password' => 'Other@2026', 'password_confirmation' => 'Other@2026']);
        $this->assertTrue(Hash::check('Rider@2026', $rider->fresh()->password));
    }

    // ---------------- Login ----------------

    public function test_TC_RID_026_rider_logs_in_before_activating(): void
    {
        $this->addPedro();
        $this->login('pedrojose.garcia@example.com', 'Rider@2026')->assertSessionHas('error');
        $this->assertNull(session('user'));
        $this->get(route('rider.dashboard'))->assertRedirect(route('login'));
    }

    public function test_TC_RID_027_rider_logs_in_after_activating(): void
    {
        $rider = $this->activatePedro();

        $this->login('pedrojose.garcia@example.com', 'Rider@2026')->assertSessionMissing('error');
        $this->assertSame($rider->id, session('user.id'));
        $this->get(route('rider.dashboard'))->assertOk()->assertSee('Pedro Jose Garcia')->assertSee('Bilis Hatid Express Inc.');
    }

    // ---------------- Access ----------------

    public function test_TC_RID_028_rider_is_visible_only_to_the_company_that_created_it(): void
    {
        $this->addPedro();
        $this->logisticsCompany('admin@agilacargo.example.com', 'Agila#Cargo26', 'Agila Cargo Movers Corp.');

        $this->asCompany()->get(route('logistics.riders', ['status' => 'all']))->assertSee('Pedro Jose Garcia');
        $this->login('admin@agilacargo.example.com', 'Agila#Cargo26');
        $this->get(route('logistics.riders', ['status' => 'all']))->assertOk()->assertDontSee('Pedro Jose Garcia');
    }

    public function test_TC_RID_029_no_public_rider_registration(): void
    {
        // The Add Rider address sends a guest to the login page...
        $this->get(route('logistics.riders.create'))->assertRedirect(route('logistics.login'));
        $this->post(route('logistics.riders.store'), $this->pedro())->assertRedirect(route('logistics.login'));
        $this->assertSame(0, User::where('role', 'rider')->count());

        // ...but riders can still apply on their own: the ERP list keeps that (option B).
        $this->get(route('rider.apply'))->assertOk();
        $this->markTestSkipped('By design: the public rider application (ERP list) stays, so "Rider is not offered as a self-registration role" is not met.');
    }

    public function test_TC_RID_030_buyer_or_seller_opens_the_add_rider_form(): void
    {
        foreach (['buyer', 'seller'] as $role) {
            $other = $this->makeUser($role);
            $session = ['user' => ['id' => $other->id, 'name' => $other->name, 'email' => $other->email, 'role' => $role]];
            $this->flushSession();
            $this->withSession($session)->get(route('logistics.riders.create'))->assertRedirect();
            $this->withSession($session)->post(route('logistics.riders.store'), $this->pedro())->assertRedirect();
        }
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_031_pending_logistics_account_tries_to_add_a_rider(): void
    {
        $this->logisticsCompany('admin@agilacargo.example.com', 'Agila#Cargo26', 'Agila Cargo Movers Corp.', 'Pending Verification');

        $this->login('admin@agilacargo.example.com', 'Agila#Cargo26')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));
        $this->assertNull(session('user'));
        $this->get(route('logistics.riders.create'))->assertRedirect(route('logistics.login'));
        $this->post(route('logistics.riders.store'), $this->pedro())->assertRedirect(route('logistics.login'));
        $this->assertSame(0, User::where('role', 'rider')->count());
    }

    public function test_TC_RID_032_open_another_roles_pages(): void
    {
        $this->activatePedro();
        $this->login('pedrojose.garcia@example.com', 'Rider@2026');

        foreach (['logistics.dashboard', 'seller.dashboard', 'admin.dashboard'] as $page) {
            $response = $this->get(route($page));
            $this->assertTrue($response->isRedirect() || $response->status() === 403, $page);
        }
    }
}
