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

        $notifications = Notification::where(
            'user_id',
            $user['id']
        )
        ->orderByDesc('created_at')
        ->get();

        return view(
            'pages.notifications',
            compact('notifications')
        );
    }

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

    private function targetFor(Notification $notification, string $role): string
    {
        $type = (string) $notification->type;
        $ref = (int) $notification->reference_id;

        // Return/refund notifications reference the request, not the order.
        $orderId = $type === 'return_refund' && $ref
            ? (int) DB::table('return_refund_requests')->where('id', $ref)->value('order_id')
            : $ref;

        $isOrder = in_array($type, ['order', 'order_status', 'delivery', 'delivery_status', 'return_refund']);

        return match ($role) {
            'buyer' => match (true) {
                $isOrder && $orderId > 0 => route('buyer.orders') . '#order-' . $orderId,
                $isOrder => route('buyer.orders'),
                $type === 'complaint' => route('complaints.index'),
                $type === 'account_status' => route('buyer.profile'),
                default => route('notifications'),
            },
            'seller' => match (true) {
                $type === 'return_refund' => route('seller.orders'),
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
