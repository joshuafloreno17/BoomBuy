<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The email-code page is the last step of every sign-up: it shows the steps
 * of the form just filled in, all done, and the code box.
 */
class OtpPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function pending(string $role): array
    {
        return [
            'pending_registration' => ['role' => $role, 'email' => 'new@example.com'],
            'otp_code' => '123456',
            'otp_email' => 'new@example.com',
            'otp_expires_at' => now()->addMinutes(5),
        ];
    }

    public function test_it_shows_the_sign_up_steps_done_and_the_code_box(): void
    {
        $this->withSession($this->pending('seller'))
            ->get(route('otp.show'))->assertOk()
            ->assertSee('Verify Code - BoomBuy')
            ->assertSee('Seller account')
            ->assertSee('Business details')
            ->assertSee('new@example.com')
            ->assertSee('name="otp_code"', false)
            ->assertSee('Resend');
    }

    public function test_a_wrong_code_says_so_on_the_page(): void
    {
        $this->withSession($this->pending('buyer'))
            ->from(route('otp.show'))
            ->followingRedirects()
            ->post(route('otp.verify'), ['otp_code' => '000000'])
            ->assertOk()
            ->assertSee('Invalid or expired code.');
    }

    public function test_the_boxes_page_has_the_padlock_and_six_boxes(): void
    {
        $this->withSession($this->pending('logistics'))
            ->get(route('otp.show'))->assertOk()
            ->assertSee('otp-lock', false)
            ->assertSee('id="otpForm"', false)
            ->assertSee('otp-box', false);
    }

    public function test_a_wrong_code_by_fetch_answers_json_and_stays(): void
    {
        $this->withSession($this->pending('buyer'))
            ->from(route('otp.show'))
            ->postJson(route('otp.verify'), ['otp_code' => '000000'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => 'Invalid or expired code.'])
            ->assertJsonMissing(['redirect' => route('otp.show')]);

        // The page shows it itself; it is not flashed again on the next load.
        $this->assertNull(session('error'));
    }

    public function test_an_expired_session_by_fetch_says_where_to_go(): void
    {
        $this->postJson(route('otp.verify'), ['otp_code' => '123456'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'redirect' => route('register')]);
    }

    public function test_the_right_code_by_fetch_creates_the_account_and_says_where_to_go(): void
    {
        $session = $this->pending('buyer');
        $session['pending_registration'] += [
            'name' => 'Juan Dela Cruz',
            'password' => bcrypt('Secret#123'),
        ];

        $this->withSession($session)
            ->from(route('otp.show'))
            ->postJson(route('otp.verify'), ['otp_code' => '123456'])
            ->assertOk()
            ->assertJson(['ok' => true, 'redirect' => route('login')])
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'account has been created'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'buyer']);
    }
}
