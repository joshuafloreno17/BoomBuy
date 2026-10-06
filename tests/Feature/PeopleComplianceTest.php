<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** Admin → Compliance → Riders and Buyers. */
class PeopleComplianceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_riders_with_stuck_or_failing_deliveries_need_attention(): void
    {
        $product = $this->makeProduct($this->makeSeller());

        $stuckRider = $this->makeRider();
        $stuckOrder = $this->makeOrder($this->makeUser(), $product, 'Out for Delivery', [
            'delivery_rider_id' => $stuckRider->id, 'updated_at' => now()->subDays(3),
        ]);

        $failingRider = $this->makeRider();
        $this->makeOrder($this->makeUser(), $product, 'Delivered', ['delivery_rider_id' => $failingRider->id]);
        $this->makeOrder($this->makeUser(), $product, 'Returned to Seller', ['delivery_rider_id' => $failingRider->id, 'delivery_attempts' => 2]);

        $goodRider = $this->makeRider();
        $this->makeOrder($this->makeUser(), $product, 'Delivered', ['delivery_rider_id' => $goodRider->id]);
        $this->makeOrder($this->makeUser(), $product, 'Out for Delivery', ['delivery_rider_id' => $goodRider->id]);

        $page = $this->actingAsAdmin()->get(route('admin.compliance.riders'))->assertOk();
        $people = collect($page->viewData('people'))->keyBy('user_id');

        $this->assertSame('issues', $page->viewData('view'));
        $this->assertSame(2, $page->viewData('withIssues'));
        $this->assertStringContainsString('not moved in over 2 days', $people[$stuckRider->id]['issues'][0]);
        $this->assertStringContainsString('67% of delivery attempts failed (2 of 3)', $people[$failingRider->id]['issues'][0]);
        $this->assertArrayNotHasKey($goodRider->id, $people->all());
        $page->assertSee('Order #' . $stuckOrder);

        // Everyone, including the rider with no issues.
        $this->actingAsAdmin()->get(route('admin.compliance.riders', ['view' => 'all']))->assertSee($goodRider->email);
    }

    public function test_buyers_with_cod_paused_or_close_need_attention(): void
    {
        $product = $this->makeProduct($this->makeSeller());
        $cancelled = fn ($buyer, $daysAgo = 1) => $this->makeOrder($buyer, $product, 'Cancelled', [
            'cancelled_by' => 'buyer', 'cancelled_at' => now()->subDays($daysAgo),
        ]);

        $paused = $this->makeUser();
        $cancelled($paused);
        $cancelled($paused);
        $this->makeOrder($paused, $product, 'Returned to Seller', ['buyer_refused_at' => now()->subDay()]);

        $close = $this->makeUser();
        $cancelled($close);
        $cancelled($close);

        $fine = $this->makeUser();
        $this->makeOrder($fine, $product, 'Delivered');
        $cancelled($fine, 60); // outside the 30-day window

        $page = $this->actingAsAdmin()->get(route('admin.compliance.buyers'))->assertOk();
        $people = collect($page->viewData('people'))->keyBy('user_id');

        $this->assertStringContainsString('Cash on Delivery paused until', $people[$paused->id]['issues'][0]);
        $this->assertStringContainsString('One strike away', $people[$close->id]['issues'][0]);
        $this->assertArrayNotHasKey($fine->id, $people->all());

        $this->actingAsAdmin()->get(route('admin.compliance.buyers', ['view' => 'all', 'q' => $fine->email]))
            ->assertSee($fine->email);
    }

    public function test_complaints_put_riders_and_buyers_on_the_list(): void
    {
        $rider = $this->makeRider();
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        foreach ([$rider, $buyer] as $against) {
            DB::table('complaints')->insert([
                'complainant_id' => $seller->id, 'complainant_role' => 'seller', 'against_user_id' => $against->id,
                'subject' => 'Rude on the phone', 'description' => 'Details', 'status' => 'Pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAsAdmin()->get(route('admin.compliance.riders'))->assertSee('Rude on the phone')->assertSee('1 open complaint(s)');
        $this->actingAsAdmin()->get(route('admin.compliance.buyers'))->assertSee('Rude on the phone');
    }

    public function test_admin_warns_and_suspends_a_rider_or_buyer(): void
    {
        $rider = $this->makeRider();
        $buyer = $this->makeUser();

        $this->actingAsAdmin()->post(route('admin.compliance.warn', $rider->id))->assertSessionHas('success');
        $this->assertDatabaseHas('notifications', ['user_id' => $rider->id, 'title' => 'Compliance Warning']);
        $this->assertStringContainsString('rider account', DB::table('notifications')->where('user_id', $rider->id)->value('message'));

        $this->actingAsAdmin()->post(route('admin.compliance.warn', $buyer->id), ['warning_message' => 'Stop refusing parcels.'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'message' => 'Stop refusing parcels.']);

        $this->actingAsAdmin()->post(route('admin.accounts.status', $buyer->id), ['status' => 'Suspended'])->assertSessionHas('success');
        $this->assertSame('Suspended', $buyer->fresh()->status);

        // Logistics staff aren't warned from here.
        $this->actingAsAdmin()->post(route('admin.compliance.warn', $this->makeLogistics()->id))->assertSessionHas('error');
    }

    public function test_tabs_and_admin_only(): void
    {
        $this->actingAsAdmin()->get(route('admin.compliance'))->assertOk()
            ->assertSee(route('admin.compliance.riders'))->assertSee(route('admin.compliance.buyers'));

        $this->flushSession();
        $this->get(route('admin.compliance.riders'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.compliance.buyers'))->assertRedirect(route('admin.login'));
    }
}
