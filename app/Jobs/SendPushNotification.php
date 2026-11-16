<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Support\PushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends one notification to the user's phones, from the queue worker
 * (start-boombuy.ps1 runs it) — so a page, like a hub scan, never waits on
 * Firebase. Gone notifications (an undone scan deletes its own) are skipped.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification) {
            PushNotifications::send($notification);
        }
    }
}
