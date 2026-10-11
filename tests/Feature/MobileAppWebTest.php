<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The Android app shows this website. Inside it, only buyers, sellers and
 * riders may stay signed in; browsers are never affected.
 */
class MobileAppWebTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private const APP = 'Mozilla/5.0 (Linux; Android 16) Chrome/140 Mobile Safari/537.36 BoomBuyApp/1.2.0';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function loginFrom(string $userAgent, User $user)
    {
        $user->forceFill(['email' => strtolower($user->email)])->save();

        return $this->withHeader('User-Agent', $userAgent)
            ->post(route('login.submit'), ['email' => $user->email, 'password' => 'password123']);
    }

    public function test_logistics_cannot_stay_signed_in_inside_the_app(): void
    {
        $this->loginFrom(self::APP, $this->makeLogistics())
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', MobileAccess::DESKTOP_ONLY_MESSAGE);

        $this->assertNull(session('user'));
    }

    public function test_the_admin_cannot_stay_signed_in_inside_the_app(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@boombuy.com', 'password' => bcrypt('password123'), 'role' => 'admin']);

        $this->loginFrom(self::APP, $admin)->assertRedirect(route('login'))->assertSessionHas('error', MobileAccess::DESKTOP_ONLY_MESSAGE);
        $this->assertFalse((bool) session('admin_logged_in'));

        // An admin session that somehow exists is ended on the next page.
        $this->withHeader('User-Agent', self::APP)->withSession(['admin_logged_in' => true])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_buyers_sellers_and_riders_use_the_website_inside_the_app(): void
    {
        $this->loginFrom(self::APP, $this->makeUser('buyer'))->assertRedirect(route('buyer.dashboard'));
        $this->flushSession();
        $this->loginFrom(self::APP, $this->makeSeller())->assertRedirect(route('seller.dashboard'));
        $this->flushSession();
        $this->loginFrom(self::APP, $this->makeRider())->assertRedirect(route('rider.dashboard'));
    }

    public function test_browsers_are_not_affected(): void
    {
        $this->loginFrom('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140 Safari/537.36', $this->makeLogistics())
            ->assertRedirect(route('logistics.dashboard'));

        $this->flushSession();
        $this->loginFrom('Mozilla/5.0 (Linux; Android 16) Chrome/140 Mobile Safari/537.36', $this->makeLogistics())
            ->assertRedirect(route('logistics.dashboard'));
    }
}
