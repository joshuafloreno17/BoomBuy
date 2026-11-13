<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SortingCenter;
use Illuminate\Support\Facades\DB;

/**
 * What goes on a seller's shipping label. Until the first print it is built
 * from the order as it is now; the first print saves that copy on the order
 * (waybill_snapshot), and every later view or reprint shows the saved copy, so
 * it always matches the label stuck on the parcel.
 */
class ShippingLabel
{
    /** The label to show: the saved copy once printed, else a fresh one. */
    public static function for(object $order, int $sellerId): array
    {
        $saved = !empty($order->waybill_snapshot) ? json_decode($order->waybill_snapshot, true) : null;

        $label = is_array($saved) ? $saved : self::build($order, $sellerId);

        $label['printed_at'] = $order->waybill_printed_at ?? null;
        $label['print_count'] = (int) ($order->waybill_print_count ?? 0);

        return $label;
    }

    /**
     * Record a print. The first one saves the label as it is now; later ones
     * only count as reprints. Returns the label that was printed.
     */
    public static function markPrinted(object $order, int $sellerId): array
    {
        if (empty($order->waybill_snapshot)) {
            // Only the first print saves: two tabs printing at once keep one copy.
            DB::table('orders')
                ->where('id', $order->id)
                ->whereNull('waybill_snapshot')
                ->update([
                    'waybill_snapshot' => json_encode(self::build($order, $sellerId)),
                    'waybill_printed_at' => now(),
                ]);
        }

        DB::table('orders')->where('id', $order->id)->increment('waybill_print_count');

        return self::for(DB::table('orders')->where('id', $order->id)->first(), $sellerId);
    }

    /** The label from the order as it is right now. */
    public static function build(object $order, int $sellerId): array
    {
        $items = DB::table('order_items')->where('order_id', $order->id)->where('seller_id', $sellerId)->get();

        $seller = DB::table('users')->where('id', $sellerId)->first();
        $shopName = DB::table('seller_applications')->where('user_id', $sellerId)->value('business_name') ?: ($seller->name ?? 'Seller');

        $sellerTown = ParcelRoute::sellerLocation($sellerId);
        $origin = $order->origin_center_id
            ? SortingCenter::find($order->origin_center_id)
            : ParcelRoute::centerFor($sellerTown['province'], $sellerTown['city']);

        $buyerTown = $order->shipping_province
            ? ['province' => $order->shipping_province, 'city' => $order->shipping_city]
            : PhLocations::locate((string) $order->shipping_address);
        $destination = $order->destination_center_id
            ? SortingCenter::find($order->destination_center_id)
            : ($buyerTown ? ParcelRoute::centerFor($buyerTown['province'], $buyerTown['city']) : null);

        // Actual weight from the products (kg); shipping charges it per whole kg.
        $weights = Product::withTrashed()->whereIn('id', $items->pluck('product_id'))->pluck('weight_kg', 'id');
        $kg = $items->sum(fn ($i) => (float) ($weights[$i->product_id] ?? 0) * (int) $i->quantity);

        $isCod = CodPolicy::isCod((string) $order->payment_method);
        $waybill = Waybill::number((int) $order->id);

        return [
            'waybill' => $waybill,
            'scan_url' => route('logistics.scan', $waybill),
            'order_id' => (int) $order->id,
            'order_date' => (string) $order->created_at,
            'is_cod' => $isCod,
            'collect' => $isCod ? (float) $order->total_amount : 0.0,
            'origin' => $origin->name ?? null,
            'destination' => ($destination ?? $origin)->name ?? null,
            // Hub relay: the centers the truck passes in between.
            'hubs' => ($origin && $destination)
                ? array_values(array_filter(array_map(fn ($id) => ParcelRoute::centerName((int) $id), HubRoute::relays((int) $origin->id, (int) $destination->id))))
                : [],
            'to_name' => (string) $order->shipping_name,
            'to_address' => (string) $order->shipping_address,
            'to_phone' => (string) $order->shipping_phone,
            'shop_name' => (string) $shopName,
            'from_address' => (string) ($seller->address ?? ''),
            'from_phone' => (string) ($seller->phone ?? ''),
            'items' => $items->map(fn ($i) => [
                'quantity' => (int) $i->quantity,
                'name' => (string) $i->product_name,
                'variation' => $i->variation_label ?? null,
            ])->values()->all(),
            'item_count' => (int) $items->sum('quantity'),
            'weight_kg' => round($kg, 2),
        ];
    }

    /** "BoomBuy Sorting Center – Cebu" → "Cebu": the short place name is what sorters read. */
    public static function place(?string $centerName): string
    {
        return $centerName ? trim(preg_replace('/^BoomBuy Sorting Center\s*[–-]\s*/u', '', $centerName)) : '—';
    }
}
