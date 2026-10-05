<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\CodPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\BuyerOrderService;
use App\Exceptions\ActionFailed;
use App\Http\Requests\ProfilePhotoRequest;
use App\Services\ProfilePhotoService;
use App\Http\Requests\StoreAddressRequest;

class BuyerController extends Controller
{
    // Order status → how far along the 5-step tracker it is, and a short note.
    private const ORDER_PROGRESS = [
        'Pending' => [1, 'Waiting for the seller to confirm.'],
        'Processing' => [2, 'Seller is packing your order.'],
        'Ready for Pickup' => [2, 'Packed and waiting for a rider.'],
        'Assigned' => [2, 'A rider is on the way to the seller.'],
        'Picked Up' => [3, 'Picked up — heading to the Sorting Center.'],
        'At Sorting Center' => [3, 'At the Sorting Center.'],
        'Assigned for Delivery' => [3, 'Assigned to a rider for delivery.'],
        'Out for Delivery' => [4, 'Rider is on the way — arriving soon.'],
        'Delivery Failed' => [4, 'Delivery attempt failed — it will be rescheduled.'],
    ];

    private const TO_SHIP_STATUSES = ['Pending', 'Processing', 'Ready for Pickup'];

    private const TO_RECEIVE_STATUSES = ['Assigned', 'Picked Up', 'At Sorting Center', 'Assigned for Delivery', 'Out for Delivery'];

    public function dashboard()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $buyerId = (int) $user['id'];

        /*
        |--------------------------------------------------------------------------
        | ACTIVE ORDERS (not yet delivered, cancelled or returned)
        |--------------------------------------------------------------------------
        */

        $activeOrderRows = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->whereIn('status', array_keys(self::ORDER_PROGRESS))
            // A refused parcel is on its way back to the seller, not to the buyer.
            ->whereNull('buyer_refused_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $activeItems = DB::table('order_items')
            ->whereIn('order_id', $activeOrderRows->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');

        $activeProducts = Product::whereIn(
            'id',
            $activeItems->flatten()->pluck('product_id')->unique()
        )->get(['id', 'image', 'category'])->keyBy('id');

        $activeOrders = $activeOrderRows->map(function ($order) use ($activeItems, $activeProducts) {

            $items = $activeItems->get($order->id, collect());
            $first = $items->first();
            $product = $first ? $activeProducts->get($first->product_id) : null;

            [$step, $note] = self::ORDER_PROGRESS[$order->status];

            return [
                'id' => $order->id,
                'status' => $order->status,
                'total' => (float) $order->total_amount,
                'name' => $first
                    ? $first->product_name . ($items->count() > 1 ? ' + ' . ($items->count() - 1) . ' more' : '')
                    : 'Order #' . $order->id,
                'step' => $step,
                'note' => $note,
                'seller_id' => $first->seller_id ?? null,
                'image' => productImageUrl($product->image ?? null),
                'icon' => \App\Support\Categories::icon($product->category ?? null),
            ];
        })->all();

        /*
        |--------------------------------------------------------------------------
        | BUY AGAIN — products from delivered orders that can still be bought
        |--------------------------------------------------------------------------
        */

        $boughtIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.buyer_id', $buyerId)
            ->where('orders.status', 'Delivered')
            ->orderByDesc('orders.created_at')
            ->pluck('order_items.product_id')
            ->unique()
            ->values();

        $buyAgain = Product::whereIn('id', $boughtIds)
            ->withSellableStock()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            ->onSale()
            ->get()
            ->sortBy(fn ($p) => $boughtIds->search($p->id))
            ->take(4)
            ->map(fn ($p) => $this->dashboardProductCard($p))
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | DISCOVER — Latest / Top rated / Under ₱1,000
        |--------------------------------------------------------------------------
        */

        $visible = fn () => Product::onSale()
            ->withSellableStock()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating');

        $wishlistIds = DB::table('wishlists')
            ->where('user_id', $buyerId)
            ->pluck('product_id')
            ->all();

        $toCards = fn ($products) => $products
            ->map(fn ($p) => $this->dashboardProductCard($p, $wishlistIds))
            ->values()
            ->all();

        $discover = [
            'latest' => $toCards($visible()->latest()->take(6)->get()),
            'top' => $toCards(
                $visible()
                    ->has('reviews')
                    ->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('reviews_count')
                    ->take(6)
                    ->get()
            ),
            'budget' => $toCards($visible()->where('price', '<', 1000)->latest()->take(6)->get()),
        ];

        $panel = $this->accountPanelData($user);

        return view(
            'pages.buyer.dashboard',
            compact('user', 'activeOrders', 'buyAgain', 'discover', 'panel')
        );
    }

    // The mobile "Me" screen — the dashboard's desktop sidebar as a page.
    public function account()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $panel = $this->accountPanelData($user);

        return view('pages.buyer.account', compact('user', 'panel'));
    }

