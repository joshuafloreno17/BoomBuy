<?php

namespace Tests\Feature;

use App\Models\SortingCenter;
use App\Models\User;
use App\Support\HubRoute;
use App\Support\Waybill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Hub relay: a parcel whose two centers are more than HubRoute::LEG_KM apart
 * is trucked through a hub on the way, picked automatically. Vigan seller →
 * Santa Cruz buyer, about 222 km apart here, with Cabanatuan in between.
 */
class HubRelayTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private SortingCenter $vigan;
    private SortingCenter $cabanatuan;
    private SortingCenter $santaCruz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        // Fixed positions 1° of latitude (~111 km) apart, so the route is known.
        $this->vigan = $this->center('Ilocos Sur', 'Vigan City', 16.0);
        $this->cabanatuan = $this->center('Nueva Ecija', 'Cabanatuan City', 15.0);
        $this->santaCruz = $this->center('Laguna', 'Santa Cruz', 14.0);
        DB::table('sorting_centers')->whereNotIn('id', [$this->vigan->id, $this->cabanatuan->id, $this->santaCruz->id])->update(['is_active' => false]);
        HubRoute::flush();
    }

    private function center(string $province, string $city, float $lat): SortingCenter
    {
        DB::table('place_coordinates')->updateOrInsert(['town' => $city, 'province' => $province], ['lat' => $lat, 'lng' => 121.0, 'fetched_at' => now()]);

        return SortingCenter::create([
            'name' => $city . ' Sorting Center', 'region' => \App\Support\PhLocations::region($province),
            'province' => $province, 'city_municipality' => $city, 'address' => 'Poblacion, ' . $city, 'is_active' => true,
        ]);
    }

    private function staff(SortingCenter $center): User
    {
        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $center->id]);

        return $staff;
    }

    private function scan(User $staff, string $code, ?string $mode = null)
    {
        $this->travel(31)->seconds();

        return $this->actingAsUser($staff)->post(route('logistics.parcels.scan'), array_filter(['code' => $code, 'mode' => $mode]));
    }

    private function order(): object
    {
        return DB::table('orders')->latest('id')->first();
    }

    public function test_the_route_goes_through_the_hub_and_never_has_a_leg_over_the_limit(): void
    {
        $this->assertSame([$this->vigan->id, $this->cabanatuan->id, $this->santaCruz->id], HubRoute::path($this->vigan->id, $this->santaCruz->id));
        $this->assertSame($this->cabanatuan->id, HubRoute::nextHop($this->vigan->id, $this->santaCruz->id));
        $this->assertSame($this->santaCruz->id, HubRoute::nextHop($this->cabanatuan->id, $this->santaCruz->id));
        $this->assertLessThanOrEqual(HubRoute::LEG_KM, HubRoute::legKm($this->vigan->id, $this->cabanatuan->id));
        $this->assertSame([], HubRoute::relays($this->cabanatuan->id, $this->santaCruz->id));
    }

    public function test_a_parcel_is_scanned_in_and_out_at_the_hub_on_its_way(): void
    {
        $seller = $this->makeSeller('shoes', 'Vigan Shoes');
        $seller->forceFill(['province' => 'Ilocos Sur', 'city_municipality' => 'Vigan City'])->save();
        $buyer = $this->makeUser();
        $this->actingAsUser($buyer, ['cart' => ["{$this->makeProduct($seller)->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => '15 Rizal St, Santa Cruz, Laguna', 'phone' => '09171234567', 'payment' => 'Cash on Delivery',
        ]);
        $id = (int) $this->order()->id;
        $this->shipFromSeller($seller, $id);
        $this->assertSame($this->santaCruz->id, (int) $this->order()->destination_center_id);
        $waybill = Waybill::number($id);

        $viganStaff = $this->staff($this->vigan);
        $hubStaff = $this->staff($this->cabanatuan);
        $santaCruzStaff = $this->staff($this->santaCruz);

        // The label names the hub.
        $this->assertSame(['Cabanatuan City Sorting Center'], \App\Support\ShippingLabel::build($this->order(), (int) $seller->id)['hubs']);

        // Vigan: in, then out — the truck goes to the hub, not straight to Santa Cruz.
        $this->scan($viganStaff, $waybill, 'in')->assertSessionHas('scan.ok', true);
        $this->scan($viganStaff, $waybill, 'out')->assertSessionHas('scan.ok', true);
        $this->assertSame('In Transit', $this->order()->status);
        $this->assertSame($this->cabanatuan->id, (int) $this->order()->next_center_id);

        // Santa Cruz can't take it yet; the hub sees it coming.
        $this->scan($santaCruzStaff, $waybill, 'in')->assertSessionHas('scan.ok', false);
        $this->assertStringContainsString('Cabanatuan', session('scan')['message']);
        $this->actingAsUser($hubStaff)->get(route('logistics.parcels', ['tab' => 'arrivals']))->assertSee('Order #' . $id);

        // The hub: in (it only passes through — not sorted here), then out to Santa Cruz.
        $this->scan($hubStaff, $waybill, 'in')->assertSessionHas('scan.ok', true);
        $this->assertSame('At Sorting Center', $this->order()->status);
        $this->assertSame($this->cabanatuan->id, (int) $this->order()->current_center_id);
        $this->actingAsUser($hubStaff)->get(route('logistics.parcels', ['tab' => 'arrivals']))->assertSee('Dispatch to Santa Cruz Sorting Center');
        $this->scan($hubStaff, $waybill, 'out')->assertSessionHas('scan.ok', true);
        $this->assertSame($this->santaCruz->id, (int) $this->order()->next_center_id);

        // Santa Cruz: in — arrived and sorted for delivery.
        $this->scan($santaCruzStaff, $waybill, 'in')->assertSessionHas('scan.ok', true);
        $this->assertSame('Sorted', $this->order()->status);
        $this->assertNull($this->order()->next_center_id);

        $titles = DB::table('order_events')->where('order_id', $id)->pluck('title')->implode(' | ');
        $this->assertStringContainsString('On its way to Cabanatuan City Sorting Center (hub)', $titles);
        $this->assertStringContainsString('Arrived at Cabanatuan City Sorting Center (hub)', $titles);
    }

    public function test_the_buttons_follow_the_same_route(): void
    {
        $seller = $this->makeSeller('shoes', 'Vigan Shoes');
        $seller->forceFill(['province' => 'Ilocos Sur', 'city_municipality' => 'Vigan City'])->save();
        $buyer = $this->makeUser();
        $this->actingAsUser($buyer, ['cart' => ["{$this->makeProduct($seller)->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => '15 Rizal St, Santa Cruz, Laguna', 'phone' => '09171234567', 'payment' => 'Cash on Delivery',
        ]);
        $id = (int) $this->order()->id;
        $this->shipFromSeller($seller, $id);

        $centers = app(\App\Services\SortingCenterService::class);
        $centers->confirmReceived($id, $this->vigan->id);
        $this->assertStringContainsString('Cabanatuan', $centers->dispatch($id, $this->vigan->id));

        try {
            $centers->confirmArrival($id, $this->santaCruz->id);
            $this->fail('Santa Cruz confirmed a parcel still headed to the hub.');
        } catch (\App\Exceptions\ActionFailed $e) {
            $this->assertSame('In Transit', $this->order()->status);
        }

        $this->assertStringContainsString('Next: dispatch it to Santa Cruz', $centers->confirmArrival($id, $this->cabanatuan->id));
        $this->assertStringContainsString('1 parcel(s) dispatched', $centers->dispatchAll($this->santaCruz->id, $this->cabanatuan->id));
        $centers->confirmArrival($id, $this->santaCruz->id);
        $this->assertSame($this->santaCruz->id, (int) $this->order()->current_center_id);
    }
}
