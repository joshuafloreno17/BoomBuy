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

if (!function_exists('notifyLogisticsUsers')) {

    function notifyLogisticsUsers(
        string $title,
        string $message,
        ?string $type = null,
        ?int $referenceId = null
    ): void {

        $logisticsUserIds = \Illuminate\Support\Facades\DB::table('users')
            ->where('role', 'logistics')
            ->where('status', 'Active')
            ->pluck('id');

        foreach ($logisticsUserIds as $logisticsUserId) {
            createNotification($logisticsUserId, $title, $message, $type, $referenceId);
        }
    }
}

if (!function_exists('calculateAge')) {

    function calculateAge(?string $birthdate): ?int
    {
        if (empty($birthdate)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($birthdate)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('formatFullName')) {

    function formatFullName(?string $firstName, ?string $middleInitial, ?string $lastName): string
    {
        $middle = !empty($middleInitial) ? rtrim(trim($middleInitial), '.') . '. ' : '';

        return trim($firstName . ' ' . $middle . $lastName);
    }
}
