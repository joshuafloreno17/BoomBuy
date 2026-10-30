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
}
