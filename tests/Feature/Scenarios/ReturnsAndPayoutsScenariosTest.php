<?php

namespace Tests\Feature\Scenarios;

use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use App\Models\Voucher;
use App\Support\SellerBalance;
use App\Support\StaleOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Buyer returns through the Sorting Centers, BoomBuy deciding disputes and
 * sending refunds, and paying sellers what they earned.
 */
class ReturnsAndPayoutsScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private SortingCenter $ncr;
    private User $lagunaStaff;
    private User $ncrStaff;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->ncr = $this->center('Metro Manila (NCR)', 'Quezon City');
        $this->lagunaStaff = $this->staffAt($this->laguna);
        $this->ncrStaff = $this->staffAt($this->ncr);
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Santa Cruz Kicks');
        $this->shoes = $this->makeProduct($this->seller, ['name' => 'Canvas Sneakers', 'price' => 500, 'stock' => 10]);
        PlatformSetting::set(StaleOrders::SINCE_KEY, now()->subMonth()->toDateTimeString());
    }

    private function as(?User $user): static
    {
        $this->flushSession();

        return $user ? $this->actingAsUser($user) : $this->actingAsAdmin();
    }

    /** A QC buyer's delivered order from the Laguna seller, received $daysAgo days ago. */
    private function qcOrder(User $buyer, int $daysAgo = 1, array $over = []): int
    {
        return $this->makeOrder($buyer, $this->shoes, 'Delivered', array_merge([
            'shipping_address' => self::QC_ADDRESS,
            'shipping_province' => 'Metro Manila (NCR)',
            'shipping_city' => 'Quezon City',
            'origin_center_id' => $this->laguna->id,
            'destination_center_id' => $this->ncr->id,
            'delivered_at' => now()->subDays($daysAgo + 1),
            'buyer_received_at' => now()->subDays($daysAgo),
        ], $over));
    }

    private function ask(User $buyer, int $orderId, string $type = 'Return')
    {
        return $this->as($buyer)->post(route('buyer.return-refund.store', $orderId), [
            'order_item_id' => DB::table('order_items')->where('order_id', $orderId)->value('id'),
            'request_type' => $type,
            'reason' => 'Damaged item',
            'refund_method' => 'GCash',
            'refund_account_name' => 'Ana Buyer',
            'refund_account_number' => '09171234567',
        ]);
    }

    private function statusOf(int $id): string
    {
        return DB::table('return_refund_requests')->where('id', $id)->value('status');
    }

    // ------------------------------------------------------------------
    // Returns
    // ------------------------------------------------------------------

    public function test_RT01_return_from_a_qc_buyer_travels_back_to_the_laguna_seller_and_is_refunded(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        Voucher::create(['seller_id' => $this->seller->id, 'code' => 'LESS100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 0, 'used_count' => 1, 'is_active' => true]);
        $orderId = $this->qcOrder($buyer, 1, ['voucher_code' => 'LESS100', 'discount_amount' => 100, 'total_amount' => 400]);

        $this->ask($buyer, $orderId)->assertSessionHas('success');
        $id = (int) DB::table('return_refund_requests')->value('id');
        // ₱500 item less its ₱100 voucher share; the delivery fee isn't refunded.
        $this->assertEquals(400, DB::table('return_refund_requests')->where('id', $id)->value('refund_amount'));

        $this->as($this->seller)->post(route('seller.return-refund.approve', $id))->assertSessionHas('success');
        $approved = DB::table('notifications')->where('user_id', $buyer->id)->where('title', 'Return Approved')->value('message');
        $this->assertStringContainsString($this->ncr->name, $approved);
        $this->assertStringContainsString('#' . $id, $approved);

        // Only the buyer's own center takes it in.
        $this->as($this->lagunaStaff)->post(route('logistics.returns.receive', $id))->assertSessionHas('error');
        $this->as($this->ncrStaff)->get(route('logistics.parcels'))->assertOk()->assertSee('Return #' . $id);
        $this->post(route('logistics.returns.receive', $id))->assertSessionHas('success');
        $this->assertSame('dropped_off', $this->statusOf($id));
        $this->post(route('logistics.returns.send', $id))->assertSessionHas('success');
        $this->assertSame('in_transit', $this->statusOf($id));

        $this->as($this->lagunaStaff)->post(route('logistics.returns.arrive', $id))->assertSessionHas('success');
        $this->assertSame('ready_for_seller', $this->statusOf($id));
        $this->assertTrue($this->notifiedWith($this->seller, 'Collect a Returned Item'));
        $this->post(route('logistics.returns.hand-to-seller', $id))->assertSessionHas('success');
        $this->assertSame('refund_pending', $this->statusOf($id));

        // BoomBuy sends the money and the buyer sees the reference.
        $this->as(null)->get(route('admin.returns', ['tab' => 'refund']))->assertOk()->assertSee('09171234567');
        $this->post(route('admin.returns.refunded', $id), ['reference' => 'GC-2026-0001'])->assertSessionHas('success');
        $this->assertSame('completed', $this->statusOf($id));

        $this->as($buyer)->get(route('buyer.orders'))->assertOk()->assertSee('GC-2026-0001')->assertSee('Refunded');
    }

    public function test_RT02_rejected_request_goes_to_boombuy_whose_decision_is_final(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer, $this->qcOrder($buyer))->assertSessionHas('success');
        $id = (int) DB::table('return_refund_requests')->value('id');

        $this->as($buyer)->post(route('buyer.return-refund.escalate', $id))->assertSessionHas('error'); // not rejected yet
        $this->as($this->seller)->post(route('seller.return-refund.reject', $id), ['seller_note' => 'Looks used'])->assertSessionHas('success');

        $this->as($buyer)->get(route('buyer.orders'))->assertSee('Ask BoomBuy to review');
        $this->post(route('buyer.return-refund.escalate', $id), ['why' => 'It arrived broken, see photo'])->assertSessionHas('success');
        $this->assertSame('disputed', $this->statusOf($id));

        $this->as(null)->get(route('admin.returns'))->assertOk()->assertViewHas('tab', 'review')->assertSee('It arrived broken');
        $this->post(route('admin.returns.decide', $id), ['decision' => 'approve', 'note' => ''])->assertSessionHas('error');
        $this->post(route('admin.returns.decide', $id), ['decision' => 'approve', 'note' => 'The photo shows a crack.'])->assertSessionHas('success');
        $this->assertSame('approved', $this->statusOf($id));
        $this->assertTrue($this->notifiedWith($this->seller, 'BoomBuy Approved a Return'));

        // A second rejected request, closed by BoomBuy: no second review.
        $buyer2 = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer2, $this->qcOrder($buyer2), 'Refund');
        $id2 = (int) DB::table('return_refund_requests')->orderByDesc('id')->value('id');
        $this->as($this->seller)->post(route('seller.return-refund.reject', $id2), ['seller_note' => 'No damage']);
        $this->as($buyer2)->post(route('buyer.return-refund.escalate', $id2));
        $this->as(null)->post(route('admin.returns.decide', $id2), ['decision' => 'reject', 'note' => 'Photos show no damage.'])->assertSessionHas('success');
        $this->assertSame('rejected', $this->statusOf($id2));
        $this->as($buyer2)->post(route('buyer.return-refund.escalate', $id2))->assertSessionHas('error', fn ($m) => str_contains($m, 'final'));
    }

    public function test_RT03_rejections_can_only_be_escalated_for_seven_days(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer, $this->qcOrder($buyer));
        $id = (int) DB::table('return_refund_requests')->value('id');
        $this->as($this->seller)->post(route('seller.return-refund.reject', $id), ['seller_note' => 'No']);

        $this->travelTo(now()->addDays(8));
        $this->as($buyer)->post(route('buyer.return-refund.escalate', $id))->assertSessionHas('error', fn ($m) => str_contains($m, 'within 7 days'));
    }

    public function test_RT04_silent_seller_and_returns_never_brought_in(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer, $this->qcOrder($buyer));
        $silent = (int) DB::table('return_refund_requests')->value('id');
        DB::table('return_refund_requests')->where('id', $silent)->update(['created_at' => now()->subDays(4)]);

        $buyer2 = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer2, $this->qcOrder($buyer2));
        $idle = (int) DB::table('return_refund_requests')->orderByDesc('id')->value('id');
        $this->as($this->seller)->post(route('seller.return-refund.approve', $idle));
        DB::table('return_refund_requests')->where('id', $idle)->update(['approved_at' => now()->subDays(8)]);

        $done = StaleOrders::sweep(true);

        $this->assertSame(1, $done['escalated']);
        $this->assertSame('disputed', $this->statusOf($silent));
        $this->assertSame(1, $done['returns_cancelled']);
        $this->assertSame('cancelled', $this->statusOf($idle));
        $this->assertTrue($this->notifiedWith($buyer2, 'Return Cancelled'));
    }

    public function test_RT05_buyer_cancels_before_dropping_it_off_but_not_after(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->ask($buyer, $this->qcOrder($buyer));
        $id = (int) DB::table('return_refund_requests')->value('id');

        $this->as($this->seller)->post(route('seller.return-refund.approve', $id));
        $this->as($this->ncrStaff)->post(route('logistics.returns.receive', $id));

        $this->as($buyer)->post(route('buyer.return-refund.cancel', $id))->assertSessionHas('error');

        $this->ask($buyer, $other = $this->qcOrder($buyer));
        $id2 = (int) DB::table('return_refund_requests')->where('order_id', $other)->value('id');
        $this->as($buyer)->post(route('buyer.return-refund.cancel', $id2))->assertSessionHas('success');
        $this->assertSame('cancelled', $this->statusOf($id2));
    }

    // ------------------------------------------------------------------
    // Payouts
    // ------------------------------------------------------------------

    public function test_PY01_balance_counts_only_orders_that_can_no_longer_be_returned(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        // ₱500 each, 10% commission → ₱450 to the seller.
        $waitingCash = $this->qcOrder($buyer, 10, ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => now()->subDays(10), 'commission_rate' => 10]);
        $returnable = $this->qcOrder($buyer, 2, ['payment_method' => 'GCash', 'commission_rate' => 10]);
        $ready = $this->qcOrder($buyer, 10, ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => now()->subDays(10), 'cod_remitted_at' => now()->subDays(9), 'commission_rate' => 10]);
        $refunded = $this->qcOrder($buyer, 10, ['payment_method' => 'GCash', 'commission_rate' => 10]);
        DB::table('return_refund_requests')->insert([
            'order_id' => $refunded, 'order_item_id' => DB::table('order_items')->where('order_id', $refunded)->value('id'),
            'buyer_id' => $buyer->id, 'seller_id' => $this->seller->id, 'request_type' => 'Refund', 'reason' => 'x',
            'status' => 'completed', 'refund_amount' => 500, 'refunded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $balance = SellerBalance::for($this->seller->id);

        $this->assertEquals(450, $balance['waiting_cash']);
        $this->assertEquals(450, $balance['on_hold']);
        $this->assertEquals(450, $balance['available']); // the refunded order earns nothing
        $this->assertEquals(500, $balance['refunds']);
        $states = $balance['orders']->pluck('state', 'id');
        $this->assertSame('waiting_cash', $states[$waitingCash]);
        $this->assertSame('on_hold', $states[$returnable]);
        $this->assertSame('available', $states[$ready]);

        // Changing the commission later doesn't touch these orders.
        PlatformSetting::set('commission_rate', '25');
        $this->assertEquals(450, SellerBalance::for($this->seller->id)['available']);
    }

    public function test_PY02_new_orders_save_todays_commission_rate(): void
    {
        PlatformSetting::set('commission_rate', '12.5');
        $buyer = $this->buyerAt();
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 1])->assertRedirect();

        $this->assertEquals(12.5, $this->lastOrder($buyer)->commission_rate);
    }

    public function test_PY03_seller_sets_an_account_and_admin_pays_up_to_the_balance(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $this->qcOrder($buyer, 10, ['payment_method' => 'GCash', 'commission_rate' => 10]);
        $this->qcOrder($buyer, 10, ['payment_method' => 'GCash', 'commission_rate' => 10]);

        // No account yet: the admin can't record a payout.
        $this->as(null)->post(route('admin.payouts.store', $this->seller->id), ['amount' => 100, 'reference' => 'R1'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'has not set where to be paid'));

        $this->as($this->seller)->post(route('seller.payouts.account'), ['payout_method' => 'GCash', 'payout_account_name' => 'Kicks Owner', 'payout_account_number' => '123'])
            ->assertSessionHas('error');
        $this->post(route('seller.payouts.account'), ['payout_method' => 'GCash', 'payout_account_name' => 'Kicks Owner', 'payout_account_number' => '09998887777'])
            ->assertSessionHas('success');

        $this->as(null)->get(route('admin.payouts'))->assertOk()->assertSee('Santa Cruz Kicks')->assertSee('₱900.00');
        $this->post(route('admin.payouts.store', $this->seller->id), ['amount' => 1000, 'reference' => 'R1'])->assertSessionHas('error', fn ($m) => str_contains($m, 'more than'));
        $this->post(route('admin.payouts.store', $this->seller->id), ['amount' => 600])->assertSessionHas('error');
        $this->post(route('admin.payouts.store', $this->seller->id), ['amount' => 600, 'reference' => 'GC-PAY-1'])->assertSessionHas('success');

        $this->assertEquals(300, SellerBalance::for($this->seller->id)['available']);
        $this->assertTrue($this->notifiedWith($this->seller, 'Payout Sent'));

        $this->as($this->seller)->get(route('seller.payouts'))->assertOk()
            ->assertSee('GC-PAY-1')
            ->assertViewHas('balance', fn ($b) => $b['paid_out'] == 600 && $b['available'] == 300);
        $this->get(route('seller.dashboard'))->assertOk()->assertSee('₱300 ready');
    }

    public function test_PY04_reports_match_payouts_after_refunds(): void
    {
        $buyer = $this->buyerAt(self::QC_ADDRESS);
        $kept = $this->qcOrder($buyer, 10, ['payment_method' => 'GCash', 'commission_rate' => 10]);
        $refunded = $this->qcOrder($buyer, 10, ['payment_method' => 'GCash', 'commission_rate' => 10]);
        DB::table('return_refund_requests')->insert([
            'order_id' => $refunded, 'order_item_id' => DB::table('order_items')->where('order_id', $refunded)->value('id'),
            'buyer_id' => $buyer->id, 'seller_id' => $this->seller->id, 'request_type' => 'Refund', 'reason' => 'x',
            'status' => 'completed', 'refund_amount' => 500, 'refunded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->as($this->seller)->get(route('seller.reports', ['from' => now()->subMonth()->toDateString()]))->assertOk()
            ->assertViewHas('totalSales', 500.0)
            ->assertViewHas('commissionOwed', 50.0)
            ->assertViewHas('netEarnings', 450.0);

        $row = $this->as(null)->get(route('admin.reports'))->viewData('sellerSales')->firstWhere('seller_id', $this->seller->id);
        $this->assertEquals(500, $row['sales']);
        $this->assertEquals(50, $row['commission']);
    }
}
