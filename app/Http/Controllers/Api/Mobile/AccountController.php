<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Requests\StoreAddressRequest;
use App\Models\BuyerAddress;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A buyer's address book and wishlist. */
class AccountController extends MobileController
{
    public function addresses(Request $request): JsonResponse
    {
        return response()->json([
            'addresses' => BuyerAddress::forUser((int) $this->user($request)->id)->map(fn ($a) => self::addressJson($a))->values(),
        ]);
    }

    /** label, phone, address, is_default — the website's address rules (StoreAddressRequest). */
    public function storeAddress(StoreAddressRequest $request): JsonResponse
    {
        $userId = (int) $this->user($request)->id;
        $data = $request->validated();

        if (BuyerAddress::where('user_id', $userId)->count() >= 10) {
            return $this->fail('You can save up to 10 addresses. Delete one first.');
        }

        $makeDefault = $request->boolean('is_default') || !BuyerAddress::where('user_id', $userId)->exists();

        DB::transaction(function () use ($data, $userId, $makeDefault) {
            if ($makeDefault) {
                BuyerAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            BuyerAddress::create([
                'user_id' => $userId,
                'label' => trim((string) ($data['label'] ?? '')) ?: null,
                'phone' => trim($data['phone']),
                'address' => trim($data['address']),
                'is_default' => $makeDefault,
            ]);
        });

        return response()->json(['message' => 'Address saved.'] + $this->addressesPayload($userId));
    }

    public function defaultAddress(Request $request, int $id): JsonResponse
    {
        $userId = (int) $this->user($request)->id;
        $address = BuyerAddress::where('user_id', $userId)->find($id);

        if (!$address) {
            return $this->fail('Address not found.', 404);
        }

        DB::transaction(function () use ($address) {
            BuyerAddress::where('user_id', $address->user_id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json(['message' => 'Default address updated.'] + $this->addressesPayload($userId));
    }

    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        $userId = (int) $this->user($request)->id;
        $address = BuyerAddress::where('user_id', $userId)->find($id);

        if (!$address) {
            return $this->fail('Address not found.', 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Keep one default while any address is left.
        if ($wasDefault) {
            BuyerAddress::where('user_id', $userId)->latest('updated_at')->first()?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Address deleted.'] + $this->addressesPayload($userId));
    }

    public function wishlist(Request $request): JsonResponse
    {
        $userId = (int) $this->user($request)->id;
        $ids = DB::table('wishlists')->where('user_id', $userId)->orderByDesc('created_at')->pluck('product_id');

        $products = Product::whereIn('id', $ids)
            ->withSellableStock()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            ->get()
            ->sortBy(fn ($p) => $ids->search($p->id));

        $onSale = Product::onSale()->whereIn('products.id', $ids)->pluck('products.id')->flip();

        return response()->json([
            'products' => $products->map(fn ($p) => $this->productCard($p, $ids->all()) + ['on_sale' => $onSale->has($p->id)])->values(),
        ]);
    }

    public function toggleWishlist(Request $request, int $id): JsonResponse
    {
        $userId = (int) $this->user($request)->id;

        if (!Product::where('id', $id)->exists()) {
            return $this->fail('Product not found.', 404);
        }

        $existing = DB::table('wishlists')->where('user_id', $userId)->where('product_id', $id)->first();

        if ($existing) {
            DB::table('wishlists')->where('id', $existing->id)->delete();
        } else {
            DB::table('wishlists')->insert(['user_id' => $userId, 'product_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json([
            'in_wishlist' => !$existing,
            'message' => $existing ? 'Removed from your wishlist.' : 'Added to your wishlist.',
        ]);
    }

    public static function addressJson(BuyerAddress $address): array
    {
        return [
            'id' => (int) $address->id,
            'label' => $address->label,
            'phone' => $address->phone,
            'address' => $address->address,
            'is_default' => (bool) $address->is_default,
        ];
    }

    private function addressesPayload(int $userId): array
    {
        return ['addresses' => BuyerAddress::forUser($userId)->map(fn ($a) => self::addressJson($a))->values()];
    }
}
