<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;

class MessageController extends Controller
{
    public function index()
    {
        $me = currentMessagingUser();

        if (!$me) {
            return redirect()->route('login');
        }

        $conversations = Message::where('sender_id', $me['id'])
            ->orWhere('recipient_id', $me['id'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function ($message) use ($me) {
                return $message->sender_id === $me['id']
                    ? $message->recipient_id
                    : $message->sender_id;
            })
            ->map(function ($messages, $partnerId) use ($me) {

                $partner = User::find($partnerId);
                $lastMessage = $messages->first();

                $unreadCount = $messages
                    ->where('recipient_id', $me['id'])
                    ->whereNull('read_at')
                    ->count();

                return [
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->name ?? 'Deleted User',
                    'partner_role' => $partner->role ?? '',
                    'last_message' => $lastMessage->message,
                    'last_message_at' => $lastMessage->created_at,
                    'unread_count' => $unreadCount,
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

        return view(
            'pages.messages.inbox',
            compact('me', 'conversations')
        );
    }

    public function thread($userId)
    {
        $me = currentMessagingUser();

        if (!$me) {
            return redirect()->route('login');
        }

        $partner = User::find($userId);

        if (!$partner) {
            return redirect()->route('messages.index')->with('error', 'User not found.');
        }

        $thread = Message::where(function ($query) use ($me, $userId) {
                $query->where('sender_id', $me['id'])->where('recipient_id', $userId);
            })
            ->orWhere(function ($query) use ($me, $userId) {
                $query->where('sender_id', $userId)->where('recipient_id', $me['id']);
            })
            ->orderBy('created_at')
            ->get();

        // Mark incoming messages as read
        Message::where('sender_id', $userId)
            ->where('recipient_id', $me['id'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view(
            'pages.messages.thread',
            compact('me', 'partner', 'thread')
        );
    }

    public function store($userId)
    {
        $me = currentMessagingUser();

        if (!$me) {
            return redirect()->route('login');
        }

        request()->validate([
            'message' => 'required|string|max:2000',
        ]);

        $partner = User::find($userId);

        if (!$partner) {
            return back()->with('error', 'User not found.');
        }

        Message::create([
            'sender_id' => $me['id'],
            'recipient_id' => $userId,
            'message' => request('message'),
        ]);

        return redirect()->route('messages.thread', $userId);
    }
}
