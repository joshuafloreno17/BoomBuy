<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cash on Delivery rules:
 *  - a COD order can be cancelled by the buyer only while Pending/Confirmed/Preparing
 *    (prepaid orders can't be cancelled by the buyer once checked out);
 *  - buyers who keep cancelling or refusing parcels lose COD for a while,
 *    since every one of those costs the seller packing and shipping.
 */
class CodPolicy
{
    public const METHOD = 'Cash on Delivery';

    public const PAYMENT_METHODS = ['Cash on Delivery', 'GCash', 'Maya', 'Credit / Debit Card'];

    public const CANCELLABLE_STATUSES = OrderStatus::WITH_SELLER;

    /** Cancellations + refusals allowed inside the window before COD is paused. */
    public const STRIKE_LIMIT = 3;

    public const WINDOW_DAYS = 30;

    public const CANCEL_REASONS = [
        'Changed my mind',
        'Wrong address or contact number',
        'Ordered by mistake',
        'Found a cheaper price elsewhere',
        'Delivery takes too long',
        'Other',
    ];

    public static function isCod(?string $paymentMethod): bool
    {
        return $paymentMethod === self::METHOD;
    }

    public static function buyerCanCancel(object|array $order): bool
    {
        $order = (object) $order;

        return self::isCod($order->payment_method ?? null)
            && in_array($order->status ?? null, self::CANCELLABLE_STATUSES);
    }

    /**
     * Timestamps of this buyer's cancellations and refusals inside the
     * window, oldest first.
     */
    private static function strikeTimes(int $buyerId): array
    {
        $since = now()->subDays(self::WINDOW_DAYS);

        $cancelled = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('cancelled_by', 'buyer')
            ->where('cancelled_at', '>=', $since)
            ->pluck('cancelled_at');

        $refused = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('buyer_refused_at', '>=', $since)
            ->pluck('buyer_refused_at');

        return $cancelled->merge($refused)
            ->map(fn ($t) => Carbon::parse($t))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array{strikes:int, limit:int, blocked:bool, available_at:?Carbon, next_drop_at:?Carbon}
     */
    public static function status(int $buyerId): array
    {
        $times = self::strikeTimes($buyerId);
        $count = count($times);
        $blocked = $count >= self::STRIKE_LIMIT;

        // COD comes back once enough strikes age out of the window to drop
        // below the limit.
        $availableAt = $blocked
            ? $times[$count - self::STRIKE_LIMIT]->copy()->addDays(self::WINDOW_DAYS)
            : null;

        return [
            'strikes' => $count,
            'limit' => self::STRIKE_LIMIT,
            'blocked' => $blocked,
            'available_at' => $availableAt,
            // When the oldest strike leaves the 30-day window (one fewer).
            'next_drop_at' => $count > 0 ? $times[0]->copy()->addDays(self::WINDOW_DAYS) : null,
        ];
    }

    /**
     * All-time numbers for the seller's view of a buyer.
     *
     * @return array{orders:int, cancelled:int, refused:int, recent:int}
     */
    public static function buyerHistory(int $buyerId): array
    {
        return [
            'orders' => DB::table('orders')->where('buyer_id', $buyerId)->count(),
            'cancelled' => DB::table('orders')->where('buyer_id', $buyerId)->where('cancelled_by', 'buyer')->count(),
            'refused' => DB::table('orders')->where('buyer_id', $buyerId)->whereNotNull('buyer_refused_at')->count(),
            'recent' => count(self::strikeTimes($buyerId)),
        ];
    }
}
