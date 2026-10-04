<?php

namespace App\Support;

use App\Models\Message;
use Illuminate\Support\Facades\DB;

/**
 * Messages the shop "sends" by itself in the buyer–seller chat:
 *  - an auto-reply when a buyer writes and the seller hasn't replied lately;
 *  - order updates (placed, ready for pickup, out for delivery, delivered,
 *    cancelled) shown as an order card.
 * They are saved as already read, so they never add to the unread badges
 * (the bell already announces order changes).
 */
class ChatAutomation
{
    /** No second auto-reply (or none at all, if the seller wrote) within this window. */
    public const AUTO_REPLY_GAP_HOURS = 12;

    public const AUTO_REPLY_MAX = 300;

    private const ORDER_TEXT = [
        'placed' => 'Thanks for your order #%d! We received it and will start preparing it soon.',
        'ready' => 'Your order #%d is packed and ready for pickup. A rider will collect it soon.',
        'out_for_delivery' => 'Your order #%d is out for delivery — it is on its way to you!',
        'delivered' => 'Your order #%d has been delivered. Enjoy, and please confirm once you have received it.',
        'cancelled' => 'Order #%d has been cancelled.',
    ];

    public static function defaultAutoReply(string $shopName): string
    {
        return 'Hi! Thanks for messaging ' . $shopName . ' 😊 We got your message and will reply as soon as we can.';
    }

    /** The shop's auto-reply settings: enabled + the text it sends. */
    public static function settingsFor(int $sellerId): array
    {
        $shop = DB::table('seller_applications')->where('user_id', $sellerId)->orderByDesc('id')
            ->first(['business_name', 'auto_reply_enabled', 'auto_reply_message']);

        $name = $shop->business_name ?? (DB::table('users')->where('id', $sellerId)->value('name') ?: 'our shop');

        return [
            'enabled' => $shop ? (bool) $shop->auto_reply_enabled : true,
            'message' => trim((string) ($shop->auto_reply_message ?? '')) ?: self::defaultAutoReply($name),
            'custom' => trim((string) ($shop->auto_reply_message ?? '')),
        ];
    }

    /**
     * After a buyer writes to a seller: the shop's auto-reply, unless it is
     * turned off or the seller already wrote (or auto-replied) recently.
     */
    public static function autoReply(int $buyerId, int $sellerId): ?Message
    {
        if (DB::table('users')->where('id', $sellerId)->value('role') !== 'seller') {
            return null;
        }

        $settings = self::settingsFor($sellerId);

        if (!$settings['enabled']) {
            return null;
        }

        $recentFromShop = Message::where('sender_id', $sellerId)
            ->where('recipient_id', $buyerId)
            ->whereIn('kind', [Message::TEXT, Message::AUTO_REPLY])
            ->where('created_at', '>=', now()->subHours(self::AUTO_REPLY_GAP_HOURS))
            ->exists();

        if ($recentFromShop) {
            return null;
        }

        return Message::create([
            'sender_id' => $sellerId,
            'recipient_id' => $buyerId,
            'message' => $settings['message'],
            'kind' => Message::AUTO_REPLY,
            'read_at' => now(),
        ]);
    }

    /** An order update from the order's shop to its buyer. Silently skips unknown events. */
    public static function orderUpdate(int $orderId, string $event): ?Message
    {
        if (!isset(self::ORDER_TEXT[$event])) {
            return null;
        }

        $order = DB::table('orders')->where('id', $orderId)->first(['id', 'buyer_id']);
        $sellerId = DB::table('order_items')->where('order_id', $orderId)->value('seller_id');

        if (!$order || !$sellerId || (int) $sellerId === (int) $order->buyer_id) {
            return null;
        }

        return Message::create([
            'sender_id' => $sellerId,
            'recipient_id' => $order->buyer_id,
            'message' => sprintf(self::ORDER_TEXT[$event], $orderId),
            'kind' => Message::ORDER_UPDATE,
            'order_id' => $orderId,
            'read_at' => now(),
        ]);
    }
}
