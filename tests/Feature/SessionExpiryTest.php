<?php

namespace Tests\Feature;

use App\Support\LoginGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class SessionExpiryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private const NOTICE = 'You were logged out after being inactive for a while';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_logging_in_marks_the_browser_as_signed_in(): void
    {
        $buyer = $this->makeUser('buyer', ['email' => 'signed-in@example.com']);

        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123'])
            ->assertCookie(LoginGate::SIGNED_IN_COOKIE);
    }

    public function test_a_run_out_session_says_so_once(): void
    {
        // Signed-in cookie still there, but the session behind it is gone.
        $this->withCookie(LoginGate::SIGNED_IN_COOKIE, '1')
            ->get(route('products'))
            ->assertOk()
            ->assertSee(self::NOTICE)
            ->assertCookieExpired(LoginGate::SIGNED_IN_COOKIE);

        // Shown on that page, not again on the next (the browser no longer has the cookie).
        $this->defaultCookies = [];
        $this->get(route('products'))->assertDontSee(self::NOTICE);
    }

    public function test_it_follows_a_redirect_to_the_login_page(): void
    {
        $this->withCookie(LoginGate::SIGNED_IN_COOKIE, '1')
            ->get(route('buyer.orders'))
            ->assertRedirect();

        $this->defaultCookies = [];
        $this->get(route('login'))->assertSee(self::NOTICE);
    }

    public function test_no_notice_for_guests_or_logged_in_users(): void
    {
        $this->get(route('products'))->assertDontSee(self::NOTICE);

        $this->actingAsUser($this->makeUser())
            ->withCookie(LoginGate::SIGNED_IN_COOKIE, '1')
            ->get(route('products'))
            ->assertDontSee(self::NOTICE);
    }

    public function test_logging_out_on_purpose_clears_the_mark(): void
    {
        $this->actingAsUser($this->makeUser())
            ->withCookie(LoginGate::SIGNED_IN_COOKIE, '1')
            ->post(route('logout'))
            ->assertCookieExpired(LoginGate::SIGNED_IN_COOKIE);
    }
}
