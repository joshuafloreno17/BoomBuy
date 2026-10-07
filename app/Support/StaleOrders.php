<?php

namespace App\Support;

use App\Services\SortingCenterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Orders that would otherwise wait forever:
 *
 *  - Pending: the seller never confirmed it. A reminder after
 *    SELLER_REMIND_DAYS, then it is cancelled after SELLER_DAYS (stock and
 *    voucher come back; it doesn't count against the buyer's COD limit).
 *  - Ready to Collect: the buyer never came. A reminder after
 *    PICKUP_REMIND_DAYS, then it goes back to the seller after PICKUP_DAYS.
 *  - A return/refund request the seller doesn't answer within
 *    RETURN_ANSWER_DAYS goes to BoomBuy to decide.
 *  - An approved return the buyer doesn't drop off within RETURN_DROP_DAYS
 *    is cancelled.
 *
 * Days are counted from the order's date, but never from before this
 * feature first ran (SINCE_KEY) — orders already waiting when it was
 * switched on get the full grace period instead of being cancelled at once.
 *
 * Runs with the auto-receive sweep from a web middleware (the laptop
 * serving boombuy.store has no cron), at most every few minutes; the
 * scheduler runs it too where there is one (routes/console.php).
 */
class StaleOrders
{
    public const SELLER_REMIND_DAYS = 2;

    public const SELLER_DAYS = 3;

    public const PICKUP_REMIND_DAYS = 5;

    public const PICKUP_DAYS = 7;

    public const RETURN_ANSWER_DAYS = 3;

    public const RETURN_DROP_DAYS = 7;

    /** Platform setting: when the timers started (set on the first sweep). */
    public const SINCE_KEY = 'stale_orders_since';

    private const THROTTLE_KEY = 'stale-orders-sweep';

    private const THROTTLE_MINUTES = 10;

    /** @return array{cancelled:int, returned:int, reminded:int, escalated:int, returns_cancelled:int} */
    public static function sweep(bool $force = false): array
    {
        $done = ['cancelled' => 0, 'returned' => 0, 'reminded' => 0, 'escalated' => 0, 'returns_cancelled' => 0];

        if (!$force && !Cache::add(self::THROTTLE_KEY, true, now()->addMinutes(self::THROTTLE_MINUTES))) {
            return $done;
        }

        $since = self::since();

        if ($since->lte(now()->subDays(self::SELLER_REMIND_DAYS))) {
            $done['reminded'] += self::remindSellers();
        }

        if ($since->lte(now()->subDays(self::SELLER_DAYS))) {
            $done['cancelled'] = self::cancelUnconfirmed();
        }

        if ($since->lte(now()->subDays(self::PICKUP_REMIND_DAYS))) {
            $done['reminded'] += self::remindPickups();
        }

        if ($since->lte(now()->subDays(self::PICKUP_DAYS))) {
            $done['returned'] = self::returnUncollected();
        }

        if ($since->lte(now()->subDays(self::RETURN_ANSWER_DAYS))) {
            $done['escalated'] = self::escalateUnanswered();
        }

        if ($since->lte(now()->subDays(self::RETURN_DROP_DAYS))) {
            $done['returns_cancelled'] = self::cancelUndelivered();
        }

        return $done;
    }

    /** Requests the seller left unanswered go to BoomBuy. */
    private static function escalateUnanswered(): int
    {
        $due = DB::table('return_refund_requests')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subDays(self::RETURN_ANSWER_DAYS))
            ->get(['id', 'order_id', 'buyer_id', 'seller_id']);

        $moved = 0;

        foreach ($due as $request) {
            $updated = DB::table('return_refund_requests')->where('id', $request->id)->where('status', 'pending')->update([
                'status' => 'disputed',
                'escalated_at' => now(),
                'admin_note' => 'The seller did not answer within ' . self::RETURN_ANSWER_DAYS . ' days.',
                'updated_at' => now(),
            ]);

            if (!$updated) {
                continue;
            }

            createNotification((int) $request->buyer_id, 'Return Sent to BoomBuy', 'The seller did not answer your request for order #' . $request->order_id . ' in time, so BoomBuy will decide it.', 'return_refund', (int) $request->id);

            if ($request->seller_id) {
                createNotification((int) $request->seller_id, 'Return Sent to BoomBuy', 'You did not answer the return/refund request for order #' . $request->order_id . ' within ' . self::RETURN_ANSWER_DAYS . ' days, so BoomBuy will decide it.', 'return_refund', (int) $request->id);
            }

            if ($adminId = SupportAccount::id()) {
                createNotification($adminId, 'Return Review Requested', 'Request #' . $request->id . ' (order #' . $request->order_id . ') was not answered by the seller. Please decide it.', 'return_refund', (int) $request->id);
            }

            $moved++;
        }

