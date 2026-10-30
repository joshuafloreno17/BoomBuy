<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The login page signs in with fetch() and asks for JSON, so it can play the
 * orb animation only after the server says yes. A plain form post still gets
 * the normal redirects (covered by the other login tests).
 */
class LoginJsonTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

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

    public function test_a_good_login_answers_with_where_to_go(): void
    {
        $email = $this->lowercase($buyer = $this->makeUser('buyer'));

        $this->postJson(route('login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertOk()
            ->assertJson(['ok' => true, 'redirect' => route('buyer.dashboard')]);

        $this->assertSame($buyer->id, session('user.id'));
    }

    public function test_only_the_first_page_after_login_builds_itself_in(): void
    {
        $email = $this->lowercase($this->makeSeller());

        $this->postJson(route('login.submit'), ['email' => $email, 'password' => 'password123'])->assertOk();

        $this->get(route('seller.dashboard'))->assertOk()->assertSee('bb-build', false);
        $this->get(route('seller.dashboard'))->assertOk()->assertDontSee('bb-build', false);
    }

    public function test_a_seller_is_sent_to_the_seller_dashboard(): void
    {
        $email = $this->lowercase($this->makeSeller());

        $this->postJson(route('login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertOk()
            ->assertJson(['ok' => true, 'redirect' => route('seller.dashboard')]);
    }

    public function test_a_wrong_password_answers_with_the_error_and_leaves_nothing_flashed(): void
    {
        $email = $this->lowercase($this->makeUser('buyer'));

        $this->postJson(route('login.submit'), ['email' => $email, 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'Invalid email or password.']);

        $this->assertNull(session('user'));
        $this->assertNull(session('error'));
    }

    public function test_the_wrong_portal_comes_back_with_a_hint(): void
    {
        $email = $this->lowercase($this->makeLogistics());

        $this->postJson(route('login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'hint' => 'logistics']);

        $this->assertNull(session('user'));
    }

    public function test_the_logistics_login_sends_staff_to_the_command_center(): void
    {
        $email = $this->lowercase($this->makeLogistics());

        $this->postJson(route('logistics.login.submit'), ['email' => $email, 'password' => 'password123'])
            ->assertOk()
            ->assertJson(['ok' => true, 'redirect' => route('logistics.dashboard')]);
    }

    public function test_the_page_has_the_orb_and_both_portals_keep_their_titles(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('never sleeps.')
            ->assertSee('id="authOrb"', false)
            ->assertSee('Login to BoomBuy');

        $this->get(route('logistics.login'))->assertOk()
            ->assertSee('on its way.')
            ->assertSee('Login to BoomBuy Logistics');
    }

    public function test_create_one_flips_to_buyer_and_seller_and_rider_only_in_the_app(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('id="authBack"', false)
            ->assertSee(route('buyer.register'), false)
            ->assertSee(route('seller.register'), false)
            ->assertDontSee(route('rider.apply'), false)
            ->assertSee('Run a sorting center?');

        $app = 'Mozilla/5.0 (Linux; Android 16) Chrome/140 Mobile Safari/537.36 BoomBuyApp/1.2.0';

        // Sorting centers sign up on BoomBuy Logistics on a computer, not in the app.
        $this->withHeader('User-Agent', $app)->get(route('login'))->assertOk()
            ->assertSee(route('rider.apply'), false)
            ->assertDontSee('Run a sorting center?');
    }

    public function test_register_is_the_login_page_opened_on_the_account_types(): void
    {
        $this->get(route('register'))->assertOk()
            ->assertSee('Create account — BoomBuy')
            ->assertSee('is-join', false)
            ->assertSee(route('buyer.register'), false);
    }

    public function test_the_logistics_login_has_no_account_types_to_flip_to(): void
    {
        $this->get(route('logistics.login'))->assertOk()
            ->assertDontSee('id="authBack"', false)
            ->assertSee(route('logistics.register'), false);
    }
}
