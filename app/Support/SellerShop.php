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
