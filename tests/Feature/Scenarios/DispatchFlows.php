<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use App\Support\Waybill;
use Illuminate\Support\Facades\DB;

/**
 * One parcel's trip through the real routes, for the Rider and Logistics
 * Modules sheets: assign → waybill scan → hub inbound/outbound → Out for
 * Delivery → Delivered (photo, OTP, COD) or a failed attempt.
 */
trait DispatchFlows
{
    private function asRider(User $rider): static
    {
        $this->flushSession();

        return $this->actingAsUser($rider);
    }

    private function asLogistics(?User $company = null): static
    {
        $this->flushSession();

        return $this->actingAsUser($company ?? $this->logistics1());
    }

    private function assignTo(int $orderId, User $rider)
    {
        return $this->asLogistics()->post(route('logistics.dispatch.assign', $orderId), ['rider_id' => $rider->id]);
    }

    private function autoAssign(int $orderId)
    {
        return $this->asLogistics()->post(route('logistics.dispatch.auto', $orderId));
    }

    private function pendingOffer(int $orderId): ?object
    {
        return DB::table('job_offers')->where('order_id', $orderId)->where('status', 'pending')->first();
    }

    private function pickUp(int $orderId, User $rider)
    {
        return $this->asRider($rider)->post(route('rider.pickup.scan', $orderId), ['tracking_number' => Waybill::number($orderId)]);
    }

    /** Picked up by $rider, received at the hub, sorted and handed to $deliverer for delivery. */
    private function toDelivery(int $orderId, User $rider, ?User $deliverer = null): void
    {
        $this->goOnline($rider, $deliverer ?? $rider);
        $this->assignTo($orderId, $rider)->assertSessionHas('success');
        $this->pickUp($orderId, $rider)->assertSessionHas('success');
        $this->asLogistics()->post(route('logistics.hub.scan'), ['code' => Waybill::number($orderId), 'stage' => 'inbound'])->assertSessionHas('success');
        $this->post(route('logistics.hub.scan'), ['code' => Waybill::number($orderId), 'stage' => 'outbound', 'rider_id' => ($deliverer ?? $rider)->id])->assertSessionHas('success');
    }

    private function outFor(int $orderId, User $rider)
    {
        return $this->asRider($rider)->post(route('rider.delivery.status', $orderId), ['status' => 'Out for Delivery']);
    }

    private function otp(int $orderId): string
    {
        return (string) DB::table('orders')->where('id', $orderId)->value('delivery_otp');
    }

    private function deliver(int $orderId, User $rider, array $form = [])
    {
        $order = DB::table('orders')->find($orderId);

        return $this->asRider($rider)->post(route('rider.delivery.status', $orderId), array_merge([
            'status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto(), 'otp' => $this->otp($orderId),
            'cod_amount' => number_format((float) $order->total_amount, 2, '.', ''),
        ], $form));
    }

    private function failAttempt(int $orderId, User $rider, string $reason)
    {
        return $this->asRider($rider)->post(route('rider.delivery.status', $orderId), ['status' => 'Delivery Failed', 'failure_code' => $reason]);
    }

    private function statusOf(int $orderId): string
    {
        return (string) DB::table('orders')->where('id', $orderId)->value('status');
    }

    /** A COD parcel Out for Delivery with Rider 1. */
    private function outJob(): int
    {
        $orderId = $this->readyJob();
        $this->toDelivery($orderId, $this->rider1());
        $this->outFor($orderId, $this->rider1())->assertSessionHas('success');

        return $orderId;
    }

    private function retry(int $orderId): void
    {
        $this->asLogistics()->post(route('logistics.parcels.confirm-back', $orderId))->assertSessionHas('success');
        $this->post(route('logistics.parcels.reschedule', $orderId), ['rider_id' => $this->rider1()->id])->assertSessionHas('success');
        $this->outFor($orderId, $this->rider1())->assertSessionHas('success');
    }

    /**
     * Rider 1 delivered COD orders of these products today, each through the
     * real flow. P-03 is weighed at 1 kg so its COD is the sheet's ₱1,300
     * (₱1,250 + ₱50 shipping); at 1.5 kg the sheet's own rates give ₱65.
     */
    private function codDelivered(array $products): array
    {
        $ids = [];
        $this->p03()->update(['weight_kg' => 1.0]);

        foreach ($products as $product) {
            $orderId = $this->readyJob($product);
            $this->toDelivery($orderId, $this->rider1());
            $this->outFor($orderId, $this->rider1());
            $this->deliver($orderId, $this->rider1())->assertSessionHas('success');
            $ids[] = $orderId;
        }

        return $ids;
    }

}
