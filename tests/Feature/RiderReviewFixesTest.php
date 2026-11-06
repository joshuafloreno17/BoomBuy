<?php

namespace Tests\Feature;

use App\Services\CodRemittanceService;
use App\Services\SortingCenterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The rider bugs found in the full-system review (2026-11-06): D1 parcels
 * stuck with a suspended rider, D2 a COD shortfall that could never be paid,
 * D3 the same cash recorded by a remittance and a center hand-in.
 */
class RiderReviewFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** A parcel with the rider at the given status. */
    private function parcelWith($rider, string $status, array $extra = []): int
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 5]);

        return $this->makeOrder($this->makeUser(), $product, $status, $extra + [
            'delivery_rider_id' => $rider->id,
            'pickup_date' => now()->toDateString(),
        ]);
    }

    /** D1: suspending a rider puts their deliveries back in the queue. */
    public function test_suspending_a_rider_releases_their_deliveries(): void
    {
        $rider = $this->makeRider();
        $assigned = $this->parcelWith($rider, 'Assigned for Delivery');
        $out = $this->parcelWith($rider, 'Out for Delivery', ['delivery_otp' => '123456']);

        $this->actingAsUser($this->makeLogistics())
            ->post(route('logistics.riders.suspend', $rider->id), ['reason' => 'Repeated late deliveries this week.'])
            ->assertSessionHas('success');

        foreach ([$assigned, $out] as $id) {
            $order = DB::table('orders')->find($id);
            $this->assertSame(['Sorted', null], [$order->status, $order->delivery_rider_id]);
        }
    }

    /** D1: logistics can take a parcel back from an active rider who can't finish it. */
    public function test_logistics_can_take_a_parcel_back_from_its_rider(): void
    {
        $rider = $this->makeRider();
        $out = $this->parcelWith($rider, 'Out for Delivery', ['delivery_otp' => '123456', 'delivery_attempts' => 0]);

        $this->actingAsUser($this->makeLogistics())
            ->post(route('logistics.parcels.take-back', $out))
            ->assertSessionHas('success');

        $order = DB::table('orders')->find($out);
        $this->assertSame(['Sorted', null, null, 0], [$order->status, $order->delivery_rider_id, $order->delivery_otp, (int) $order->delivery_attempts]);

        // Only parcels that are with a rider.
        $this->expectException(\App\Exceptions\ActionFailed::class);
        app(SortingCenterService::class)->takeBack($out);
    }

    /** D2: a shortfall can be paid off, with or without new COD cash. */
    public function test_a_cod_shortfall_can_be_paid_off(): void
    {
        $rider = $this->makeRider();
        $cod = app(CodRemittanceService::class);
        $logistics = $this->makeLogistics();

        // ₱1,000 collected, ₱900 handed in: ₱100 short.
        $this->parcelWith($rider, 'Delivered', ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => now(), 'cod_amount_collected' => 1000]);
        $cod->submit($rider->id, '900');
        $cod->confirm((int) DB::table('cod_remittances')->latest('id')->value('id'), $logistics->id);
        $this->assertSame(100.0, $cod->shortfall($rider->id));

        // No new cash, but the ₱100 can still be remitted — not more.
        try {
            $cod->submit($rider->id, '150');
            $this->fail('Remitting more than owed should be refused.');
        } catch (\App\Exceptions\ActionFailed $e) {
            $this->assertStringContainsString('₱100.00', $e->getMessage());
        }

        $cod->submit($rider->id, '100');
        $cod->confirm((int) DB::table('cod_remittances')->latest('id')->value('id'), $logistics->id);
        $this->assertSame(0.0, $cod->shortfall($rider->id));

        // Nothing held, nothing owed: nothing to remit.
        $this->expectException(\App\Exceptions\ActionFailed::class);
        $cod->submit($rider->id, '1');
    }

    /** D4: cash collected one day and remitted the next counts for the day it was collected. */
    public function test_reconciliation_matches_remittances_to_their_orders(): void
    {
        $rider = $this->makeRider();
        $cod = app(CodRemittanceService::class);
        $yesterday = now()->subDay();

        $this->parcelWith($rider, 'Delivered', ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => $yesterday, 'cod_amount_collected' => 1000]);
        $cod->submit($rider->id, '1000');

        $row = $cod->reconciliation(null, $yesterday->toDateString())->firstWhere('rider_id', $rider->id);
        $this->assertSame([1000.0, 1000.0, false], [$row->collected, $row->remitted, $row->short]);

        // Nothing was collected today, so today shows nothing for this rider.
        $this->assertNull($cod->reconciliation(null, now()->toDateString())->firstWhere('rider_id', $rider->id));
    }

    /** D3: cash handed in at the center cancels the rider's pending remittance for it. */
    public function test_a_center_hand_in_cancels_the_pending_remittance(): void
    {
        $rider = $this->makeRider();
        $cod = app(CodRemittanceService::class);

        $this->parcelWith($rider, 'Delivered', ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => now(), 'cod_amount_collected' => 1000]);
        $cod->submit($rider->id, '1000');

        app(SortingCenterService::class)->receiveCod($rider->id);

        $this->assertSame('Cancelled', DB::table('cod_remittances')->value('status'));

        try {
            $cod->confirm((int) DB::table('cod_remittances')->value('id'), $this->makeLogistics()->id);
            $this->fail('A cancelled remittance should not be confirmed.');
        } catch (\App\Exceptions\ActionFailed $e) {
            $this->assertStringContainsString('cancelled', $e->getMessage());
        }

        $this->assertSame(0.0, $cod->shortfall($rider->id));
    }
}
