<?php

namespace App\Support;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What buyers see about a seller: the shop name (their approved business
 * name, else their own name), photo, location and shop-wide rating.
 * Used by the product page's seller card, the shop page and the cart.
 */
class SellerShop
{
    /**
     * @param  iterable<int>  $sellerIds
     * @return Collection<int, array> keyed by seller id
     */
    public static function many(iterable $sellerIds, bool $withStats = false): Collection
    {
        $ids = collect($sellerIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $users = User::whereIn('id', $ids)
            ->get(['id', 'name', 'profile_photo', 'city_municipality', 'province', 'created_at'])
            ->keyBy('id');

        $applications = DB::table('seller_applications')
            ->whereIn('user_id', $ids)
            ->where('status', 'Approved')
            ->get(['user_id', 'business_name', 'shop_description'])
            ->keyBy('user_id');

        $businessNames = $applications->pluck('business_name', 'user_id');

        $stats = $withStats ? self::stats($ids) : collect();

        return $ids->mapWithKeys(function ($id) use ($users, $businessNames, $applications, $stats) {
            $user = $users->get($id);
            $stat = $stats->get($id);

            return [$id => [
                'id' => $id,
                'name' => $businessNames[$id] ?? ($user->name ?? 'BoomBuy Seller'),
                'owner' => $user->name ?? null,
                'initial' => strtoupper(mb_substr($businessNames[$id] ?? ($user->name ?? 'B'), 0, 1)),
                'photo' => !empty($user?->profile_photo)
                    ? asset('storage/profile-photos/' . $user->profile_photo)
                    : null,
                'location' => collect([$user->city_municipality ?? null, $user->province ?? null])->filter()->implode(', ') ?: null,
                'since' => $user?->created_at?->format('M Y'),
                'description' => $applications->get($id)?->shop_description,
                'url' => route('shop.seller', $id),
                'products' => (int) ($stat->products ?? 0),
                'rating' => ($stat->reviews ?? 0) > 0 ? round((float) $stat->rating, 1) : null,
                'reviews' => (int) ($stat->reviews ?? 0),
            ]];
        });
    }

    public static function one(int $sellerId, bool $withStats = true): ?array
    {
        return self::many([$sellerId], $withStats)->get($sellerId);
    }

    /** How far back chats count for the response rate and time. */
    public const RESPONSE_DAYS = 90;

    /**
     * The shop card on a product page: the shop, plus items sold, how long
     * it has been on BoomBuy, and how it answers buyers' chats.
     */
    public static function card(int $sellerId): ?array
    {
        $shop = self::one($sellerId);

        if (!$shop) {
            return null;
        }

        $joined = User::whereKey($sellerId)->value('created_at');

        return $shop + [
            'sold' => (int) DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.seller_id', $sellerId)
                ->whereIn('orders.status', OrderStatus::DONE)
                ->sum('order_items.quantity'),
            'joined_ago' => $joined ? \Illuminate\Support\Carbon::parse($joined)->diffForHumans(['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) : null,
            ...self::responses($sellerId),
        ];
    }

    /**
     * Of the buyers who wrote to this shop lately, how many got a reply typed
     * by the seller (auto-replies don't count), and how fast the first one came.
     *
     * @return array{response_rate: ?int, response_time: ?string}
     */
    public static function responses(int $sellerId): array
    {
        $messages = DB::table('messages')
            ->join('users as senders', 'senders.id', '=', 'messages.sender_id')
            ->where(fn ($q) => $q->where('messages.recipient_id', $sellerId)->where('senders.role', 'buyer')
                ->orWhere('messages.sender_id', $sellerId))
            ->where(fn ($q) => $q->whereNull('messages.kind')->orWhere('messages.kind', 'text'))
            ->where('messages.created_at', '>=', now()->subDays(self::RESPONSE_DAYS))
            ->orderBy('messages.id')
            ->get(['messages.sender_id', 'messages.recipient_id', 'messages.created_at']);

        $chats = 0;
        $answered = 0;
        $waits = [];

        foreach ($messages->groupBy(fn ($m) => (int) $m->sender_id === $sellerId ? (int) $m->recipient_id : (int) $m->sender_id) as $thread) {
            $asked = $thread->first(fn ($m) => (int) $m->sender_id !== $sellerId);

            if (!$asked) {
                continue; // the seller wrote first: not a buyer waiting for an answer
            }

            $chats++;
            $reply = $thread->first(fn ($m) => (int) $m->sender_id === $sellerId && $m->created_at >= $asked->created_at);

            if ($reply) {
                $answered++;
                $waits[] = strtotime($reply->created_at) - strtotime($asked->created_at);
            }
        }

        if ($chats === 0) {
            return ['response_rate' => null, 'response_time' => null];
        }

        $time = null;

        if ($waits) {
            sort($waits);
            $median = $waits[intdiv(count($waits), 2)];
            $time = match (true) {
                $median <= 3600 => 'within minutes',
                $median <= 6 * 3600 => 'within hours',
                $median <= 86400 => 'within a day',
                default => 'within days',
            };
        }

        return ['response_rate' => (int) round($answered / $chats * 100), 'response_time' => $time];
    }

    /** Listed products and shop-wide review numbers per seller. */
    private static function stats(Collection $ids): Collection
    {
        $products = Product::whereIn('seller_id', $ids)
            ->onSale()
            ->selectRaw('seller_id, COUNT(*) as products')
            ->groupBy('seller_id')
            ->pluck('products', 'seller_id');

        $reviews = DB::table('product_reviews')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->whereIn('products.seller_id', $ids)
            ->selectRaw('products.seller_id, COUNT(*) as reviews, AVG(product_reviews.rating) as rating')
            ->groupBy('products.seller_id')
            ->get()
            ->keyBy('seller_id');

        return $ids->mapWithKeys(fn ($id) => [$id => (object) [
            'products' => $products[$id] ?? 0,
            'reviews' => $reviews[$id]->reviews ?? 0,
            'rating' => $reviews[$id]->rating ?? null,
        ]]);
    }
}
