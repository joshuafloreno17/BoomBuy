<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\ActionFailed;
use App\Models\SortingCenter;
use App\Services\DeliveryService;
use App\Services\PickupService;
use App\Services\SortingCenterService;
use App\Support\CodPolicy;
use App\Support\DeliveryFee;
use App\Support\OrderStatus;
use App\Support\Waybill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A rider's work: pickups from sellers (PickupService) and deliveries to
 * buyers (DeliveryService) — the same rules as the rider web pages.
 */
class RiderController extends MobileController
{
    /** Statuses a rider still has delivery work on. */
    private const ACTIVE = ['Assigned for Delivery', 'Out for Delivery'];

    /** Today's numbers, the cash to hand in, and the app's choices. */
    public function dashboard(Request $request, PickupService $pickups): JsonResponse
    {
        $rider = $this->user($request);
        $riderId = (int) $rider->id;
        $cod = app(SortingCenterService::class)->codHeldBy($riderId);

        $counts = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $fee = DeliveryFee::baseFee();
        $today = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', OrderStatus::DONE)
            ->whereDate('delivered_at', now()->toDateString())
            ->get(['rider_fee']);

        return response()->json([
            'stats' => [
                'active' => (int) collect(self::ACTIVE)->sum(fn ($s) => $counts[$s] ?? 0),
                'delivered' => (int) (($counts['Delivered'] ?? 0) + ($counts['Completed'] ?? 0)),
                'delivered_today' => $today->count(),
                'earned_today' => round((float) $today->sum(fn ($o) => $o->rider_fee !== null ? (float) $o->rider_fee : $fee), 2),
                'pickups_available' => $pickups->available($riderId)->count(),
                'pickups_mine' => $pickups->mine($riderId)->count(),
            ],
            'cod_to_hand_in' => [
                'orders' => $cod->count(),
                'amount' => round((float) $cod->sum('total_amount'), 2),
            ],
            'areas' => DB::table('rider_areas')->where('rider_id', $riderId)->get(['province', 'city_municipality'])
                ->map(fn ($a) => trim($a->city_municipality . ', ' . str_replace(' (NCR)', '', $a->province), ', '))->values(),
            'failure_reasons' => DeliveryService::FAILURE_REASONS,
        ]);
    }

    // ---- Pickups (seller → Sorting Center) ---------------------------

    /** ?filter=available (default) or mine. */
    public function pickups(Request $request, PickupService $pickups): JsonResponse
    {
        $riderId = (int) $this->user($request)->id;
        $mine = $request->query('filter') === 'mine';
        $orders = $mine ? $pickups->mine($riderId) : $pickups->available($riderId);

        return response()->json(['pickups' => $orders->map(fn ($o) => $this->pickupJson($o))->values()]);
    }

    public function acceptPickup(Request $request, int $id, PickupService $pickups): JsonResponse
    {
        $riderId = (int) $this->user($request)->id;

        return $this->act(fn () => $pickups->accept($riderId, $id));
    }

    public function pickedUp(Request $request, int $id, PickupService $pickups): JsonResponse
    {
        $riderId = (int) $this->user($request)->id;

        return $this->act(fn () => $pickups->pickedUp($riderId, $id));
    }

    // ---- Deliveries (Sorting Center → buyer) -------------------------

    /** ?filter=active (default) or history. */
    public function deliveries(Request $request): JsonResponse
    {
        $riderId = (int) $this->user($request)->id;
        $history = $request->query('filter') === 'history';

        $orders = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->when($history,
                fn ($q) => $q->whereIn('status', ['Delivered', 'Completed', 'Delivery Failed'])->orderByDesc('updated_at')->limit(100),
                fn ($q) => $q->whereIn('status', self::ACTIVE)->orderBy('updated_at'))
            ->get();

        $items = DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->get()->groupBy('order_id');

        return response()->json([
            'deliveries' => $orders->map(fn ($o) => $this->deliveryJson($o, $items->get($o->id, collect())))->values(),
        ]);
    }

    public function delivery(Request $request, int $id): JsonResponse
    {
        $order = $this->ownOrder($request, $id);

        if (!$order) {
            return $this->fail('Delivery not found.', 404);
        }

        return response()->json(['delivery' => $this->deliveryJson($order, DB::table('order_items')->where('order_id', $id)->get(), true)]);
    }

    public function outForDelivery(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->step($request, $id, fn ($riderId) => $deliveries->updateStatus($riderId, $id, 'Out for Delivery'));
    }

    /** multipart/form-data with "photo": the parcel handed over. */
    public function delivered(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->step($request, $id, fn ($riderId) => $deliveries->updateStatus($riderId, $id, 'Delivered', '', '', $request->file('photo')));
    }

