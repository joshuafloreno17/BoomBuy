<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        $mine = fn () => Notification::where('user_id', $user['id']);

        // Chips: all, unread, then one per kind of notification the buyer actually has.
        $byType = $mine()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $counts = ['all' => (int) $byType->sum(), 'unread' => $mine()->whereNull('read_at')->count()];
        foreach (self::FILTERS as $key => $types) {
            $counts[$key] = (int) $byType->only($types)->sum();
        }

        $filter = request('filter');
        if ($filter !== 'unread' && !array_key_exists($filter, self::FILTERS)) {
            $filter = 'all';
        }

        $notifications = $mine()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when(isset(self::FILTERS[$filter]), fn ($q) => $q->whereIn('type', self::FILTERS[$filter]))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $unreadCount = $counts['unread'];

        return view(
            'pages.notifications',
            compact('notifications', 'unreadCount', 'counts', 'filter')
        );
    }

    /** Filter chips on the notifications page => the notification types they cover. */
    private const FILTERS = [
        'orders' => ['order', 'order_status', 'delivery', 'delivery_status', 'payment'],
        'returns' => ['return_refund'],
        'account' => ['account_status', 'complaint', 'buyer'],
    ];

    public function markRead($id)
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        Notification::where('id', $id)
            ->where('user_id', $user['id'])
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }

    /**
     * Clicking a notification: mark it read, then go straight to what it's
     * about (the order, the refund, the profile…) instead of the list.
     */
    public function open($id)
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        $notification = Notification::where('id', $id)
            ->where('user_id', $user['id'])
            ->first();

        if (!$notification) {
            return redirect()->route('notifications');
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        // The order it was about may be gone (deleted) — say so instead of landing on nothing.
        $orderId = $this->orderIdFor($notification);
        if ($orderId > 0 && !DB::table('orders')->where('id', $orderId)->exists()) {
            $back = ($user['role'] ?? 'buyer') === 'buyer' ? route('buyer.orders') : url()->previous(route('notifications'));

            return redirect($back)->with('error', 'Order #' . $orderId . ' is no longer available.');
        }

        return redirect($this->targetFor($notification, $user['role'] ?? 'buyer'));
    }

    public function markAllRead()
    {
        $user = session()->get('user');

        if (!$user) {
            return redirect()->route('login');
        }

        Notification::where('user_id', $user['id'])
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /** Notification types whose reference points at an order (or a return request). */
    private const ORDER_TYPES = ['order', 'order_status', 'delivery', 'delivery_status', 'return_refund'];

    /** The order a notification is about, or 0. */
    private function orderIdFor(Notification $notification): int
    {
        $type = (string) $notification->type;
        $ref = (int) $notification->reference_id;

        if (!$ref || !in_array($type, self::ORDER_TYPES)) {
            return 0;
        }

        // Return/refund notifications reference the request, not the order.
        return $type === 'return_refund'
            ? (int) DB::table('return_refund_requests')->where('id', $ref)->value('order_id')
            : $ref;
    }

    private function targetFor(Notification $notification, string $role): string
    {
        $type = (string) $notification->type;
        $orderId = $this->orderIdFor($notification);
        $isOrder = in_array($type, self::ORDER_TYPES);

        return match ($role) {
            'buyer' => match (true) {
                $isOrder && $orderId > 0 => route('buyer.orders') . '#order-' . $orderId,
                $isOrder => route('buyer.orders'),
                $type === 'complaint' => route('complaints.index'),
                $type === 'account_status' => route('buyer.profile'),
                default => route('notifications'),
            },
            'seller' => match (true) {
                $type === 'return_refund' => route('seller.orders', ['tab' => 'returns']),
                $isOrder && $orderId > 0 => route('seller.order.details', $orderId),
                $type === 'compliance_warning' => route('seller.dashboard'),
                default => route('seller.notifications'),
            },
            'rider' => $isOrder && $orderId > 0
                ? route('rider.delivery.details', $orderId)
                : route('rider.notifications'),
            'logistics' => $isOrder || $type === 'parcel'
                ? route('logistics.parcels')
                : route('logistics.notifications'),
            default => route('notifications'),
        };
    }
}
