<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Online payment (GCash, Maya, card) through BoomBuy's sandbox gateway —
 * a test checkout page, no real money moves. One checkout is paid at once
 * even when it became several orders (one per seller): they share a
 * payment_group.
 *
 *   COD      payment_status 'COD'  → To Ship right away, collected on delivery
 *   online   'Unpaid' (To Pay) → 'Paid' (To Ship) with a reference
 *            'Failed' stays To Pay with Pay Again
 *            unpaid after PAY_WINDOW_MINUTES → cancelled, stock and voucher back
 *   refunds  'Refund Pending' → 'Refunded'
 */
class Payments
{
    public const PAY_WINDOW_MINUTES = 60;

    public const UNPAID = ['Unpaid', 'Failed'];

    public static function isOnline(?string $method): bool
    {
        return $method !== null && $method !== '' && !CodPolicy::isCod($method);
    }

    /** The order columns a new order starts with. */
    public static function forNewOrder(string $method, string $group): array
    {
        return self::isOnline($method)
            ? ['payment_status' => 'Unpaid', 'payment_group' => $group, 'pay_by' => now()->addMinutes(self::PAY_WINDOW_MINUTES)]
            : ['payment_status' => 'COD', 'payment_group' => $group];
    }

    public static function newGroup(): string
    {
        return (string) Str::uuid();
    }

    /** Waiting for the buyer to pay (the buyer's To Pay tab); the seller can't ship it yet. */
    public static function awaitingPayment(object $order): bool
    {
        return in_array($order->payment_status ?? null, self::UNPAID, true) && ($order->status ?? null) === 'Pending';
    }

    /** Orders of one checkout this buyer can still pay. */
    public static function payable(int $buyerId, string $group)
    {
        return DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('payment_group', $group)
            ->where('status', 'Pending')
            ->whereIn('payment_status', self::UNPAID)
            ->where('pay_by', '>', now())
            ->orderBy('id')
            ->get();
    }

    /**
     * Mark one checkout paid: each order once (a second confirmation —
     * Back, refresh, a closed tab coming back — changes nothing).
     *
     * @return int  orders that became paid now
     */
    public static function markPaid(int $buyerId, string $group): int
    {
        $reference = 'BBPAY-' . strtoupper(Str::random(10));
        $paid = 0;

        foreach (self::payable($buyerId, $group) as $order) {
            $changed = DB::table('orders')
                ->where('id', $order->id)
                ->whereIn('payment_status', self::UNPAID)
                ->where('status', 'Pending')
                ->update(['payment_status' => 'Paid', 'payment_reference' => $reference, 'paid_at' => now(), 'updated_at' => now()]);

            if (!$changed) {
                continue;
            }

            $paid++;
            OrderTimeline::log((int) $order->id, 'Pending', 'Payment received', $order->payment_method . ' · ref ' . $reference . ' · ₱' . number_format((float) $order->total_amount, 2));

            foreach (DB::table('order_items')->where('order_id', $order->id)->distinct()->pluck('seller_id') as $sellerId) {
                createNotification((int) $sellerId, 'New Paid Order', 'Order #' . $order->id . ' was paid by ' . $order->payment_method . '. You can accept and prepare it now.', 'order', (int) $order->id);
            }
        }

        return $paid;
    }

    public static function markFailed(int $buyerId, string $group): int
    {
        return DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('payment_group', $group)
            ->where('status', 'Pending')
            ->where('payment_status', 'Unpaid')
            ->update(['payment_status' => 'Failed', 'updated_at' => now()]);
    }

    /**
     * Unpaid orders past their hour are cancelled: stock and the voucher
     * go back. Run on page visits (no cron on the laptop server) and by the
     * scheduler.
     *
     * @return int  orders cancelled
     */
    public static function sweep(): int
    {
        $cancelled = 0;

        $expired = DB::table('orders')
            ->where('status', 'Pending')
            ->whereIn('payment_status', self::UNPAID)
            ->whereNotNull('pay_by')
            ->where('pay_by', '<=', now())
            ->limit(200)
            ->get();

        foreach ($expired as $order) {
            $changed = DB::table('orders')
                ->where('id', $order->id)
                ->where('status', 'Pending')
                ->whereIn('payment_status', self::UNPAID)
                ->update([
                    'status' => 'Cancelled',
                    'payment_status' => 'Unpaid',
                    'cancellation_reason' => 'Cancelled: unpaid. The payment was not completed within ' . self::PAY_WINDOW_MINUTES . ' minutes.',
                    'cancelled_by' => 'system',
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            if (!$changed) {
                continue;
            }

            $cancelled++;
            OrderTimeline::log((int) $order->id, 'Cancelled', 'Cancelled: unpaid', 'Not paid within ' . self::PAY_WINDOW_MINUTES . ' minutes.');
            OrderStock::cancelled((int) $order->id, 'Pending');

            createNotification((int) $order->buyer_id, 'Order Cancelled (Unpaid)', 'Order #' . $order->id . ' was cancelled because it was not paid within ' . self::PAY_WINDOW_MINUTES . ' minutes. Any voucher you used is back in your wallet.', 'order', (int) $order->id);
        }

        return $cancelled;
    }
}
