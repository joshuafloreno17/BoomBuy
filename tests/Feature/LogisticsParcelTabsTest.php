<?php

namespace Tests\Feature;

use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** The Parcels page's work tabs and its "Confirm all" / "Sort all" buttons. */
class LogisticsParcelTabsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private SortingCenter $qc;
    private SortingCenter $santaCruz;
    private User $qcStaff;
    private User $santaCruzStaff;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->qc = $this->center('Metro Manila (NCR)', 'Quezon City');
        $this->santaCruz = $this->center('Laguna', 'Santa Cruz');
        $this->qcStaff = $this->staff($this->qc);
        $this->santaCruzStaff = $this->staff($this->santaCruz);
        $this->seller = $this->makeSeller('shoes', 'Cubao Kicks');
        $this->seller->forceFill(['province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City'])->save();
    }

    private function center(string $province, string $city): SortingCenter
    {
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

    private function inTransit(): int
    {
        return $this->makeOrder($this->makeUser(), $this->makeProduct($this->seller, ['price' => 500]), 'In Transit', [
            'shipping_address' => '15 P. Guevarra St, Santa Cruz, Laguna', 'shipping_province' => 'Laguna', 'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->qc->id, 'destination_center_id' => $this->santaCruz->id, 'delivery_zone' => 'island',
            'dispatched_at' => now(),
        ]);
    }

    public function test_center_sees_its_seven_tabs_with_the_route_bar(): void
    {
        $id = $this->inTransit();

        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertOk()
            ->assertSee('data-pq-tab="arrivals"', false)
            ->assertSee('data-pq-tab="sort"', false)
            ->assertSee('data-pq-tab="sendout"', false)
            ->assertDontSee('data-pq-tab="collect" aria-selected', false)
            ->assertSee('Santa Cruz Sorting Center (you)')
            ->assertSee('Order #' . $id);
    }

    public function test_confirm_all_then_sort_all(): void
    {
        $a = $this->inTransit();
        $b = $this->inTransit();

        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertSee('Confirm all 2 arrived');

        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.bulk'), ['arrival' => [$a, $b]])
            ->assertSessionHas('success', '2 parcels done.');
        $this->assertSame(['At Sorting Center', 'At Sorting Center'], DB::table('orders')->whereIn('id', [$a, $b])->orderBy('id')->pluck('status')->all());

        $this->actingAsUser($this->santaCruzStaff)->get(route('logistics.parcels'))->assertSee('Sort all 2');

        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.bulk'), ['sort' => [$a, $b]])
            ->assertSessionHas('success');
        $this->assertSame(['Sorted', 'Sorted'], DB::table('orders')->whereIn('id', [$a, $b])->orderBy('id')->pluck('status')->all());
    }

    public function test_another_center_cannot_bulk_confirm_and_nothing_changes(): void
    {
        $a = $this->inTransit();

        $this->actingAsUser($this->qcStaff)
            ->post(route('logistics.parcels.bulk'), ['arrival' => [$a]])
            ->assertSessionHas('error');
        $this->assertSame('In Transit', DB::table('orders')->where('id', $a)->value('status'));
    }

    public function test_a_parcel_that_already_moved_is_skipped_not_fatal(): void
    {
        $a = $this->inTransit();
        $b = $this->inTransit();
        $this->actingAsUser($this->santaCruzStaff)->post(route('logistics.parcels.confirm-arrival', $a));

        $this->actingAsUser($this->santaCruzStaff)
            ->post(route('logistics.parcels.bulk'), ['arrival' => [$a, $b]])
            ->assertSessionHas('success', '1 parcel done. Skipped #' . $a . ' (already moved on).');
    }
}
