<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use App\Support\RiderJobs;
use App\Support\Waybill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The classmate's Rider Modules workbook (R1–R7): one test per row, named
 * after its id (RID-PROF-001 → test_RID_PROF_001_…), with the Test Data
 * sheet's riders (Rider 1 Pedro Jose Garcia, Rider 2 Mark Anthony Bautista,
 * Rider 3 Ramon Cruz — suspended) under Logistics 1 (Bilis Hatid Express,
 * Quezon City Sorting Center). Delivery OTP and COD checks are on.
 */
class RiderModulesSheetTest extends TestCase
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

    /* ---------------------------------------------------------------
     | Shared steps (each through the real routes)
     * --------------------------------------------------------------- */

    /* ---------------------------------------------------------------
     | R1 Profile, Vehicle & Documents
     * --------------------------------------------------------------- */

    public function test_RID_PROF_001_view_profile_vehicle_and_verification_status(): void
    {
        $rider = $this->rider1();

        $html = $this->asRider($rider)->get(route('rider.profile'))->assertOk()->getContent();
        foreach (['Pedro Jose Garcia', 'N01-23-456789', '03/22/2030', 'Motorcycle', '123ABC', 'Bilis Hatid Express Inc.', 'Active'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }

        $this->get(route('rider.document', 'drivers_license'))->assertOk();
        $this->get(route('rider.document', 'or_cr'))->assertOk();
    }

    public function test_RID_PROF_002_update_vehicle_details(): void
    {
        $rider = $this->rider1();
        $orcr = $this->realUpload('or_cr.pdf', '%PDF-1.4' . str_repeat("\n%", 460000));

        $this->asRider($rider)->post(route('rider.vehicle.update'), ['plate_number' => '789GHI', 'or_cr' => $orcr])
            ->assertSessionHas('success', 'Saved. Your logistics company will verify the new details.');

        $record = DB::table('rider_applications')->where('user_id', $rider->id)->first();
        $this->assertSame('789GHI', $record->plate_number);
        $this->assertTrue((bool) $record->needs_reverification);
        $this->assertTrue(Storage::disk('local')->exists($record->or_cr));

        $page = $this->asLogistics()->get(route('logistics.riders', ['status' => 'all']))->assertOk();
        $page->assertSee('789GHI')->assertSee('Re-verify')->assertDontSee('123ABC');
        $this->get(route('logistics.riders.document', [$record->id, 'or_cr']))->assertOk();
    }

    public function test_RID_PROF_003_plate_number_already_used(): void
    {
        $rider = $this->rider1();
        $this->rider2();

        foreach (['NBC1234', 'nbc1234'] as $plate) {
            $this->asRider($rider)->post(route('rider.vehicle.update'), ['plate_number' => $plate])
                ->assertSessionHas('error', 'That plate number is already registered to another rider.');
        }

        $this->assertSame('123ABC', DB::table('rider_applications')->where('user_id', $rider->id)->value('plate_number'));
    }

    public function test_RID_PROF_004_license_expiry_boundary(): void
    {
        $rider = $this->rider1();

        $this->asRider($rider)->post(route('rider.vehicle.update'), ['license_expiry' => today()->subDay()->toDateString()])
            ->assertSessionHas('error', 'Your driver\'s license has expired. Enter an expiry date from today onward.');
        $this->post(route('rider.vehicle.update'), ['license_expiry' => today()->toDateString()])->assertSessionHas('success');

        $this->assertSame(today()->toDateString(), substr((string) DB::table('rider_applications')->where('user_id', $rider->id)->value('license_expiry'), 0, 10));
    }

    public function test_RID_PROF_005_document_upload_rules(): void
    {
        $rider = $this->rider1();
        $before = DB::table('rider_applications')->where('user_id', $rider->id)->value('drivers_license');

        foreach ([$this->jpegOfSize('size_5.1MB.jpg', $this->mb(5.1)), $this->docx('price_list.docx'), $this->exe('renamed_exe.jpg')] as $file) {
            $this->asRider($rider)->post(route('rider.vehicle.update'), ['drivers_license' => $file])
                ->assertSessionHas('error', 'The driver\'s license must be a JPG, PNG or PDF file of at most 5 MB.');
            $this->assertSame($before, DB::table('rider_applications')->where('user_id', $rider->id)->value('drivers_license'));
        }

        $this->asRider($rider)->post(route('rider.vehicle.update'), ['drivers_license' => $this->jpegOfSize('size_5.0MB.jpg', $this->mb(5.0))])->assertSessionHas('success');
        $this->assertNotSame($before, DB::table('rider_applications')->where('user_id', $rider->id)->value('drivers_license'));
    }

    public function test_RID_PROF_006_suspended_rider_cannot_work(): void
    {
        $suspended = $this->rider3();

        $this->asRider($suspended)->post(route('rider.availability'), ['online' => 1]);
        $this->assertFalse(RiderJobs::isOnline($suspended->id));

        $orderId = $this->readyJob();
        $this->autoAssign($orderId);
        $this->assertNull($this->pendingOffer($orderId));

        $this->asLogistics()->get(route('logistics.ops'))->assertOk()->assertViewHas('assignableRiders', fn ($riders) => !$riders->contains('id', $suspended->id));
        $this->assignTo($orderId, $suspended)->assertSessionHas('error', 'Ramon Cruz is not an active rider.');
        $this->assertSame('Ready for Pickup', $this->statusOf($orderId));
    }

    public function test_RID_PROF_007_rider_cannot_see_another_riders_profile(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $theirs = DB::table('rider_applications')->where('user_id', $rider2->id)->value('id');

        // The profile has no rider id to change: it is always your own.
        $this->asRider($rider1)->get(route('rider.profile'))->assertOk()->assertSee('Pedro Jose Garcia')->assertDontSee('NBC1234');
        $response = $this->get(route('logistics.riders.document', [$theirs, 'drivers_license']));
        $this->assertContains($response->status(), [302, 403]);
        $this->assertNotSame('license', (string) $response->getContent());
    }

    /* ---------------------------------------------------------------
     | R2 Availability
     * --------------------------------------------------------------- */

    public function test_RID_AVAIL_001_go_online(): void
    {
        $rider1 = $this->rider1();
        $this->rider2();

        $this->asRider($rider1)->post(route('rider.availability'), ['online' => 1])->assertSessionHas('success');
        $this->asLogistics()->get(route('logistics.ops'))->assertViewHas('riders', fn ($riders) => (bool) $riders->firstWhere('id', $rider1->id)->is_online);

        $orderId = $this->readyJob();
        $this->assertSame(500.0, (float) DB::table('orders')->where('id', $orderId)->value('total_amount'));
        $this->autoAssign($orderId)->assertSessionHas('success');
        $this->assertSame($rider1->id, (int) $this->pendingOffer($orderId)->rider_id);
    }

    public function test_RID_AVAIL_002_go_offline(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $this->goOnline($rider1, $rider2);

        $this->asRider($rider1)->post(route('rider.availability'), ['online' => 0])->assertSessionHas('success');
        $orderId = $this->readyJob();
        $this->autoAssign($orderId);

        $this->assertSame($rider2->id, (int) $this->pendingOffer($orderId)->rider_id);
        $this->asLogistics()->get(route('logistics.ops'))->assertViewHas('assignableRiders', fn ($riders) => !$riders->contains('id', $rider1->id) && $riders->contains('id', $rider2->id));
    }

    public function test_RID_AVAIL_003_go_offline_with_an_active_job(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $rider1);
        $this->pickUp($orderId, $rider1)->assertSessionHas('success');

        $this->asRider($rider1)->post(route('rider.availability'), ['online' => 0])->assertSessionHas('success');
        $this->assertSame('Picked Up', $this->statusOf($orderId));
        $this->assertSame($rider1->id, (int) DB::table('orders')->where('id', $orderId)->value('rider_id'));
        $this->get(route('rider.pickups'))->assertSee(Waybill::number($orderId));

        // It still goes on to delivery; no new offers come.
        $this->asLogistics()->post(route('logistics.hub.scan'), ['code' => Waybill::number($orderId), 'stage' => 'inbound'])->assertSessionHas('success');
        $next = $this->readyJob();
        $this->autoAssign($next);
        $this->assertNull($this->pendingOffer($next));
    }

    public function test_RID_AVAIL_004_offline_after_logout_or_session_timeout(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);

        $this->asRider($rider1)->post(route('logout'));
        $this->assertFalse(RiderJobs::isOnline($rider1->id));

        $orderId = $this->readyJob();
        $this->asLogistics()->get(route('logistics.ops'))->assertViewHas('riders', fn ($riders) => !$riders->firstWhere('id', $rider1->id)->is_online);
        $this->autoAssign($orderId);
        $this->assertNull($this->pendingOffer($orderId));
    }

    /* ---------------------------------------------------------------
     | R3 Job Assignment
     * --------------------------------------------------------------- */

    public function test_RID_JOB_001_view_and_accept_a_job(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->autoAssign($orderId);

        $offer = collect($this->asRider($rider1)->getJson(route('rider.offers'))->assertOk()->json('offers'))->first();
        $this->assertSame('Maria Clara Handicrafts', $offer['shop_name']);
        $this->assertStringContainsString('Commonwealth, Quezon City', $offer['pickup_address']);
        $this->assertSame('Brgy. Central, Quezon City', $offer['dropoff_area']);
        $this->assertEquals(500, $offer['cod']);
        $this->assertEquals(30, $offer['pay']);

        $this->post(route('rider.offers.respond', $offer['id']), ['answer' => 'accept'])->assertSessionHas('success');
        $this->assertSame('Pickup Assigned', $this->statusOf($orderId));
        $this->assertSame($rider1->id, (int) DB::table('orders')->where('id', $orderId)->value('rider_id'));
        $this->asLogistics()->get(route('logistics.ops'))->assertViewHas('assignedPickups', fn ($jobs) => (int) $jobs->firstWhere('id', $orderId)->rider_id === $rider1->id);
    }

    public function test_RID_JOB_002_decline_a_job(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $this->goOnline($rider1, $rider2);
        $orderId = $this->readyJob();
        $this->autoAssign($orderId);
        $offer = $this->pendingOffer($orderId);
        $this->assertSame($rider1->id, (int) $offer->rider_id);

        $this->asRider($rider1)->post(route('rider.offers.respond', $offer->id), ['answer' => 'decline'])->assertSessionHas('success', 'Job declined.');
        $this->assertSame([], $this->getJson(route('rider.offers'))->json('offers'));
        $this->assertSame($rider2->id, (int) $this->pendingOffer($orderId)->rider_id);
        $this->assertNull(DB::table('orders')->where('id', $orderId)->value('rider_id'));
    }

    public function test_RID_JOB_003_offer_timeout(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $this->goOnline($rider1, $rider2);

        $first = $this->readyJob();
        $this->autoAssign($first);
        $this->travel(59)->seconds();
        $this->asRider($rider1)->post(route('rider.offers.respond', $this->pendingOffer($first)->id), ['answer' => 'accept'])->assertSessionHas('success');
        $this->assertSame($rider1->id, (int) DB::table('orders')->where('id', $first)->value('rider_id'));

        $second = $this->readyJob();
        $this->autoAssign($second);
        $offer = $this->pendingOffer($second);
        $this->assertSame($rider1->id, (int) $offer->rider_id);
        $this->travel(61)->seconds();
        $this->asRider($rider1)->post(route('rider.offers.respond', $offer->id), ['answer' => 'accept'])->assertSessionHas('error', 'This job offer has expired.');
        $this->assertSame('expired', DB::table('job_offers')->where('id', $offer->id)->value('status'));
        $this->assertSame($rider2->id, (int) $this->pendingOffer($second)->rider_id);
    }

    public function test_RID_JOB_004_job_goes_to_unassigned_after_3_declines(): void
    {
        $riders = [$this->rider1(), $this->rider2(), $this->rider4()];
        $this->goOnline(...$riders);
        $orderId = $this->readyJob();
        $this->autoAssign($orderId);

        foreach ($riders as $rider) {
            $offer = $this->pendingOffer($orderId);
            $this->assertSame($rider->id, (int) $offer->rider_id);
            $this->asRider($rider)->post(route('rider.offers.respond', $offer->id), ['answer' => 'decline']);
        }

        $this->assertNull($this->pendingOffer($orderId));
        $this->asLogistics()->get(route('logistics.ops'))->assertViewHas('unassigned', fn ($queue) => $queue->contains('id', $orderId));
        $this->autoAssign($orderId)->assertSessionHas('error', 'This job is in the Unassigned queue — assign a rider by hand.');
    }

    public function test_RID_JOB_005_accept_and_manual_assignment_at_the_same_moment(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $this->goOnline($rider1, $rider2);
        $orderId = $this->readyJob();
        $this->autoAssign($orderId);
        $offer = $this->pendingOffer($orderId);

        $this->asRider($rider1)->post(route('rider.offers.respond', $offer->id), ['answer' => 'accept'])->assertSessionHas('success');
        $this->assignTo($orderId, $rider2)->assertSessionHas('error', 'This job was already assigned to Pedro Jose Garcia.');

        $this->assertSame($rider1->id, (int) DB::table('orders')->where('id', $orderId)->value('rider_id'));
        $this->assertSame(1, DB::table('orders')->where('id', $orderId)->count());
        $this->assertSame(0, RiderJobs::activeCount($rider2->id));
    }

    public function test_RID_JOB_006_concurrent_job_limit(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        foreach ([1, 2] as $_) {
            $this->assignTo($this->readyJob(), $rider1)->assertSessionHas('success');
        }
        $this->assertSame(2, RiderJobs::activeCount($rider1->id));

        $third = $this->readyJob();
        $this->autoAssign($third);
        $this->asRider($rider1)->post(route('rider.offers.respond', $this->pendingOffer($third)->id), ['answer' => 'accept'])->assertSessionHas('success');
        $this->assertSame(3, RiderJobs::activeCount($rider1->id));

        $fourth = $this->readyJob();
        $this->autoAssign($fourth);
        $this->assertNull($this->pendingOffer($fourth));
        $this->assignTo($fourth, $rider1)->assertSessionHas('error', 'Pedro Jose Garcia already has 3 active jobs (the limit).');
    }

    public function test_RID_JOB_007_cannot_accept_another_companys_job(): void
    {
        $rider1 = $this->rider1();
        $davaoRider = $this->davaoRider();
        $this->goOnline($rider1, $davaoRider);
        $theirs = $this->makeOrder($this->buyer2(), $this->p05(), 'Ready for Pickup', ['origin_center_id' => $this->davaoCenter()->id, 'pickup_date' => today()]);
        $offerId = DB::table('job_offers')->insertGetId(['order_id' => $theirs, 'rider_id' => $davaoRider->id, 'status' => 'pending', 'offered_at' => now(), 'expires_at' => now()->addMinute(), 'created_at' => now(), 'updated_at' => now()]);

        $this->asRider($rider1)->post(route('rider.offers.respond', $offerId), ['answer' => 'accept'])->assertSessionHas('error', 'Job offer not found.');
        $this->post(route('rider.pickup.accept', $theirs))->assertSessionHas('error');

        $this->assertSame('Ready for Pickup', $this->statusOf($theirs));
        $this->assertNull(DB::table('orders')->where('id', $theirs)->value('rider_id'));
    }

    /* ---------------------------------------------------------------
     | R4 Pickup
     * --------------------------------------------------------------- */

    public function test_RID_PICK_001_pickup_details_shown(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $rider1);

        $html = $this->asRider($rider1)->get(route('rider.pickups'))->assertOk()->getContent();
        foreach (['Maria Clara Handicrafts', 'Commonwealth, Quezon City', '09189876543', Waybill::number($orderId), '1× Rattan Fruit Basket'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_RID_PICK_002_confirm_pickup_by_scanning(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $rider1);

        $this->pickUp($orderId, $rider1)->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Picked Up', $order->status);
        $this->assertNotNull($order->picked_up_at);
        $this->assertSame('to-receive', \App\Support\BuyerOrderTabs::group($order));
        $this->assertSame(1, DB::table('order_events')->where('order_id', $orderId)->where('status', 'Picked Up')->count());

        $this->flushSession();
        $this->actingAsUser($this->seller1())->post(route('seller.order.status', $orderId), ['status' => 'Cancelled', 'cancel_reason' => 'Out of stock'])->assertSessionHas('error');
        $this->assertSame('Picked Up', $this->statusOf($orderId));
        $this->assertSame(30.0, RiderJobs::pay($rider1->id, today()->startOfDay(), today()->endOfDay())['total']);
    }

    public function test_RID_PICK_003_confirm_pickup_by_typing_the_tracking_number(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $rider1);

        $typed = strtolower(str_replace('-', '', Waybill::number($orderId)));
        $this->asRider($rider1)->post(route('rider.pickup.scan', $orderId), ['tracking_number' => '  ' . $typed . ' '])->assertSessionHas('success');
        $this->assertSame('Picked Up', $this->statusOf($orderId));
    }

    public function test_RID_PICK_004_wrong_tracking_number(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $other = $this->readyJob();
        $this->assignTo($orderId, $rider1);

        foreach ([Waybill::number($other), 'TRK-0000000'] as $code) {
            $this->asRider($rider1)->post(route('rider.pickup.scan', $orderId), ['tracking_number' => $code])
                ->assertSessionHas('error', 'The tracking number does not match this job.');
        }
        $this->assertSame('Pickup Assigned', $this->statusOf($orderId));
    }

    public function test_RID_PICK_005_double_scan_at_pickup(): void
    {
        $rider1 = $this->rider1();
        $this->goOnline($rider1);
        $orderId = $this->readyJob();
        $this->assignTo($orderId, $rider1);

        $this->pickUp($orderId, $rider1)->assertSessionHas('success');
        $this->pickUp($orderId, $rider1)->assertSessionHas('error', 'This parcel was already picked up.');

        $this->assertSame(1, DB::table('order_events')->where('order_id', $orderId)->where('status', 'Picked Up')->count());
        $this->assertSame(30.0, RiderJobs::pay($rider1->id, today()->startOfDay(), today()->endOfDay())['total']);
    }

    /* ---------------------------------------------------------------
     | R5 Delivery & Status Updates
     * --------------------------------------------------------------- */

    public function test_RID_DLV_001_deliver_with_proof_in_sequence(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $payBefore = RiderJobs::pay($rider1->id, today()->startOfDay(), today()->endOfDay())['total'];

        $this->outFor($orderId, $rider1)->assertSessionHas('success');
        $otp = $this->otp($orderId);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee($otp);

        $this->deliver($orderId, $rider1, ['cod_amount' => '500.00'])->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Delivered', $order->status);
        $this->assertNotNull($order->delivery_proof);

        $statuses = DB::table('order_events')->where('order_id', $orderId)->orderBy('id')->pluck('status')->all();
        foreach (['Picked Up', 'At Sorting Center', 'Assigned for Delivery', 'Out for Delivery', 'Delivered'] as $status) {
            $this->assertContains($status, $statuses);
        }

        $this->assertSame(500.0, app(\App\Services\CodRemittanceService::class)->balance($rider1->id)['balance']);
        $this->assertSame($payBefore + 50.0, RiderJobs::pay($rider1->id, today()->startOfDay(), today()->endOfDay())['total']);
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->get(route('buyer.orders'))->assertSee('Delivered');
    }

    public function test_RID_DLV_002_skipping_a_status(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $this->assertSame('Assigned for Delivery', $this->statusOf($orderId));

        $this->deliver($orderId, $rider1)->assertSessionHas('error', 'This order must be moved to Out for Delivery first.');
        $this->assertSame('Assigned for Delivery', $this->statusOf($orderId));
    }

    public function test_RID_DLV_003_photo_is_required(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $this->outFor($orderId, $rider1);
        $message = 'Take a photo of the parcel with the buyer (JPG, PNG or WEBP, up to 5 MB) to mark it Delivered.';

        $this->deliver($orderId, $rider1, ['delivery_proof' => null])->assertSessionHas('error', $message);
        $this->deliver($orderId, $rider1, ['delivery_proof' => $this->jpegOfSize('pod_photo.jpg', $this->mb(5.1))])->assertSessionHas('error', $message);
        $this->assertSame('Out for Delivery', $this->statusOf($orderId));
    }

    public function test_RID_DLV_004_otp_checks(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $this->outFor($orderId, $rider1);
        $right = $this->otp($orderId);
        $wrong = $right === '000000' ? '111111' : '000000';

        $this->deliver($orderId, $rider1, ['otp' => '12345'])->assertSessionHas('error', 'Enter the buyer\'s 6-digit delivery OTP.');
        for ($i = 1; $i <= 4; $i++) {
            $this->deliver($orderId, $rider1, ['otp' => $wrong])->assertSessionHas('error', 'Wrong OTP. ' . (5 - $i) . ' tries left before OTP entry locks.');
        }
        $this->deliver($orderId, $rider1, ['otp' => $wrong])->assertSessionHas('error', 'Wrong OTP. After 5 wrong entries, OTP entry is locked for 15 minutes.');
        $this->deliver($orderId, $rider1, ['otp' => $right])->assertSessionHas('error', fn ($m) => str_starts_with($m, 'Too many wrong OTPs.'));
        $this->assertSame('Out for Delivery', $this->statusOf($orderId));

        $this->travel(15)->minutes();
        $this->travel(1)->seconds();
        $this->deliver($orderId, $rider1, ['otp' => $right])->assertSessionHas('success');
        $this->assertSame('Delivered', $this->statusOf($orderId));
    }

    public function test_RID_DLV_005_cod_amount_must_match(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $this->outFor($orderId, $rider1);

        foreach (['499.99', '500.01'] as $amount) {
            $this->deliver($orderId, $rider1, ['cod_amount' => $amount])->assertSessionHas('error', 'The COD amount collected must be exactly ₱500.00.');
        }
        $this->assertSame('Out for Delivery', $this->statusOf($orderId));

        $this->deliver($orderId, $rider1, ['cod_amount' => '500.00'])->assertSessionHas('success');
        $this->assertSame(500.0, (float) DB::table('orders')->where('id', $orderId)->value('cod_amount_collected'));
    }

    public function test_RID_DLV_006_prepaid_order_has_no_cod(): void
    {
        $rider1 = $this->rider1();
        $orderId = $this->readyJob(null, 'GCash');
        $this->assertSame('Paid', DB::table('orders')->where('id', $orderId)->value('payment_status'));
        $this->toDelivery($orderId, $rider1);
        $this->outFor($orderId, $rider1);

        $this->asRider($rider1)->get(route('rider.delivery.details', $orderId))->assertOk()->assertSee('COD ₱0.00')->assertDontSee('name="cod_amount"', false);
        $this->deliver($orderId, $rider1, ['cod_amount' => null])->assertSessionHas('success');

        $this->assertSame('Delivered', $this->statusOf($orderId));
        $this->assertSame(0.0, app(\App\Services\CodRemittanceService::class)->balance($rider1->id)['balance']);
    }

    public function test_RID_DLV_007_cannot_update_another_riders_job(): void
    {
        $rider1 = $this->rider1();
        $rider2 = $this->rider2();
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1, $rider2);

        $this->asRider($rider1)->post(route('rider.delivery.status', $orderId), ['status' => 'Out for Delivery'])->assertSessionHas('error', 'This delivery is not assigned to you.');
        $this->assertSame('Assigned for Delivery', $this->statusOf($orderId));
        $this->assertSame($rider2->id, (int) DB::table('orders')->where('id', $orderId)->value('delivery_rider_id'));
    }

    /* ---------------------------------------------------------------
     | R6 Failed Delivery
     * --------------------------------------------------------------- */

    public function test_RID_FAIL_001_mark_failed_with_a_reason(): void
    {
        $orderId = $this->outJob();
        $payBefore = RiderJobs::pay($this->rider1()->id, today()->startOfDay(), today()->endOfDay())['total'];

        $this->failAttempt($orderId, $this->rider1(), 'Buyer not available')->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertSame(['Delivery Failed', 1, null], [$order->status, (int) $order->delivery_attempts, $order->cod_collected_at]);
        $this->assertStringContainsString('Buyer not available', $order->failure_reason);
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Delivery Attempt Failed'));
        $this->assertTrue($this->notifiedWith($this->logistics1(), 'Delivery Attempt Failed'));
        $this->assertSame($payBefore, RiderJobs::pay($this->rider1()->id, today()->startOfDay(), today()->endOfDay())['total']);
        $this->asRider($this->rider1())->get(route('rider.delivery.details', $orderId))->assertSee('Attempt 1 of 3');
    }

    public function test_RID_FAIL_002_reason_required(): void
    {
        $orderId = $this->outJob();

        $this->asRider($this->rider1())->post(route('rider.delivery.status', $orderId), ['status' => 'Delivery Failed'])
            ->assertSessionHas('error', 'Please choose a reason for the failed delivery.');
        $this->assertSame('Out for Delivery', $this->statusOf($orderId));
    }

    public function test_RID_FAIL_003_refused_goes_straight_to_return_to_sender(): void
    {
        $orderId = $this->outJob();

        $this->failAttempt($orderId, $this->rider1(), \App\Services\DeliveryService::REFUSED_REASON)->assertSessionHas('success');
        $order = DB::table('orders')->find($orderId);
        $this->assertNotNull($order->rts_at);
        $this->assertSame('Return to Sender', \App\Support\OrderStatus::shipmentLabel($order));

        $this->asLogistics()->post(route('logistics.parcels.confirm-back', $orderId));
        $this->post(route('logistics.parcels.reschedule', $orderId), ['rider_id' => $this->rider1()->id])->assertSessionHas('error', fn ($m) => str_contains($m, 'refused'));
    }

    public function test_RID_FAIL_004_attempt_counter_and_reschedule(): void
    {
        $orderId = $this->outJob();
        $this->failAttempt($orderId, $this->rider1(), 'Buyer not available');

        $this->retry($orderId);
        $this->asRider($this->rider1())->get(route('rider.delivery.details', $orderId))->assertSee('Attempt 2 of 3');
        $this->deliver($orderId, $this->rider1())->assertSessionHas('success');
        $this->assertSame('Delivered', $this->statusOf($orderId));
    }

    public function test_RID_FAIL_005_return_to_sender_after_the_3rd_failed_attempt(): void
    {
        $orderId = $this->outJob();
        $this->failAttempt($orderId, $this->rider1(), 'Buyer not available');
        $this->retry($orderId);
        $this->failAttempt($orderId, $this->rider1(), 'Buyer not available');
        $this->retry($orderId);

        $this->failAttempt($orderId, $this->rider1(), 'Wrong or incomplete address')->assertSessionHas('success', fn ($m) => str_contains($m, 'Return to Sender'));
        $order = DB::table('orders')->find($orderId);
        $this->assertSame(3, (int) $order->delivery_attempts);
        $this->assertSame('Return to Sender', \App\Support\OrderStatus::shipmentLabel($order));

        $this->asLogistics()->post(route('logistics.parcels.confirm-back', $orderId));
        $this->post(route('logistics.parcels.reschedule', $orderId), ['rider_id' => $this->rider1()->id])->assertSessionHas('error', fn ($m) => str_contains($m, 'maximum delivery attempts'));
        $this->assertTrue($this->notifiedWith($this->buyer1(), 'Return to Sender'));
        $this->assertTrue($this->notifiedWith($this->seller1(), 'Return to Sender'));
    }

    /* ---------------------------------------------------------------
     | R7 History, Earnings & COD Remittance
     * --------------------------------------------------------------- */

    public function test_RID_EARN_001_delivery_history(): void
    {
        [$delivered] = $this->codDelivered([$this->p02()]);
        $failed = $this->outJob();
        $this->failAttempt($failed, $this->rider1(), 'Buyer not available');

        $jobs = $this->asRider($this->rider1())->get(route('rider.deliveries.history', ['from' => today()->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()->viewData('jobs');

        $rows = collect($jobs)->map(fn ($j) => $j['tracking_number'] . '|' . $j['kind'] . '|' . explode(':', $j['result'])[0] . '|' . $j['pay'])->all();
        $this->assertEqualsCanonicalizing([
            Waybill::number($delivered) . '|Pickup|Picked up|30', Waybill::number($delivered) . '|Delivery|Delivered|50',
            Waybill::number($failed) . '|Pickup|Picked up|30', Waybill::number($failed) . '|Delivery|Failed|0',
        ], $rows);
        $this->assertCount(count(array_unique($rows)), $rows);
    }

    public function test_RID_EARN_002_pay_per_job_and_daily_total(): void
    {
        $this->sheetRates();
        $rider = $this->rider1();
        $product = $this->p02();

        // Today: 2 pickups, 3 successful deliveries, 1 failed attempt.
        foreach ([1, 2] as $_) {
            $this->makeOrder($this->buyer1(), $product, 'Picked Up', ['rider_id' => $rider->id, 'picked_up_at' => now(), 'pickup_fee' => RiderJobs::PICKUP_PAY]);
        }
        foreach ([1, 2, 3] as $_) {
            $this->makeOrder($this->buyer1(), $product, 'Delivered', ['delivery_rider_id' => $rider->id, 'delivered_at' => now(), 'rider_fee' => \App\Support\DeliveryFee::baseFee()]);
        }
        $failed = $this->makeOrder($this->buyer1(), $product, 'Delivery Failed', ['delivery_rider_id' => $rider->id, 'delivery_attempts' => 1, 'delivery_failed_at' => now()]);
        \App\Support\OrderTimeline::log($failed, 'Delivery Failed', 'Delivery attempt 1 of 3 failed', 'Buyer not available');

        $page = $this->asRider($rider)->get(route('rider.profit'))->assertOk();
        $this->assertSame(210.0, $page->viewData('payToday')['total']);
        $this->assertGreaterThanOrEqual(210.0, $page->viewData('payWeek')['total']);
        $page->assertSee('₱210.00');
    }

    public function test_RID_EARN_003_cod_balance_to_remit(): void
    {
        $ids = $this->codDelivered([$this->p02(), $this->p03()]);
        $this->assertSame([500.0, 1300.0], array_map(fn ($id) => (float) DB::table('orders')->where('id', $id)->value('total_amount'), $ids));

        $page = $this->asRider($this->rider1())->get(route('rider.profit'))->assertOk();
        $cod = $page->viewData('cod');
        $this->assertSame(1800.0, $cod['balance']);
        $this->assertEqualsCanonicalizing($ids, $cod['orders']->pluck('id')->map(fn ($id) => (int) $id)->all());
        $page->assertSee('₱1,800.00');
    }

    public function test_RID_EARN_004_remit_cod_and_confirmation(): void
    {
        $this->codDelivered([$this->p02(), $this->p03()]);
        $cod = app(\App\Services\CodRemittanceService::class);

        $this->asRider($this->rider1())->post(route('rider.cod.remit'), ['amount' => '1800.00'])->assertSessionHas('success');
        $remittance = DB::table('cod_remittances')->first();
        $this->assertSame('Pending', $remittance->status);
        $this->assertSame(1800.0, $cod->balance($this->rider1()->id)['balance']);
        $this->assertNotNull($cod->balance($this->rider1()->id)['pending']);

        $this->asLogistics()->post(route('logistics.cod.confirm', $remittance->id))->assertSessionHas('success');
        $this->assertSame(0.0, $cod->balance($this->rider1()->id)['balance']);
    }

    public function test_RID_EARN_005_short_remittance(): void
    {
        $this->codDelivered([$this->p02(), $this->p03()]);

        $this->asRider($this->rider1())->post(route('rider.cod.remit'), ['amount' => '1750.00'])->assertSessionHas('success');
        $row = $this->asLogistics()->get(route('logistics.cod'))->assertOk()->viewData('rows')->firstWhere('rider_id', $this->rider1()->id);
        $this->assertTrue($row->short);
        $this->assertSame(-50.0, $row->difference);

        $this->post(route('logistics.cod.confirm', DB::table('cod_remittances')->value('id')))->assertSessionHas('success', 'Remittance of ₱1,750.00 confirmed. Shortfall: ₱50.00.');
        $balance = app(\App\Services\CodRemittanceService::class)->balance($this->rider1()->id);
        $this->assertSame([50.0, 50.0], [$balance['shortfall'], $balance['balance']]);
    }

    public function test_RID_EARN_006_rider_cannot_confirm_own_remittance(): void
    {
        $this->codDelivered([$this->p02()]);
        $this->asRider($this->rider1())->post(route('rider.cod.remit'), ['amount' => '500.00']);
        $id = DB::table('cod_remittances')->value('id');

        $this->asRider($this->rider1())->post(route('logistics.cod.confirm', $id))->assertForbidden();
        $this->assertSame('Pending', DB::table('cod_remittances')->where('id', $id)->value('status'));
    }
}