    /** "reason" (one of failure_reasons) and, for "Other", "details". */
    public function failed(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->step($request, $id, fn ($riderId) => $deliveries->updateStatus(
            $riderId,
            $id,
            'Delivery Failed',
            (string) $request->input('reason'),
            (string) $request->input('details')
        ));
    }

    /** ?from=YYYY-MM-DD&to=YYYY-MM-DD (default: the last 30 days). */
    public function earnings(Request $request): JsonResponse
    {
        $riderId = (int) $this->user($request)->id;
        $from = $this->date($request->query('from')) ?? now()->subDays(30)->toDateString();
        $to = $this->date($request->query('to')) ?? now()->toDateString();
        $fee = DeliveryFee::baseFee();

        $done = DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereIn('status', OrderStatus::DONE)
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->orderByDesc('delivered_at')
            ->get(['id', 'delivered_at', 'rider_fee']);

        $earned = fn ($o) => $o->rider_fee !== null ? (float) $o->rider_fee : $fee;

        return response()->json([
            'from' => $from,
            'to' => $to,
            'deliveries' => $done->count(),
            'total' => round((float) $done->sum($earned), 2),
            'days' => $done->groupBy(fn ($o) => Carbon::parse($o->delivered_at)->toDateString())
                ->map(fn ($day, $date) => ['date' => $date, 'deliveries' => $day->count(), 'earned' => round((float) $day->sum($earned), 2)])
                ->values(),
        ]);
    }

    // ------------------------------------------------------------------

    private function ownOrder(Request $request, int $id): ?object
    {
        return DB::table('orders')->where('id', $id)->where('delivery_rider_id', $this->user($request)->id)->first();
    }

    /** Runs a delivery step; its rule message comes back as 422. */
    private function step(Request $request, int $id, callable $step): JsonResponse
    {
        if (!$this->ownOrder($request, $id)) {
            return $this->fail('Delivery not found.', 404);
        }

        try {
            $message = $step((int) $this->user($request)->id);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage());
        }

        $order = DB::table('orders')->where('id', $id)->first();

        return response()->json([
            'message' => $message,
            'delivery' => $this->deliveryJson($order, DB::table('order_items')->where('order_id', $id)->get(), true),
        ]);
    }

    private function date($value): ?string
    {
        try {
            return $value ? Carbon::parse((string) $value)->toDateString() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function pickupJson(object $order): array
    {
        return [
            'id' => (int) $order->id,
            'waybill' => Waybill::number((int) $order->id),
            'status' => $order->status,
            'shop_name' => $order->shop_name,
            'seller_phone' => $order->seller_phone,
            'pickup_address' => $order->pickup_address,
            'pickup_day' => $order->pickup_day,
            'center_name' => $order->center_name,
            'items_count' => (int) $order->items_count,
            'items' => collect($order->items)->map(fn ($i) => [
                'name' => $i->product_name,
                'option' => $i->variation_label,
                'quantity' => (int) $i->quantity,
            ])->values(),
            'maps_url' => $order->pickup_address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $order->pickup_address) : null,
            'can' => [
                'accept' => $order->status === 'Ready for Pickup' && empty($order->rider_id),
                'picked_up' => $order->status === 'Pickup Assigned',
            ],
        ];
    }

    private function deliveryJson(object $order, $items, bool $full = false): array
    {
        $isCod = CodPolicy::isCod((string) $order->payment_method);

        $json = [
            'id' => (int) $order->id,
            'waybill' => Waybill::number((int) $order->id),
            'status' => $order->status,
            'buyer_name' => $order->shipping_name,
            'phone' => $order->shipping_phone,
            'address' => $order->shipping_address,
            'cod' => $isCod,
            'collect_amount' => $isCod ? round((float) $order->total_amount, 2) : 0.0,
            'items_count' => (int) $items->sum('quantity'),
            'updated_at' => self::iso($order->updated_at),
            // What the rider can do next.
            'can' => [
                'start' => $order->status === 'Assigned for Delivery',
                'deliver' => $order->status === 'Out for Delivery',
                'fail' => $order->status === 'Out for Delivery',
            ],
        ];

        if ($full) {
            $center = $order->destination_center_id ? SortingCenter::find($order->destination_center_id) : null;

            $json['items'] = $items->map(fn ($i) => [
                'name' => $i->product_name,
                'option' => $i->variation_label,
                'quantity' => (int) $i->quantity,
            ])->values();
            $json['pickup_center'] = $center ? ['name' => $center->name, 'address' => $center->address ?: $center->town] : null;
            $json['delivery_attempts'] = (int) $order->delivery_attempts;
            $json['failure_reason'] = $order->failure_reason;
            $json['delivered_at'] = self::iso($order->delivered_at);
            $json['maps_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $order->shipping_address);
        }

        return $json;
    }
}
