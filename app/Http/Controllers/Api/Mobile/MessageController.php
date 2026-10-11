<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Requests\MessageRequest;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Support\ChatAutomation;
use App\Support\SellerShop;
use App\Support\SupportAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Messages in the app: the conversation list, one conversation (polled with
 * ?after=<last id>), and sending. Who may write to whom follows the website.
 */
class MessageController extends MobileController
{
    private const NOT_ALLOWED = 'You can message shops, the people on your orders, and BoomBuy Support.';

    public function index(Request $request): JsonResponse
    {
        $myId = (int) $this->user($request)->id;

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

        $last = Message::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $people = $this->people(User::whereIn('id', $rows->pluck('partner_id'))->get());

        $support = SupportAccount::user();

        return response()->json([
            'conversations' => $rows->map(function ($row) use ($last, $people, $myId) {
                $message = $last->get($row->last_id);

                return ($people->get($row->partner_id) ?? ['id' => (int) $row->partner_id, 'name' => 'Deleted User', 'role' => '', 'photo' => null]) + [
                    'last_message' => $message->message ?? '',
                    'last_is_mine' => $message && (int) $message->sender_id === $myId,
                    'last_message_at' => self::iso($message->created_at ?? null),
                    'unread_count' => (int) $row->unread_count,
                ];
            })->sortByDesc('last_message_at')->values(),
            'support' => $support ? $this->person($support) : null,
        ]);
    }

    /** ?after=<message id> returns only newer messages. */
    public function thread(Request $request, int $userId): JsonResponse
    {
        $me = $this->user($request);
        $partner = User::find($userId);

        if (!$partner || (int) $partner->id === (int) $me->id) {
            return $this->fail('User not found.', 404);
        }

        if (!$this->canMessage($me, $partner)) {
            return $this->fail(self::NOT_ALLOWED, 403);
        }

        $messages = Message::with('product')
            ->where(fn ($q) => $q
                ->where(fn ($m) => $m->where('sender_id', $me->id)->where('recipient_id', $userId))
                ->orWhere(fn ($m) => $m->where('sender_id', $userId)->where('recipient_id', $me->id)))
            ->where('id', '>', (int) $request->query('after', 0))
            ->orderBy('id')
            ->limit(300)
            ->get();

        Message::where('sender_id', $partner->id)->where('recipient_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'partner' => $this->person($partner),
            'messages' => $messages->map(fn ($m) => $this->messageJson($m, (int) $me->id))->values(),
            'seen_up_to' => (int) Message::where('sender_id', $me->id)->where('recipient_id', $partner->id)
                ->where('kind', Message::TEXT)->whereNotNull('read_at')->max('id'),
        ]);
    }

    /** message, product_id (optional: a product of either person). */
    public function store(MessageRequest $request, int $userId): JsonResponse
    {
        $me = $this->user($request);
        $partner = User::find($userId);

        if (!$partner || (int) $partner->id === (int) $me->id) {
            return $this->fail('User not found.', 404);
        }

        if (!$this->canMessage($me, $partner)) {
            return $this->fail(self::NOT_ALLOWED, 403);
        }

        $product = $request->validated('product_id')
            ? Product::where('id', (int) $request->validated('product_id'))->whereIn('seller_id', [$partner->id, $me->id])->first()
            : null;

        $message = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $partner->id,
            'message' => trim((string) $request->input('message')),
            'product_id' => $product?->id,
        ]);

        // A buyer writing to a shop gets its auto-reply (when on).
        $autoReply = $me->role === 'buyer' ? ChatAutomation::autoReply((int) $me->id, (int) $partner->id) : null;

        return response()->json([
            'message' => $this->messageJson($message->load('product'), (int) $me->id),
            'auto_reply' => $autoReply ? $this->messageJson($autoReply, (int) $me->id) : null,
        ]);
    }

    // ------------------------------------------------------------------

    /** The website's rule (MessageController::canMessage). */
    private function canMessage(object $me, User $partner): bool
    {
        $myId = (int) $me->id;

        if ($partner->role === 'admin') {
            return true;
        }

        if ($me->role === 'buyer' && $partner->role === 'seller' && $partner->status === 'Active') {
            return true;
        }

        if ($partner->role === 'logistics' && in_array($me->role, ['rider', 'seller'], true)) {
            return true;
        }

        $talkedBefore = Message::where(fn ($q) => $q->where('sender_id', $myId)->where('recipient_id', $partner->id))
            ->orWhere(fn ($q) => $q->where('sender_id', $partner->id)->where('recipient_id', $myId))
            ->exists();

        if ($talkedBefore) {
            return true;
        }

        $on = fn (int $userId) => fn ($query) => $query->where('buyer_id', $userId)
            ->orWhere('rider_id', $userId)
            ->orWhere('delivery_rider_id', $userId)
            ->orWhereExists(fn ($items) => $items->select(DB::raw(1))->from('order_items')
                ->whereColumn('order_items.order_id', 'orders.id')
                ->where('order_items.seller_id', $userId));

        return DB::table('orders')->where($on($myId))->where($on((int) $partner->id))->exists();
    }

    private function people($users)
    {
        $shops = SellerShop::many($users->where('role', 'seller')->pluck('id'));

        return $users->mapWithKeys(fn ($user) => [$user->id => $this->person($user, $shops->get($user->id))]);
    }

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
            'role' => $user->role,
            'role_label' => match ($user->role) {
                'admin' => 'Support',
                'logistics' => 'Logistics',
                default => ucfirst((string) $user->role),
            },
            'photo' => self::photoUrl($user->profile_photo),
        ];
    }

    private function messageJson(Message $message, int $myId): array
    {
        return [
            'id' => (int) $message->id,
            'kind' => $message->kind ?? Message::TEXT,
            'mine' => (int) $message->sender_id === $myId,
            'text' => $message->message,
            'order_id' => $message->order_id ? (int) $message->order_id : null,
            'product' => $message->product ? [
                'id' => (int) $message->product->id,
                'name' => $message->product->name,
                'price' => (float) $message->product->price,
                'image' => productImageUrl($message->product->image),
            ] : null,
            'created_at' => self::iso($message->created_at),
        ];
    }
}
