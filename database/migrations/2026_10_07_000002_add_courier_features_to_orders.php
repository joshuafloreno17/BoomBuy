<?php

use App\Support\ParcelRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Courier details on each order:
 *  - delivery_zone: how far it travels (same town … another island group),
 *    for the estimated arrival;
 *  - fulfillment: "delivery" to the buyer's door, or "pickup" at the
 *    buyer's Sorting Center;
 *  - delivery_proof: the rider's photo of the hand-over;
 *  - cod_*: Cash on Delivery money — collected by the rider, remitted to
 *    the Sorting Center (which releases it to the seller).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_zone', 12)->nullable()->after('delivery_fee');
            $table->string('fulfillment', 12)->default('delivery')->after('delivery_zone');
            $table->string('delivery_proof')->nullable()->after('delivered_at');
            $table->timestamp('cod_collected_at')->nullable()->after('delivery_proof');
            $table->timestamp('cod_remitted_at')->nullable()->after('cod_collected_at');
            $table->foreignId('cod_remitted_center_id')->nullable()->after('cod_remitted_at')
                ->constrained('sorting_centers')->nullOnDelete();
        });

        // Existing orders: their distance, from the seller's and buyer's provinces.
        foreach (DB::table('orders')->get(['id', 'shipping_province', 'shipping_city']) as $order) {
            $sellerId = (int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id');
            $buyer = $order->shipping_province ? ['province' => $order->shipping_province, 'city' => $order->shipping_city] : null;

            DB::table('orders')->where('id', $order->id)->update([
                'delivery_zone' => ParcelRoute::zone(ParcelRoute::sellerLocation($sellerId), $buyer),
            ]);
        }

        // COD already delivered before this: the money is counted as handed in.
        DB::table('orders')
            ->where('payment_method', 'Cash on Delivery')
            ->where('status', 'Delivered')
            ->update(['cod_collected_at' => DB::raw('COALESCE(delivered_at, updated_at)'), 'cod_remitted_at' => DB::raw('COALESCE(delivered_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cod_remitted_center_id');
            $table->dropColumn(['delivery_zone', 'fulfillment', 'delivery_proof', 'cod_collected_at', 'cod_remitted_at']);
        });
    }
};
