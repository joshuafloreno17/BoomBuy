<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * BoomBuy Logistics (/logistics) is its own portal: own landing page, login
 * and sign-up for logistics staff. Buyers, sellers and the admin sign in on
 * the marketplace (/login). Riders work only in the BoomBuy app.
 */
class LogisticsPortalTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private const APP = 'Mozilla/5.0 (Linux; Android 16) Chrome/140 Mobile Safari/537.36 BoomBuyApp/1.2.0';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        config(['app.riders_app_only' => true]);
    }

    private function lowercase(User $user): string
    {
        $user->forceFill(['email' => strtolower($user->email)])->save();

        return $user->email;
    }

    public function test_the_portal_has_its_own_landing_login_and_sign_up(): void
    {
        $this->get('/logistics')->assertOk()
            ->assertSee('Every parcel.')
            ->assertSee(route('logistics.login'), false)
            ->assertSee(route('logistics.register'), false)
            ->assertDontSee(route('rider.apply'), false)
            ->assertDontSee(route('app.download'), false)
            ->assertDontSee('For riders');

        $this->get('/logistics/login')->assertOk()
            ->assertSee('Login to BoomBuy Logistics')
            ->assertSee(route('logistics.login.submit'), false)
            ->assertDontSee('Rider?');

        // No chooser: sign-up is the sorting center form itself.
        $this->get('/logistics/register')->assertOk()
            ->assertSee(route('logistics.register.submit'), false)
            ->assertDontSee(route('rider.apply'), false);
    }

    public function test_the_marketplace_sign_up_offers_buyer_and_seller_and_rider_only_in_the_app(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee(route('buyer.register'), false)
            ->assertSee(route('seller.register'), false)
            ->assertDontSee(route('rider.apply'), false)
            ->assertDontSee(route('logistics.register'), false)
            ->assertSee(route('logistics.home'), false);

        $this->withHeader('User-Agent', self::APP)->get('/register')->assertOk()
            ->assertSee(route('rider.apply'), false);
    }

    public function test_logistics_signs_in_on_the_portal_only(): void
    {
        $email = $this->lowercase($logistics = $this->makeLogistics());

        $this->post(route('login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertSessionHas('error')
            ->assertSessionHas('portal_hint', 'logistics');
        $this->assertNull(session('user'));

        $this->post(route('logistics.login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertRedirect(route('logistics.dashboard'));
        $this->assertSame($logistics->id, session('user.id'));
    }

    public function test_buyers_sellers_and_the_admin_cannot_sign_in_on_the_portal(): void
    {
        foreach ([$this->makeUser('buyer'), $this->makeSeller()] as $user) {
            $this->flushSession();

            $this->post(route('logistics.login.submit'), ['email' => $this->lowercase($user), 'password' => 'password123'])
                ->assertSessionHas('error')
                ->assertSessionHas('portal_hint', 'market');
            $this->assertNull(session('user'));
        }

        $this->flushSession();
        $this->post(route('logistics.login.submit'), ['email' => 'admin@boombuy.com', 'password' => 'admin123'])
            ->assertSessionHas('error');
        $this->assertNull(session('admin_logged_in'));
    }

    public function test_riders_cannot_sign_in_or_open_rider_pages_in_a_browser(): void
    {
        $email = $this->lowercase($rider = $this->makeRider());

        foreach (['login.submit', 'logistics.login.submit'] as $form) {
            $this->post(route($form), ['email' => $email, 'password' => 'password123'])
                ->assertSessionHas('error', MobileAccess::RIDER_APP_ONLY_MESSAGE)
                ->assertSessionHas('portal_hint', 'app');
            $this->assertNull(session('user'));
        }

        $this->get(route('rider.apply'))->assertRedirect(route('app.download'));
        $this->get(route('rider.dashboard'))->assertRedirect(route('app.download'));

        // A rider still signed in from before is signed out.
        $this->actingAsUser($rider)->get(route('home'))->assertRedirect(route('app.download'));
        $this->assertNull(session('user'));

        $this->get(route('app.download'))->assertOk()->assertSee('Riders use the BoomBuy app');
    }

    public function test_riders_sign_in_with_the_normal_login_inside_the_app(): void
    {
        $email = $this->lowercase($rider = $this->makeRider());

        $this->withHeader('User-Agent', self::APP)
            ->post(route('login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertRedirect(route('rider.dashboard'));
        $this->assertSame($rider->id, session('user.id'));

        $this->withHeader('User-Agent', self::APP)->get(route('rider.dashboard'))->assertOk();
        $this->withHeader('User-Agent', self::APP)->post(route('logout'))->assertRedirect(route('login'));

        $this->withHeader('User-Agent', self::APP)->get(route('rider.apply'))->assertOk();
    }

    public function test_signed_in_logistics_skip_the_landing_and_log_out_to_the_portal(): void
    {
        $logistics = $this->makeLogistics();

        $this->actingAsUser($logistics)->get('/logistics')->assertRedirect(route('logistics.dashboard'));
        $this->actingAsUser($logistics)->post(route('logout'))->assertRedirect(route('logistics.login'));

        $this->get(route('logistics.dashboard'))->assertRedirect(route('logistics.login'));
        $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
    }

    public function test_old_rider_links_still_work(): void
    {
        $this->get('/rider/deliveries')->assertRedirect('/logistics/rider/deliveries')->assertStatus(301);
        $this->assertSame(url('/logistics/rider/deliveries'), route('rider.deliveries'));
    }

    public function test_approval_emails_link_to_the_right_place(): void
    {
        $cases = [
            'logistics' => route('logistics.login'),
            'rider' => route('app.download'),
            'seller' => route('login'),
            'buyer' => route('login'),
        ];

        foreach ($cases as $role => $url) {
            (new \App\Mail\ApplicationStatusMail('Juan', $role, 'Approved'))->assertSeeInHtml($url);
        }

        (new \App\Mail\ApplicationStatusMail('Juan', 'logistics', 'Rejected', 'Blurry permit'))
            ->assertDontSeeInHtml(route('logistics.login'));
    }

    public function test_forgot_password_from_the_portal_stays_on_the_portal(): void
    {
        $this->get(route('logistics.login'))->assertSee(route('password.request', ['portal' => 'logistics']), false);

        $this->get(route('password.request', ['portal' => 'logistics']))->assertOk()
            ->assertSee('BoomBuy Logistics Account')
            ->assertSee('href="' . route('logistics.login') . '"', false);

        // Straight to /forgot-password: the marketplace page again.
        $this->get(route('password.request'))->assertOk()
            ->assertDontSee('BoomBuy Logistics Account')
            ->assertSee('href="' . route('login') . '"', false);
    }

    public function test_the_verify_code_page_shows_logistics_when_signing_up_for_it(): void
    {
        $this->withSession(['pending_registration' => ['role' => 'logistics', 'email' => 'hub@example.com'], 'otp_email' => 'hub@example.com'])
            ->get(route('otp.show'))->assertOk()
            ->assertSee('Verify Code - BoomBuy Logistics')
            ->assertSee('<span class="bb-portal-tag">Logistics</span>', false);

        $this->withSession(['pending_registration' => ['role' => 'seller', 'email' => 'shop@example.com'], 'otp_email' => 'shop@example.com'])
            ->get(route('otp.show'))->assertOk()
            ->assertDontSee('<span class="bb-portal-tag">Logistics</span>', false);
    }
}
