<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function adminRow(string $password = 'admin123'): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@boombuy.com',
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);
    }

    private function login(string $email, string $password)
    {
        $this->flushSession();

        return $this->post(route('login.submit'), ['email' => $email, 'password' => $password]);
    }

    public function test_fresh_install_admin_logs_in_with_the_default_password(): void
    {
        $this->login('admin@boombuy.com', 'admin123')->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(Hash::check('admin123', User::where('role', 'admin')->value('password')));
    }

    public function test_the_admin_password_comes_from_the_database(): void
    {
        $this->adminRow('s3cret-pass');

        $this->login('admin@boombuy.com', 'admin123')->assertSessionHas('error');
        $this->assertFalse((bool) session('admin_logged_in'));

        $this->login('ADMIN@boombuy.com', 's3cret-pass')->assertRedirect(route('admin.dashboard'));
        $this->assertTrue((bool) session('admin_logged_in'));

        // The old admin-only form checks the database too.
        $this->flushSession();
        $this->post(route('admin.login.submit'), ['email' => 'admin@boombuy.com', 'password' => 'admin123'])
            ->assertSessionHas('error');
    }

    public function test_account_page_loads_and_needs_the_admin(): void
    {
        $this->adminRow();

        $this->get(route('admin.account'))->assertRedirect(route('admin.login'));

        $this->actingAsAdmin()->get(route('admin.account'))
            ->assertOk()
            ->assertSee('My Account')
            ->assertSee('admin@boombuy.com');
    }

    public function test_admin_changes_name_and_login_email(): void
    {
        $admin = $this->adminRow();

        // A new email needs the current password.
        $this->actingAsAdmin()->post(route('admin.account.update'), ['name' => 'Jairo', 'email' => 'boss@boombuy.store'])
            ->assertSessionHas('error');
        $this->assertSame('admin@boombuy.com', $admin->fresh()->email);

        // The name alone does not.
        $this->post(route('admin.account.update'), ['name' => 'Jairo', 'email' => 'admin@boombuy.com'])
            ->assertSessionHas('success');
        $this->assertSame('Jairo', $admin->fresh()->name);

        $this->post(route('admin.account.update'), ['name' => 'Jairo', 'email' => 'Boss@BoomBuy.store', 'current_password' => 'admin123'])
            ->assertSessionHas('success');
        $this->assertSame('boss@boombuy.store', $admin->fresh()->email);

        // Logs in with the new email only; support messages still reach the same row.
        $this->login('admin@boombuy.com', 'admin123')->assertSessionHas('error');
        $this->login('boss@boombuy.store', 'admin123')->assertRedirect(route('admin.dashboard'));
        $this->assertSame($admin->id, \App\Support\SupportAccount::id());
    }

    public function test_admin_cannot_take_another_accounts_email(): void
    {
        $admin = $this->adminRow();
        $buyer = $this->makeUser();

        $this->actingAsAdmin()->post(route('admin.account.update'), [
            'name' => 'Admin', 'email' => $buyer->email, 'current_password' => 'admin123',
        ])->assertSessionHas('error');

        $this->assertSame('admin@boombuy.com', $admin->fresh()->email);
    }

    public function test_admin_changes_password(): void
    {
        $admin = $this->adminRow();
        $this->actingAsAdmin();

        $this->post(route('admin.account.password'), [
            'current_password' => 'wrong', 'new_password' => 'brand-new-1', 'new_password_confirmation' => 'brand-new-1',
        ])->assertSessionHas('error', 'Current password is incorrect.');

        $this->post(route('admin.account.password'), [
            'current_password' => 'admin123', 'new_password' => 'short', 'new_password_confirmation' => 'short',
        ])->assertSessionHas('error');

        $this->post(route('admin.account.password'), [
            'current_password' => 'admin123', 'new_password' => 'brand-new-1', 'new_password_confirmation' => 'different',
        ])->assertSessionHas('error', 'New passwords do not match.');

        $this->post(route('admin.account.password'), [
            'current_password' => 'admin123', 'new_password' => 'brand-new-1', 'new_password_confirmation' => 'brand-new-1',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-1', $admin->fresh()->password));
        $this->login('admin@boombuy.com', 'admin123')->assertSessionHas('error');
        $this->login('admin@boombuy.com', 'brand-new-1')->assertRedirect(route('admin.dashboard'));
    }
}
