<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ActionFailed;
use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use App\Services\SortingCenterService;
use App\Support\ApiToken;
use App\Support\CodPolicy;
use App\Support\LoginGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The BoomBuy Rider app's API (/api/v1). JSON in and out; sign in once with
 * POST /login and send "Authorization: Bearer <token>" after that. The rules
 * are the same as on the rider web pages — both go through DeliveryService.
 */
class RiderApiController extends Controller
{
    /** Statuses a rider still has work on. */
    private const ACTIVE = ['Assigned for Delivery', 'Out for Delivery'];

    public function login(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        if ($email === '' || $password === '') {
            return response()->json(['message' => 'Enter your email and password.'], 422);
        }

        $user = DB::table('users')->where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        if ($user->role !== 'rider') {
            return response()->json(['message' => 'This app is for BoomBuy riders. Buyers and sellers use boombuy.store.'], 403);
        }

        if ($reason = LoginGate::blockReason($user)) {
            return response()->json(['message' => $reason], 403);
        }

        $token = ApiToken::issue($user, (string) $request->input('device_name', 'Rider app'));

        return response()->json(['token' => $token, 'rider' => $this->riderJson($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        ApiToken::revoke($request->bearerToken());

        return response()->json(['message' => 'Logged out.']);
    }

    /** Who is signed in, today's numbers, the cash to hand in, and the app's choices. */
    public function me(Request $request): JsonResponse
    {
        $rider = $this->rider($request);
        $cod = app(SortingCenterService::class)->codHeldBy((int) $rider->id);

        $counts = DB::table('orders')
            ->where('delivery_rider_id', $rider->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'rider' => $this->riderJson($rider),
            'stats' => [
                'active' => (int) collect(self::ACTIVE)->sum(fn ($s) => $counts[$s] ?? 0),
                'delivered' => (int) (($counts['Delivered'] ?? 0) + ($counts['Completed'] ?? 0)),
                'delivered_today' => DB::table('orders')->where('delivery_rider_id', $rider->id)->whereIn('status', \App\Support\OrderStatus::DONE)->whereDate('delivered_at', now()->toDateString())->count(),
            ],
            'cod_to_hand_in' => [
                'orders' => $cod->count(),
                'amount' => round((float) $cod->sum('total_amount'), 2),
            ],
            'failure_reasons' => DeliveryService::FAILURE_REASONS,
            'unread_notifications' => DB::table('notifications')->where('user_id', $rider->id)->whereNull('read_at')->count(),
        ]);
    }

    /** ?filter=active (default) or history. */
    public function deliveries(Request $request): JsonResponse
    {
        $rider = $this->rider($request);
        $history = $request->query('filter') === 'history';

        $orders = DB::table('orders')
            ->where('delivery_rider_id', $rider->id)
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
        $items = DB::table('order_items')->where('order_id', $id)->get();

        return response()->json(['delivery' => $this->deliveryJson($order, $items, true)]);
    }

    public function outForDelivery(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->act($request, $id, fn ($riderId) => $deliveries->updateStatus($riderId, $id, 'Out for Delivery'));
    }

    /** multipart/form-data with "photo": the parcel handed over. */
    public function delivered(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->act($request, $id, fn ($riderId) => $deliveries->updateStatus($riderId, $id, 'Delivered', '', '', $request->file('photo')));
    }

    /** "reason" (one of failure_reasons) and, for "Other", "details". */
    public function failed(Request $request, int $id, DeliveryService $deliveries): JsonResponse
    {
        return $this->act($request, $id, fn ($riderId) => $deliveries->updateStatus(
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
        $rider = $this->rider($request);
        $from = $this->date($request->query('from')) ?? now()->subDays(30)->toDateString();
        $to = $this->date($request->query('to')) ?? now()->toDateString();
        $fee = \App\Support\DeliveryFee::baseFee();

        $done = DB::table('orders')
            ->where('delivery_rider_id', $rider->id)
            ->whereIn('status', \App\Support\OrderStatus::DONE)
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

    public function notifications(Request $request): JsonResponse
    {
        $rider = $this->rider($request);

        return response()->json([
            'notifications' => DB::table('notifications')
                ->where('user_id', $rider->id)
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'title', 'message', 'type', 'reference_id', 'read_at', 'created_at'])
                ->map(fn ($n) => [
                    'id' => (int) $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'order_id' => in_array($n->type, ['delivery', 'delivery_status', 'order'], true) ? (int) $n->reference_id : null,
                    'read' => $n->read_at !== null,
                    'created_at' => Carbon::parse($n->created_at)->toIso8601String(),
                ]),
        ]);
    }

    public function readNotification(Request $request, int $id): JsonResponse
    {
        DB::table('notifications')->where('id', $id)->where('user_id', $this->rider($request)->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    // ------------------------------------------------------------------

    private function rider(Request $request): object
    {
        return $request->attributes->get('apiUser');
    }

    /** A delivery handed to this rider, or 404. */
    private function ownOrder(Request $request, int $id): object
    {
        $order = DB::table('orders')->where('id', $id)->where('delivery_rider_id', $this->rider($request)->id)->first();

        abort_unless($order, 404, 'Delivery not found.');

        return $order;
    }

    /** Runs a delivery step; its rule message comes back as 422. */
    private function act(Request $request, int $id, callable $step): JsonResponse
    {
        $this->ownOrder($request, $id);

        try {
            $message = $step((int) $this->rider($request)->id);
        } catch (ActionFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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

    private function riderJson(object $rider): array
    {
        return [
            'id' => (int) $rider->id,
            'name' => $rider->name,
            'email' => $rider->email,
            'phone' => $rider->phone,
            'area' => trim(($rider->city_municipality ? $rider->city_municipality . ', ' : '') . (string) $rider->province, ', '),
            'photo' => !empty($rider->profile_photo) ? asset('storage/profile-photos/' . $rider->profile_photo) : null,
        ];
    }

    private function deliveryJson(object $order, $items, bool $full = false): array
    {
        $isCod = CodPolicy::isCod((string) $order->payment_method);
        $center = $order->destination_center_id ? \App\Models\SortingCenter::find($order->destination_center_id) : null;

        $json = [
            'id' => (int) $order->id,
            'waybill' => \App\Support\Waybill::number((int) $order->id),
            'status' => $order->status,
            'buyer_name' => $order->shipping_name,
            'phone' => $order->shipping_phone,
            'address' => $order->shipping_address,
            'cod' => $isCod,
            'collect_amount' => $isCod ? round((float) $order->total_amount, 2) : 0.0,
            'items_count' => (int) $items->sum('quantity'),
            'updated_at' => Carbon::parse($order->updated_at)->toIso8601String(),
            // What the rider can do next.
            'can' => [
                'start' => $order->status === 'Assigned for Delivery',
                'deliver' => $order->status === 'Out for Delivery',
                'fail' => $order->status === 'Out for Delivery',
            ],
        ];

        if ($full) {
            $json['items'] = $items->map(fn ($i) => [
                'name' => $i->product_name,
                'option' => $i->variation_label,
                'quantity' => (int) $i->quantity,
            ])->values();
            $json['pickup_center'] = $center ? ['name' => $center->name, 'address' => $center->address ?: $center->town] : null;
            $json['delivery_attempts'] = (int) $order->delivery_attempts;
            $json['failure_reason'] = $order->failure_reason;
            $json['delivered_at'] = $order->delivered_at ? Carbon::parse($order->delivered_at)->toIso8601String() : null;
            $json['maps_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) $order->shipping_address);
        }

        return $json;
    }
}
