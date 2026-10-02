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

    public function test_deactivating_an_account_keeps_its_history(): void
    {
        $buyer = $this->makeUser();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()));

        $this->actingAsAdmin()->post(route('admin.accounts.status', $buyer->id), ['status' => 'Deactivated']);

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

    public function test_admin_orders_can_be_searched_and_filtered_by_tab(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $alice = $this->makeUser('buyer', ['name' => 'Alice Reyes']);
        $bob = $this->makeUser('buyer', ['name' => 'Bob Cruz']);

        $aliceOrder = $this->makeOrder($alice, $product, 'Pending');
        $bobOrder = $this->makeOrder($bob, $product, 'Delivered');

        $this->actingAsAdmin()->get(route('admin.orders', ['q' => 'Alice']))
            ->assertOk()
            ->assertSee('Order #' . $aliceOrder)
            ->assertDontSee('Order #' . $bobOrder);

        $this->actingAsAdmin()->get(route('admin.orders', ['q' => '#' . $bobOrder]))
            ->assertSee('Order #' . $bobOrder)
            ->assertDontSee('Order #' . $aliceOrder);

        $this->actingAsAdmin()->get(route('admin.orders', ['tab' => 'delivered']))
            ->assertSee('Order #' . $bobOrder)
            ->assertDontSee('Order #' . $aliceOrder);
    }

    public function test_admin_cancel_restocks_and_needs_a_reason(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['stock' => 4]);
        $buyer = $this->makeUser();
        $orderId = $this->makeOrder($buyer, $product, 'Processing');

        $this->actingAsAdmin()->post(route('admin.order.cancel', $orderId), ['reason' => ''])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Processing']);

        $this->actingAsAdmin()->post(route('admin.order.cancel', $orderId), ['reason' => 'Suspected fraud'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Cancelled', 'cancelled_by' => 'admin']);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Order Cancelled']);
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'title' => 'Order Cancelled by Admin']);
    }

    public function test_admin_cannot_cancel_once_the_parcel_left_the_seller(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $orderId = $this->makeOrder($this->makeUser(), $product, 'Out for Delivery');

        $this->actingAsAdmin()->post(route('admin.order.cancel', $orderId), ['reason' => 'Test'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Out for Delivery']);
    }

    public function test_admin_accounts_filter_by_role_and_search(): void
    {
        $buyer = $this->makeUser('buyer', ['name' => 'Carla Buyer']);
        $rider = $this->makeRider();

        $this->actingAsAdmin()->get(route('admin.accounts', ['role' => 'rider']))
            ->assertOk()
            ->assertSee($rider->email)
            ->assertDontSee($buyer->email);

        $this->actingAsAdmin()->get(route('admin.accounts', ['q' => 'Carla']))
            ->assertSee($buyer->email)
            ->assertDontSee($rider->email);
    }

    public function test_saved_policies_are_shown_to_users(): void
    {
        // Built-in text until the admin saves their own.
        $this->get(route('policies'))->assertOk()->assertSee('Return Window');

        $this->actingAsAdmin()->post(route('admin.settings.policies.update'), [
            'terms_policy' => 'Custom terms for BoomBuy shoppers.',
            'privacy_policy' => '',
            'return_policy' => 'Returns accepted within 3 days.',
        ])->assertSessionHas('success');

        $this->get(route('policies'))
            ->assertSee('Custom terms for BoomBuy shoppers.')
            ->assertSee('Returns accepted within 3 days.')
            ->assertSee('Information We Collect'); // privacy left empty → default

        $this->get(route('login'))->assertSee('Custom terms for BoomBuy shoppers.');
    }

    public function test_applications_open_on_pending_and_can_be_searched(): void
    {
        [$pendingUser] = $this->pendingSellerApplication();
        $approvedSeller = $this->makeSeller('electronics', 'Volt Shop');

        $this->actingAsAdmin()->get(route('admin.applications'))
            ->assertOk()
            ->assertSee($pendingUser->email)
            ->assertDontSee($approvedSeller->email);

        $this->actingAsAdmin()->get(route('admin.applications', ['type' => 'seller', 'status' => 'all', 'q' => 'Volt']))
            ->assertSee($approvedSeller->email)
            ->assertDontSee($pendingUser->email);
    }

    public function test_admin_products_filter_by_status_and_seller(): void
    {
        $seller = $this->makeSeller('shoes', 'Stride Footwear');
        $seller->update(['name' => 'Stride Owner']); // no digits, so the ID search below can't match it
        $live = $this->makeProduct($seller, ['name' => 'Runner One']);
        $this->makeProduct($seller, ['name' => 'Old Boot', 'is_archived' => true]);

        $this->actingAsAdmin()->get(route('admin.products', ['state' => 'archived']))
            ->assertOk()
            ->assertSee('Old Boot')
            ->assertDontSee('Runner One');

        $this->actingAsAdmin()->get(route('admin.products', ['q' => $seller->name]))
            ->assertSee('Runner One')
            ->assertSee('Old Boot');

        $this->actingAsAdmin()->get(route('admin.products', ['q' => (string) $live->id]))
            ->assertSee('Runner One')
            ->assertDontSee('Old Boot');
    }

    public function test_notifications_are_paginated(): void
    {
        $buyer = $this->makeUser();

        foreach (range(1, 25) as $i) {
            \App\Models\Notification::create([
                'user_id' => $buyer->id, 'title' => "Note {$i}", 'message' => 'x', 'type' => 'order',
            ]);
        }

        $response = $this->actingAsUser($buyer)->get(route('notifications'))->assertOk();

        $this->assertSame(20, $response->viewData('notifications')->count());
        $this->assertSame(25, $response->viewData('unreadCount'));
    }

    public function test_compliance_opens_on_sellers_that_need_attention(): void
    {
        $offender = $this->makeSeller('shoes', 'Mixed Bag Shop');
        $this->makeProduct($offender, ['name' => 'Phone Case', 'category' => 'Electronics']);
        $this->makeProduct($offender, ['name' => 'Fake Kicks', 'category' => 'Shoes', 'is_flagged' => true, 'flag_reason' => 'Counterfeit.']);
        $tidy = $this->makeSeller('electronics', 'Tidy Tech');

        $response = $this->actingAsAdmin()->get(route('admin.compliance'))->assertOk();

        $this->assertSame('issues', $response->viewData('view'));
        $this->assertSame(['Mixed Bag Shop'], $response->viewData('sellers')->pluck('shop')->all());

        // A flagged product is counted once (as flagged), not also as a mismatch.
        $this->assertSame(1, $response->viewData('totalMismatches'));
        $this->assertSame(1, $response->viewData('totalFlagged'));

        // Category names are searchable too.
        $this->actingAsAdmin()->get(route('admin.compliance', ['view' => 'all', 'q' => 'electronics']))
            ->assertSee('Tidy Tech')
            ->assertDontSee('Mixed Bag Shop');

        $this->assertNotNull($tidy);
    }

    public function test_compliance_shows_everyone_when_there_is_nothing_to_fix(): void
    {
        $this->makeSeller('shoes', 'Clean Shop');

        $response = $this->actingAsAdmin()->get(route('admin.compliance'))->assertOk();

        $this->assertSame('all', $response->viewData('view'));
        $response->assertSee('Clean Shop');
    }
}
