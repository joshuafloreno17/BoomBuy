<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Support\SellerShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One Messages page for every role: the conversation list on the left and
 * the open conversation on the right (on phones, one at a time).
 */
class MessageController extends Controller
{
    public const ROLE_FILTERS = [
        'buyer' => 'Buyers',
        'seller' => 'Sellers',
        'logistics' => 'Logistics',
        'rider' => 'Riders',
    ];

    public function index()
    {
        $me = currentMessagingUser();

        if (!$me) {
            return redirect()->route('login');
        }

        return view('pages.messages.inbox', $this->listData($me) + [
            'me' => $me,
            'partner' => null,
            'thread' => collect(),
            'seenUpTo' => 0,
        ]);
    }

    public function thread($userId)
    {
        $me = currentMessagingUser();

        if (!$me) {
            return request()->wantsJson()
                ? response()->json(['message' => 'Please log in again.'], 401)
                : redirect()->route('login');
        }

        $partner = User::find($userId);

        if (!$partner || (int) $partner->id === (int) $me['id']) {
            return redirect()->route('messages.index')->with('error', 'User not found.');
        }

        $thread = Message::where(function ($query) use ($me, $userId) {
                $query->where('sender_id', $me['id'])->where('recipient_id', $userId);
            })
            ->orWhere(function ($query) use ($me, $userId) {
                $query->where('sender_id', $userId)->where('recipient_id', $me['id']);
            });

        // The open conversation polls this for anything newer than what it
        // shows, and for how far the other person has read.
        if (request()->wantsJson()) {
            $after = (int) request('after', 0);

            $new = (clone $thread)->where('id', '>', $after)->orderBy('id')->get();

            $this->markRead($partner->id, $me['id']);

            return response()->json([
                'messages' => $new->map(fn ($m) => $this->toJson($m, $me['id']))->values(),
                'seen_up_to' => $this->seenUpTo($me['id'], $partner->id),
            ]);
        }

        $thread = $thread->orderBy('created_at')->orderBy('id')->get();

        $this->markRead($partner->id, $me['id']);

        return view('pages.messages.inbox', $this->listData($me) + [
            'me' => $me,
            'partner' => $this->person($partner),
            'thread' => $thread,
            'seenUpTo' => $this->seenUpTo($me['id'], $partner->id),
        ]);
    }

    public function store($userId)
    {
        $me = currentMessagingUser();

        if (!$me) {
            return request()->wantsJson()
                ? response()->json(['message' => 'Please log in again.'], 401)
                : redirect()->route('login');
        }

        request()->validate([
            'message' => 'required|string|max:2000',
        ]);

        $partner = User::find($userId);

        if (!$partner) {
            return back()->with('error', 'User not found.');
        }

        if ((int) $partner->id === (int) $me['id']) {
            return redirect()->route('messages.index')->with('error', 'You cannot send a message to yourself.');
        }

        $message = Message::create([
            'sender_id' => $me['id'],
            'recipient_id' => $userId,
            'message' => trim((string) request('message')),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['message' => $this->toJson($message, $me['id'])]);
        }

        return redirect()->route('messages.thread', $userId);
    }

