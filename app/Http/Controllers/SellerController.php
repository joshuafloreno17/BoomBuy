<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CodPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\SellerOrderService;
use App\Exceptions\ActionFailed;
use App\Services\ProductService;
use App\Support\ProductPhotos;
use App\Services\ReturnRefundService;
use App\Http\Requests\ProfilePhotoRequest;
use App\Services\ProfilePhotoService;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\AutoReplyRequest;
use App\Http\Requests\UpdateShopRequest;

class SellerController extends Controller
{
    public function dashboard()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        /*
        |--------------------------------------------------------------------------
        | SELLER PRODUCTS
        |--------------------------------------------------------------------------
        */

        $products = Product::where(
            'seller_id',
            $user['id']
        )->where('is_archived', false)
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'variations'])
            // With options, the stock is theirs added up (what buyers can actually order).
            ->withSellableStock()
            ->latest()
            ->get();

        $archivedProducts = Product::where(
            'seller_id',
            $user['id']
        )->where('is_archived', true)->latest()->get();

        /*
        |--------------------------------------------------------------------------
        | SELLER STATISTICS
        |--------------------------------------------------------------------------
        |
        | Straight from the database (orders that contain this seller's items).
        | Sales count only this seller's own items on delivered orders, net of
        | the vouchers the seller funds.
        |
        */

        $totalProducts = count($products);

        $sellerOrderStatuses = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->select('orders.id', 'orders.status')
            ->distinct()
            ->get();

        $totalOrders = $sellerOrderStatuses->count();

        $pendingOrders = $sellerOrderStatuses
            ->whereIn('status', ['Pending', 'Processing'])
            ->count();

        $totalSales = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->where('orders.status', 'Delivered')
            ->sum(DB::raw('order_items.price * order_items.quantity'));

        $totalSales = max(0, $totalSales - $this->voucherDiscounts((int) $user['id']));

        /*
        |--------------------------------------------------------------------------
        | TODAY + TO-DO (dashboard tiles and "Needs your action")
        |--------------------------------------------------------------------------
        */

        $sellerItems = fn () => DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id']);

        $deliveredToday = $sellerItems()
            ->where('orders.status', 'Delivered')
            ->whereRaw('DATE(COALESCE(orders.delivered_at, orders.updated_at)) = ?', [now()->toDateString()])
            ->selectRaw('COUNT(DISTINCT orders.id) as orders, COALESCE(SUM(order_items.price * order_items.quantity), 0) as revenue')
            ->first();

        // Waiting since before today: the ones to pack first.
        $toShipOld = $sellerItems()
            ->whereIn('orders.status', ['Pending', 'Processing'])
            ->where('orders.created_at', '<', now()->startOfDay())
            ->distinct()
            ->count('orders.id');

        $toDropOff = $sellerOrderStatuses->where('status', 'Processing')->count();
        $newOrders = $sellerOrderStatuses->where('status', 'Pending')->count();

        $lowStockCount = $products->filter(fn ($p) => (int) $p->sellable_stock <= 5)->count();

        $rating = DB::table('product_reviews')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('products.seller_id', $user['id'])
            ->selectRaw('AVG(product_reviews.rating) as average, COUNT(*) as total')
            ->first();

        $pendingReturns = DB::table('return_refund_requests')
            ->where('seller_id', $user['id'])
            ->where('status', 'pending')
            ->count();

        $shopName = DB::table('seller_applications')->where('user_id', $user['id'])->value('business_name')
            ?: ($user['name'] ?? 'Seller');

        $unreadNotes = \App\Support\Inbox::unreadNotifications((int) $user['id']);
        $recentNotes = Notification::where('user_id', $user['id'])->latest()->limit(6)->get();

        $recentOrders = $sellerItems()
            ->groupBy('orders.id', 'orders.status', 'orders.created_at', 'orders.shipping_name', 'orders.payment_method')
            ->orderByDesc('orders.created_at')
            ->limit(5)
            ->select(
                'orders.id',
                'orders.status',
                'orders.created_at',
                'orders.shipping_name',
                'orders.payment_method',
                DB::raw('SUM(order_items.price * order_items.quantity) as subtotal'),
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('MIN(order_items.product_name) as first_item'),
                DB::raw('COUNT(*) as line_count')
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | ORDER STATUS BREAKDOWN (for dashboard chart)
        |--------------------------------------------------------------------------
        */

        $orderStatusBreakdown = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->select('orders.status', DB::raw('COUNT(DISTINCT order_items.order_id) as total'))
            ->groupBy('orders.status')
            ->get();

        // Last 7 days sales trend (delivered only)
        $salesTrend = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->where('orders.status', 'Delivered')
            ->whereDate('orders.created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(orders.created_at) as day'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Every one of the 7 days, ₱0 where nothing sold, so the chart has a line.
        $salesTrend = $this->everyDay($salesTrend, now()->subDays(6)->toDateString(), now()->toDateString());


        /*
        |--------------------------------------------------------------------------
        | SELLER DASHBOARD
        |--------------------------------------------------------------------------
        */

        return view(
            'pages.seller.dashboard',
            compact(
                'user',
                'products',
                'archivedProducts',
                'totalProducts',
                'totalOrders',
                'pendingOrders',
                'totalSales',
                'orderStatusBreakdown',
                'salesTrend',
                'deliveredToday',
                'toShipOld',
                'toDropOff',
                'newOrders',
                'lowStockCount',
                'rating',
                'pendingReturns',
                'recentOrders',
                'shopName',
                'unreadNotes',
                'recentNotes'
            )
        );
    }

    public function profile()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $dbUser = User::find($user['id']);

        $application = DB::table('seller_applications')
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->first();

        $totalProducts = DB::table('products')
            ->where('seller_id', $user['id'])
            ->count();

        $totalSales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->where('orders.status', 'Delivered')
            ->sum(DB::raw('order_items.price * order_items.quantity'));

        $totalSales = max(0, (float) $totalSales - $this->voucherDiscounts((int) $user['id']));

        return view(
            'pages.seller.profile',
            compact('user', 'dbUser', 'application', 'totalProducts', 'totalSales') + [
                'autoReply' => \App\Support\ChatAutomation::settingsFor((int) $user['id']),
                'autoReplyDefault' => \App\Support\ChatAutomation::defaultAutoReply($application->business_name ?? $user['name']),
            ]
        );
    }

    public function updateProfile()
    {
        $user = requireUserRole('seller');

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

        session()->put('user', array_merge($user, [
            'name' => $name,
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Shop name and "about this shop", shown on the seller's shop page.
     * The category stays as registered — it's what the category lock uses.
     */
    public function updateAutoReply(AutoReplyRequest $request)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $data = $request->validated();

        DB::table('seller_applications')
            ->where('user_id', $user['id'])
            ->update([
                'auto_reply_enabled' => (bool) ($data['auto_reply_enabled'] ?? false),
                // Blank = the default greeting.
                'auto_reply_message' => trim((string) ($data['auto_reply_message'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        return redirect(route('seller.profile') . '#auto-reply')->with('success', 'Auto-reply saved.');
    }

    public function updateShop(UpdateShopRequest $request)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $data = $request->validated();

        $shopName = trim($data['business_name']);

        // Two shops with the same name would confuse buyers.
        $taken = DB::table('seller_applications')
            ->where('user_id', '!=', $user['id'])
            ->whereRaw('LOWER(business_name) = ?', [mb_strtolower($shopName)])
            ->exists();

        if ($taken) {
            return back()
                ->withInput()
                ->with('error', 'Another shop already uses that name. Please choose a different one.');
        }

        $updated = DB::table('seller_applications')
            ->where('user_id', $user['id'])
            ->update([
                'business_name' => $shopName,
                'shop_description' => trim((string) ($data['shop_description'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'We could not find your seller application.');
        }

        return back()->with('success', 'Shop details updated.');
    }

    public function updatePhoto(ProfilePhotoRequest $request, ProfilePhotoService $photos)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        session()->put('user', $photos->store($user, $request->file('profile_photo')));

        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function updatePassword()
    {
        $user = requireUserRole('seller');

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

    public function reviews()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $reviews = DB::table('product_reviews')
            ->join('users', 'users.id', '=', 'product_reviews.buyer_id')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('products.seller_id', $user['id'])
            ->select(
                'product_reviews.*',
                'users.name as buyer_name',
                'products.name as product_name'
            )
            ->orderByDesc('product_reviews.created_at')
            ->get();

        return view('pages.seller.reviews', compact('user', 'reviews'));
    }

    public function replyReview($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $reply = trim((string) request('seller_reply'));

        if (empty($reply)) {
            return back()->with('error', 'Please write a reply before submitting.');
        }

        $review = DB::table('product_reviews')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('product_reviews.id', $id)
            ->where('products.seller_id', $user['id'])
            ->select('product_reviews.id')
            ->first();

        if (!$review) {
            abort(404);
        }

        DB::table('product_reviews')
            ->where('id', $id)
            ->update([
                'seller_reply' => $reply,
                'seller_replied_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Reply posted.');
    }

    public function reports()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        // Date range — defaults to the last 30 days if not specified
        $from = request('from') ?: now()->subDays(30)->format('Y-m-d');
        $to = request('to') ?: now()->format('Y-m-d');

        $baseQuery = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user['id'])
            ->whereDate('orders.created_at', '>=', $from)
            ->whereDate('orders.created_at', '<=', $to);

        $deliveredQuery = (clone $baseQuery)->where('orders.status', 'Delivered');

        $grossSales = (float) $deliveredQuery->sum(DB::raw('order_items.price * order_items.quantity'));

        // The seller funds their own vouchers — commission and earnings are
        // on what the buyer actually paid for their items.
        $voucherDiscounts = $this->voucherDiscounts((int) $user['id'], $from, $to);

        $totalSales = max(0, $grossSales - $voucherDiscounts);

        $totalOrders = (clone $baseQuery)->distinct('order_items.order_id')->count('order_items.order_id');

        $deliveredOrders = (clone $deliveredQuery)->distinct('order_items.order_id')->count('order_items.order_id');

        $averageOrder = $deliveredOrders > 0 ? $totalSales / $deliveredOrders : 0;

        $commissionRate = (float) PlatformSetting::get('commission_rate', '10');
        $commissionOwed = $totalSales * ($commissionRate / 100);
        $netEarnings = $totalSales - $commissionOwed;

        // Sales by product, within range, delivered only
        $productSales = (clone $deliveredQuery)
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('revenue')
            ->get();

        // Sales by day, within range, delivered only
        $dailySales = (clone $deliveredQuery)
            ->select(
                DB::raw('DATE(orders.created_at) as day'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // The chart gets every day of the range (₱0 included); the table keeps only days with sales.
        $trendDays = $this->everyDay($dailySales, $from, $to);

        return view(
            'pages.seller.reports',
            compact(
                'user',
                'from',
                'to',
                'totalSales',
                'totalOrders',
                'deliveredOrders',
                'averageOrder',
                'commissionRate',
                'commissionOwed',
                'netEarnings',
                'voucherDiscounts',
                'productSales',
                'dailySales',
                'trendDays'
            )
        );
    }

    /**
     * One row per day from $from to $to, ₱0 where nothing sold — so a sales
     * chart draws a line instead of a lone dot. Long ranges keep only the
     * days with sales (a year of zeros helps nobody).
     */
    private function everyDay($rows, string $from, string $to)
    {
        $start = \Illuminate\Support\Carbon::parse($from)->startOfDay();
        $end = \Illuminate\Support\Carbon::parse($to)->startOfDay();

        if ($end->lt($start) || $start->diffInDays($end) > 92) {
            return collect($rows)->values();
        }

        $byDay = collect($rows)->keyBy(fn ($row) => \Illuminate\Support\Carbon::parse($row->day)->toDateString());

        $days = collect();
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days->push((object) [
                'day' => $day->toDateString(),
                'revenue' => (float) ($byDay[$day->toDateString()]->revenue ?? 0),
            ]);
        }

        return $days;
    }

    public function vouchers()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $vouchers = Voucher::where('seller_id', $user['id'])
            ->orderByDesc('created_at')
            ->get();

        return view(
            'pages.seller.vouchers',
            compact('user', 'vouchers')
        );
    }

    public function storeVoucher(StoreVoucherRequest $request)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        Voucher::create([
            'seller_id' => $user['id'],
            'code' => strtoupper(request('code')),
            'discount_type' => request('discount_type'),
            'discount_value' => request('discount_value'),
            'min_order_amount' => request('min_order_amount', 0),
            'max_uses' => request('max_uses') ?: null,
            'expires_at' => request('expires_at') ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Voucher created successfully.');
    }

    public function toggleVoucher($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $voucher = Voucher::where('id', $id)->where('seller_id', $user['id'])->first();

        if (!$voucher) {
            abort(404);
        }

        $voucher->update(['is_active' => !$voucher->is_active]);

        return back()->with('success', 'Voucher updated.');
    }

    public function deleteVoucher($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        Voucher::where('id', $id)->where('seller_id', $user['id'])->delete();

        return back()->with('success', 'Voucher deleted.');
    }

    public function variations($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

        if (!$product) {
            abort(404);
        }

        $variations = ProductVariation::where('product_id', $id)
            ->orderBy('variation_type')
            ->orderBy('variation_value')
            ->get();

        return view(
            'pages.seller.variations',
            compact('user', 'product', 'variations')
        );
    }

    public function storeVariation($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

        if (!$product) {
            abort(404);
        }

        request()->validate([
            'variation_type' => 'required|string|max:50',
            'variation_value' => 'required|string|max:50',
            // The final price (base + adjustment) must stay above ₱0.
            'price_adjustment' => 'nullable|numeric|gt:' . (0 - (float) $product->price),
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:4096',
        ]);

        // The same option twice ("Color: Red" + "Color: Red") would show up
        // twice in the buyer's picker with separate stock.
        $duplicate = ProductVariation::where('product_id', $product->id)
            ->where('variation_type', ProductVariation::normalizeType((string) request('variation_type')))
            ->where('variation_value', ProductVariation::normalizeValue((string) request('variation_value')))
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->with('error', 'This product already has that option. Edit its stock instead of adding it again.');
        }

        $option = ProductVariation::create([
            'product_id' => $product->id,
            'variation_type' => request('variation_type'),
            'variation_value' => request('variation_value'),
            'price_adjustment' => request('price_adjustment', 0),
            'stock' => request('stock'),
        ]);

        // Its photo becomes this option's first photo.
        if (request()->hasFile('image')) {
            app(ProductService::class)->storeFiles($product, $option->id, [request()->file('image')]);
            ProductPhotos::sync($product);
        }

        return back()->with('success', 'Variation added successfully.');
    }

    /**
     * Restock / reprice existing options in one go (and swap a photo):
     * variations[{id}][stock], variations[{id}][price_adjustment], images[{id}].
     */
    public function updateVariations($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

        if (!$product) {
            abort(404);
        }

        request()->validate([
            'variations' => 'required|array',
            'variations.*.stock' => 'required|integer|min:0',
            // The final price (base + adjustment) must stay above ₱0.
            'variations.*.price_adjustment' => 'required|numeric|gt:' . (0 - (float) $product->price),
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:4096',
        ], [
            'variations.*.stock.min' => 'Stock can’t be below 0.',
            'variations.*.price_adjustment.gt' => 'An option can’t end up costing ₱0 or less.',
        ]);

        $changed = 0;

        // Only this product's options; ids from anywhere else are ignored.
        $variations = ProductVariation::where('product_id', $product->id)
            ->whereIn('id', array_keys(request('variations')))
            ->get();

        foreach ($variations as $variation) {
            $input = request('variations')[$variation->id];
            $stock = (int) $input['stock'];
            $price = round((float) $input['price_adjustment'], 2);
            $photo = request()->file("images.{$variation->id}");

            // Compare the numbers themselves: the decimal cast makes "0.00" vs 0 look changed.
            if ($stock === (int) $variation->stock && $price === round((float) $variation->price_adjustment, 2) && !$photo) {
                continue;
            }

            $variation->stock = $stock;
            $variation->price_adjustment = $price;

            $variation->save();

            // A new photo goes first among this option's photos (so it is the chip photo).
            if ($photo) {
                app(ProductService::class)->storeFiles($product, $variation->id, [$photo], 'first');
            }

            $changed++;
        }

        ProductPhotos::sync($product);

        return back()->with('success', $changed
            ? 'Saved changes to ' . $changed . ' ' . Str::plural('option', $changed) . '.'
            : 'Nothing changed.');
    }

    public function deleteVariation($id, $variationId)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

        if (!$product) {
            abort(404);
        }

        $variation = ProductVariation::where('id', $variationId)
            ->where('product_id', $product->id)
            ->first();

        if ($variation) {
            // Its own photos go with it.
            $paths = $variation->images()->pluck('path')->push($variation->image)->filter()->all();
            $variation->images()->delete();
            $variation->delete();

            ProductPhotos::sync($product);
            ProductPhotos::deleteUnused($paths);
        }

        return back()->with('success', 'Variation removed.');
    }

    public function showCreateProduct()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $registeredCategory = $this->registeredCategory((int) $user['id']);

        return view('pages.seller.add-product', compact('user', 'registeredCategory'));
    }

    public function redirectLegacyAddProduct()
    {
        return redirect()->route('seller.products.create');
    }

    /**
     * The one category this seller registered for (slug). Sellers may only
     * list products there. Null for older accounts that never declared one —
     * those keep the full category list.
     */
    private function registeredCategory(int $sellerId): ?string
    {
        return app(ProductService::class)->registeredCategory($sellerId);
    }

    /** The Add Product variation rows, each with its photo (files arrive separately from the text fields). */
    private function variationsWithPhotos(): array
    {
        $variations = (array) request('variations', []);

        foreach ((array) request()->file('variations', []) as $i => $files) {
            if (!isset($variations[$i])) {
                continue;
            }
            if ($files['image'] ?? null) {
                $variations[$i]['image'] = $files['image'];
            }
            $variations[$i]['photos'] = (array) ($files['photos'] ?? []);
        }

        return $variations;
    }

    public function storeProduct(ProductService $products)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        try {
            $product = $products->create(
                (int) $user['id'],
                request()->only('name', 'category', 'price', 'stock', 'description'),
                request()->hasFile('image') ? request()->file('image') : null,
                $this->variationsWithPhotos(),
                (array) request()->file('photos', []),
                (string) request('cover_ref', '')
            );
        } catch (ActionFailed $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('seller.dashboard')
            ->with('success', $product->name . ' has been added to your store!');
    }

    // Tabs on the seller's Orders page => the order statuses each one shows.
    public const ORDER_TABS = [
        'all' => ['label' => 'All', 'statuses' => null],
        'to-process' => ['label' => 'To Process', 'statuses' => ['Pending']],
        'to-ship' => ['label' => 'To Drop Off', 'statuses' => ['Processing']],
        'shipped' => ['label' => 'Shipped', 'statuses' => ['Dropped Off', 'At Sorting Center', 'In Transit', 'Assigned for Delivery', 'Out for Delivery', 'Delivery Failed']],
        'completed' => ['label' => 'Completed', 'statuses' => ['Delivered']],
        'cancelled' => ['label' => 'Cancelled / Returned', 'statuses' => ['Cancelled', 'Returned to Seller']],
        'returns' => ['label' => 'Return Requests', 'statuses' => null],
    ];
    public function orders()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];

        $tab = array_key_exists((string) request('tab'), self::ORDER_TABS) ? request('tab') : 'all';
        $search = trim((string) request('q', ''));

        // Orders that contain at least one of this seller's items.
        $mine = function () use ($sellerId) {
            return DB::table('orders')->whereExists(function ($q) use ($sellerId) {
                $q->select(DB::raw(1))
                    ->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.seller_id', $sellerId);
            });
        };

        // Order #, buyer name/phone, or one of the product names.
        $searched = function () use ($mine, $search, $sellerId) {
            return $mine()->when($search !== '', function ($query) use ($search, $sellerId) {
                $number = ltrim($search, '#');
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($number, $like, $sellerId) {
                    if (ctype_digit($number)) {
                        $q->orWhere('orders.id', (int) $number);
                    }

                    $q->orWhere('orders.shipping_name', 'like', $like)
                        ->orWhere('orders.shipping_phone', 'like', $like)
                        ->orWhereExists(function ($items) use ($like, $sellerId) {
                            $items->select(DB::raw(1))
                                ->from('order_items')
                                ->whereColumn('order_items.order_id', 'orders.id')
                                ->where('order_items.seller_id', $sellerId)
                                ->where('order_items.product_name', 'like', $like);
                        });
                });
            });
        };

        $statusCounts = $searched()
            ->select('orders.status', DB::raw('COUNT(*) as total'))
            ->groupBy('orders.status')
            ->pluck('total', 'status');

        $pendingReturns = DB::table('return_refund_requests')
            ->where('seller_id', $sellerId)
            ->where('status', 'pending')
            ->count();

        $tabCounts = [];

        foreach (self::ORDER_TABS as $key => $definition) {
            $tabCounts[$key] = match (true) {
                $key === 'returns' => $pendingReturns,
                $definition['statuses'] === null => (int) $statusCounts->sum(),
                default => (int) collect($definition['statuses'])->sum(fn ($s) => $statusCounts[$s] ?? 0),
            };
        }

        // Summary cards: the seller's whole shop, not just this search/tab.
        $allStatuses = $mine()
            ->select('orders.status', DB::raw('COUNT(*) as total'))
            ->groupBy('orders.status')
            ->pluck('total', 'status');

        $deliveredSales = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $sellerId)
            ->where('orders.status', 'Delivered')
            ->sum(DB::raw('order_items.price * order_items.quantity'));

        $summary = [
            'total' => (int) $allStatuses->sum(),
            'to_process' => (int) (($allStatuses['Pending'] ?? 0) + ($allStatuses['Processing'] ?? 0)),
            'sales' => max(0, $deliveredSales - $this->voucherDiscounts($sellerId)),
        ];

        $orders = null;
        $returnRequests = collect();

        if ($tab === 'returns') {

            $returnRequests = DB::table('return_refund_requests')
                ->join('orders', 'orders.id', '=', 'return_refund_requests.order_id')
                ->join('order_items', 'order_items.id', '=', 'return_refund_requests.order_item_id')
                ->where('return_refund_requests.seller_id', $sellerId)
                ->when($search !== '', function ($query) use ($search) {
                    $number = ltrim($search, '#');
                    $like = '%' . $search . '%';

                    $query->where(function ($q) use ($number, $like) {
                        if (ctype_digit($number)) {
                            $q->orWhere('return_refund_requests.order_id', (int) $number);
                        }

                        $q->orWhere('orders.shipping_name', 'like', $like)
                            ->orWhere('order_items.product_name', 'like', $like);
                    });
                })
                ->select(
                    'return_refund_requests.*',
                    'orders.shipping_name',
                    'orders.shipping_phone',
                    'order_items.product_name'
                )
                // Requests still waiting on the seller first.
                ->orderByRaw("CASE WHEN return_refund_requests.status = 'pending' THEN 0 ELSE 1 END")
                ->orderByDesc('return_refund_requests.created_at')
                ->get();

        } else {

            $orders = $searched()
                ->when(self::ORDER_TABS[$tab]['statuses'], fn ($q, $statuses) => $q->whereIn('orders.status', $statuses))
                ->select(
                    'orders.id',
                    'orders.buyer_id',
                    'orders.total_amount',
                    'orders.status',
                    'orders.buyer_received_at',
                    'orders.restocked_at',
                    'orders.shipping_name',
                    'orders.shipping_phone',
                    'orders.shipping_address',
                    'orders.payment_method',
                    'orders.created_at'
                )
                ->orderByDesc('orders.created_at')
                ->orderByDesc('orders.id')
                ->paginate(15)
                ->withQueryString();

            // This seller's lines for the whole page in one query.
            $itemsByOrder = DB::table('order_items')
                ->whereIn('order_id', $orders->pluck('id'))
                ->where('seller_id', $sellerId)
                ->get()
                ->groupBy('order_id');

            $orders->getCollection()->each(function ($order) use ($itemsByOrder) {
                $order->items = $itemsByOrder->get($order->id, collect());
                $order->seller_total = $order->items->sum(fn ($item) => $item->price * $item->quantity);
            });
        }

        return view('pages.seller.orders', [
            'user' => $user,
            'orders' => $orders,
            'returnRequests' => $returnRequests,
            'tabs' => self::ORDER_TABS,
            'tab' => $tab,
            'tabCounts' => $tabCounts,
            'search' => $search,
            'summary' => $summary,
        ]);
    }

    public function orderDetails($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        // Kunin ang order
        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            abort(404);
        }

        // Kunin lang ang products na pagmamay-ari ng logged-in seller
        $sellerItems = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('seller_id', $user['id'])
            ->get();

        // Kung walang product ng seller sa order, bawal makita
        if ($sellerItems->isEmpty()) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | CONVERT ORDER TO ARRAY
        |--------------------------------------------------------------------------
        */

        $order = (array) $order;

        /*
        |--------------------------------------------------------------------------
        | COMPATIBILITY FIELDS FOR SELLER ORDER DETAILS BLADE
        |--------------------------------------------------------------------------
        */

        $order['buyer_name'] =
            $order['shipping_name'] ?? 'Buyer';

        $order['date'] =
            $order['created_at'] ?? 'N/A';

        $order['phone'] =
            $order['shipping_phone'] ?? 'N/A';

        $order['payment'] =
            $order['payment_method'] ?? 'N/A';

        $order['address'] =
            $order['shipping_address'] ?? 'N/A';

        /*
        |--------------------------------------------------------------------------
        | ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $order['items'] = $sellerItems->map(function ($item) {

            $item = (array) $item;

            // Existing Blade expects "name"
            $item['name'] =
                $item['product_name'] ?? 'Product';

            // Compute subtotal
            $item['subtotal'] =
                (float) ($item['price'] ?? 0) *
                (int) ($item['quantity'] ?? 0);

            return $item;

        })->toArray();

        /*
        |--------------------------------------------------------------------------
        | SELLER TOTAL
        |--------------------------------------------------------------------------
        */

        $order['seller_total'] = array_sum(
            array_column($order['items'], 'subtotal')
        );

        /*
        |--------------------------------------------------------------------------
        | ASSIGNED COURIER (for shipment tracking)
        |--------------------------------------------------------------------------
        */

        $assignedRider = !empty($order['rider_id'])
            ? DB::table('users')->where('id', $order['rider_id'])->first()
            : null;

        // Lets the seller spot buyers who often cancel or refuse COD parcels.
        $buyerHistory = CodPolicy::buyerHistory((int) $order['buyer_id']);

        return view(
            'pages.seller.order-details',
            compact('user', 'order', 'assignedRider', 'buyerHistory')
        );
    }

    public function orderWaybill($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            abort(404);
        }

        $sellerItems = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('seller_id', $user['id'])
            ->get();

        if ($sellerItems->isEmpty()) {
            abort(404);
        }

        $order = (array) $order;

        $order['buyer_name'] = $order['shipping_name'] ?? 'Buyer';
        $order['phone'] = $order['shipping_phone'] ?? 'N/A';
        $order['address'] = $order['shipping_address'] ?? 'N/A';

        $order['items'] = $sellerItems->map(function ($item) {

            $item = (array) $item;
            $item['name'] = $item['product_name'] ?? 'Product';
            $item['subtotal'] = (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);

            return $item;

        })->toArray();

        $order['seller_total'] = array_sum(array_column($order['items'], 'subtotal'));

        return view(
            'pages.seller.waybill',
            compact('user', 'order')
        );
    }

    public function updateOrderStatus($id, SellerOrderService $orders)
    {
        return $this->sellerOrderAction(
            fn (array $user) => $orders->updateStatus((int) $user['id'], (int) $id, (string) request('status'), (string) request('cancellation_reason'))
        );
    }

    /** A printable shipping label for the seller's parcel: waybill no., QR, from/to and route. */
    public function shippingLabel($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')->where('id', $id)->first();

        $items = $order
            ? DB::table('order_items')->where('order_id', $order->id)->where('seller_id', $user['id'])->get()
            : collect();

        if ($items->isEmpty()) {
            abort(404);
        }

        $seller = DB::table('users')->where('id', $user['id'])->first();
        $shopName = DB::table('seller_applications')->where('user_id', $user['id'])->value('business_name') ?: $seller->name;

        $sellerTown = \App\Support\ParcelRoute::sellerLocation((int) $user['id']);
        $origin = $order->origin_center_id
            ? \App\Models\SortingCenter::find($order->origin_center_id)
            : \App\Support\ParcelRoute::centerFor($sellerTown['province'], $sellerTown['city']);

        $buyerTown = $order->shipping_province
            ? ['province' => $order->shipping_province, 'city' => $order->shipping_city]
            : \App\Support\PhLocations::locate($order->shipping_address);
        $destination = $order->destination_center_id
            ? \App\Models\SortingCenter::find($order->destination_center_id)
            : ($buyerTown ? \App\Support\ParcelRoute::centerFor($buyerTown['province'], $buyerTown['city']) : null);

        $waybill = \App\Support\Waybill::number((int) $order->id);
        $scanUrl = route('logistics.scan', $waybill);
        $isCod = \App\Support\CodPolicy::isCod((string) $order->payment_method);

        return view('pages.seller.shipping-label', compact(
            'order', 'items', 'seller', 'shopName', 'origin', 'destination', 'waybill', 'scanUrl', 'isCod'
        ));
    }


    public function editProduct($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$product) {
            abort(404);
        }

        $registeredCategory = $this->registeredCategory((int) $user['id']);

        // With options, stock is kept per option (on the Variations page).
        $variationCount = $product->variations()->count();
        $variationStock = (int) $product->variations()->sum('stock');

        return view(
            'pages.seller.edit-product',
            compact('user', 'product', 'registeredCategory', 'variationCount', 'variationStock')
        );
    }

    public function updateProduct($id, ProductService $products)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        // Only the seller's own products.
        if ((string) $product->seller_id !== (string) ($user['id'] ?? '')) {
            abort(403);
        }

        try {
            $products->update(
                $product,
                (int) $user['id'],
                request()->only('name', 'category', 'price', 'stock', 'description'),
                request()->hasFile('image') ? request()->file('image') : null,
                [
                    'add' => (array) request()->file('photos', []),
                    'option_add' => (array) request()->file('option_photos', []),
                    'remove' => (array) request('remove_photos', []),
                    'first' => (int) request('first_photo', 0),
                    'cover' => (int) request('cover_photo', 0),
                ]
            );
        } catch (ActionFailed $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Product updated successfully!');
    }
    public function deleteProduct($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$product) {
            abort(404);
        }

        // order_items.product_id cascades on delete — if any order ever
        // included this product, deleting it would silently wipe those
        // buyers' order lines (and any reviews) out from under real orders.
        // Archive is the safe equivalent: it hides the product without
        // touching order history.
        $hasOrderHistory = DB::table('order_items')
            ->where('product_id', $product->id)
            ->exists();

        if ($hasOrderHistory) {

            return back()->with(
                'error',
                'This product has order history and cannot be deleted. Archive it instead to hide it from the shop.'
            );
        }

        $productName = $product->name;

        // Don't leave the product's (and its variations') images behind.
        $images = $product->variations()->pluck('image')
            ->merge($product->images()->pluck('path'))
            ->push($product->image)->filter()->all();

        $product->delete();

        ProductPhotos::deleteUnused($images);

        return redirect()
            ->route('seller.dashboard')
            ->with(
                'success',
                $productName . ' deleted successfully!'
            );
    }

    public function archiveProduct($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$product) {
            abort(404);
        }

        $product->update(['is_archived' => true]);

        return back()->with(
            'success',
            $product->name . ' has been archived and removed from the storefront.'
        );
    }

    public function unarchiveProduct($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$product) {
            abort(404);
        }

        $product->update(['is_archived' => false]);

        return back()->with(
            'success',
            $product->name . ' has been restored to the storefront.'
        );
    }

    public function approveReturnRefund($id, ReturnRefundService $returns)
    {
        return $this->sellerOrderAction(fn (array $user) => $returns->approve((int) $user['id'], (int) $id));
    }

    public function rejectReturnRefund($id, ReturnRefundService $returns)
    {
        return $this->sellerOrderAction(fn (array $user) => $returns->reject((int) $user['id'], (int) $id, (string) request('seller_note')));
    }

    public function markReturnRefundReturned($id, ReturnRefundService $returns)
    {
        return $this->sellerOrderAction(fn (array $user) => $returns->markReturned((int) $user['id'], (int) $id, request()->boolean('restock', true)));
    }

    public function startReturnRefundProcessing($id, ReturnRefundService $returns)
    {
        return $this->sellerOrderAction(fn (array $user) => $returns->startRefund((int) $user['id'], (int) $id));
    }

    public function completeReturnRefund($id, ReturnRefundService $returns)
    {
        return $this->sellerOrderAction(fn (array $user) => $returns->completeRefund((int) $user['id'], (int) $id));
    }

    public function showRegister()
    {
        return view('pages.seller.register');
    }

    // Seller Registration Submit
    public function register(Request $request)
    {
        $lastName = trim($request->input('last_name'));
        $firstName = trim($request->input('first_name'));
        $middleInitial = trim($request->input('middle_initial'));
        $sex = $request->input('sex');
        $birthdate = $request->input('birthdate');
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');
        $passwordConfirmation = $request->input('password_confirmation');
        $phone = trim($request->input('phone'));
        $province = trim($request->input('province'));
        $cityMunicipality = trim($request->input('city_municipality'));
        $barangay = trim($request->input('barangay'));
        $streetAddress = trim($request->input('street_address'));
        $businessName = trim($request->input('business_name'));
        $businessCategory = $request->input('business_category');

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
            empty($streetAddress) ||
            empty($businessName) ||
            empty($businessCategory)
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

        // SELLER VERIFICATION DOCUMENTS
        $request->validate([
            'national_id' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],

            'business_permit' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ], [], [
            'national_id' => 'valid government ID',
            'business_permit' => 'proof of business',
        ]);

        // Store the documents now (private disk) — the account itself is only
        // created once the OTP is verified, so we keep the file paths in the
        // pending_registration session data alongside the rest of the form.
        $folder = 'seller-applications/' . (string) Str::uuid();

        $nationalIdPath = $request
            ->file('national_id')
            ->store($folder, 'local');

        $businessPermitPath = $request
            ->file('business_permit')
            ->store($folder, 'local');

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
            'role' => 'seller',
            'national_id' => $nationalIdPath,
            'business_permit' => $businessPermitPath,
            'business_name' => $businessName,
            'business_category' => $businessCategory,
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

    public function restockOrder($id, SellerOrderService $orders)
    {
        return $this->sellerOrderAction(fn (array $user) => $orders->restock((int) $user['id'], (int) $id));
    }

    /** Runs a seller order action and flashes its message (or the rule it broke). */
    private function sellerOrderAction(callable $action)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        try {
            return back()->with('success', $action($user));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function notifications()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $notifications = Notification::where(
            'user_id',
            $user['id']
        )
        ->orderByDesc('created_at')
        ->paginate(20);

        $unreadCount = Notification::where('user_id', $user['id'])->whereNull('read_at')->count();

        return view(
            'pages.seller.notifications',
            compact('user', 'notifications', 'unreadCount')
        );
    }

    /**
     * Total discount given by this seller's own vouchers on delivered orders
     * (optionally within an order-date range).
     */
    private function voucherDiscounts(int $sellerId, ?string $from = null, ?string $to = null): float
    {
        $codes = Voucher::where('seller_id', $sellerId)->pluck('code');

        if ($codes->isEmpty()) {
            return 0.0;
        }

        return (float) DB::table('orders')
            ->whereIn('voucher_code', $codes)
            ->where('status', 'Delivered')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('discount_amount');
    }

    public function markNotificationRead($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        Notification::where('id', $id)
            ->where('user_id', $user['id'])
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }
}