    // Everything the account sidebar / Me screen shows.
    private function accountPanelData(array $user): array
    {
        $buyerId = (int) $user['id'];

        $counts = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sumOf = fn (array $statuses) => (int) collect($statuses)->sum(fn ($s) => $counts[$s] ?? 0);

        // Delivered orders with at least one item the buyer hasn't reviewed yet.
        $toReview = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('product_reviews', function ($join) use ($buyerId) {
                $join->on('product_reviews.order_id', '=', 'orders.id')
                    ->on('product_reviews.product_id', '=', 'order_items.product_id')
                    ->where('product_reviews.buyer_id', '=', $buyerId);
            })
            ->where('orders.buyer_id', $buyerId)
            ->where('orders.status', 'Delivered')
            ->whereNull('product_reviews.id')
            ->distinct()
            ->count('orders.id');

        $dbUser = User::find($buyerId);

        return [
            'name' => $user['name'] ?? 'Buyer',
            'email' => $user['email'] ?? '',
            'photo' => !empty($dbUser?->profile_photo)
                ? asset('storage/profile-photos/' . $dbUser->profile_photo)
                : null,
            'since' => $dbUser?->created_at?->format('M Y'),
            'to_ship' => $sumOf(self::TO_SHIP_STATUSES),
            'to_receive' => $sumOf(self::TO_RECEIVE_STATUSES),
            'to_review' => $toReview,
            'active_orders' => $sumOf(self::TO_SHIP_STATUSES) + $sumOf(self::TO_RECEIVE_STATUSES),
            'unread_messages' => \App\Models\Message::where('recipient_id', $buyerId)->whereNull('read_at')->count(),
            'unread_notifications' => \App\Models\Notification::where('user_id', $buyerId)->whereNull('read_at')->count(),
            'cod' => CodPolicy::status($buyerId),
        ];
    }