    /**
     * People the admin can start a conversation with ("New message").
     * Admin only — everyone else starts chats from a shop, order or Support.
     */
    public function recipients()
    {
        $me = currentMessagingUser();

        if (!$me || $me['role'] !== 'admin') {
            return response()->json(['message' => 'Not allowed.'], 403);
        }

        $search = trim((string) request('q', ''));

        // Sellers first (who the admin talks to most), then the other roles.
        $roleOrder = "CASE users.role WHEN 'seller' THEN 0 WHEN 'logistics' THEN 1 WHEN 'rider' THEN 2 ELSE 3 END";

        $users = User::query()
            ->leftJoin('seller_applications', function ($join) {
                $join->on('seller_applications.user_id', '=', 'users.id')
                    ->where('seller_applications.status', 'Approved');
            })
            ->whereIn('users.role', array_keys(self::ROLE_FILTERS))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $starts = $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('users.name', 'like', $like)
                        ->orWhere('users.email', 'like', $like)
                        ->orWhere('seller_applications.business_name', 'like', $like);
                })
                    // Names that start with what was typed come first,
                    // so a single letter already gives sensible suggestions.
                    ->orderByRaw(
                        'CASE WHEN seller_applications.business_name LIKE ? OR users.name LIKE ? THEN 0 ELSE 1 END',
                        [$starts, $starts]
                    );
            })
            // Nothing typed yet: suggest people right away.
            ->orderByRaw($roleOrder)
            ->orderByRaw('COALESCE(seller_applications.business_name, users.name)')
            ->limit($search === '' ? 15 : 12)
            ->get(['users.*']);

        return response()->json([
            'people' => $users->map(fn ($user) => $this->person($user) + [
                'email' => $user->email,
                'url' => route('messages.thread', $user->id),
            ])->values(),
        ]);
    }

    /** The conversation list (with search and filters) shared by both views. */
    private function listData(array $me): array
    {
        $myId = (int) $me['id'];

        $search = trim((string) request('q', ''));
        $filter = request('filter') === 'unread' ? 'unread' : 'all';
        $roleFilter = array_key_exists((string) request('role'), self::ROLE_FILTERS) ? request('role') : null;

        // One row per conversation partner: their latest message id and how
        // many of their messages to me are still unread.
        $rows = DB::table('messages')
            ->where('sender_id', $myId)
            ->orWhere('recipient_id', $myId)
            ->selectRaw(
                'CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS partner_id,
                 MAX(id) AS last_id,
                 SUM(CASE WHEN recipient_id = ? AND read_at IS NULL THEN 1 ELSE 0 END) AS unread_count',
                [$myId, $myId]
            )
            ->groupBy('partner_id')
            ->get();

        $lastMessages = Message::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $people = $this->people(User::whereIn('id', $rows->pluck('partner_id'))->get());

        $all = $rows
            ->map(function ($row) use ($lastMessages, $people, $myId) {
                $person = $people->get($row->partner_id) ?? [
                    'id' => (int) $row->partner_id, 'name' => 'Deleted User', 'subtitle' => null,
                    'role' => '', 'role_label' => '', 'photo' => null, 'initial' => '?',
                ];
                $last = $lastMessages->get($row->last_id);

                return $person + [
                    'partner_id' => (int) $row->partner_id,
                    'last_message' => $last->message ?? '',
                    'last_is_mine' => $last && (int) $last->sender_id === $myId,
                    'last_message_at' => $last->created_at ?? now(),
                    'unread_count' => (int) $row->unread_count,
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

        $conversations = $all
            ->when($filter === 'unread', fn ($c) => $c->where('unread_count', '>', 0))
            ->when($roleFilter, fn ($c) => $c->where('role', $roleFilter))
            ->when($search !== '', fn ($c) => $c->filter(
                fn ($conv) => stripos($conv['name'], $search) !== false
                    || stripos((string) $conv['subtitle'], $search) !== false
            ))
            ->values();

        return [
            'conversations' => $conversations,
            'totalConversations' => $all->count(),
            'unreadConversations' => $all->where('unread_count', '>', 0)->count(),
            'search' => $search,
            'filter' => $filter,
            'roleFilter' => $roleFilter,
            'roleFilters' => $me['role'] === 'admin' ? self::ROLE_FILTERS : [],
            'support' => $me['role'] !== 'admin' ? User::where('email', 'admin@boombuy.com')->first() : null,
        ];
    }

    /** Display details for several users at once, keyed by id. */
    private function people(Collection $users): Collection
    {
        $shops = SellerShop::many($users->where('role', 'seller')->pluck('id'));

        return $users->mapWithKeys(fn ($user) => [$user->id => $this->person($user, $shops->get($user->id))]);
    }

    /** How a person shows up in Messages: sellers by shop, the admin as Support. */
    private function person(User $user, ?array $shop = null): array
    {
        if ($user->role === 'seller' && $shop === null) {
            $shop = SellerShop::many([$user->id])->get($user->id);
        }

        $name = match (true) {
            $user->role === 'admin' => 'BoomBuy Support',
            $user->role === 'seller' && $shop => $shop['name'],
            default => $user->name,
        };

        return [
            'id' => (int) $user->id,
            'name' => $name,
            // Under a shop name, show who runs it.
            'subtitle' => $user->role === 'seller' && $shop && $shop['name'] !== $user->name ? $user->name : null,
            'role' => $user->role,
            'role_label' => match ($user->role) {
                'admin' => 'Support',
                'logistics' => 'Logistics',
                default => ucfirst((string) $user->role),
            },
            'photo' => !empty($user->profile_photo) ? asset('storage/profile-photos/' . $user->profile_photo) : null,
            'initial' => strtoupper(mb_substr($name, 0, 1)),
            'shop_url' => $user->role === 'seller' && $user->status === 'Active' ? route('shop.seller', $user->id) : null,
        ];
    }

    private function markRead(int $partnerId, int $myId): void
    {
        Message::where('sender_id', $partnerId)
            ->where('recipient_id', $myId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Id of the newest message I sent that the other person has read. */
    private function seenUpTo(int $myId, int $partnerId): int
    {
        return (int) Message::where('sender_id', $myId)
            ->where('recipient_id', $partnerId)
            ->whereNotNull('read_at')
            ->max('id');
    }

    private function toJson(Message $message, int $myId): array
    {
        return [
            'id' => $message->id,
            'mine' => (int) $message->sender_id === $myId,
            'text' => $message->message,
            'time' => $message->created_at->format('g:i A'),
            'day' => $message->created_at->toDateString(),
            'day_label' => self::dayLabel($message->created_at),
        ];
    }

    /** "Today", "Yesterday", or "Sep 28" (with the year when it isn't this year). */
    public static function dayLabel($date): string
    {
        $date = \Illuminate\Support\Carbon::parse($date);

        return match (true) {
            $date->isToday() => 'Today',
            $date->isYesterday() => 'Yesterday',
            $date->year === now()->year => $date->format('M j'),
            default => $date->format('M j, Y'),
        };
    }
}
