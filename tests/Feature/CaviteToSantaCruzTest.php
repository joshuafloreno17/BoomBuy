<?php

namespace Tests\Feature;

use App\Models\RiderArea;
use App\Support\ParcelRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The example walked through: a seller in Cavite, a buyer in Santa Cruz,
 * Laguna. Seller drops it at the Cavite center → sent to the Laguna center →
 * a Laguna rider delivers it to Santa Cruz.
 */
class CaviteToSantaCruzTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    public function test_cavite_seller_to_santa_cruz_buyer(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\SortingCenterSeeder::class);

        $caviteCenter = ParcelRoute::centerFor('Cavite');
        $lagunaCenter = ParcelRoute::centerFor('Laguna');

        $caviteStaff = $this->makeLogistics();
        DB::table('users')->where('id', $caviteStaff->id)->update(['sorting_center_id' => $caviteCenter->id]);
        $lagunaStaff = $this->makeLogistics();
        DB::table('users')->where('id', $lagunaStaff->id)->update(['sorting_center_id' => $lagunaCenter->id]);

        $lagunaRider = $this->makeRider();
        RiderArea::create(['rider_id' => $lagunaRider->id, 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz']);

        $seller = $this->makeSeller();
        $seller->forceFill(['province' => 'Cavite', 'city_municipality' => 'Dasmariñas City'])->save();
        $product = $this->makeProduct($seller, ['price' => 400]);
        $buyer = $this->makeUser('buyer', ['address' => '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna']);

        // Buyer orders.
        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])->post(route('checkout.place'), [
            'address' => '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna', 'phone' => '09171234567', 'payment' => 'GCash',
        ])->assertRedirect();
        $id = (int) DB::table('orders')->where('buyer_id', $buyer->id)->value('id');
        $this->assertSame($caviteCenter->id, (int) DB::table('orders')->where('id', $id)->value('origin_center_id'));
        $this->assertSame($lagunaCenter->id, (int) DB::table('orders')->where('id', $id)->value('destination_center_id'));
        $this->assertEquals(120, DB::table('orders')->where('id', $id)->value('delivery_fee')); // Cavite → Laguna: same island group

        // Seller drops it at the Cavite center.
        $this->actingAsUser($seller)->get(route('seller.order.details', $id))->assertSee('Drop off at: BoomBuy Sorting Center – Cavite');
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $id), ['status' => 'Dropped Off'])->assertSessionHas('success');

        // Cavite center: receive, then send to Laguna.
        $this->actingAsUser($lagunaStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('error');
        $this->actingAsUser($caviteStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');
        $this->actingAsUser($caviteStaff)->get(route('logistics.parcels'))->assertSee('Dispatch to BoomBuy Sorting Center – Laguna');
        $this->actingAsUser($caviteStaff)->post(route('logistics.parcels.dispatch', $id))->assertSessionHas('success');
        $this->assertSame('In Transit', DB::table('orders')->where('id', $id)->value('status'));

        // Laguna center: confirm it arrived, hand it to the Santa Cruz rider.
        $this->actingAsUser($lagunaStaff)->get(route('logistics.parcels'))->assertSee('Order #' . $id)->assertSee('Confirm Arrival');
        $this->actingAsUser($lagunaStaff)->post(route('logistics.parcels.confirm-arrival', $id))->assertSessionHas('success');
        $this->actingAsUser($lagunaStaff)->get(route('logistics.parcels'))->assertSee($lagunaRider->name . ' (area match)');
        $this->actingAsUser($lagunaStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $lagunaRider->id])->assertSessionHas('success');

        // Rider delivers to Santa Cruz.
        $this->actingAsUser($lagunaRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery']);
        $this->actingAsUser($lagunaRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivered'])->assertSessionHas('success');

        $this->assertSame([
            'Order placed',
            'Seller is preparing your order',
            'Seller dropped it off at BoomBuy Sorting Center – Cavite',
            'Received at BoomBuy Sorting Center – Cavite',
            'On its way to BoomBuy Sorting Center – Laguna',
            'Arrived at BoomBuy Sorting Center – Laguna',
            'Handed to a rider for delivery',
            'Out for delivery',
            'Delivered',
        ], DB::table('order_events')->where('order_id', $id)->orderBy('id')->pluck('title')->all());
    }
}
