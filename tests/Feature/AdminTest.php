<?php

namespace Tests\Feature;

use App\Mail\ApplicationStatusMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Mail::fake();
    }

    private function pendingSellerApplication(string $category = 'shoes'): array
    {
        $user = $this->makeUser('seller');

        $id = DB::table('seller_applications')->insertGetId([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'business_name' => 'New Shop',
            'phone' => $user->phone,
            'address' => $user->address,
            'business_category' => $category,
            'status' => 'Pending Verification',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $id];
    }

    public function test_admin_pages_need_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.applications'))->assertRedirect(route('admin.login'));

        // A logged-in buyer is not an admin either.
        $this->actingAsUser($this->makeUser())
            ->get(route('admin.accounts'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_wrong_admin_password_is_refused(): void
    {
        $this->post(route('admin.login.submit'), ['email' => 'admin@boombuy.com', 'password' => 'guess'])
            ->assertSessionHas('error');

        $this->assertFalse((bool) session('admin_logged_in'));
    }

    public function test_admin_pages_load(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        $orderId = $this->makeOrder($this->makeUser(), $product);
        $this->pendingSellerApplication();

        $routes = [
            route('admin.dashboard'), route('admin.accounts'), route('admin.applications'),
            route('admin.compliance'), route('admin.logistics'), route('admin.orders'),
            route('admin.order.details', $orderId), route('admin.products'),
            route('admin.products.edit', $product->id), route('admin.reports'),
            route('admin.settings'), route('admin.notifications'), route('admin.complaints'),
        ];

        foreach ($routes as $url) {
            $this->actingAsAdmin()->get($url)->assertOk();
        }
    }

    public function test_approving_a_seller_notifies_and_emails_them(): void
    {
        [$user, $id] = $this->pendingSellerApplication('shoes');

        $this->actingAsAdmin()
            ->post(route('admin.applications.approve', ['seller', $id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('seller_applications', ['id' => $id, 'status' => 'Approved', 'business_category' => 'shoes']);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'Seller Application Approved']);
        Mail::assertSent(ApplicationStatusMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_admin_can_correct_the_category_but_only_to_a_real_one(): void
    {
        [, $id] = $this->pendingSellerApplication('shoes');

        $this->actingAsAdmin()
            ->post(route('admin.applications.approve', ['seller', $id]), ['business_category' => 'not-a-category'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('seller_applications', ['id' => $id, 'status' => 'Pending Verification']);

        $this->actingAsAdmin()
            ->post(route('admin.applications.approve', ['seller', $id]), ['business_category' => 'electronics'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('seller_applications', ['id' => $id, 'status' => 'Approved', 'business_category' => 'electronics']);
    }

    public function test_rejecting_keeps_the_remarks_and_tells_the_applicant(): void
    {
        [$user, $id] = $this->pendingSellerApplication();

        $this->actingAsAdmin()
            ->post(route('admin.applications.reject', ['seller', $id]), ['admin_remarks' => 'ID photo is blurry.'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('seller_applications', ['id' => $id, 'status' => 'Rejected', 'admin_remarks' => 'ID photo is blurry.']);

        $message = DB::table('notifications')->where('user_id', $user->id)->value('message');
        $this->assertStringContainsString('ID photo is blurry.', $message);
        Mail::assertSent(ApplicationStatusMail::class);
    }

    public function test_unknown_application_type_is_a_404(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.applications.approve', ['wizard', 1]))
            ->assertNotFound();
    }

    public function test_account_status_changes_and_blocks_login(): void
    {
        $buyer = $this->makeUser();

        $this->actingAsAdmin()
            ->post(route('admin.accounts.status', $buyer->id), ['status' => 'Banana'])
            ->assertSessionHas('error');

        $this->actingAsAdmin()
            ->post(route('admin.accounts.status', $buyer->id), ['status' => 'Suspended'])
            ->assertSessionHas('success');

        $this->assertSame('Suspended', $buyer->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Account Status Updated']);

        $this->flushSession();
        $this->post(route('login.submit'), ['email' => $buyer->email, 'password' => 'password123'])
            ->assertSessionHas('error');
        $this->assertNull(session('user'));
    }

    public function test_deleting_an_account_only_deactivates_it(): void
    {
        $buyer = $this->makeUser();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()));

        $this->actingAsAdmin()->delete(route('admin.accounts.delete', $buyer->id));

        // Order history (and the seller's records) must survive.
        $this->assertSame('Deactivated', $buyer->fresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $orderId]);
    }

    public function test_flagged_product_leaves_the_shop_until_unflagged(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['name' => 'Suspicious Sneakers']);

        $this->actingAsAdmin()
            ->post(route('admin.compliance.flag', $product->id), ['flag_reason' => 'Counterfeit brand.']);

        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'type' => 'compliance_warning']);
        $this->flushSession();
        $this->get(route('products'))->assertOk()->assertDontSee('Suspicious Sneakers');

        $this->actingAsAdmin()->post(route('admin.compliance.unflag', $product->id));
        $this->flushSession();
        $this->get(route('products'))->assertSee('Suspicious Sneakers');
    }

    public function test_warning_reaches_the_seller(): void
    {
        $seller = $this->makeSeller();

        $this->actingAsAdmin()
            ->post(route('admin.compliance.warn', $seller->id), ['warning_message' => 'Please fix your listings.'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id, 'title' => 'Compliance Warning', 'message' => 'Please fix your listings.',
        ]);
    }
}
