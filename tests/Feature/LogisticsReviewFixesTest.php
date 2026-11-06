<?php

namespace Tests\Feature;

use App\Exceptions\ActionFailed;
use App\Services\ShipmentExceptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The logistics bugs found in the full-system review (2026-11-06): L1 Lost
 * on an order the seller still holds, L2 the manual Delivered override and
 * COD, L3 only the center a parcel is at may act on it.
 */
class LogisticsReviewFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private function order(string $status, array $extra = []): int
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 5]);

        return $this->makeOrder($this->makeUser(), $product, $status, $extra);
    }

    private function exceptions(): ShipmentExceptionService
    {
        return app(ShipmentExceptionService::class);
    }

    /** L1: an order still with the seller can't be marked Lost; one at a center can. */
    public function test_lost_only_once_logistics_has_the_parcel(): void
    {
        $staff = $this->makeLogistics();

        foreach (['Pending', 'Preparing', 'Ready for Pickup', 'Pickup Assigned'] as $status) {
            $id = $this->order($status);

            try {
                $this->exceptions()->lost($id, 'Not found after two hub inventory checks.', $staff->id);
                $this->fail($status . ' should not be markable Lost.');
            } catch (ActionFailed $e) {
                $this->assertStringContainsString('still with the seller', $e->getMessage());
            }

            $this->assertSame($status, DB::table('orders')->where('id', $id)->value('status'));
        }

        $atCenter = $this->order('At Sorting Center');
        $this->exceptions()->lost($atCenter, 'Not found after two hub inventory checks.', $staff->id);
        $this->assertSame('Lost', DB::table('orders')->where('id', $atCenter)->value('status'));
    }

    /** L2: Delivered by hand — the rider is charged the COD only if they had the parcel. */
    public function test_manual_delivered_charges_the_rider_only_when_out_for_delivery(): void
    {
        $staff = $this->makeLogistics();
        $rider = $this->makeRider();
        $reason = 'Rider app offline, delivery confirmed by phone.';
        $cod = ['payment_method' => 'Cash on Delivery', 'delivery_rider_id' => $rider->id, 'total_amount' => 1000];

        $out = $this->order('Out for Delivery', $cod);
        $this->exceptions()->override($out, 'Delivered', $reason, $staff->id);
        $row = DB::table('orders')->find($out);
        $this->assertNotNull($row->cod_collected_at);
        $this->assertNull($row->cod_remitted_at);
        $this->assertGreaterThan(0, (float) $row->rider_fee);

        $assigned = $this->order('Assigned for Delivery', $cod);
        $this->exceptions()->override($assigned, 'Delivered', $reason, $staff->id);
        $row = DB::table('orders')->find($assigned);
        $this->assertNotNull($row->cod_remitted_at);
        $this->assertSame(0.0, (float) $row->rider_fee);

        // Only the Out for Delivery parcel's cash is on the rider.
        $this->assertSame([$out], app(\App\Services\CodRemittanceService::class)->held($rider->id)->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    /** L3: a parcel at one center can't be marked Lost by the center it came from. */
    public function test_only_the_center_holding_the_parcel_can_mark_it(): void
    {
        $staff = $this->makeLogistics();
        $from = \App\Models\SortingCenter::create(['name' => 'Santa Cruz Sorting Center', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'is_active' => true]);
        $at = \App\Models\SortingCenter::create(['name' => 'Quezon City Sorting Center', 'province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City', 'is_active' => true]);
        $id = $this->order('At Sorting Center', ['origin_center_id' => $from->id, 'destination_center_id' => $at->id, 'current_center_id' => $at->id]);

        try {
            $this->exceptions()->lost($id, 'Not found after two hub inventory checks.', $staff->id, $from->id);
            $this->fail('The origin center should not mark a parcel at another center.');
        } catch (ActionFailed $e) {
            $this->assertStringContainsString('only that center can act on it', $e->getMessage());
        }

        $this->exceptions()->lost($id, 'Not found after two hub inventory checks.', $staff->id, $at->id);
        $this->assertSame('Lost', DB::table('orders')->where('id', $id)->value('status'));
    }
}
