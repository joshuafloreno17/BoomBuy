<?php

namespace Tests\Feature;

use App\Support\SellerBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The admin findings of the full-system review (2026-11-06): A1 dashboard
 * commission = Reports, A2 paying a seller's payout request, A3 sending the
 * refund of a cancelled paid order, A4 compliance pages check the admin first.
 */
class AdminReviewFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** A1: a BoomBuy voucher and the delivery fee don't change BoomBuy's commission on the dashboard. */
    public function test_dashboard_commission_matches_reports(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 5]);
        $this->makeOrder($this->makeUser(), $product, 'Completed', [
            'total_amount' => 900, 'platform_discount' => 100, 'shipping_discount' => 50, 'delivery_fee' => 50,
            'commission_rate' => 10, 'delivered_at' => now(), 'buyer_received_at' => now(),
        ]);

        $dashboard = $this->actingAsAdmin()->get(route('admin.dashboard'))->assertOk();

        $this->assertEquals(100.0, $dashboard->viewData('commissionToday'));
        $this->assertEquals(100.0, SellerBalance::summary($seller->id)['commission']);
        $this->assertEquals(100.0, collect($dashboard->viewData('salesByMonth'))->last()['commission']);
    }

    /** A2: the admin marks a payout request paid, with a reference, once. */
    public function test_admin_pays_a_payout_request(): void
    {
        $seller = $this->makeSeller();
        $id = DB::table('seller_payout_requests')->insertGetId([
            'seller_id' => $seller->id, 'amount' => 500, 'status' => 'Pending', 'method' => 'GCash',
            'account_name' => 'Juan Dela Cruz', 'account_number' => '09171234567', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsAdmin()->get(route('admin.payouts'))->assertOk()->assertSee('₱500.00')->assertSee('09171234567');

        $this->actingAsAdmin()->post(route('admin.payouts.paid', $id), ['reference' => ''])->assertSessionHas('error');
        $this->actingAsAdmin()->post(route('admin.payouts.paid', $id), ['reference' => 'GCASH-123'])->assertSessionHas('success');
        $this->actingAsAdmin()->post(route('admin.payouts.paid', $id), ['reference' => 'GCASH-123'])->assertSessionHas('error');

        $row = DB::table('seller_payout_requests')->find($id);
        $this->assertSame(['Paid', 'GCASH-123'], [$row->status, $row->reference]);
        $this->assertNotNull($row->paid_at);
        $this->assertSame(500.0, SellerBalance::balances($seller->id)['paid_out']);
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'title' => 'Payout Sent']);

        // Not the admin: no page, no action.
        $this->flushSession();
        $this->get(route('admin.payouts'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.payouts.paid', $id), ['reference' => 'X'])->assertRedirect(route('admin.login'));
    }

    /** A3: the refund of a cancelled paid order is marked sent. */
    public function test_admin_marks_a_cancelled_order_refunded(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 5]);
        $orderId = $this->makeOrder($buyer, $product, 'Cancelled', [
            'payment_method' => 'GCash', 'payment_status' => 'Refund Pending', 'refund_amount' => 1000, 'total_amount' => 1000,
        ]);

        $this->actingAsAdmin()->get(route('admin.payouts'))->assertOk()->assertSee('#' . $orderId);
        $this->actingAsAdmin()->post(route('admin.orders.refunded', $orderId), ['reference' => ''])->assertSessionHas('error');
        $this->actingAsAdmin()->post(route('admin.orders.refunded', $orderId), ['reference' => 'GCASH-999'])->assertSessionHas('success');
        $this->actingAsAdmin()->post(route('admin.orders.refunded', $orderId), ['reference' => 'GCASH-999'])->assertSessionHas('error');

        $this->assertSame('Refunded', DB::table('orders')->where('id', $orderId)->value('payment_status'));
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Refund Sent']);
    }

    /** A4: the compliance pages send a visitor who isn't the admin away first. */
    public function test_compliance_pages_need_the_admin(): void
    {
        $this->get(route('admin.compliance.riders'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.compliance.buyers'))->assertRedirect(route('admin.login'));
        $this->actingAsAdmin()->get(route('admin.compliance.riders'))->assertOk();
    }
}
