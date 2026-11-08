<?php

namespace Tests\Feature;

use App\Support\RiderJobs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** Opening the app goes Online by itself; a quiet app for 30 minutes goes Offline. */
class RiderAutoOnlineTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        config(['app.rider_location' => true]);
    }

    private function offlineRider()
    {
        $rider = $this->makeRider();
        DB::table('users')->where('id', $rider->id)->update(['is_online' => false, 'online_at' => null, 'location_at' => null]);

        return $rider;
    }

    public function test_opening_the_app_goes_online_with_a_location(): void
    {
        $rider = $this->offlineRider();

        $this->actingAsUser($rider)->postJson(route('rider.location'), ['lat' => 14.28, 'lng' => 121.41, 'auto_online' => 1])
            ->assertOk()->assertJson(['online' => true, 'went_online' => true]);

        $this->assertTrue(RiderJobs::isOnline($rider->id));
    }

    public function test_a_plain_location_ping_does_not_turn_an_offline_rider_online(): void
    {
        $rider = $this->offlineRider();

        $this->actingAsUser($rider)->postJson(route('rider.location'), ['lat' => 14.28, 'lng' => 121.41])
            ->assertOk()->assertJson(['online' => false, 'went_online' => false]);
    }

    public function test_a_rider_not_yet_approved_is_not_put_online(): void
    {
        $rider = $this->offlineRider();
        DB::table('rider_applications')->where('user_id', $rider->id)->update(['status' => 'Pending']);

        $this->actingAsUser($rider)->postJson(route('rider.location'), ['lat' => 14.28, 'lng' => 121.41, 'auto_online' => 1])
            ->assertJson(['went_online' => false]);
        $this->assertFalse((bool) DB::table('users')->where('id', $rider->id)->value('is_online'));
    }

    public function test_quiet_for_30_minutes_goes_offline_and_frees_open_offers(): void
    {
        $rider = $this->makeRider();
        DB::table('users')->where('id', $rider->id)->update(['is_online' => true, 'online_at' => now()->subHour(), 'location_at' => now()->subMinutes(31)]);
        $fresh = $this->makeRider();
        DB::table('users')->where('id', $fresh->id)->update(['is_online' => true, 'online_at' => now()->subHour(), 'location_at' => now()->subMinutes(2)]);

        $this->assertSame(1, RiderJobs::expireStale());
        $this->assertFalse((bool) DB::table('users')->where('id', $rider->id)->value('is_online'));
        $this->assertTrue((bool) DB::table('users')->where('id', $fresh->id)->value('is_online'));
    }

    public function test_the_go_offline_button_still_works(): void
    {
        $rider = $this->makeRider();

        $this->actingAsUser($rider)->post(route('rider.availability'), ['online' => 0])->assertSessionHas('success');
        $this->assertFalse((bool) DB::table('users')->where('id', $rider->id)->value('is_online'));
    }
}
