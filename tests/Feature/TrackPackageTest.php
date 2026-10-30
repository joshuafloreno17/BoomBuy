<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class TrackPackageTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();

        // Map lookups are faked per test; never reach the real OSRM / Nominatim.
        Http::preventStrayRequests();
    }

    private function inTransitOrder(): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $seller->update(['province' => 'Cavite', 'city_municipality' => 'Imus City']);

        $orderId = $this->makeOrder($buyer, $this->makeProduct($seller), 'In Transit', [
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'delivery_zone' => 'island',
            'dispatched_at' => now(),
        ]);

        return [$buyer, $orderId];
    }

    /** Nominatim knows Imus and Santa Cruz; OSRM answers with $osrm. */
    private function fakeMaps($osrm): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => fn ($request) => Http::response(match (true) {
                str_contains(urldecode($request->url()), 'Imus') => [['lat' => '14.4297', 'lon' => '120.9367']],
                str_contains(urldecode($request->url()), 'Santa Cruz') => [['lat' => '14.2785', 'lon' => '121.4163']],
                default => [],
            }),
            'router.project-osrm.org/*' => $osrm,
        ]);
    }

    private function sentTo(string $host): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), $host))->count();
    }

    public function test_buyer_sees_the_road_route_waybill_and_cod_amount(): void
    {
        $this->fakeMaps(Http::response([
            'code' => 'Ok',
            'routes' => [['distance' => 82000, 'geometry' => ['coordinates' => [[120.937, 14.43], [121.1, 14.3], [121.416, 14.279]]]]],
        ]));

        [$buyer, $orderId] = $this->inTransitOrder();

        $this->actingAsUser($buyer)->get(route('buyer.order.track', $orderId))
            ->assertOk()
            ->assertSee('Track package')
            ->assertSee(\App\Support\Waybill::number($orderId))
            ->assertSee('Please prepare')
            ->assertSee('~82 km by road')
            ->assertSee('data-from="Imus City, Cavite"', false)
            ->assertSee('Santa Cruz, Laguna');

        // Saved: the second visit asks neither OSRM nor Nominatim again.
        $this->actingAsUser($buyer)->get(route('buyer.order.track', $orderId))->assertOk();
        $this->assertSame(1, $this->sentTo('osrm'));
        $this->assertSame(2, $this->sentTo('nominatim'));
        $this->assertDatabaseHas('parcel_routes', ['distance_km' => 82]);
        $this->assertDatabaseHas('place_coordinates', ['town' => 'Imus City', 'province' => 'Cavite', 'lat' => 14.4297]);
    }

    public function test_a_failed_road_lookup_falls_back_to_a_curve(): void
    {
        $this->fakeMaps(Http::response('down', 503));

        [$buyer, $orderId] = $this->inTransitOrder();

        $this->actingAsUser($buyer)->get(route('buyer.order.track', $orderId))
            ->assertOk()
            ->assertSee('data-road="0"', false)
            ->assertDontSee('km by road');

        $this->assertNull(DB::table('parcel_routes')->value('points'));

        // One retry, then not tried again for a while.
        $this->actingAsUser($buyer)->get(route('buyer.order.track', $orderId))->assertOk();
        $this->assertSame(2, $this->sentTo('osrm'));
    }

    public function test_sorting_centers_on_the_trip_are_landmarks_linked_from_the_history(): void
    {
        $this->fakeMaps(Http::response('down', 503));

        $center = fn (string $province, string $town) => \App\Models\SortingCenter::create([
            'name' => \App\Models\SortingCenter::nameFor($province),
            'region' => 'Region IV-A (CALABARZON)',
            'province' => $province,
            'city_municipality' => $town,
            'address' => '1 Center Rd, ' . $town,
            'is_active' => true,
        ]);
        $cavite = $center('Cavite', 'Imus City');
        $laguna = $center('Laguna', 'Santa Cruz');

        [$buyer, $orderId] = $this->inTransitOrder();
        DB::table('orders')->where('id', $orderId)->update([
            'origin_center_id' => $cavite->id,
            'destination_center_id' => $laguna->id,
            'current_center_id' => $cavite->id,
        ]);

        $page = $this->actingAsUser($buyer)->get(route('buyer.order.track', $orderId))->assertOk();

        // Both centers are on the map and their history steps point at them.
        $page->assertSee('data-map-stop="6"', false)
            ->assertSee('data-map-stop="8"', false)
            ->assertSee('1 Center Rd, Santa Cruz');
    }

    public function test_another_buyers_order_is_not_found(): void
    {
        [, $orderId] = $this->inTransitOrder();

        $this->actingAsUser($this->makeUser())->get(route('buyer.order.track', $orderId))->assertNotFound();
    }
}