        return $moved;
    }

    /** Approved returns the buyer never brought in are cancelled. */
    private static function cancelUndelivered(): int
    {
        $due = DB::table('return_refund_requests')
            ->where('status', 'approved')
            ->where('request_type', 'Return')
            ->where('approved_at', '<=', now()->subDays(self::RETURN_DROP_DAYS))
            ->get(['id', 'order_id', 'buyer_id', 'seller_id']);

        $cancelled = 0;

        foreach ($due as $request) {
            $updated = DB::table('return_refund_requests')->where('id', $request->id)->where('status', 'approved')->update([
                'status' => 'cancelled',
                'closed_at' => now(),
                'admin_note' => 'Cancelled: the item was not brought to a Sorting Center within ' . self::RETURN_DROP_DAYS . ' days.',
                'updated_at' => now(),
            ]);

            if (!$updated) {
                continue;
            }

            createNotification((int) $request->buyer_id, 'Return Cancelled', 'Your return for order #' . $request->order_id . ' was cancelled because the item was not brought to a Sorting Center within ' . self::RETURN_DROP_DAYS . ' days.', 'return_refund', (int) $request->id);

            if ($request->seller_id) {
                createNotification((int) $request->seller_id, 'Return Cancelled', 'The buyer did not send back the item for order #' . $request->order_id . ', so the return was cancelled.', 'return_refund', (int) $request->id);
            }

            $cancelled++;
        }

        return $cancelled;
    }

    /** When the timers started counting (now, the first time). */
    private static function since(): Carbon
    {
        $saved = \App\Models\PlatformSetting::get(self::SINCE_KEY);

        if ($saved === '') {
            \App\Models\PlatformSetting::set(self::SINCE_KEY, now()->toDateTimeString());

            return now();
        }

        return Carbon::parse($saved);
    }

    /** Pending past SELLER_DAYS: cancelled for the seller. */
    private static function cancelUnconfirmed(): int
    {
        $overdue = DB::table('orders')
            ->where('status', 'Pending')
            ->where('created_at', '<=', now()->subDays(self::SELLER_DAYS))
            ->get(['id', 'buyer_id', 'status']);

        $cancelled = 0;
        $reason = 'Not confirmed by the seller within ' . self::SELLER_DAYS . ' days';

        foreach ($overdue as $order) {
            // Conditional, so a seller acting at the same moment wins.
            $updated = DB::table('orders')
                ->where('id', $order->id)
                ->where('status', 'Pending')
                ->update([
                    'status' => 'Cancelled',
                    'cancelled_by' => 'system',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Cancelled automatically: ' . strtolower($reason) . '.',
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                continue;
            }

            OrderStock::cancelled((int) $order->id, 'Pending');
            OrderTimeline::log((int) $order->id, 'Cancelled', 'Cancelled automatically', $reason);
            ChatAutomation::orderUpdate((int) $order->id, 'cancelled');

            createNotification(
                (int) $order->buyer_id,
                'Order Cancelled',
                'Your order #' . $order->id . ' was cancelled because the seller did not confirm it within ' . self::SELLER_DAYS . ' days. Nothing was charged — you can order it again from another shop.',
                'order',
                (int) $order->id
            );

            notifyOrderSellers(
                (int) $order->id,
                'Order Cancelled Automatically',
                'Order #' . $order->id . ' was cancelled because you did not confirm it within ' . self::SELLER_DAYS . ' days. The items were returned to your stock.'
            );

            $cancelled++;
        }

        return $cancelled;
    }

    /** Pending for SELLER_REMIND_DAYS: one reminder to the seller. */
    private static function remindSellers(): int
    {
        $due = DB::table('orders')
            ->where('status', 'Pending')
            ->where('created_at', '<=', now()->subDays(self::SELLER_REMIND_DAYS))
            ->where('created_at', '>', now()->subDays(self::SELLER_DAYS))
            ->pluck('id');

        $sent = 0;

        foreach ($due as $orderId) {
            $deadline = Carbon::parse(DB::table('orders')->where('id', $orderId)->value('created_at'))->addDays(self::SELLER_DAYS);

            foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
                if (self::alreadyTold((int) $sellerId, 'Confirm Order Soon', (int) $orderId)) {
                    continue;
                }

                createNotification(
                    (int) $sellerId,
                    'Confirm Order Soon',
                    'Order #' . $orderId . ' is still Pending. Confirm it by ' . $deadline->format('M j, g:i A') . ' or it will be cancelled automatically.',
                    'order',
                    (int) $orderId
                );
                $sent++;
            }
        }

        return $sent;
    }

    /** Ready to Collect past PICKUP_DAYS: back to the seller. */
    private static function returnUncollected(): int
    {
        $overdue = DB::table('orders')
            ->where('status', 'Ready to Collect')
            ->where('updated_at', '<=', now()->subDays(self::PICKUP_DAYS))
            ->pluck('id');

        $centers = app(SortingCenterService::class);
        $returned = 0;

        foreach ($overdue as $orderId) {
            try {
                $centers->returnToSeller((int) $orderId);
                $returned++;
            } catch (\App\Exceptions\ActionFailed $e) {
                // Someone handled it at the same moment.
            }
        }

        return $returned;
    }

    /** Ready to Collect for PICKUP_REMIND_DAYS: one reminder to the buyer. */
    private static function remindPickups(): int
    {
        $due = DB::table('orders')
            ->where('status', 'Ready to Collect')
            ->where('updated_at', '<=', now()->subDays(self::PICKUP_REMIND_DAYS))
            ->where('updated_at', '>', now()->subDays(self::PICKUP_DAYS))
            ->get(['id', 'buyer_id', 'updated_at']);

        $sent = 0;

        foreach ($due as $order) {
            if (self::alreadyTold((int) $order->buyer_id, 'Collect Your Order Soon', (int) $order->id)) {
                continue;
            }

            createNotification(
                (int) $order->buyer_id,
                'Collect Your Order Soon',
                'Order #' . $order->id . ' is still waiting for you at the Sorting Center. Collect it by '
                    . Carbon::parse($order->updated_at)->addDays(self::PICKUP_DAYS)->format('M j') . ' or it goes back to the seller.',
                'order',
                (int) $order->id
            );
            $sent++;
        }

        return $sent;
    }

    private static function alreadyTold(int $userId, string $title, int $orderId): bool
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('title', $title)
            ->where('reference_id', $orderId)
            ->exists();
    }
}
