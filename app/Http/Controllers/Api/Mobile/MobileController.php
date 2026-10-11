<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\ActionFailed;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Categories;
use App\Support\DeliveryFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shared pieces of the BoomBuy mobile API (/api/mobile/v1). The signed-in
 * user comes from the Bearer token (AuthenticateApiToken); every rule lives
 * in the same services the website uses.
 */
abstract class MobileController extends Controller
{
    protected function user(Request $request): object
    {
        return $request->attributes->get('apiUser');
    }

    /** The user the way the website's session holds it (what the services expect). */
    protected function sessionUser(Request $request): array
    {
        $user = $this->user($request);

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile_photo' => $user->profile_photo ?? null,
        ];
    }

    /** Runs a service step; its rule message comes back as 422. */
    protected function act(callable $step, ?callable $extra = null): JsonResponse
    {
        try {
            $message = $step();
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage());
        }

        return response()->json(array_merge(['message' => $message], $extra ? $extra() : []));
    }

    protected function fail(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $extra), $status);
    }

    protected static function iso($value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }

    protected static function photoUrl(?string $file): ?string
    {
        return !empty($file) ? asset('storage/profile-photos/' . $file) : null;
    }

    /** A product as the shop grid shows it. */
    protected function productCard(Product $product, array $wishlistIds = [], $shops = null): array
    {
        $reviewCount = (int) ($product->reviews_count ?? 0);
        $shop = $shops ? $shops->get($product->seller_id) : null;

        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            'original_price' => $product->isDiscounted() ? (float) $product->original_price : null,
            'discount' => $product->isDiscounted() ? (int) $product->discount_percent : null,
            'stock' => (int) ($product->sellable_stock ?? $product->stock),
            'sold' => (int) ($product->sold_count ?? 0),
            'image' => productImageUrl($product->image),
            'rating' => $reviewCount > 0 ? round((float) $product->reviews_avg_rating, 1) : null,
            'reviews' => $reviewCount,
            'has_variations' => (int) ($product->variations_count ?? 0) > 0,
            'free_shipping' => (float) $product->price >= DeliveryFee::freeShippingMin(),
            'shop_name' => $shop['name'] ?? null,
            'in_wishlist' => in_array($product->id, $wishlistIds),
        ];
    }

    protected function wishlistIds(int $userId): array
    {
        return DB::table('wishlists')->where('user_id', $userId)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    /** The 16 shop categories. */
    protected static function categoryList(): array
    {
        return collect(Categories::LIST)
            ->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])
            ->values()
            ->all();
    }

    /** One notification for the app. */
    protected static function notificationJson(object $n): array
    {
        return [
            'id' => (int) $n->id,
            'title' => $n->title,
            'message' => $n->message,
            'type' => $n->type,
            'reference_id' => $n->reference_id !== null ? (int) $n->reference_id : null,
            'read' => $n->read_at !== null,
            'created_at' => self::iso($n->created_at),
        ];
    }
}
