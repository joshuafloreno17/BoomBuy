<?php

use App\Models\Notification;

if (!function_exists('createNotification')) {

    function createNotification(
        int $userId,
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): Notification {

        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'reference_id' => $referenceId,
            'read_at' => null,
        ]);
    }
}

if (!function_exists('createAdminNotification')) {

    function createAdminNotification(
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): void {

        $notifications = session()->get(
            'admin_notifications',
            []
        );

        $notifications[] = [
            'id' => uniqid(),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'reference_id' => $referenceId,
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
        ];

        session()->put(
            'admin_notifications',
            $notifications
        );
    }
}
