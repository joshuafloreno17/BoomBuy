<?php

namespace Tests\Feature\Scenarios;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\ParcelRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test Plan → "Admin" (A01–A12), plus the admin gaps from the review. */
class AdminScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Santa Cruz Kicks');
        $this->shoes = $this->makeProduct($this->seller, ['name' => 'Canvas Sneakers', 'price' => 500, 'stock' => 10]);
    }

    private function admin(): static
    {
        $this->flushSession();

        return $this->actingAsAdmin();
    }

    public function test_A01_admin_logs_in_through_the_main_login_and_sees_todays_numbers(): void
    {
        $this->post(route('login.submit'), ['email' => 'admin@boombuy.com', 'password' => 'admin123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->makeOrder($this->buyerAt(), $this->shoes, 'Pending');
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered', ['delivered_at' => now(), 'delivery_fee' => 0]);

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('ordersToday', fn ($o) => (int) $o->total === 2)
            ->assertViewHas('commissionToday', 50.0)
            ->assertViewHas('activeSellers', 1);
    }

    public function test_A02_seller_applications_need_a_line_of_business_and_rejections_carry_remarks(): void
    {
        $applicant = $this->makeUser('seller');
        $appId = DB::table('seller_applications')->insertGetId(['user_id' => $applicant->id, 'full_name' => 'A', 'phone' => '0917', 'address' => 'x', 'business_name' => 'New Shop', 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);

        $this->admin()->post(route('admin.applications.approve', ['seller', $appId]))
            ->assertSessionHas('error', 'Please choose a valid line of business before approving.');
        $this->post(route('admin.applications.approve', ['seller', $appId]), ['business_category' => 'shoes'])->assertSessionHas('success');
        $this->assertSame('shoes', DB::table('seller_applications')->where('id', $appId)->value('business_category'));
        Mail::assertSent(\App\Mail\ApplicationStatusMail::class);

        $other = $this->makeUser('seller');
        $otherId = DB::table('seller_applications')->insertGetId(['user_id' => $other->id, 'full_name' => 'B', 'phone' => '0917', 'address' => 'x', 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);
        $this->post(route('admin.applications.reject', ['seller', $otherId]), ['admin_remarks' => 'Permit expired'])->assertSessionHas('success');
        $this->assertStringContainsString('Permit expired', DB::table('notifications')->where('user_id', $other->id)->value('message'));
    }

    public function test_A03_suspending_a_seller_hides_products_and_a_rider_releases_parcels(): void
    {
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Pending');

        $this->admin()->get(route('admin.accounts', ['role' => 'seller']))->assertOk()
            ->assertViewHas('users', fn ($p) => $p->total() === 1);

        $this->post(route('admin.accounts.status', $this->seller->id), ['status' => 'Suspended'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'products are hidden') && str_contains($m, '1 open order'));
        $this->assertFalse(Product::onSale()->whereKey($this->shoes->id)->exists());

        $rider = $this->riderFor($this->laguna);
        $orderId = $this->makeOrder($this->buyerAt(), $this->shoes, 'Out for Delivery', ['delivery_rider_id' => $rider->id, 'current_center_id' => $this->laguna->id]);
        $this->post(route('admin.accounts.status', $rider->id), ['status' => 'Suspended']);
        $this->assertSame('At Sorting Center', $this->orderStatus($orderId));

        // Logged out on their next click.
        $this->flushSession();
        $this->actingAsUser($this->seller)->get(route('seller.dashboard'))->assertRedirect(route('login'));
    }

    public function test_A04_products_edit_flag_and_no_delete_with_history(): void
    {
        $this->admin()->put(route('admin.products.update', $this->shoes->id), [
            'name' => 'Canvas Sneakers v2', 'category' => 'Shoes', 'price' => 550, 'stock' => 8, 'description' => 'Updated.',
        ])->assertRedirect(route('admin.products'));
        $this->assertSame('Canvas Sneakers v2', $this->shoes->fresh()->name);

        $this->post(route('admin.compliance.flag', $this->shoes->id), ['flag_reason' => 'Fake brand'])->assertSessionHas('success');
        $this->assertFalse(Product::onSale()->whereKey($this->shoes->id)->exists());
        $this->assertTrue($this->notifiedWith($this->seller, 'Product Flagged: Canvas Sneakers v2'));

        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered');
        $this->delete(route('admin.products.delete', $this->shoes->id))->assertSessionHas('error');
        $this->assertNotNull(Product::find($this->shoes->id));
    }

    public function test_A05_admin_cancels_only_orders_still_with_the_seller(): void
    {
        $pending = $this->makeOrder($this->buyerAt(), $this->shoes, 'Pending');
        DB::table('products')->where('id', $this->shoes->id)->update(['stock' => 9]);

        $this->admin()->post(route('admin.order.cancel', $pending))->assertSessionHas('error', 'Please give a reason for cancelling this order.');
        $this->post(route('admin.order.cancel', $pending), ['reason' => 'Seller unreachable'])->assertSessionHas('success');
        $this->assertSame('Cancelled', $this->orderStatus($pending));
        $this->assertSame(10, $this->stockOf($this->shoes));

        $moving = $this->makeOrder($this->buyerAt(), $this->shoes, 'In Transit');
        $this->post(route('admin.order.cancel', $moving), ['reason' => 'x'])->assertSessionHas('error');
    }

    public function test_A05b_admin_can_act_on_a_parcel_stuck_at_a_center_for_weeks(): void
    {
        $buyer = $this->buyerAt();
        $stuck = $this->makeOrder($buyer, $this->shoes, 'At Sorting Center', ['origin_center_id' => $this->laguna->id, 'current_center_id' => $this->laguna->id, 'updated_at' => now()->subWeeks(3)]);
        $fresh = $this->makeOrder($buyer, $this->shoes, 'At Sorting Center', ['origin_center_id' => $this->laguna->id, 'current_center_id' => $this->laguna->id]);

        // A parcel that moved recently is left to the Sorting Center.
        $this->admin()->get(route('admin.order.details', $fresh))->assertViewHas('canForceReturn', false);
        $this->post(route('admin.order.force-return', $fresh), ['reason' => 'x'])->assertSessionHas('error');

        $this->get(route('admin.order.details', $stuck))->assertOk()
            ->assertViewHas('canForceReturn', true)
            ->assertSee('Stuck parcel');
        $this->post(route('admin.order.force-return', $stuck))->assertSessionHas('error', 'Please give a reason for sending this parcel back.');
        $this->post(route('admin.order.force-return', $stuck), ['reason' => 'Unclaimed for 3 weeks'])->assertSessionHas('success');

        // It goes through the normal return: waiting at the seller's center.
        $this->assertSame('Return Ready', $this->orderStatus($stuck));
        $this->assertTrue($this->notifiedWith($this->seller, 'Parcel Sent Back by BoomBuy'));
        $this->assertTrue($this->notifiedWith($buyer, 'Order Returned to Seller'));
    }

    public function test_A06_sorting_center_region_check_and_closed_center_falls_back_to_the_hub(): void
    {
        $this->admin()->post(route('admin.sorting-centers.store'), ['region' => 'ncr', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'is not in'));

        $this->post(route('admin.sorting-centers.store'), ['region' => 'calabarzon', 'province' => 'Cavite', 'city_municipality' => 'Imus City'])
            ->assertSessionHas('success');
        $cavite = SortingCenter::where('province', 'Cavite')->first();
        $this->assertNotNull($cavite);

        $this->post(route('admin.sorting-centers.toggle', $cavite->id))->assertSessionHas('success');
        $this->assertFalse($cavite->fresh()->is_active);
        // Cavite's orders now go to the CALABARZON hub (Laguna).
        $this->assertSame($this->laguna->id, ParcelRoute::centerFor('Cavite', 'Imus City')->id);
    }

    public function test_A07_staff_assignment_changes_what_they_see(): void
    {
        $staff = $this->makeLogistics();

        $this->admin()->post(route('admin.sorting-centers.staff', $staff->id), ['sorting_center_id' => $this->laguna->id])->assertSessionHas('success');
        $this->assertSame($this->laguna->id, (int) $staff->fresh()->sorting_center_id);

        $this->post(route('admin.sorting-centers.staff', $staff->id), ['sorting_center_id' => 'head-office'])->assertSessionHas('success', fn ($m) => str_contains($m, 'head office'));
        $this->assertTrue((bool) $staff->fresh()->is_head_office);

        // Empty = not assigned: no access until the admin picks a center.
        $this->post(route('admin.sorting-centers.staff', $staff->id), ['sorting_center_id' => ''])->assertSessionHas('success');
        $this->assertFalse((bool) $staff->fresh()->is_head_office);
        $this->assertNull($staff->fresh()->sorting_center_id);
    }

    public function test_A08_compliance_lists_problems_and_sends_warnings(): void
    {
        // Seller selling outside their category.
        $this->makeProduct($this->seller, ['name' => 'Phone Charger', 'category' => 'Electronics']);
        // Rider with a parcel untouched for 3 days.
        $rider = $this->riderFor($this->laguna);
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Out for Delivery', ['delivery_rider_id' => $rider->id, 'updated_at' => now()->subDays(3)]);
        // Buyer with COD paused.
        $buyer = $this->buyerAt();
        foreach (range(1, 3) as $i) {
            $this->makeOrder($buyer, $this->shoes, 'Cancelled', ['cancelled_by' => 'buyer', 'cancelled_at' => now()->subDays($i)]);
        }

        $this->admin()->get(route('admin.compliance'))->assertOk()
            ->assertViewHas('totalMismatches', 1);
        $this->get(route('admin.compliance.riders'))
            ->assertViewHas('people', fn ($p) => $p->firstWhere('user_id', $rider->id)['has_issues'] === true);
        $this->get(route('admin.compliance.buyers'))
            ->assertViewHas('people', fn ($p) => $p->firstWhere('user_id', $buyer->id)['cod_blocked'] === true);

        $this->post(route('admin.compliance.warn', $this->seller->id), ['warning_message' => 'Remove the charger listing.'])->assertSessionHas('success');
        $this->assertTrue($this->notifiedWith($this->seller, 'Compliance Warning'));
    }

    public function test_A09_complaint_status_and_notes_reach_the_complainant(): void
    {
        $buyer = $this->buyerAt();
        $complaint = \App\Models\Complaint::create(['complainant_id' => $buyer->id, 'complainant_role' => 'buyer', 'subject' => 'Late', 'description' => 'Late parcel', 'status' => 'Pending']);

        $this->admin()->post(route('admin.complaints.update', $complaint->id), ['status' => 'Resolved', 'admin_notes' => 'Rider coached.'])->assertSessionHas('success');
        $this->assertTrue($this->notifiedWith($buyer, 'Complaint Update'));

        $this->flushSession();
        $this->actingAsUser($buyer)->get(route('complaints.index'))->assertSee('Rider coached.');
    }

    public function test_A10_admin_and_seller_reports_agree(): void
    {
        PlatformSetting::set('commission_rate', '10');
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered');
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered', ['voucher_code' => 'X', 'discount_amount' => 0]);

        $admin = $this->admin()->get(route('admin.reports'))->assertOk();
        $row = $admin->viewData('sellerSales')->firstWhere('seller_id', $this->seller->id);

        $this->flushSession();
        $seller = $this->actingAsUser($this->seller)->get(route('seller.reports', ['from' => now()->subYear()->toDateString()]));

        $this->assertEquals($seller->viewData('totalSales'), $row['sales']);
        $this->assertEquals($seller->viewData('commissionOwed'), $row['commission']);
    }

    public function test_A11_settings_fees_announcement_and_policies(): void
    {
        $this->admin()->post(route('admin.settings.commission.update'), ['commission_rate' => 150])->assertSessionHasErrors('commission_rate');
        $this->post(route('admin.settings.delivery-fee.update'), ['delivery_fee' => 40, 'delivery_fee_province' => 70, 'delivery_fee_island' => 100, 'delivery_fee_far' => 150])->assertSessionHas('success');
        $this->assertEquals(40.0, \App\Support\DeliveryFee::zoneFee('local'));

        $this->post(route('admin.settings.announcements.store'), ['title' => 'Holiday cut-off', 'message' => 'No pickups on Nov 1.'])->assertSessionHas('success');
        $this->post(route('admin.settings.policies.update'), ['terms_policy' => 'New terms', 'privacy_policy' => 'P', 'return_policy' => 'R'])->assertSessionHas('success');

        $this->flushSession();
        $this->actingAsUser($this->seller)->get(route('seller.dashboard'))->assertSee('Holiday cut-off');

        $announcement = PlatformAnnouncement::first();
        $this->admin()->post(route('admin.settings.announcements.toggle', $announcement->id));
        $this->flushSession();
        $this->actingAsUser($this->seller)->get(route('seller.dashboard'))->assertDontSee('Holiday cut-off');
    }

    public function test_A12_admin_messages_a_seller_as_boombuy_support(): void
    {
        $this->admin()->getJson(route('messages.recipients', ['q' => 'Santa']))->assertOk()
            ->assertJson(fn ($json) => $json->has('people', 1)->etc());

        $this->post(route('messages.store', $this->seller->id), ['message' => 'Please update your permit.'])->assertRedirect();

        $this->flushSession();
        $this->actingAsUser($this->seller)->get(route('messages.index'))->assertOk()->assertSee('BoomBuy Support');

        // Only the admin may list recipients.
        $this->getJson(route('messages.recipients'))->assertForbidden();
    }
}
