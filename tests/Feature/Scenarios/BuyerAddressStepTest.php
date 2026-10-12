<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use App\Support\PhLocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The sign-up address step: region, barangay list, street / building, postal code. */
class BuyerAddressStepTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Mail::fake();
        Storage::fake('local');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz', 'first_name' => 'Juan', 'sex' => 'Male', 'birthdate' => '1998-05-14',
            'email' => 'juan.address@example.com', 'phone' => '09171234567',
            'region' => 'Region IV-A (CALABARZON)', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz',
            'barangay' => 'Patimbao', 'street' => '123 Rizal Street',
            'building' => 'Camella Homes', 'postal_code' => '4009',
            'street_address' => '',
            'password' => 'Mamimili@2026', 'password_confirmation' => 'Mamimili@2026', 'terms' => '1',
            'id_photo' => $this->png('id.png'),
        ], $overrides);
    }

    public function test_the_address_step_has_the_postal_form_fields(): void
    {
        $this->get(route('buyer.register'))
            ->assertOk()
            ->assertSee('id="region"', false)
            ->assertSee('<select id="barangay" name="barangay"', false)
            ->assertDontSee('id="house_number"', false)
            ->assertSee('id="street"', false)
            ->assertSee('Building / Subdivision')
            ->assertDontSee('Unit / Floor')
            ->assertSee('Automatically filled when available');
    }

    public function test_barangay_and_postal_code_data_cover_the_towns(): void
    {
        $barangays = json_decode(file_get_contents(public_path('js/data/barangays.json')), true);
        $postal = json_decode(file_get_contents(public_path('js/data/postal-codes.json')), true);

        $this->assertContains('Patimbao', $barangays['Laguna']['Santa Cruz']);
        $this->assertGreaterThan(800, count($barangays['Metro Manila (NCR)']['Manila']));
        $this->assertSame('4009', $postal['towns']['Laguna']['Santa Cruz']);
        $this->assertSame('1100', $postal['towns']['Metro Manila (NCR)']['Quezon City']);
        $this->assertSame('6000', $postal['towns']['Cebu']['Cebu City']);
    }

    public function test_the_parts_become_one_street_line_with_the_postal_code(): void
    {
        $this->post(route('buyer.register.submit'), $this->form())->assertSessionHasNoErrors();
        $this->assertSame('123 Rizal Street, Camella Homes', session('pending_registration.street_address'));
        $this->assertSame('123 Rizal Street, Camella Homes, Patimbao, Santa Cruz, Laguna 4009', session('pending_registration.address'));

        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')]);
        $user = User::where('email', 'juan.address@example.com')->first();
        $this->assertSame('Patimbao', $user->barangay);

        // The Sorting Center lookup still reads the town and province.
        $this->assertSame(['province' => 'Laguna', 'city' => 'Santa Cruz'], PhLocations::locate($user->address));
    }

    public function test_optional_parts_can_be_left_empty_and_the_postal_code_must_be_4_digits(): void
    {
        $this->post(route('buyer.register.submit'), $this->form(['building' => '', 'postal_code' => '']))->assertSessionHasNoErrors();
        $this->assertSame('123 Rizal Street', session('pending_registration.street_address'));
        $this->assertSame('123 Rizal Street, Patimbao, Santa Cruz, Laguna', session('pending_registration.address'));

        $this->flushSession();
        $this->post(route('buyer.register.submit'), $this->form(['email' => 'other@example.com', 'phone' => '09171234568', 'postal_code' => '40A9']))
            ->assertSessionHasErrors('postal_code');

        $this->flushSession();
        $this->post(route('buyer.register.submit'), $this->form(['email' => 'third@example.com', 'phone' => '09171234569', 'street' => '']))
            ->assertSessionHasErrors('street_address');
    }
}
