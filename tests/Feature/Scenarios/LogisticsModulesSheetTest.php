<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use App\Support\RiderJobs;
use App\Support\Shipments;
use App\Support\Waybill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The classmate's Logistics Modules workbook (L1–L8): one test per row,
 * named after its id (LOG-DASH-001 → test_LOG_DASH_001_…). Logistics 1
 * (Bilis Hatid Express) runs the Quezon City Sorting Center with Riders 1–3;
 * Logistics 2 (Agila Cargo) runs Davao. Delivery OTP and COD checks are on.
 */
class LogisticsModulesSheetTest extends TestCase
{
    use RefreshDatabase;
    use ModuleSheetWorld;
    use DispatchFlows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        config(['app.delivery_checks' => true]);
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(10, 0));
    }

    private function ops(array $query = [], ?User $company = null)
    {
        return $this->asLogistics($company)->get(route('logistics.ops', $query))->assertOk();
    }

    /** A shipment of Logistics 1's center (or $center) at a status, placed now. */
    private function shipment(string $status, array $columns = [], ?\App\Models\SortingCenter $center = null): int
    {
        $center ??= $this->qcCenter();

        return $this->makeOrder($this->buyer1(), $this->p02(), $status, array_merge([
            'origin_center_id' => $center->id, 'destination_center_id' => $center->id, 'current_center_id' => $center->id,
            'shipping_address' => self::ADDR_1, 'shipping_name' => 'Juan Miguel Dela Cruz', 'payment_status' => 'COD',
        ], $columns));
    }

    private function audit(string $action): ?object
    {
        return DB::table('audit_logs')->where('action', $action)->latest('id')->first();
    }

    /** The sheet's zones and rates (Within NCR ₱50+₱15, NCR ↔ Visayas ₱120+₱40, Within Visayas ₱60+₱20). */
    private function sheetZones(): array
    {
        $this->sheetRates();
        $ncr = DB::table('shipping_zones')->insertGetId(['name' => 'NCR', 'provinces' => json_encode(['Metro Manila (NCR)']), 'created_at' => now(), 'updated_at' => now()]);
        $vis = DB::table('shipping_zones')->insertGetId(['name' => 'Visayas', 'provinces' => json_encode(['Cebu', 'Bohol', 'Negros Oriental', 'Siquijor']), 'created_at' => now(), 'updated_at' => now()]);

        foreach ([[$ncr, $ncr, 50, 15], [$vis, $ncr, 120, 40], [$ncr, $vis, 120, 40], [$vis, $vis, 60, 20]] as [$from, $to, $first, $extra]) {
            DB::table('shipping_rates')->insert(['from_zone_id' => $from, 'to_zone_id' => $to, 'first_kg' => $first, 'per_kg' => $extra, 'created_at' => now(), 'updated_at' => now()]);
        }

        return ['ncr' => $ncr, 'visayas' => $vis];
    }

    /* ---------------------------------------------------------------
     | L1 Operations Dashboard
     * --------------------------------------------------------------- */

    public function test_LOG_DASH_001_counts_match_the_records(): void
    {
        $this->shipment('Ready for Pickup');
        $this->shipment('Ready for Pickup', ['dispatch_unassigned_at' => now()]);
        $this->shipment('Out for Delivery');
        $this->shipment('At Sorting Center');
        $this->shipment('Delivered', ['delivered_at' => now()]);
        $this->shipment('Delivery Failed');

        $counts = $this->ops()->viewData('counts');
        $this->assertSame(['pending' => 2, 'in_transit' => 2, 'delivered' => 1, 'failed' => 1], $counts);

        foreach ($counts as $bucket => $count) {
            $this->assertCount($count, $this->ops(['status' => $bucket])->viewData('shipments'), $bucket);
        }
    }

    public function test_LOG_DASH_002_counts_update_after_a_status_change(): void
    {
        $this->shipment('Delivered', ['delivered_at' => now()]);
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $this->rider1());
        $this->outFor($orderId, $this->rider1());
        $before = $this->ops()->viewData('counts');

        $this->deliver($orderId, $this->rider1())->assertSessionHas('success');
        $after = $this->ops()->viewData('counts');

        $this->assertSame($before['in_transit'] - 1, $after['in_transit']);
        $this->assertSame($before['delivered'] + 1, $after['delivered']);
        $this->assertSame([$before['pending'], $before['failed']], [$after['pending'], $after['failed']]);
    }

    public function test_LOG_DASH_003_date_filter(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(9, 0));
        $start = $this->shipment('Out for Delivery');
        $this->travelTo(now()->setDate(2026, 10, 17)->setTime(23, 30));
        $end = $this->shipment('Delivered', ['delivered_at' => now()]);
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(10, 0));
        $today = $this->shipment('Ready for Pickup');

        $this->assertSame([$today], $this->ops(['from' => '2026-10-19', 'to' => '2026-10-19'])->viewData('shipments')->pluck('id')->all());
        $range = $this->ops(['from' => '2026-10-15', 'to' => '2026-10-17'])->viewData('shipments')->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$start, $end], $range);
        $this->assertSame(1, $this->ops(['from' => '2026-10-15', 'to' => '2026-10-17'])->viewData('counts')['in_transit']);
    }

    public function test_LOG_DASH_004_only_own_companys_data(): void
    {
        $ours = $this->shipment('Out for Delivery');
        $theirs = $this->shipment('Out for Delivery', [], $this->davaoCenter());
        $this->logistics2();

        $page = $this->ops();
        $this->assertSame(1, $page->viewData('counts')['in_transit']);
        $this->assertSame([$ours], $page->viewData('shipments')->pluck('id')->all());
        $this->assertSame([$theirs], $this->ops([], $this->logistics2())->viewData('shipments')->pluck('id')->all());
    }

    /* ---------------------------------------------------------------
     | L2 Rider Management
     * --------------------------------------------------------------- */

    /** rider.test02@example.com, added by Logistics 1, waiting for verification. */
    private function pendingRider(): User
    {
        $rider = $this->sheetRiderFor('rider.test02@example.com', 'Ramil Dizon', '555MNO');
        DB::table('rider_applications')->where('user_id', $rider->id)->update(['status' => 'Pending Verification']);

        return $rider->fresh();
    }

    private function sheetRiderFor(string $email, string $name, string $plate): User
    {
        $method = new \ReflectionMethod($this, 'sheetRider');

        return $method->invoke($this, $this->logistics1(), $email, 'Rider@2026', $name, 'Motorcycle', $plate, 'N09-26-000111', '2030-01-01', 'Quezon City');
    }

    private function applicationOf(User $rider): object
    {
        return DB::table('rider_applications')->where('user_id', $rider->id)->first();
    }

    public function test_LOG_RIDER_001_approve_a_rider(): void
    {
        $rider = $this->pendingRider();
        $app = $this->applicationOf($rider);

        $this->asLogistics()->get(route('logistics.riders.document', [$app->id, 'drivers_license']))->assertOk();
        $this->get(route('logistics.riders.document', [$app->id, 'or_cr']))->assertOk();
        $this->post(route('logistics.riders.approve', $app->id))->assertSessionHas('success', 'Rider application approved.');

        $this->assertSame('Approved', $this->applicationOf($rider)->status);
        $this->asRider($rider)->post(route('rider.availability'), ['online' => 1])->assertSessionHas('success');
        $this->assertTrue(RiderJobs::isOnline($rider->id));
        $this->assertSame($rider->id, (int) $this->audit('rider.approved')->subject_id);
    }

    public function test_LOG_RIDER_002_reject_a_rider_with_a_reason(): void
    {
        $rider = $this->pendingRider();
        $app = $this->applicationOf($rider);
        $reason = 'OR/CR photo is unreadable, please re-upload.';

        $this->asLogistics()->post(route('logistics.riders.reject', $app->id), ['admin_remarks' => ''])->assertSessionHas('error', 'Please give the reason for rejecting (10 to 300 characters).');
        $this->assertSame('Pending Verification', $this->applicationOf($rider)->status);

        $this->post(route('logistics.riders.reject', $app->id), ['admin_remarks' => $reason])->assertSessionHas('success');
        $this->assertSame('Rejected', $this->applicationOf($rider)->status);
        $this->assertStringContainsString($reason, DB::table('notifications')->where('user_id', $rider->id)->where('title', 'Rider Application Rejected')->value('message'));

        $this->asRider($rider)->post(route('rider.availability'), ['online' => 1]);
        $this->assertFalse(RiderJobs::isOnline($rider->id));
        $this->assertNotNull(RiderJobs::blockReason($rider->id));
    }

    public function test_LOG_RIDER_003_suspend_a_rider_with_active_jobs(): void
    {
        $rider2 = $this->rider2();
        $this->goOnline($rider2);
        $assigned = $this->readyJob();
        $picked = $this->readyJob();
        $this->assignTo($assigned, $rider2)->assertSessionHas('success');
        $this->assignTo($picked, $rider2)->assertSessionHas('success');
        $this->pickUp($picked, $rider2)->assertSessionHas('success');

        $this->asLogistics()->post(route('logistics.riders.suspend', $rider2->id), ['reason' => 'Repeated late deliveries this week.'])->assertSessionHas('success');

        $this->assertSame('Suspended', DB::table('users')->where('id', $rider2->id)->value('status'));
        $this->assertFalse(RiderJobs::isOnline($rider2->id));
        $this->asRider($rider2)->post(route('rider.availability'), ['online' => 1]);
        $this->assertFalse(RiderJobs::isOnline($rider2->id));

        $this->assertTrue($this->ops()->viewData('unassigned')->contains('id', $assigned));
        $this->assertSame(['Picked Up', $rider2->id], [$this->statusOf($picked), (int) DB::table('orders')->where('id', $picked)->value('rider_id')]);
        $log = $this->audit('rider.suspended');
        $this->assertSame('Repeated late deliveries this week.', json_decode($log->details, true)['reason']);
    }

    public function test_LOG_RIDER_004_suspend_without_a_reason(): void
    {
        $rider1 = $this->rider1();

        foreach (['', 'Too late.'] as $reason) {
            $this->asLogistics()->post(route('logistics.riders.suspend', $rider1->id), ['reason' => $reason])
                ->assertSessionHas('error', 'Please give a reason of 10 to 300 characters.');
        }
        $this->assertSame('Active', DB::table('users')->where('id', $rider1->id)->value('status'));
    }

    public function test_LOG_RIDER_005_reactivate_a_rider(): void
    {
        $rider3 = $this->rider3();

        $this->asLogistics()->post(route('logistics.riders.reactivate', $rider3->id))->assertSessionHas('success', 'Ramon Cruz is active again.');
        $this->assertSame('Active', DB::table('users')->where('id', $rider3->id)->value('status'));
        $this->asRider($rider3->fresh())->post(route('rider.availability'), ['online' => 1])->assertSessionHas('success');
        $this->assertTrue(RiderJobs::isOnline($rider3->id));
        $this->assertNotNull($this->audit('rider.reactivated'));
    }

    public function test_LOG_RIDER_006_rider_status_and_workload(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $this->goOnline($rider1);
        $job = $this->readyJob();
        $this->assignTo($job, $rider1);
        $this->shipment('Delivered', ['delivery_rider_id' => $rider2->id, 'delivered_at' => now()]);

        $stats = $this->asLogistics()->get(route('logistics.riders', ['status' => 'all']))->assertOk()->viewData('riderStats');
        $this->assertSame(['online' => true, 'active_jobs' => 1, 'delivered_today' => 0], $stats[$rider1->id]);
        $this->assertSame(['online' => false, 'active_jobs' => 0, 'delivered_today' => 1], $stats[$rider2->id]);
    }

    public function test_LOG_RIDER_007_cannot_manage_another_companys_rider(): void
    {
        $rider1 = $this->rider1();
        $app = $this->applicationOf($rider1);

        $this->asLogistics($this->logistics2())->get(route('logistics.riders.document', [$app->id, 'drivers_license']))->assertForbidden();
        $this->post(route('logistics.riders.suspend', $rider1->id), ['reason' => 'Trying to suspend another company\'s rider.'])
            ->assertSessionHas('error', 'This rider belongs to another logistics company.');
        $this->assertSame('Active', DB::table('users')->where('id', $rider1->id)->value('status'));
    }

    /* ---------------------------------------------------------------
     | L3 Dispatch & Assignment
     * --------------------------------------------------------------- */

    public function test_LOG_DISP_001_auto_assign_to_the_nearest_eligible_rider(): void
    {
        $this->goOnline($this->rider1(), $this->rider2());
        $orderId = $this->readyJob();

        $this->autoAssign($orderId)->assertSessionHas('success', 'Job #' . $orderId . ' offered to Pedro Jose Garcia (60 seconds to accept).');
        $this->assertSame($this->rider1()->id, (int) $this->pendingOffer($orderId)->rider_id);
    }

    public function test_LOG_DISP_002_manual_assign(): void
    {
        $this->goOnline($this->rider2());
        $orderId = $this->readyJob();

        $this->assignTo($orderId, $this->rider2())->assertSessionHas('success', 'Job #' . $orderId . ' assigned to Mark Anthony Bautista.');
        $this->assertSame(['Pickup Assigned', $this->rider2()->id], [$this->statusOf($orderId), (int) DB::table('orders')->where('id', $orderId)->value('rider_id')]);
        $this->assertTrue(DB::table('order_events')->where('order_id', $orderId)->where('status', 'Pickup Assigned')->where('title', 'like', '%Mark Anthony Bautista%')->exists());
        $this->assertTrue($this->notifiedWith($this->rider2(), 'Pickup Assigned to You'));
    }

    public function test_LOG_DISP_003_offline_or_suspended_rider_not_assignable(): void
    {
        $rider1 = $this->rider1();
        $rider3 = $this->rider3();
        $orderId = $this->readyJob();

        $listed = $this->ops()->viewData('assignableRiders')->pluck('id')->all();
        $this->assertNotContains($rider1->id, $listed);
        $this->assertNotContains($rider3->id, $listed);
        $this->assignTo($orderId, $rider3)->assertSessionHas('error', 'Ramon Cruz is not an active rider.');
        $this->assignTo($orderId, $rider1)->assertSessionHas('error', 'Pedro Jose Garcia is offline.');
        $this->assertSame('Ready for Pickup', $this->statusOf($orderId));
    }

    public function test_LOG_DISP_004_rider_at_the_job_limit(): void
    {
        $rider2 = $this->rider2();
        $this->goOnline($rider2);
        foreach ([1, 2, 3] as $_) {
            $this->assignTo($this->readyJob(), $rider2)->assertSessionHas('success');
        }

        $fourth = $this->readyJob();
        $this->assignTo($fourth, $rider2)->assertSessionHas('error', 'Mark Anthony Bautista already has 3 active jobs (the limit).');
        $this->assertSame('Ready for Pickup', $this->statusOf($fourth));
    }

    public function test_LOG_DISP_005_reassign_before_pickup(): void
    {
        $this->goOnline($this->rider1(), $this->rider2());
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $this->rider1());

        $this->asLogistics()->post(route('logistics.dispatch.reassign', $orderId), ['rider_id' => $this->rider2()->id])
            ->assertSessionHas('success', 'Job #' . $orderId . ' moved from Pedro Jose Garcia to Mark Anthony Bautista.');

        $this->assertSame($this->rider2()->id, (int) DB::table('orders')->where('id', $orderId)->value('rider_id'));
        $this->assertSame(0, RiderJobs::activeCount($this->rider1()->id));
        $titles = DB::table('order_events')->where('order_id', $orderId)->where('status', 'Pickup Assigned')->pluck('title')->all();
        $this->assertContains('Rider Pedro Jose Garcia will pick it up from the seller', $titles);
        $this->assertContains('Pickup moved from Pedro Jose Garcia to Mark Anthony Bautista', $titles);
    }

    public function test_LOG_DISP_006_no_reassign_after_pickup(): void
    {
        $this->goOnline($this->rider1(), $this->rider2());
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $this->rider1());
        $this->pickUp($orderId, $this->rider1());

        $this->assertFalse($this->ops()->viewData('assignedPickups')->contains('id', $orderId));
        $this->asLogistics()->post(route('logistics.dispatch.reassign', $orderId), ['rider_id' => $this->rider2()->id])
            ->assertSessionHas('error', 'This parcel was already picked up, so it can no longer be reassigned.');
        $this->assertSame($this->rider1()->id, (int) DB::table('orders')->where('id', $orderId)->value('rider_id'));
    }

    public function test_LOG_DISP_007_bulk_assignment_over_the_limit(): void
    {
        $this->goOnline($this->rider2());
        $ids = array_map(fn () => $this->readyJob(), range(1, 5));

        $this->asLogistics()->post(route('logistics.dispatch.bulk'), ['order_ids' => $ids, 'rider_id' => $this->rider2()->id])
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, '3 assigned, 2 skipped'));

        $result = session('bulk_result');
        $this->assertCount(3, $result['assigned']);
        $this->assertCount(2, $result['skipped']);
        foreach (array_keys($result['skipped']) as $id) {
            $this->assertSame('Ready for Pickup', $this->statusOf($id));
            $this->assertStringContainsString('3 active jobs', $result['skipped'][$id]);
        }
    }

    public function test_LOG_DISP_008_assign_a_parcel_that_is_not_ready(): void
    {
        $this->goOnline($this->rider1());
        $packed = $this->shipment('Preparing');

        $this->assertFalse($this->ops()->viewData('readyForPickup')->contains('id', $packed));
        $this->assignTo($packed, $this->rider1())->assertSessionHas('error', 'This parcel is not ready for pickup, so it can\'t be assigned.');
        $this->assertSame('Preparing', $this->statusOf($packed));
    }

    /* ---------------------------------------------------------------
     | L4 Hub & Warehouse Operations
     * --------------------------------------------------------------- */

    private function pickedUpJob(): int
    {
        $this->goOnline($this->rider1());
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $this->rider1());
        $this->pickUp($orderId, $this->rider1())->assertSessionHas('success');

        return $orderId;
    }

    private function scan(int|string $code, string $stage = 'inbound', ?User $rider = null, ?User $company = null)
    {
        return $this->asLogistics($company)->post(route('logistics.hub.scan'), array_filter([
            'code' => is_int($code) ? Waybill::number($code) : $code, 'stage' => $stage, 'rider_id' => $rider?->id,
        ]));
    }

    public function test_LOG_HUB_001_inbound_receiving_by_scan(): void
    {
        $orderId = $this->pickedUpJob();

        $this->scan($orderId)->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertSame('At Sorting Center', $order->status);
        $this->assertNotNull($order->sorting_center_received_at);
        $this->assertTrue(DB::table('order_events')->where('order_id', $orderId)->where('status', 'At Sorting Center')->exists());

        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee('Received at BoomBuy Sorting Center');
    }

    public function test_LOG_HUB_002_sort_by_zone(): void
    {
        $this->sheetZones();
        $orderId = $this->pickedUpJob();
        $this->scan($orderId);

        $this->asLogistics()->post(route('logistics.parcels.sort', $orderId))->assertSessionHas('success');
        $zones = $this->ops()->viewData('zoneLists');
        $this->assertSame(['NCR' => [$orderId]], $zones->map(fn ($ids) => array_map('intval', $ids))->all());
    }

    public function test_LOG_HUB_003_outbound_dispatch(): void
    {
        $orderId = $this->pickedUpJob();
        $this->scan($orderId);

        $this->scan($orderId, 'outbound', $this->rider1())->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->statusOf($orderId));
        $this->outFor($orderId, $this->rider1())->assertSessionHas('success');

        $this->assertSame('Out for Delivery', $this->statusOf($orderId));
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee('Delivery OTP')->assertSee($this->otp($orderId));
    }

    public function test_LOG_HUB_004_duplicate_scan(): void
    {
        $orderId = $this->pickedUpJob();

        $this->scan($orderId)->assertSessionHas('success');
        $this->scan($orderId)->assertSessionHas('error', 'Parcel ' . Waybill::number($orderId) . ' was already received at the hub.');
        $this->assertSame(1, DB::table('order_events')->where('order_id', $orderId)->where('status', 'At Sorting Center')->count());
    }

    public function test_LOG_HUB_005_parcel_not_in_the_system(): void
    {
        $count = DB::table('orders')->count();

        $this->scan('TRK-0000000')->assertSessionHas('error', 'Parcel "TRK-0000000" not found.');
        $this->assertSame($count, DB::table('orders')->count());
    }

    public function test_LOG_HUB_006_out_of_order_scan(): void
    {
        $orderId = $this->pickedUpJob();

        $this->scan($orderId, 'outbound', $this->rider1())->assertSessionHas('error', 'Scan parcel ' . Waybill::number($orderId) . ' at Inbound first.');
        $this->assertSame('Picked Up', $this->statusOf($orderId));
    }

    public function test_LOG_HUB_007_another_companys_parcel(): void
    {
        $theirs = $this->shipment('Picked Up', [], $this->davaoCenter());

        $this->scan($theirs)->assertSessionHas('error', 'Parcel ' . Waybill::number($theirs) . ' belongs to another logistics company.');
        $this->assertSame('Picked Up', $this->statusOf($theirs));
    }

    /* ---------------------------------------------------------------
     | L5 Shipment Tracking & Exceptions
     * --------------------------------------------------------------- */

    public function test_LOG_TRK_001_search_by_tracking_number_order_id_or_buyer(): void
    {
        $mine = $this->shipment('Out for Delivery');
        $other = $this->makeOrder($this->buyer2(), $this->p05(), 'Out for Delivery', ['origin_center_id' => $this->qcCenter()->id, 'destination_center_id' => $this->qcCenter()->id, 'shipping_name' => 'Ana Marie Reyes']);

        foreach ([Waybill::number($mine), (string) $mine, 'Juan Miguel Dela Cruz'] as $term) {
            $this->assertSame([$mine], $this->ops(['q' => $term])->viewData('shipments')->pluck('id')->all(), $term);
        }
        $this->assertSame([$other], $this->ops(['q' => 'Ana Marie'])->viewData('shipments')->pluck('id')->all());

        foreach (['Nobody Here', "' OR '1'='1"] as $term) {
            $this->assertSame([], $this->ops(['q' => $term])->viewData('shipments')->pluck('id')->all(), $term);
        }
    }

    public function test_LOG_TRK_002_manual_override_needs_a_reason(): void
    {
        $orderId = $this->shipment('Out for Delivery');
        $reason = 'Rider app offline, delivery confirmed by phone.';

        $this->asLogistics()->post(route('logistics.shipments.override', $orderId), ['status' => 'Delivered', 'reason' => ''])
            ->assertSessionHas('error', 'Please give a reason of 10 to 300 characters.');
        $this->assertSame('Out for Delivery', $this->statusOf($orderId));

        $this->post(route('logistics.shipments.override', $orderId), ['status' => 'Delivered', 'reason' => $reason])->assertSessionHas('success');
        $this->assertSame('Delivered', $this->statusOf($orderId));
    }

    public function test_LOG_TRK_003_override_leaves_an_audit_log(): void
    {
        $orderId = $this->shipment('Out for Delivery');
        $reason = 'Rider app offline, delivery confirmed by phone.';
        $this->asLogistics()->post(route('logistics.shipments.override', $orderId), ['status' => 'Delivered', 'reason' => $reason]);

        $entry = $this->audit('shipment.override');
        $this->assertSame([$this->logistics1()->id, $orderId], [(int) $entry->user_id, (int) $entry->subject_id]);
        $this->assertSame(['from' => 'Out for Delivery', 'to' => 'Delivered', 'reason' => $reason], json_decode($entry->details, true));

        $html = $this->get(route('logistics.audit'))->assertOk()->getContent();
        foreach (['Carlos Antonio Mendoza', 'Out for Delivery → Delivered', $reason, \Illuminate\Support\Carbon::parse($entry->created_at)->format('M d, Y g:i:s A')] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        // The log itself has no Edit or Delete (no form or button at all).
        $log = substr($html, strpos($html, '<section class="ops-card">'));
        $log = substr($log, 0, strpos($log, '</section>'));
        foreach (['Delete', 'Edit', '<form', '<button'] as $control) {
            $this->assertStringNotContainsString($control, $log, $control);
        }
        $this->assertFalse(collect(\Illuminate\Support\Facades\Route::getRoutes())->contains(fn ($r) => str_contains($r->uri(), 'audit') && array_intersect($r->methods(), ['PUT', 'PATCH', 'DELETE', 'POST'])));
    }

    public function test_LOG_TRK_004_mark_a_parcel_lost(): void
    {
        $orderId = $this->shipment('At Sorting Center');

        $this->asLogistics()->post(route('logistics.shipments.lost', $orderId), ['reason' => 'Not found after two hub inventory checks.'])
            ->assertSessionHas('success', 'Parcel #' . $orderId . ' marked Lost. Claim opened.');

        $this->assertSame('Lost', $this->statusOf($orderId));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Parcel Lost'));
        $this->assertTrue($this->notifiedWith($this->seller1(), 'Parcel Lost'));
    }

    public function test_LOG_TRK_005_mark_a_parcel_damaged_with_a_photo(): void
    {
        $orderId = $this->shipment('At Sorting Center');
        $form = ['reason' => 'Box crushed during unloading, basket rim broken.'];

        $this->asLogistics()->post(route('logistics.shipments.damaged', $orderId), $form)->assertSessionHas('error', 'Attach a photo of the damage (JPG or PNG, up to 5 MB).');
        $this->assertSame('At Sorting Center', $this->statusOf($orderId));

        $this->post(route('logistics.shipments.damaged', $orderId), $form + ['photo' => $this->jpegOfSize('pod_photo.jpg', 40000)])->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Damaged', $order->status);
        $this->assertTrue(Storage::disk('local')->exists($order->exception_photo));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Parcel Damaged'));
        $this->assertTrue($this->notifiedWith($this->seller1(), 'Parcel Damaged'));
    }

    /* ---------------------------------------------------------------
     | L6 Returns (Return-to-Sender)
     * --------------------------------------------------------------- */

    /** A parcel that failed 3 times, back at the hub (or prepaid with $payment). */
    private function rtsJob(string $payment = 'Cash on Delivery'): int
    {
        $orderId = $this->readyJob(null, $payment);
        $this->toDelivery($orderId, $this->rider1());
        $this->outFor($orderId, $this->rider1());
        foreach (['Buyer not available', 'Buyer unreachable by phone'] as $reason) {
            $this->failAttempt($orderId, $this->rider1(), $reason);
            $this->retry($orderId);
        }
        $this->failAttempt($orderId, $this->rider1(), 'Wrong or incomplete address');

        return $orderId;
    }

    /** Back to the seller with Rider 1. */
    private function returnWithRider(int $orderId): void
    {
        $this->asLogistics()->post(route('logistics.parcels.confirm-back', $orderId))->assertSessionHas('success');
        $this->post(route('logistics.parcels.return-to-seller', $orderId))->assertSessionHas('success');
        $this->post(route('logistics.shipments.return-rider', $orderId), ['rider_id' => $this->rider1()->id])->assertSessionHas('success');
        $this->asRider($this->rider1())->post(route('rider.return.delivered', $orderId))->assertSessionHas('success');
    }

    public function test_LOG_RTS_001_rts_queue_after_3_failed_attempts(): void
    {
        $orderId = $this->rtsJob();

        $parcel = $this->ops()->viewData('rtsQueue')->firstWhere('id', $orderId);
        $this->assertSame('Return to Sender', $parcel->status_label);
        $this->assertSame(['Buyer not available', 'Buyer unreachable by phone', 'Wrong or incomplete address'], $parcel->failure_reasons);
    }

    public function test_LOG_RTS_002_deliver_back_to_the_seller(): void
    {
        $orderId = $this->rtsJob();
        $this->returnWithRider($orderId);

        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Returned to Seller', $order->status);
        $this->assertSame('cancelled', \App\Support\BuyerOrderTabs::group($order));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Order Cancelled (Not Delivered)'));
        $this->flushSession();
        $this->actingAsUser($this->seller1())->get(route('seller.order.details', $orderId))->assertOk()->assertSee('Returned');
        $this->assertTrue($this->notifiedWith($this->seller1(), 'Order Returned to You'));
    }

    public function test_LOG_RTS_003_stock_restored_only_when_the_seller_confirms(): void
    {
        $orderId = $this->rtsJob();
        $this->returnWithRider($orderId);
        $this->assertSame(19, $this->stockOf($this->p02()));

        $this->flushSession();
        $this->actingAsUser($this->seller1())->post(route('seller.order.restock', $orderId))->assertSessionHas('success');
        $this->assertSame(20, $this->stockOf($this->p02()));
    }

    public function test_LOG_RTS_004_prepaid_rts_refund_and_no_earnings(): void
    {
        $orderId = $this->rtsJob('GCash');
        $this->returnWithRider($orderId);

        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Refund Pending', $order->payment_status);
        $this->assertSame((float) $order->total_amount, (float) $order->refund_amount);
        $balances = \App\Support\SellerBalance::balances($this->seller1()->id);
        $this->assertSame([0.0, 0.0], [$balances['pending'], $balances['available']]);
    }

    /* ---------------------------------------------------------------
     | L7 Delivery Zones & Shipping Rates
     * --------------------------------------------------------------- */

    private function checkoutFee(?string $address = null): array
    {
        $this->flushSession();
        DB::table('saved_carts')->delete();
        if ($address) {
            \App\Models\BuyerAddress::where('user_id', $this->buyer1()->id)->update(['is_default' => false]);
            \App\Models\BuyerAddress::create(['user_id' => $this->buyer1()->id, 'label' => 'Other', 'phone' => '09171234567', 'address' => $address, 'is_default' => true]);
        }

        return $this->actingAsUser($this->buyer1(), ['cart' => [$this->p02()->id . ':0' => 1]])->get(route('checkout'))->assertOk()->viewData('checkoutPlan');
    }

    public function test_LOG_RATE_001_rate_change_shows_at_checkout(): void
    {
        $zones = $this->sheetZones();
        $earlier = $this->readyJob();
        $this->assertSame(50.0, (float) DB::table('orders')->where('id', $earlier)->value('delivery_fee'));
        $rate = DB::table('shipping_rates')->where('from_zone_id', $zones['ncr'])->where('to_zone_id', $zones['ncr'])->first();

        $this->asLogistics()->put(route('logistics.rates.update', $rate->id), ['first_kg' => '55', 'per_kg' => '15'])->assertSessionHas('success');
        $this->assertSame(55.0, $this->checkoutFee()['delivery_fee']);
        $this->assertSame(50.0, (float) DB::table('orders')->where('id', $earlier)->value('delivery_fee'));

        $log = json_decode($this->audit('rate.changed')->details, true);
        $this->assertSame([50.0, 55.0], [(float) $log['from']['first_kg'], (float) $log['to']['first_kg']]);
    }

    public function test_LOG_RATE_002_invalid_rate_values(): void
    {
        $zones = $this->sheetZones();
        $rate = DB::table('shipping_rates')->where('from_zone_id', $zones['ncr'])->where('to_zone_id', $zones['ncr'])->first();

        foreach (['-1', '0', 'abc'] as $value) {
            $this->asLogistics()->put(route('logistics.rates.update', $rate->id), ['first_kg' => $value, 'per_kg' => '15'])
                ->assertSessionHas('error', 'The first-kg rate must be a number more than ₱0 (at least ₱0.01).');
        }
        $this->assertSame(50.0, (float) DB::table('shipping_rates')->where('id', $rate->id)->value('first_kg'));

        $this->put(route('logistics.rates.update', $rate->id), ['first_kg' => '0.01', 'per_kg' => '15'])->assertSessionHas('success');
        $this->assertSame(0.01, (float) DB::table('shipping_rates')->where('id', $rate->id)->value('first_kg'));
    }

    public function test_LOG_RATE_003_unserviceable_area_blocked(): void
    {
        $this->sheetZones();
        $addr4 = 'Purok 2, Brgy. Raele, Itbayat, Batanes';
        $this->assertNotNull($this->checkoutFee($addr4)['unserviceable']);

        $this->post(route('checkout.place'), ['address' => $addr4, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])
            ->assertSessionHas('error', 'Sorry, we don\'t deliver to Batanes yet — this area is not serviceable. Choose another address.');
        $this->assertSame(0, DB::table('orders')->where('buyer_id', $this->buyer1()->id)->count());
    }

    public function test_LOG_RATE_004_add_a_zone(): void
    {
        $this->sheetZones();
        $addr4 = 'Purok 2, Brgy. Raele, Itbayat, Batanes';

        $this->asLogistics()->post(route('logistics.zones.store'), ['name' => 'Batanes Islands', 'provinces' => ['Batanes'], 'first_kg' => '150', 'per_kg' => '50'])
            ->assertSessionHas('success', 'Zone Batanes Islands added.');

        $plan = $this->checkoutFee($addr4);
        $this->assertNull($plan['unserviceable']);
        $this->assertSame(150.0, $plan['delivery_fee']);
        $this->post(route('checkout.place'), ['address' => $addr4, 'phone' => '09171234567', 'payment' => 'Cash on Delivery'])->assertSessionDoesntHaveErrors();
        $this->assertSame(150.0, (float) DB::table('orders')->where('buyer_id', $this->buyer1()->id)->value('delivery_fee'));
    }

    /* ---------------------------------------------------------------
     | L8 COD Reconciliation & Reports
     * --------------------------------------------------------------- */

    private function reconciliation(): ?object
    {
        return $this->asLogistics()->get(route('logistics.cod'))->assertOk()->viewData('rows')->firstWhere('rider_id', $this->rider1()->id);
    }

    private function remit(string $amount): void
    {
        $this->asRider($this->rider1())->post(route('rider.cod.remit'), ['amount' => $amount])->assertSessionHas('success');
    }

    public function test_LOG_COD_001_remittance_matches_cod_collected(): void
    {
        $ids = $this->codDelivered([$this->p02(), $this->p03()]);
        $this->remit('1800.00');

        $row = $this->reconciliation();
        $this->assertSame([1800.0, 1800.0, 0.0, false], [$row->collected, $row->remitted, $row->difference, $row->short]);
        $this->assertEqualsCanonicalizing($ids, $row->orders);
    }

    public function test_LOG_COD_002_short_remittance_flagged(): void
    {
        $this->codDelivered([$this->p02(), $this->p03()]);
        $this->remit('1750.00');

        $row = $this->reconciliation();
        $this->assertSame([-50.0, true], [$row->difference, $row->short]);
        $this->get(route('logistics.cod'))->assertSee('class="is-short"', false)->assertSee('−₱50.00')->assertSee('shortfall');
    }

    public function test_LOG_COD_003_confirm_remittance(): void
    {
        $this->codDelivered([$this->p02(), $this->p03()]);
        $this->remit('1800.00');
        $cod = app(\App\Services\CodRemittanceService::class);
        $this->assertSame(1800.0, $cod->balance($this->rider1()->id)['balance']);

        $this->asLogistics()->post(route('logistics.cod.confirm', DB::table('cod_remittances')->value('id')))->assertSessionHas('success', 'Remittance of ₱1,800.00 confirmed.');
        $this->assertSame(0.0, $cod->balance($this->rider1()->id)['balance']);
        $log = $this->audit('cod.remittance.confirmed');
        $this->assertSame([$this->logistics1()->id, $this->rider1()->id], [(int) $log->user_id, (int) $log->subject_id]);
    }

    public function test_LOG_COD_004_export_reports(): void
    {
        $this->codDelivered([$this->p02(), $this->p03()]);
        $range = ['from' => today()->toDateString(), 'to' => today()->toDateString()];
        $this->asLogistics();
        $screenDelivered = Shipments::list($this->qcCenter()->id, 'delivered', $range['from'], $range['to']);
        $screenCod = $this->get(route('logistics.cod'))->viewData('rows')->sum('collected');

        foreach (['deliveries', 'cod'] as $type) {
            $csv = $this->get(route('logistics.reports.export', $range + ['type' => $type, 'format' => 'csv']))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv->getContent()))));
            $xlsx = $this->get(route('logistics.reports.export', $range + ['type' => $type, 'format' => 'xlsx']))->assertOk();
            $this->assertStringContainsString('attachment; filename="boombuy-' . $type, $xlsx->headers->get('Content-Disposition'));
            $sheet = \App\Support\XlsxWriter::read($xlsx->getContent());

            $this->assertSame(count($rows), count($sheet), $type);
            $this->assertSame($rows[0], $sheet[0]);
            $this->assertCount(3, $rows, $type); // header + 2 orders

            $total = collect(array_slice($rows, 1))->sum(fn ($r) => (float) $r[$type === 'cod' ? 3 : 5]);
            $this->assertSame($type === 'cod' ? $screenCod : (float) $screenDelivered->sum('total_amount'), round($total, 2), $type);
        }
        $this->assertCount(2, $screenDelivered);
    }

    public function test_LOG_COD_005_export_with_no_data(): void
    {
        $range = ['from' => '2026-01-01', 'to' => '2026-01-02'];

        $csv = $this->asLogistics()->get(route('logistics.reports.export', $range + ['type' => 'deliveries', 'format' => 'csv']))->assertOk()->getContent();
        $this->assertSame(['"Tracking number",Order,Buyer,Rider,"Delivered at","Order total"'], array_values(array_filter(explode("\n", trim($csv)))));

        $sheet = \App\Support\XlsxWriter::read($this->get(route('logistics.reports.export', $range + ['type' => 'cod', 'format' => 'xlsx']))->assertOk()->getContent());
        $this->assertSame([['Tracking number', 'Order', 'Rider', 'COD collected', 'Collected at', 'Remitted at']], $sheet);
    }
}
