<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The classmate's black-box sheet "Account Creation" (TC-BUY / TC-SEL /
 * TC-LOG / TC-RID), run against the forms the ERP list asks for. Cases for
 * fields the system does not have (TIN, Seller Type, SEC No., service
 * areas, postal code) are left out on purpose.
 */
class RegistrationSheetTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private const ROLES = ['buyer', 'seller', 'logistics', 'rider'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function route(string $role): string
    {
        return route($role === 'rider' ? 'rider.apply.submit' : $role . '.register.submit');
    }

    /** A complete, valid form for the role (Account 1 of the sheet's Test Data). */
    private function form(string $role, array $overrides = []): array
    {
        $base = [
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan Miguel',
            'sex' => 'Male',
            'birthdate' => '1998-05-14',
            'email' => $role . Str::lower(Str::random(6)) . '@example.com',
            'phone' => '0917' . random_int(1000000, 9999999),
            'province' => 'Laguna',
            'city_municipality' => 'Santa Cruz',
            'barangay' => 'Poblacion',
            'street_address' => 'Blk 12 Lot 8, Sampaguita St.',
            'password' => 'Mamimili@2026',
            'password_confirmation' => 'Mamimili@2026',
            'terms' => '1',
        ];

        $documents = match ($role) {
            'buyer' => ['id_photo' => $this->png('govt_id_front.png')],
            'seller' => ['business_name' => 'Shop ' . Str::random(6), 'business_category' => 'home-living', 'seller_type' => 'Individual', 'tin' => sprintf('%03d-%03d-%03d-000', random_int(100, 999), random_int(100, 999), random_int(100, 999)), 'region' => 'Region IV-A (CALABARZON)', 'postal_code' => '4009', 'national_id' => $this->png('govt_id_front.png'), 'business_permit' => $this->pdf('dti_certificate.pdf')],
            'logistics' => ['business_name' => 'Bilis Hatid ' . Str::random(6), 'id_photo' => $this->png('govt_id_front.png'), 'business_permit' => $this->pdf('mayors_permit.pdf'), 'region' => 'Region IV-A (CALABARZON)', 'postal_code' => '4009', 'sec_registration_no' => 'CS' . random_int(100000000, 999999999), 'tin' => sprintf('%03d-%03d-%03d-000', random_int(100, 999), random_int(100, 999), random_int(100, 999)), 'position' => 'Operations Manager', 'service_areas' => ['Region IV-A (CALABARZON)'], 'sec_certificate' => $this->pdf('sec.pdf'), 'bir_2303' => $this->pdf('bir.pdf'),],
            'rider' => ['vehicle_type' => 'Motorcycle', 'vehicle_model' => 'Honda Click', 'plate_number' => '123ABC', 'drivers_license_no' => 'N01-23-456789', 'license_expiry' => now()->addYears(3)->toDateString(), 'national_id' => $this->png('id.png'), 'drivers_license' => $this->png('drivers_license.png'), 'profile_selfie' => $this->png('selfie.png'), 'proof_of_address' => $this->pdf('bill.pdf'), 'or_cr' => $this->pdf('or_cr.pdf')],
        };

        return array_merge($base, $documents, $overrides);
    }

    /** Whether the form went through (an OTP is waiting) — then clears it for the next try. */
    private function accepted(string $role, array $form): bool
    {
        $this->flushSession();
        $this->post($this->route($role), $form);
        $ok = session()->has('otp_code') && session()->has('pending_registration');
        $this->flushSession();

        return $ok;
    }

    private function file(string $name, int $kb, string $mime): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, $mime);
    }

    // ---------------- 001: valid data ----------------

    public function test_001_valid_registration_sends_a_six_digit_otp_for_every_role(): void
    {
        foreach (self::ROLES as $role) {
            $form = $this->form($role);
            $this->post($this->route($role), $form)->assertSessionHasNoErrors();
            $this->assertMatchesRegularExpression('/^\d{6}$/', (string) session('otp_code'), $role);
            Mail::assertSent(\App\Mail\OtpMail::class, fn ($m) => $m->hasTo($form['email']));
            $this->flushSession();
        }
    }

    // ---------------- 002 / 003: required fields ----------------

    public function test_002_empty_form_is_blocked(): void
    {
        foreach (self::ROLES as $role) {
            $this->post($this->route($role), [])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'password']);
            $this->assertNull(session('otp_code'), $role);
            $this->flushSession();
        }
    }

    public function test_003_each_required_field_left_blank_is_named(): void
    {
        foreach (self::ROLES as $role) {
            foreach (['first_name', 'last_name', 'email', 'phone', 'birthdate', 'password'] as $field) {
                $this->post($this->route($role), $this->form($role, [$field => '']))->assertSessionHasErrors($field);
                $this->assertSame(old('first_name'), $field === 'first_name' ? null : 'Juan Miguel', "$role keeps typed values");
                $this->flushSession();
            }
        }
    }

    // ---------------- 004–006: names ----------------

    public function test_004_names_with_numbers_or_symbols_are_rejected(): void
    {
        foreach (self::ROLES as $role) {
            $this->post($this->route($role), $this->form($role, ['first_name' => 'Juan Miguel123', 'last_name' => 'Dela Cruz@#']))
                ->assertSessionHasErrors(['first_name', 'last_name']);
            $this->flushSession();
        }
    }

    public function test_005_names_with_enye_and_punctuation_are_saved_as_typed(): void
    {
        $form = $this->form('buyer', ['first_name' => 'Niño Angelo', 'last_name' => "Peñaflor-O'Hara"]);
        $this->post($this->route('buyer'), $form)->assertSessionHasNoErrors();
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);

        $user = User::where('email', $form['email'])->first();
        $this->assertSame('Niño Angelo', $user->first_name);
        $this->assertSame("Peñaflor-O'Hara", $user->last_name);
    }

    public function test_006_name_length_limits(): void
    {
        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['first_name' => 'J'])), "$role 1 char");
            $this->assertTrue($this->accepted($role, $this->form($role, ['first_name' => 'Jo'])), "$role 2 chars");
            $this->assertTrue($this->accepted($role, $this->form($role, ['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonor'])), "$role 50 chars");
            $this->assertFalse($this->accepted($role, $this->form($role, ['first_name' => 'Maria Cristina Angelica Bernadette Josefina Leonora'])), "$role 51 chars");
        }
    }

    // ---------------- 007 / 008: date of birth ----------------

    public function test_007_age_limit_is_18_for_every_role(): void
    {
        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['birthdate' => now()->subYears(18)->addDay()->toDateString()])), "$role 17");
            $this->assertTrue($this->accepted($role, $this->form($role, ['birthdate' => now()->subYears(18)->toDateString()])), "$role turns 18 today");
            $this->assertTrue($this->accepted($role, $this->form($role, ['birthdate' => now()->subYears(18)->subDay()->toDateString()])), "$role 18 + 1 day");
        }
    }

    public function test_008_birthday_in_the_future_is_rejected(): void
    {
        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['birthdate' => now()->addDay()->toDateString()])), $role);
        }
    }

    // ---------------- 009–012: email ----------------

    public function test_009_invalid_email_formats_are_rejected(): void
    {
        foreach (self::ROLES as $role) {
            foreach (['juanmiguel.delacruzexample.com', 'juanmiguel.delacruz@', '@example.com', 'juanmiguel.delacruz@example', 'juanmiguel delacruz@example.com'] as $email) {
                $this->assertFalse($this->accepted($role, $this->form($role, ['email' => $email])), "$role $email");
            }
        }
    }

    public function test_010_011_registered_email_any_case_and_any_role_is_blocked(): void
    {
        $existing = $this->makeUser('buyer', ['email' => 'juanmiguel.delacruz@example.com']);

        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['email' => $existing->email])), "$role same");
            $this->assertFalse($this->accepted($role, $this->form($role, ['email' => 'JUANMIGUEL.DELACRUZ@EXAMPLE.COM'])), "$role caps");
        }
    }

    public function test_012_spaces_around_the_email_are_removed(): void
    {
        $this->post($this->route('buyer'), $this->form('buyer', ['email' => '  buyer.test01@example.com  ']))->assertSessionHasNoErrors();
        $this->assertSame('buyer.test01@example.com', session('pending_registration.email'));
    }

    // ---------------- 013–016: mobile number ----------------

    public function test_013_014_mobile_number_format_and_length(): void
    {
        foreach (self::ROLES as $role) {
            foreach (['0917ABC4567', '0917-123-456', '12345678901', '02-8123-4567', '0917123456', '091712345678'] as $phone) {
                $this->assertFalse($this->accepted($role, $this->form($role, ['phone' => $phone])), "$role $phone");
            }
            $this->assertTrue($this->accepted($role, $this->form($role, ['phone' => '09170000001'])), "$role 11 digits");
            $this->assertTrue($this->accepted($role, $this->form($role, ['phone' => '+639170000001'])), "$role +63");
        }
    }

    public function test_015_016_registered_mobile_number_in_either_format_is_blocked(): void
    {
        $this->makeUser('buyer', ['phone' => '09171234567']);
        // Saved before numbers were normalized.
        $this->makeUser('seller', ['phone' => '+639189876543']);

        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['phone' => '09171234567'])), "$role 09");
            $this->assertFalse($this->accepted($role, $this->form($role, ['phone' => '+639171234567'])), "$role +63");
            $this->assertFalse($this->accepted($role, $this->form($role, ['phone' => '09189876543'])), "$role old +63 row");
        }
    }

    public function test_plus63_number_is_stored_as_09(): void
    {
        $this->post($this->route('buyer'), $this->form('buyer', ['phone' => '+639170000002']))->assertSessionHasNoErrors();
        $this->assertSame('09170000002', session('pending_registration.phone'));
    }

    // ---------------- 017–019: password ----------------

    public function test_017_018_019_password_rules(): void
    {
        foreach (self::ROLES as $role) {
            foreach (['Bili@26', 'mamimili@2026', 'MAMIMILI@2026', 'Mamimili@abcd', 'Mamimili2026'] as $bad) {
                $this->assertFalse($this->accepted($role, $this->form($role, ['password' => $bad, 'password_confirmation' => $bad])), "$role $bad");
            }
            $this->assertTrue($this->accepted($role, $this->form($role, ['password' => 'Bili@202', 'password_confirmation' => 'Bili@202'])), "$role 8 chars");
            $this->assertFalse($this->accepted($role, $this->form($role, ['password_confirmation' => 'Mamimili@2027'])), "$role mismatch");
        }
    }

    public function test_020_password_field_is_hidden_with_a_toggle(): void
    {
        $this->get(route('buyer.register'))->assertSee('type="password"', false)->assertSee('password-toggle', false);
    }

    // ---------------- 021: terms ----------------

    public function test_021_terms_must_be_ticked(): void
    {
        foreach (self::ROLES as $role) {
            $this->assertFalse($this->accepted($role, $this->form($role, ['terms' => null])), $role);
        }
    }

    // ---------------- Rider application: license and plate ----------------

    public function test_rider_application_checks_license_number_expiry_and_duplicates(): void
    {
        $this->assertFalse($this->accepted('rider', $this->form('rider', ['drivers_license_no' => 'N01-23-45678'])), 'license format');
        $this->assertFalse($this->accepted('rider', $this->form('rider', ['license_expiry' => now()->subDay()->toDateString()])), 'expired license');
        $this->assertTrue($this->accepted('rider', $this->form('rider', ['license_expiry' => now()->toDateString()])), 'expires today');

        $existing = $this->makeUser('rider');
        DB::table('rider_applications')->insert([
            'user_id' => $existing->id, 'full_name' => 'R', 'phone' => $existing->phone, 'address' => 'x',
            'vehicle_type' => 'Motorcycle', 'vehicle_model' => 'Beat', 'plate_number' => 'ABC 1234',
            'drivers_license_no' => 'N01-23-456789', 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertFalse($this->accepted('rider', $this->form('rider', ['plate_number' => 'abc-1234'])), 'same plate');
        $this->assertFalse($this->accepted('rider', $this->form('rider', ['drivers_license_no' => 'n0123456789'])), 'same license');
    }

    // ---------------- Seller / logistics business names ----------------

    public function test_SEL_021_022_shop_name_unique_and_3_to_30_characters(): void
    {
        $seller = $this->makeUser('seller');
        DB::table('seller_applications')->insert(['user_id' => $seller->id, 'full_name' => 'M', 'business_name' => 'Maria Clara Handicrafts', 'phone' => '0917', 'address' => 'x', 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertFalse($this->accepted('seller', $this->form('seller', ['business_name' => 'maria clara handicrafts'])), 'duplicate');
        $this->assertFalse($this->accepted('seller', $this->form('seller', ['business_name' => 'MC'])), '2 chars');
        $this->assertTrue($this->accepted('seller', $this->form('seller', ['business_name' => 'MCH'])), '3 chars');
        $this->assertTrue($this->accepted('seller', $this->form('seller', ['business_name' => 'Maria Clara Handicrafts Manila'])), '30 chars');
        $this->assertFalse($this->accepted('seller', $this->form('seller', ['business_name' => 'Maria Clara Handicrafts, Manila'])), '31 chars');
    }

    public function test_LOG_021_company_name_already_registered(): void
    {
        $company = $this->makeUser('logistics');
        DB::table('logistics_applications')->insert(['user_id' => $company->id, 'full_name' => 'C', 'business_name' => 'Bilis Hatid Express Inc.', 'phone' => '0917', 'address' => 'x', 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertFalse($this->accepted('logistics', $this->form('logistics', ['business_name' => 'BILIS HATID EXPRESS INC.'])));
        $this->assertTrue($this->accepted('logistics', $this->form('logistics', ['business_name' => 'Agila Cargo Movers Corp.'])));
    }

    // ---------------- Documents ----------------

    public function test_documents_accept_jpg_png_pdf_and_reject_other_types(): void
    {
        $field = ['buyer' => 'id_photo', 'seller' => 'national_id', 'logistics' => 'id_photo', 'rider' => 'drivers_license'];

        foreach (self::ROLES as $role) {
            foreach ([['govt_id_front.jpg', 'image/jpeg'], ['govt_id_front.png', 'image/png'], ['drivers_license.pdf', 'application/pdf']] as [$name, $mime]) {
                $this->assertTrue($this->accepted($role, $this->form($role, [$field[$role] => $this->file($name, 1200, $mime)])), "$role $name");
            }
            foreach ([['price_list.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], ['store_banner.gif', 'image/gif'], ['installer.exe', 'application/x-msdownload']] as [$name, $mime]) {
                $this->assertFalse($this->accepted($role, $this->form($role, [$field[$role] => $this->file($name, 400, $mime)])), "$role $name");
            }
        }
    }

    public function test_documents_size_limit_is_5_mb(): void
    {
        foreach (self::ROLES as $role) {
            $field = ['buyer' => 'id_photo', 'seller' => 'national_id', 'logistics' => 'id_photo', 'rider' => 'or_cr'][$role];
            $this->assertTrue($this->accepted($role, $this->form($role, [$field => $this->file('size_4.9MB.pdf', 5017, 'application/pdf')])), "$role 4.9");
            $this->assertTrue($this->accepted($role, $this->form($role, [$field => $this->file('size_5.0MB.pdf', 5120, 'application/pdf')])), "$role 5.0");
            $this->assertFalse($this->accepted($role, $this->form($role, [$field => $this->file('size_5.1MB.pdf', 5121, 'application/pdf')])), "$role 5.1");
        }
    }

    // ---------------- OTP ----------------

    public function test_OTP_correct_wrong_bad_format_expired_and_resend(): void
    {
        $form = $this->form('seller');
        $this->post($this->route('seller'), $form);
        $first = session('otp_code');
        $wrong = $first === '000000' ? '111111' : '000000';

        // Wrong code and wrong formats: nothing is created.
        foreach ([$wrong, '48291', '4829137', '48A91B', ''] as $code) {
            $this->post(route('otp.verify'), ['otp_code' => $code])->assertSessionHas('error');
        }
        $this->assertFalse(User::where('email', $form['email'])->exists());

        // Resend: the first code stops working, the new one works.
        $this->post(route('otp.resend'));
        $second = session('otp_code');
        if ($second !== $first) {
            $this->post(route('otp.verify'), ['otp_code' => $first])->assertSessionHas('error');
        }

        // Expired at 5:01.
        $this->travel(5)->minutes();
        $this->travel(1)->seconds();
        $this->post(route('otp.verify'), ['otp_code' => $second])->assertSessionHas('error');
        $this->assertFalse(User::where('email', $form['email'])->exists());

        // A fresh code within 5 minutes verifies; seller waits for admin approval.
        $this->post(route('otp.resend'));
        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));
        $user = User::where('email', $form['email'])->first();
        $this->assertSame('Pending Verification', DB::table('seller_applications')->where('user_id', $user->id)->value('status'));
    }

    public function test_cannot_log_in_before_verifying_the_email(): void
    {
        $form = $this->form('buyer');
        $this->post($this->route('buyer'), $form);
        $this->post(route('login.submit'), ['email' => $form['email'], 'password' => $form['password']]);
        $this->assertNull(session('user'));
    }

    // ---------------- Approval, rejection, resubmit ----------------

    public function test_rejected_seller_and_logistics_can_correct_and_resubmit(): void
    {
        foreach (['seller', 'logistics'] as $role) {
            $form = $this->form($role);
            $this->post($this->route($role), $form);
            $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
            $user = User::where('email', $form['email'])->first();
            $appId = DB::table($role . '_applications')->where('user_id', $user->id)->value('id');

            $this->withSession(['admin_logged_in' => true])
                ->post(route('admin.applications.reject', [$role, $appId]), ['reason' => 'Document is blurred.']);
            $this->assertSame('Rejected', DB::table($role . '_applications')->where('id', $appId)->value('status'));
            $this->flushSession();

            // Same email and phone, new document.
            $this->post($this->route($role), $this->form($role, ['email' => $form['email'], 'phone' => $form['phone'], 'business_permit' => $this->pdf('fixed.pdf')]))->assertSessionHasNoErrors();
            $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
            $this->assertSame('Pending Verification', DB::table($role . '_applications')->where('user_id', $user->id)->latest('id')->value('status'), $role);
            $this->flushSession();
        }
    }

    // ---------------- Access control ----------------

    public function test_each_role_is_kept_out_of_the_other_dashboards(): void
    {
        $pages = ['buyer' => 'buyer.dashboard', 'seller' => 'seller.dashboard', 'logistics' => 'logistics.dashboard', 'rider' => 'rider.dashboard'];

        foreach (self::ROLES as $role) {
            $user = $this->makeUser($role);
            foreach ($pages + ['admin' => 'admin.dashboard'] as $other => $page) {
                if ($other === $role) {
                    continue;
                }
                $this->flushSession();
                $response = $this->withSession(['user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $role]])->get(route($page));
                $this->assertTrue($response->isRedirect() || $response->status() === 403, "$role opened $page");
            }
        }
    }
}
