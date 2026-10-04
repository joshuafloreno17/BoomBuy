<?php

namespace App\Support;

use App\Models\Message;
use App\Models\Notification;

/**
 * Unread notifications and messages for one user — the badges on every
 * sidebar and on the buyer navbar.
 */
class Inbox
{
    public static function unreadNotifications(?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    public static function unreadMessages(?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        return Message::where('recipient_id', $userId)->whereNull('read_at')->count();
    }
}
