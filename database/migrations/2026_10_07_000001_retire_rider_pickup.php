<?php

use App\Support\ParcelRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Riders no longer collect parcels from sellers — sellers drop them at the
 * Sorting Center. Orders still on the old pickup leg move onto the new flow:
 *
 *   Ready for Pickup / Assigned (rider hadn't collected it) → Processing,
 *     so the seller drops it off;
 *   Picked Up (rider was on the way to the center)          → Dropped Off,
 *     so the center confirms it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('orders')
            ->whereIn('status', ['Ready for Pickup', 'Assigned', 'Picked Up'])
            ->get(['id', 'status', 'origin_center_id']);

        foreach ($orders as $order) {
            $sellerId = (int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id');
            $origin = $order->origin_center_id
                ?: ParcelRoute::centerFor(ParcelRoute::sellerLocation($sellerId)['province'])?->id;

            DB::table('orders')->where('id', $order->id)->update([
                'status' => $order->status === 'Picked Up' ? 'Dropped Off' : 'Processing',
                'rider_id' => $order->status === 'Picked Up' ? DB::raw('rider_id') : null,
                'origin_center_id' => $origin,
                'updated_at' => now(),
            ]);

            if ($order->status !== 'Picked Up' && $sellerId) {
                createNotification(
                    $sellerId,
                    'Please Drop Off Order #' . $order->id,
                    'Riders no longer pick up from sellers. Bring order #' . $order->id . ' to your BoomBuy Sorting Center and mark it "Dropped Off".',
                    'order_status',
                    (int) $order->id
                );
            }
        }
    }

    public function down(): void
    {
        // Not reversible.
    }
};
