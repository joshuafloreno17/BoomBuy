<?php

namespace Tests\Feature;

use App\Models\SortingCenter;
use App\Models\User;
use App\Support\Waybill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * Scan-only hub work on the Parcels page: each waybill scan at a center does
 * the parcel's next step there, with no button. Quezon City seller → Santa
 * Cruz, Laguna buyer (no branches).
 */
class ParcelScanTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private SortingCenter $qc;
    private SortingCenter $santaCruz;
    private User $qcStaff;
    private User $santaCruzStaff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->qc = $this->center('Metro Manila (NCR)', 'Quezon City');
        $this->santaCruz = $this->center('Laguna', 'Santa Cruz');
        $this->qcStaff = $this->staff($this->qc);
        $this->santaCruzStaff = $this->staff($this->santaCruz);
    }

    private function center(string $province, string $city): SortingCenter
    {
        return SortingCenter::create([
            'name' => $city . ' Sorting Center', 'region' => \App\Support\PhLocations::region($province),
            'province' => $province, 'city_municipality' => $city, 'address' => 'Poblacion, ' . $city, 'is_active' => true,
        ]);
    }

    private function staff(?SortingCenter $center): User
    {
        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $center?->id]);

        return $staff;
    }

    /** An order picked up from the QC seller, on its way to the QC center. */
    private function pickedUpOrder(): int
    {
        $seller = $this->makeSeller('shoes', 'QC Shoes');
        $seller->forceFill(['province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City'])->save();
        $buyer = $this->makeUser();

        $this->actingAsUser($buyer, ['cart' => ["{$this->makeProduct($seller)->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => '15 Rizal St, Santa Cruz, Laguna', 'phone' => '09171234567', 'payment' => 'Cash on Delivery',
        ]);

        $id = (int) DB::table('orders')->where('buyer_id', $buyer->id)->value('id');
        $this->shipFromSeller($seller, $id);

        return $id;
    }

    private function scanAs(User $staff, string $code)
    {
        return $this->actingAsUser($staff)->post(route('logistics.parcels.scan'), ['code' => $code]);
    }

    private function statusOf(int $id): string
    {
        return (string) DB::table('orders')->where('id', $id)->value('status');
    }

    public function test_scans_alone_move_a_parcel_from_the_seller_center_to_the_buyer_center(): void
    {
        $id = $this->pickedUpOrder();
        $waybill = Waybill::number($id);
        $this->assertSame('Picked Up', $this->statusOf($id));

        // QC: the rider brings it in — one scan confirms it.
        $this->scanAs($this->qcStaff, $waybill)->assertRedirect(route('logistics.parcels', ['scanned' => 1]))->assertSessionHas('scan.ok', true);
        $this->assertSame('At Sorting Center', $this->statusOf($id));
        $this->get(route('logistics.parcels', ['scanned' => 1]))->assertSee(Waybill::pretty($waybill) . ' → At Sorting Center')->assertSee('Undo');

        // The same waybill read twice in a row changes nothing.
        $this->scanAs($this->qcStaff, $waybill)->assertSessionHas('scan.ok', false);
        $this->assertSame('At Sorting Center', $this->statusOf($id));
        $this->assertStringContainsString('Already scanned', session('scan')['message']);

        // Later, onto the truck to Santa Cruz — the QR link a camera reads works too.
        $this->travel(31)->seconds();
        $this->scanAs($this->qcStaff, route('logistics.scan', $waybill))->assertSessionHas('scan.ok', true);
        $this->assertSame('In Transit', $this->statusOf($id));

        // QC scans it again by mistake: it is Santa Cruz's now — nothing changes.
        $this->travel(31)->seconds();
        $this->scanAs($this->qcStaff, $waybill)->assertSessionHas('scan.ok', false);
        $this->assertStringContainsString('Not for this center', session('scan')['message']);
        $this->assertStringContainsString('Santa Cruz Sorting Center', session('scan')['message']);
        $this->assertSame('In Transit', $this->statusOf($id));

        // Santa Cruz: typed with spaces, lower case — arrival confirmed and,
        // as this center delivers it, sorted in the same scan (no Sort button).
        $this->scanAs($this->santaCruzStaff, strtolower(Waybill::pretty($waybill)))->assertSessionHas('scan.ok', true);
        $this->assertSame('Sorted', $this->statusOf($id));
        $this->assertSame($this->santaCruz->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));
        $this->assertContains('At Sorting Center', DB::table('order_events')->where('order_id', $id)->pluck('status')->all());

        // Choosing the rider is a person's job — the scan only opens it.
        $this->travel(31)->seconds();
        $this->scanAs($this->santaCruzStaff, $waybill)
            ->assertRedirect(route('logistics.parcels', ['q' => $waybill, 'scanned' => 1]))
            ->assertSessionHas('scan.kind', 'manual');
        $this->assertStringContainsString('assign the delivery rider', session('scan')['message']);
        $this->assertSame('Sorted', $this->statusOf($id));
    }

    public function test_undo_puts_the_parcel_back(): void
    {
        $id = $this->pickedUpOrder();
        $eventsBefore = DB::table('order_events')->where('order_id', $id)->count();
        $notificationsBefore = DB::table('notifications')->where('reference_id', $id)->count();

        $this->scanAs($this->qcStaff, Waybill::number($id));
        $token = session('scan')['undo'];
        $this->assertSame('At Sorting Center', $this->statusOf($id));

        $this->post(route('logistics.parcels.scan.undo'), ['token' => $token])->assertSessionHas('scan.ok', true);
        $this->assertSame('Picked Up', $this->statusOf($id));
        $this->assertNull(DB::table('orders')->where('id', $id)->value('current_center_id'));
        $this->assertSame($eventsBefore, DB::table('order_events')->where('order_id', $id)->count());
        $this->assertSame($notificationsBefore, DB::table('notifications')->where('reference_id', $id)->count());

        // Only once; and it can be scanned in again right away.
        $this->post(route('logistics.parcels.scan.undo'), ['token' => $token])->assertSessionHas('scan.ok', false);
        $this->scanAs($this->qcStaff, Waybill::number($id))->assertSessionHas('scan.ok', true);
        $this->assertSame('At Sorting Center', $this->statusOf($id));
    }

    public function test_undo_is_refused_when_too_late(): void
    {
        $id = $this->pickedUpOrder();
        $this->scanAs($this->qcStaff, Waybill::number($id));
        $token = session('scan')['undo'];

        $this->travel(\App\Services\ParcelScanService::UNDO_SECONDS + 1)->seconds();
        $this->post(route('logistics.parcels.scan.undo'), ['token' => $token])->assertSessionHas('scan.ok', false);
        $this->assertSame('At Sorting Center', $this->statusOf($id));
    }

    public function test_head_office_and_non_waybill_input_only_search(): void
    {
        $id = $this->pickedUpOrder();
        $waybill = Waybill::number($id);

        // Head office has no center: a scan is a search.
        $this->scanAs($this->staff(null), $waybill)->assertRedirect(route('logistics.parcels', ['q' => $waybill]));
        $this->assertSame('Picked Up', $this->statusOf($id));

        // An order no. or a name at a center: a search too.
        $this->scanAs($this->qcStaff, '#' . $id)->assertRedirect(route('logistics.parcels', ['q' => '#' . $id]));
        $this->scanAs($this->qcStaff, 'Juan')->assertRedirect(route('logistics.parcels', ['q' => 'Juan']));
        $this->assertSame('Picked Up', $this->statusOf($id));

        // Unknown waybill.
        $this->scanAs($this->qcStaff, 'BB1234567890')->assertSessionHas('scan.ok', false);

        // The page offers the camera only where a scan can act.
        $this->actingAsUser($this->qcStaff)->get(route('logistics.parcels'))->assertSee('data-scan-cam', false);
        $this->actingAsUser($this->staff(null))->get(route('logistics.parcels'))->assertDontSee('data-scan-cam', false);
    }

    public function test_the_scan_station_in_out_switch_and_its_list(): void
    {
        $id = $this->pickedUpOrder();
        $waybill = Waybill::number($id);
        $scan = fn (string $mode) => $this->actingAsUser($this->qcStaff)->post(route('logistics.parcels.scan'), ['code' => $waybill, 'mode' => $mode]);

        // It is arriving: an OUT scan changes nothing and says to switch.
        $scan('out')->assertSessionHas('scan.ok', false)->assertSessionHas('scan.kind', 'warn');
        $this->assertStringContainsString('Switch to Arriving (IN)', session('scan')['message']);
        $this->assertSame('Picked Up', $this->statusOf($id));

        $scan('in')->assertSessionHas('scan.ok', true);
        $this->assertSame('At Sorting Center', $this->statusOf($id));

        // Now it waits to leave: an IN scan changes nothing.
        $this->travel(31)->seconds();
        $scan('in')->assertSessionHas('scan.kind', 'warn');
        $this->assertSame('At Sorting Center', $this->statusOf($id));

        $scan('out')->assertSessionHas('scan.ok', true);
        $this->assertSame('In Transit', $this->statusOf($id));
        $this->assertSame(['in' => 1, 'out' => 1, 'bad' => 0], array_intersect_key(session('scan_stats'), ['in' => 0, 'out' => 0, 'bad' => 0]));

        // The panel lists the scans, and the card buttons are still on the page.
        $this->get(route('logistics.parcels', ['scanned' => 1]))
            ->assertSee('Scan Station')->assertSee('Arriving (IN)')->assertSee('Leaving (OUT)')
            ->assertSee(Waybill::pretty($waybill) . ' → In Transit')->assertSee('Undo');

        // Undo takes it off today's count.
        $token = session('scan_log')[0]['undo'];
        $this->post(route('logistics.parcels.scan.undo'), ['token' => $token])->assertSessionHas('scan.ok', true);
        $this->assertSame('At Sorting Center', $this->statusOf($id));
        $this->assertSame(0, session('scan_stats')['out']);
        $this->assertSame('undone', session('scan_log')[0]['kind']);
    }
}
