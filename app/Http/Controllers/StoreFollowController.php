<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Buyers follow stores (B5): Follow / Unfollow on the store page, the
 * follower count, and the buyer's Following list.
 */
class StoreFollowController extends Controller
{
    public static function followers(int $sellerId): int
    {
        return DB::table('store_follows')->where('seller_id', $sellerId)->count();
    }

    public static function follows(?array $user, int $sellerId): bool
    {
        return $user && ($user['role'] ?? '') === 'buyer'
            && DB::table('store_follows')->where('user_id', $user['id'])->where('seller_id', $sellerId)->exists();
    }

    public function toggle(int $seller)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            // Back to the store page after logging in.
            session()->put('after_login', route('shop.seller', $seller));

            return $user;
        }

        $isStore = User::where('id', $seller)->where('role', 'seller')->where('status', 'Active')->exists();
        abort_unless($isStore, 404);

        $row = ['user_id' => $user['id'], 'seller_id' => $seller];

        // "follow" / "unfollow" are idempotent, so a double-click can't flip it back.
        $want = request('action') ?: (DB::table('store_follows')->where($row)->exists() ? 'unfollow' : 'follow');

        if ($want === 'follow') {
            DB::table('store_follows')->insertOrIgnore($row + ['created_at' => now(), 'updated_at' => now()]);
            $message = 'You are now following this store.';
        } else {
            DB::table('store_follows')->where($row)->delete();
            $message = 'You unfollowed this store.';
        }

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'following' => $want === 'follow',
                'followers' => self::followers($seller),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function index()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $ids = DB::table('store_follows')->where('user_id', $user['id'])->orderByDesc('created_at')->pluck('seller_id');
        $shops = \App\Support\SellerShop::many($ids);
        $stores = $ids->map(fn ($id) => $shops->get($id))->filter()->values();

        return view('pages.buyer.following', compact('user', 'stores'));
    }
}
