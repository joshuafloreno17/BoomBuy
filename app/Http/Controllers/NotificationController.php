<?php

namespace App\Http\Controllers;

use App\Models\Notification;

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
}
