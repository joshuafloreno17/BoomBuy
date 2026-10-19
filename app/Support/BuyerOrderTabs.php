<?php

namespace App\Support;

/**
 * The buyer's My Orders tabs (Buyer Modules A10): every order is in
 * exactly one of them.
 *
 *   To Pay      online order waiting for payment
 *   To Ship     with the seller (placed → ready for pickup)
 *   To Receive  picked up → delivered, until the buyer confirms (or 7 days pass)
 *   Completed   received
 *   Cancelled   cancelled, or sent back to the seller
 *   Return/Refund  a return or refund was requested for it
 */
class BuyerOrderTabs
{
    public const LABELS = [
        'all' => 'All',
        'to-pay' => 'To Pay',
        'to-ship' => 'To Ship',
        'to-receive' => 'To Receive',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'returns' => 'Return/Refund',
    ];

    private const TO_SHIP = ['pending', 'confirmed', 'preparing', 'ready for pickup', 'pickup assigned'];

    private const CANCELLED = ['cancelled', 'returning', 'returning to branch', 'return ready', 'returning to seller', 'returned to seller', 'lost', 'damaged'];

    public static function group(array|object $order, bool $hasReturnRequest = false): string
    {
        $order = (array) $order;
        $status = strtolower((string) ($order['status'] ?? 'pending'));

        if ($hasReturnRequest) {
            return 'returns';
        }

        if ($status === 'pending' && in_array($order['payment_status'] ?? null, Payments::UNPAID, true)) {
            return 'to-pay';
        }

        return match (true) {
            in_array($status, self::TO_SHIP, true) => 'to-ship',
            $status === 'completed' => 'completed',
            in_array($status, self::CANCELLED, true) => 'cancelled',
            default => 'to-receive',
        };
    }
}
