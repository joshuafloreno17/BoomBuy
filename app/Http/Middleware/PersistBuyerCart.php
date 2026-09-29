<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| KEEP THE BUYER'S CART WITH THEIR ACCOUNT
|--------------------------------------------------------------------------
|
| The cart lives in session('cart'). Sessions end — they expire after
| SESSION_LIFETIME ("remember me" then starts a fresh, empty one), or on
| logout — and the cart used to vanish with them. This keeps a copy in
| saved_carts: a session that has no cart yet gets the saved one back, and
| whatever the request left in the cart is written back afterwards. Every
| cart change (add, update, remove, checkout, reorder) is covered without
| touching those controllers.
|
*/

class PersistBuyerCart
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->restore();

        $response = $next($request);

        // Logging in happens during the request, so check again afterwards.
        $this->restore();
        $this->save();

        return $response;
    }

    private function buyerId(): ?int
    {
        $user = session('user');

        return ($user && ($user['role'] ?? '') === 'buyer') ? (int) $user['id'] : null;
    }

    /** A session without a cart (new, expired, just logged in) gets the saved one. */
    private function restore(): void
    {
        $buyerId = $this->buyerId();

        if (!$buyerId || session()->has('cart')) {
            return;
        }

        $saved = DB::table('saved_carts')->where('user_id', $buyerId)->value('items');
        $items = $saved ? json_decode($saved, true) : null;

        if (is_array($items) && $items !== []) {
            session()->put('cart', $items);
        }
    }

    /** Write the cart back only when it changed. */
    private function save(): void
    {
        $buyerId = $this->buyerId();

        if (!$buyerId || !session()->has('cart')) {
            return;
        }

        $cart = session('cart');
        $cart = is_array($cart) ? $cart : [];
        ksort($cart);
        $json = json_encode($cart);

        $saved = DB::table('saved_carts')->where('user_id', $buyerId)->value('items');

        if ($saved !== null) {
            $savedItems = json_decode($saved, true) ?: [];
            ksort($savedItems);

            if (json_encode($savedItems) === $json) {
                return;
            }
        }

        DB::table('saved_carts')->updateOrInsert(
            ['user_id' => $buyerId],
            ['items' => $json, 'updated_at' => now()]
        );
    }
}