    private function dashboardProductCard(Product $product, array $wishlistIds = []): array
    {
        $reviewCount = (int) ($product->reviews_count ?? 0);

        return [
            'id' => $product->id,
            'slug' => Str::slug($product->name) . '-' . $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            // The options' stock when the product has options (Product::SELLABLE_STOCK_SQL).
            'stock' => (int) ($product->sellable_stock ?? $product->stock),
            'image' => productImageUrl($product->image),
            'icon' => \App\Support\Categories::icon($product->category),
            'rating' => $reviewCount > 0 ? round((float) $product->reviews_avg_rating, 1) : null,
            'reviews' => $reviewCount,
            // Products with options have to be picked on the product page.
            'has_variations' => (int) ($product->variations_count ?? 0) > 0,
            'in_wishlist' => in_array($product->id, $wishlistIds),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ADDRESS BOOK
    |--------------------------------------------------------------------------
    */

    public function storeAddress(StoreAddressRequest $request)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $data = $request->validated();

        $userId = (int) $user['id'];

        if (\App\Models\BuyerAddress::where('user_id', $userId)->count() >= 10) {
            return back()->with('error', 'You can save up to 10 addresses. Delete one first.');
        }

        // The first address, or one marked "make default", becomes the default.
        $makeDefault = request()->boolean('is_default')
            || !\App\Models\BuyerAddress::where('user_id', $userId)->exists();

        DB::transaction(function () use ($data, $userId, $makeDefault) {
            if ($makeDefault) {
                \App\Models\BuyerAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            \App\Models\BuyerAddress::create([
                'user_id' => $userId,
                'label' => trim((string) ($data['label'] ?? '')) ?: null,
                'phone' => trim($data['phone']),
                'address' => trim($data['address']),
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('success', 'Address saved.');
    }

    public function setDefaultAddress($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $address = \App\Models\BuyerAddress::where('user_id', $user['id'])->findOrFail($id);

        DB::transaction(function () use ($address) {
            \App\Models\BuyerAddress::where('user_id', $address->user_id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', 'Default address updated.');
    }

    public function deleteAddress($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $address = \App\Models\BuyerAddress::where('user_id', $user['id'])->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();

        // Keep one default while any address is left.
        if ($wasDefault) {
            \App\Models\BuyerAddress::where('user_id', $user['id'])
                ->latest('updated_at')
                ->first()
                ?->update(['is_default' => true]);
        }

        return back()->with('success', 'Address deleted.');
    }

    public function profile()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $dbUser = User::find($user['id']);

        $totalOrders = DB::table('orders')
            ->where('buyer_id', $user['id'])
            ->count();

        $totalSpent = DB::table('orders')
            ->where('buyer_id', $user['id'])
            ->where('status', 'Delivered')
            ->sum('total_amount');

        return view(
            'pages.buyer.profile',
            compact('user', 'dbUser', 'totalOrders', 'totalSpent') + [
                'addresses' => \App\Models\BuyerAddress::forUser((int) $user['id']),
            ]
        );
    }

    public function updateProfile()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $name = trim(request('name'));
        $phone = trim(request('phone'));
        $address = trim(request('address'));

        if (empty($name) || empty($phone) || empty($address)) {
            return back()
                ->withInput()
                ->with('error', 'Please complete all fields.');
        }

        if (
            User::where('phone', $phone)
                ->where('id', '!=', $user['id'])
                ->exists()
        ) {
            return back()
                ->withInput()
                ->with('error', 'This phone number is already registered.');
        }

        $dbUser = User::find($user['id']);
        $dbUser->name = $name;
        $dbUser->phone = $phone;
        $dbUser->address = $address;
        $dbUser->save();

        // Keep the session copy in sync so the navbar/name display updates too
        session()->put('user', array_merge($user, [
            'name' => $name,
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePhoto(ProfilePhotoRequest $request, ProfilePhotoService $photos)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        session()->put('user', $photos->store($user, $request->file('profile_photo')));

        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function updatePassword()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $current = request('current_password');
        $new = request('new_password');
        $confirm = request('new_password_confirmation');

        if (empty($current) || empty($new) || empty($confirm)) {
            return back()->with('error', 'Please complete all password fields.');
        }

        $dbUser = User::find($user['id']);

        if (!Hash::check($current, $dbUser->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        if (strlen($new) < 8) {
            return back()->with('error', 'New password must be at least 8 characters.');
        }

        if ($new !== $confirm) {
            return back()->with('error', 'New passwords do not match.');
        }

        $dbUser->password = Hash::make($new);
        $dbUser->save();

        \App\Support\LoginGate::passwordChanged($dbUser, true);

        return back()->with('success', 'Password changed successfully.');
    }

    public function wishlist()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $products = DB::table('wishlists')
            ->join('products', 'products.id', '=', 'wishlists.product_id')
            ->where('wishlists.user_id', $user['id'])
            ->select('products.*', 'wishlists.created_at as wishlisted_at')
            ->orderByDesc('wishlists.created_at')
            ->get();

        // Which saved products can still be bought (not archived/flagged, and
        // the seller is still active), and which need an option picked first.
        $onSaleIds = Product::onSale()
            ->whereIn('products.id', $products->pluck('id'))
            ->pluck('products.id')
            ->flip();

        $withOptions = DB::table('product_variations')
            ->whereIn('product_id', $products->pluck('id'))
            ->distinct()
            ->pluck('product_id')
            ->flip();

        $products->each(function ($product) use ($onSaleIds, $withOptions) {
            $product->on_sale = $onSaleIds->has($product->id);
            $product->has_options = $withOptions->has($product->id);
        });

        return view(
            'pages.buyer.wishlist',
            compact('user', 'products')
        );
    }

    public function toggleWishlist($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        $existing = DB::table('wishlists')
            ->where('user_id', $user['id'])
            ->where('product_id', $id)
            ->first();

        if ($existing) {

            DB::table('wishlists')
                ->where('id', $existing->id)
                ->delete();

            $inWishlist = false;

        } else {

            DB::table('wishlists')->insert([
                'user_id' => $user['id'],
                'product_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inWishlist = true;
        }

        if (request()->wantsJson()) {
            return response()->json(['in_wishlist' => $inWishlist]);
        }

        return back()->with(
            'success',
            $inWishlist ? 'Added to your wishlist.' : 'Removed from your wishlist.'
        );
    }

    public function orders()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $orders = DB::table('orders')
            ->where('buyer_id', $user['id'])
            ->orderByDesc('created_at')
            ->get();

        // Everything the cards need, loaded once for all orders.
        $allItems = DB::table('order_items')
            ->whereIn('order_id', $orders->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');

        $itemProducts = Product::whereIn('id', $allItems->flatten()->pluck('product_id')->filter()->unique())
            ->get(['id', 'name', 'image', 'category'])
            ->keyBy('id');

        $itemShops = \App\Support\SellerShop::many($allItems->flatten()->pluck('seller_id'));

        // Has this buyer already reviewed this product for this order?
        // Without this, the "already rated" badge could never show and
        // the Rate Product form would keep reappearing after rating.
        $buyerReviews = DB::table('product_reviews')
            ->where('buyer_id', $user['id'])
            ->get()
            ->keyBy(fn ($review) => $review->order_id . ':' . $review->product_id);

        $orders = $orders->map(function ($order) use ($allItems, $itemProducts, $itemShops, $buyerReviews) {

            $order = (array) $order;

            $order['items'] = $allItems->get($order['id'], collect())->map(function ($item) use ($order, $itemProducts, $itemShops, $buyerReviews) {

                $item = (array) $item;
                $product = $itemProducts->get($item['product_id'] ?? 0);
                $shop = $itemShops->get((int) ($item['seller_id'] ?? 0));

                $item['name'] =
                    $item['product_name'] ?? 'Product';

                $item['subtotal'] =
                    (float) ($item['price'] ?? 0) *
                    (int) ($item['quantity'] ?? 1);

                // The photo of the option bought (kept on the line), else the cover.
                $item['image'] = ($item['variation_image'] ?? null) ?: ($product->image ?? null);
                $item['category'] = $product->category ?? null;
                $item['slug'] = $product ? Str::slug($product->name) . '-' . $product->id : null;

                $item['seller_name'] = $shop['name'] ?? null;
                $item['shop_url'] = $shop['url'] ?? null;

                $review = $buyerReviews->get($order['id'] . ':' . ($item['product_id'] ?? 0));
                $item['review'] = $review ? (array) $review : null;

                return $item;

            })->toArray();

            // Friendly date, and where the parcel is on the 5-step tracker.
            $order['date_label'] = !empty($order['created_at'])
                ? \Illuminate\Support\Carbon::parse($order['created_at'])->format('M j, Y · g:i A')
                : null;

            [$order['step'], $order['step_note']] = $order['status'] === 'Delivered'
                ? [5, null]
                : (empty($order['buyer_refused_at']) ? (self::ORDER_PROGRESS[$order['status']] ?? [0, null]) : [0, null]);

            // Basic order information
            $order['total'] =
                (float) ($order['total_amount'] ?? 0);

            $order['date'] =
                $order['created_at'] ?? null;

            $order['buyer_name'] =
                $order['shipping_name'] ?? 'Unknown Buyer';

            $order['address'] =
                $order['shipping_address'] ?? '';

            $order['phone'] =
                $order['shipping_phone'] ?? '';

            $order['payment'] =
                $order['payment_method'] ?? '';

            /*
            |--------------------------------------------------------------------------
            | RIDER INFORMATION
            |--------------------------------------------------------------------------
            */

            $order['rider_name'] = null;
            $order['rider_email'] = null;
            $order['rider_profile_photo'] = null;

            if (!empty($order['rider_id'])) {

                $rider = DB::table('users')
                    ->where('id', $order['rider_id'])
                    ->where('role', 'rider')
                    ->first();

                if ($rider) {

                    $order['rider_name'] =
                        $rider->name ?? 'BoomBuy Rider';

                    $order['rider_email'] =
                        $rider->email ?? '';

                    $order['rider_profile_photo'] =
                        $rider->profile_photo ?? null;
                }
            }

            return $order;

        })->toArray();

        $codStatus = CodPolicy::status((int) $user['id']);
        $cancelReasons = CodPolicy::CANCEL_REASONS;

        return view(
            'pages.buyer.orders',
            compact('user', 'orders', 'codStatus', 'cancelReasons')
        );
    }

    public function markReceived($id, BuyerOrderService $orders)
    {
        return $this->buyerOrderAction(fn (array $user) => $orders->markReceived((int) $user['id'], (int) $id));
    }

    public function cancelOrder($id, BuyerOrderService $orders)
    {
        return $this->buyerOrderAction(
            fn (array $user) => $orders->cancel((int) $user['id'], (int) $id, (string) request('cancel_reason'), (string) request('cancel_details'))
        );
    }
    public function reorder($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')
            ->where('id', $id)
            ->where('buyer_id', $user['id'])
            ->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        $items = DB::table('order_items')->where('order_id', $id)->get();

        $cart = session()->get('cart', []);
        $addedCount = 0;
        $skippedCount = 0;

        foreach ($items as $item) {

            $product = Product::find($item->product_id);

            if (!$product || !$product->isPurchasable()) {
                $skippedCount++;
                continue;
            }

            // Bought without an option, but the product has options now —
            // the buyer has to pick one on the product page.
            if (empty($item->variation_label) && $product->variations()->exists()) {
                $skippedCount++;
                continue;
            }

            $variationId = 0;
            $effectiveStock = (int) $product->stock;

            // The order only stored a display label ("Color: Red"), not a
            // variation_id — parse it back and match against this product's
            // current variations, the same technique used for seller restock.
            if (!empty($item->variation_label)) {

                [$variationType, $variationValue] = array_pad(
                    explode(': ', $item->variation_label, 2),
                    2,
                    null
                );

                $variation = \App\Models\ProductVariation::where('product_id', $product->id)
                    ->where('variation_type', $variationType)
                    ->where('variation_value', $variationValue)
                    ->first();

                if (!$variation) {
                    $skippedCount++;
                    continue;
                }

                $variationId = $variation->id;
                $effectiveStock = (int) $variation->stock;
            }

            if ($effectiveStock <= 0) {
                $skippedCount++;
                continue;
            }

            $cartKey = $product->id . ':' . $variationId;
            $quantity = min((int) $item->quantity, $effectiveStock);

            $cart[$cartKey] = ($cart[$cartKey] ?? 0) + $quantity;
            $addedCount++;
        }

        session()->put('cart', $cart);

        if ($addedCount === 0) {
            return redirect()->route('buyer.orders')->with(
                'error',
                'None of the items from this order are available to reorder right now.'
            );
        }

        $message = $addedCount . ' item(s) added to your cart.';

        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' item(s) could not be added (out of stock or no longer available).';
        }

        return redirect()->route('cart')->with('success', $message);
    }

    public function reviewProduct($orderId, $productId)
    {
        $user = requireUserRole('buyer');
        if (!is_array($user)) return $user;

        $order = DB::table('orders')
            ->where('id', $orderId)
            ->where('buyer_id', $user['id'])
            ->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        if ($order->status !== 'Delivered' || empty($order->buyer_received_at)) {
            return back()->with('error', 'You can only review products after receiving the order.');
        }

        $item = DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$item) {
            return back()->with('error', 'Product not found in this order.');
        }

        $rating = (int) request('rating');
        $review = trim((string) request('review'));

        if ($rating < 1 || $rating > 5) {
            return back()->with('error', 'Please select a rating from 1 to 5 stars.');
        }

        $existing = DB::table('product_reviews')
            ->where('buyer_id', $user['id'])
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            DB::table('product_reviews')
                ->where('id', $existing->id)
                ->update([
                    'rating' => $rating,
                    'review' => $review !== '' ? $review : null,
                    'updated_at' => now(),
                ]);

            return back()->with('success', 'Your review has been updated!');
        }

        DB::table('product_reviews')->insert([
            'buyer_id' => $user['id'],
            'order_id' => $orderId,
            'product_id' => $productId,
            'rating' => $rating,
            'review' => $review !== '' ? $review : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Thank you! Your product review has been submitted.');
    }

    public function requestReturnRefund($orderId, BuyerOrderService $orders)
    {
        return $this->buyerOrderAction(fn (array $user) => $orders->requestReturn(
            (int) $user['id'],
            (int) $orderId,
            (int) request('order_item_id'),
            (string) request('request_type'),
            (string) request('reason'),
            (string) request('message'),
            request()->file('evidence')
        ));
    }

    /** Runs a buyer order action and flashes its message (or the rule it broke). */
    private function buyerOrderAction(callable $action)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        try {
            return back()->with('success', $action($user));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function showRegister()
    {
        return view('pages.buyer.register');
    }

    public function register(Request $request)
    {
        $lastName = trim(request('last_name'));
        $firstName = trim(request('first_name'));
        $middleInitial = trim(request('middle_initial'));
        $sex = request('sex');
        $birthdate = request('birthdate');
        $email = strtolower(trim(request('email')));
        $password = request('password');
        $passwordConfirmation = request('password_confirmation');
        $phone = trim(request('phone'));
        $province = trim(request('province'));
        $cityMunicipality = trim(request('city_municipality'));
        $barangay = trim(request('barangay'));
        $streetAddress = trim(request('street_address'));

        $name = formatFullName($firstName, $middleInitial, $lastName);
        $address = trim($streetAddress . ', ' . $barangay . ', ' . $cityMunicipality . ', ' . $province, ', ');

        // VALIDATION
        if (
            empty($lastName) ||
            empty($firstName) ||
            empty($sex) ||
            empty($birthdate) ||
            empty($email) ||
            empty($password) ||
            empty($passwordConfirmation) ||
            empty($phone) ||
            empty($province) ||
            empty($cityMunicipality) ||
            empty($barangay) ||
            empty($streetAddress)
        ) {
            return back()
                ->withInput()
                ->with('error', 'Please complete all fields.');
        }

        if (!request('terms')) {
            return back()
                ->withInput()
                ->with('error', 'Please agree to the Terms & Conditions and Privacy Policy.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()
                ->withInput()
                ->with('error', 'Please enter a valid email address.');
        }

        if (strlen($password) < 8) {
            return back()
                ->withInput()
                ->with('error', 'Password must be at least 8 characters.');
        }

        if ($password !== $passwordConfirmation) {
            return back()
                ->withInput()
                ->with('error', 'Passwords do not match.');
        }

        // CHECK EXISTING EMAIL
        if (User::where('email', $email)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Email is already registered.');
        }

        // CHECK EXISTING PHONE NUMBER
        if (User::where('phone', $phone)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'This phone number is already registered.');
        }

        // VALID ID UPLOAD
        $request->validate([
            'id_photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ], [], [
            'id_photo' => 'valid ID',
        ]);

        $idPhotoPath = $request
            ->file('id_photo')
            ->store('buyer-ids/' . (string) Str::uuid(), 'local');

        // Hold registration data until OTP is verified — walang naka-save sa DB pa
        session()->put('pending_registration', [
            'name' => $name,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_initial' => $middleInitial ?: null,
            'sex' => $sex,
            'birthdate' => $birthdate,
            'age' => calculateAge($birthdate),
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'address' => $address,
            'province' => $province,
            'city_municipality' => $cityMunicipality,
            'barangay' => $barangay,
            'street_address' => $streetAddress,
            'id_photo' => $idPhotoPath,
            'role' => 'buyer',
        ]);

        if (!generateAndSendOtp($email, $name)) {
            return back()
                ->withInput()
                ->with('error', 'We could not send the verification code right now. Please try again in a moment.');
        }

        return redirect()
            ->route('otp.show')
            ->with('success', 'We sent a 6-digit code to your email.');
    }
}
