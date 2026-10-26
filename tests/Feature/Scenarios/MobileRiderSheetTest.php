<?php

namespace Tests\Feature\Scenarios;

use App\Support\RiderJobs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The server side of the mobile Rider Modules workbook's [Mobile] rows on
 * rider location (Assumption M13): location is needed to go Online, location
 * off or the app closed for 5 minutes means no new offers, and Delivered
 * keeps where the parcel was handed over. The phone side (permission
 * prompts, Settings link) is in the app.
 */
class MobileRiderSheetTest extends TestCase
{
    use RefreshDatabase;
    use ModuleSheetWorld;
    use DispatchFlows;

    /** Near ADDR-1 (Commonwealth, Quezon City). */
    private const LAT = 14.6976;
    private const LNG = 121.0880;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        config(['app.delivery_checks' => true, 'app.rider_location' => true]);
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(10, 0));
    }

    private function online($rider)
    {
        return $this->asRider($rider)->post(route('rider.availability'), ['online' => 1, 'lat' => self::LAT, 'lng' => self::LNG]);
    }

    public function test_RID_AVAIL_005_location_permission_needed_to_go_online(): void
    {
        $rider1 = $this->rider1();

        // Location not allowed: the page sends no position.
        $this->asRider($rider1)->post(route('rider.availability'), ['online' => 1])
            ->assertSessionHas('error', fn ($m) => str_starts_with($m, 'Location is needed to go Online.'));
        $this->assertFalse(RiderJobs::isOnline($rider1->id));

        // Allowed: Online, and Logistics 1 sees where the rider is.
        $this->online($rider1)->assertSessionHas('success');
        $this->assertTrue(RiderJobs::isOnline($rider1->id));

        $this->asLogistics()->get(route('logistics.ops'))
            ->assertViewHas('riders', fn ($riders) => (bool) $riders->firstWhere('id', $rider1->id)->is_online)
            ->assertSee(self::LAT . ',' . self::LNG, false);
    }

    public function test_RID_AVAIL_006_location_turned_off_while_online(): void
    {
        $rider1 = $this->rider1();
        $this->online($rider1);

        // The app reports location off: no new offer.
        $this->asRider($rider1)->postJson(route('rider.location'), ['off' => 1])->assertOk()->assertJson(['online' => false]);
        $this->travel(1)->minutes();
        $first = $this->readyJob();
        $this->autoAssign($first);
        $this->assertNull($this->pendingOffer($first));

        // Back on: offers come again, without tapping Go Online.
        $this->asRider($rider1)->postJson(route('rider.location'), ['lat' => self::LAT, 'lng' => self::LNG])->assertOk()->assertJson(['online' => true]);
        $next = $this->readyJob();
        $this->autoAssign($next);
        $this->assertSame($rider1->id, (int) $this->pendingOffer($next)->rider_id);
    }

    public function test_RID_AVAIL_007_app_force_closed_while_online(): void
    {
        $rider1 = $this->rider1();
        $this->online($rider1);
        $job = $this->readyJob();
        $this->assignTo($job, $rider1);

        // The app stops sending its location; within 5 minutes it is Offline.
        $this->travel(5)->minutes();
        $this->travel(1)->seconds();
        $this->assertFalse(RiderJobs::isOnline($rider1->id));
        $this->asLogistics()->get(route('logistics.ops'))
            ->assertViewHas('riders', fn ($riders) => !$riders->firstWhere('id', $rider1->id)->is_online);

        $next = $this->readyJob();
        $this->autoAssign($next);
        $this->assertNull($this->pendingOffer($next));

        // The accepted job stays with Rider 1 and shows when the app is reopened.
        $this->assertSame($rider1->id, (int) DB::table('orders')->where('id', $job)->value('rider_id'));
        $this->asRider($rider1)->get(route('rider.pickups'))->assertSee(\App\Support\Waybill::number($job));
    }

    public function test_RID_DLV_011_delivery_location_is_recorded(): void
    {
        $rider1 = $this->rider1();
        $this->online($rider1);
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $rider1);
        $this->outFor($orderId, $rider1);

        $this->deliver($orderId, $rider1, ['lat' => self::LAT, 'lng' => self::LNG])->assertSessionHas('success');

        $order = DB::table('orders')->find($orderId);
        $this->assertEqualsWithDelta(self::LAT, (float) $order->delivered_lat, 0.000001);
        $this->assertEqualsWithDelta(self::LNG, (float) $order->delivered_lng, 0.000001);
        $this->asLogistics()->get(route('logistics.ops'))->assertSee('data-delivered-gps', false);
    }

    public function test_location_needs_a_rider_and_a_real_position(): void
    {
        $rider1 = $this->rider1();
        $this->online($rider1);

        $this->asRider($rider1)->postJson(route('rider.location'), ['lat' => 200, 'lng' => self::LNG])->assertStatus(422);
        $before = DB::table('users')->where('id', $rider1->id)->value('location_at');
        $this->flushSession();
        $this->actingAsUser($this->buyer1())->postJson(route('rider.location'), ['lat' => 1, 'lng' => 1])->assertRedirect();
        $this->assertSame($before, DB::table('users')->where('id', $rider1->id)->value('location_at'));
    }
}
