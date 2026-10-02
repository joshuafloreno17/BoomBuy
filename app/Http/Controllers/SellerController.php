<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Categories;
use App\Support\CodPolicy;
use App\Support\OrderStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            ->withCount('reviews')
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
                'salesTrend'
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
            compact('user', 'dbUser', 'application', 'totalProducts', 'totalSales')
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
    public function updateShop(Request $request)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $data = $request->validate([
            'business_name' => 'required|string|min:3|max:60',
            'shop_description' => 'nullable|string|max:500',
        ], [
            'business_name.required' => 'Please enter your shop name.',
            'business_name.min' => 'Your shop name needs at least 3 characters.',
        ]);

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

    public function updatePhoto(Request $request)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('profile_photo');

        // Extension from the real content, not the client filename (see BuyerController::updatePhoto).
        $filename = 'seller_' . $user['id'] . '_' . time() . '.' . $file->extension();

        $file->storeAs('profile-photos', $filename, 'public');

        DB::table('users')
            ->where('id', $user['id'])
            ->update([
                'profile_photo' => $filename,
                'updated_at' => now(),
            ]);

        $user['profile_photo'] = $filename;
        session()->put('user', $user);

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
                'dailySales'
            )
        );
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

    public function storeVoucher()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        request()->validate([
            'code' => 'required|string|max:30|unique:vouchers,code',
            'discount_type' => 'required|in:percentage,fixed',
            // A percentage over 100 would discount more than the seller's
            // items are worth and eat into the rest of the buyer's order.
            'discount_value' => 'required|numeric|min:0.01' . (request('discount_type') === 'percentage' ? '|max:100' : ''),
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

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

        $imagePath = null;

        if (request()->hasFile('image')) {
            $imagePath = request()->file('image')->store('variations', 'public');
        }

        ProductVariation::create([
            'product_id' => $product->id,
            'variation_type' => request('variation_type'),
            'variation_value' => request('variation_value'),
            'price_adjustment' => request('price_adjustment', 0),
            'stock' => request('stock'),
            'image' => $imagePath,
        ]);

        return back()->with('success', 'Variation added successfully.');
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

            if ($variation->image) {
                Storage::disk('public')->delete($variation->image);
            }

            $variation->delete();
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
        $declared = DB::table('seller_applications')
            ->where('user_id', $sellerId)
            ->orderByDesc('id')
            ->value('business_category');

        return Categories::slug($declared);
    }

    public function storeProduct()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $name = trim(request('name'));
        $category = trim(request('category'));

        // Sellers may only sell in the category they registered for.
        $registeredCategory = $this->registeredCategory((int) $user['id']);

        if ($registeredCategory) {
            if ($category !== '' && Categories::slug($category) !== $registeredCategory) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'You can only sell ' . Categories::LIST[$registeredCategory] . ' products — the category your shop is registered for.'
                    );
            }

            $category = $registeredCategory;
        }
        $price = (float) request('price');
        $stock = (int) request('stock');
        $description = trim(request('description'));

        // Validate basic fields
        // FIX: $stock is now actually defined above instead of being
        // referenced without ever being read from the request.
        if (
            empty($name) ||
            empty($category) ||
            $price <= 0 ||
            $stock <= 0 ||
            empty($description)
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please complete all product fields.'
                );
        }

        // Validate category
        $categoryName = isset(Categories::LIST[strtolower($category)])
            ? Categories::LIST[strtolower($category)]
            : null;

        if ($categoryName === null) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please select a valid product category.'
                );
        }

        // Prevent duplicate product names within this seller's own store
        $existingProduct = Product::where('name', $name)
            ->where('seller_id', $user['id'])
            ->first();

        if ($existingProduct) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'You already have a product with this name.'
                );
        }

        // Validate image
        if (!request()->hasFile('image')) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please upload a product image.'
                );
        }

        $image = request()->file('image');

        if (!$image->isValid()) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'The uploaded image is invalid.'
                );
        }

        // Checks the file's real content, not its name — store() names the
        // file from the content too, so a disguised script can't slip through.
        if (!isValidProductImage($image)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Product image must be a JPG, JPEG, PNG, or WEBP image no larger than 5MB.'
                );
        }

        // Inline variations: an "extra price" can be negative (a cheaper
        // option) but must never bring the final price to ₱0 or below.
        foreach (request('variations', []) as $variation) {

            if (trim($variation['type'] ?? '') === '' || trim($variation['value'] ?? '') === '') {
                continue;
            }

            if ($price + (float) ($variation['price_adjustment'] ?? 0) <= 0) {
                return back()
                    ->withInput()
                    ->with('error', 'The option "' . trim($variation['value']) . '" would make the price ₱0 or less. Please lower its discount.');
            }
        }

        // Save uploaded image
        $imagePath = $image->store(
            'products',
            'public'
        );

        // Create product
        $product = Product::create([
            'seller_id' => $user['id'],
            'name' => $name,
            'category' => $categoryName,
            'price' => $price,
            'stock' => $stock,
            'description' => $description,
            'image' => $imagePath,
        ]);

        // Optional variations submitted inline on this form
        $submittedVariations = request('variations', []);
        $seenOptions = [];

        foreach ($submittedVariations as $variation) {

            $type = trim($variation['type'] ?? '');
            $value = trim($variation['value'] ?? '');

            if (empty($type) || empty($value)) {
                continue;
            }

            // The same option typed twice on the form is only saved once.
            $optionKey = strtolower(ProductVariation::normalizeType($type) . '|' . ProductVariation::normalizeValue($value));

            if (isset($seenOptions[$optionKey])) {
                continue;
            }

            $seenOptions[$optionKey] = true;

            ProductVariation::create([
                'product_id' => $product->id,
                'variation_type' => $type,
                'variation_value' => $value,
                'price_adjustment' => (float) ($variation['price_adjustment'] ?? 0),
                'stock' => max(0, (int) ($variation['stock'] ?? 0)),
            ]);
        }

        return redirect()
            ->route('seller.dashboard')
            ->with(
                'success',
                $name . ' has been added to your store!'
            );
    }

    // Tabs on the seller's Orders page => the order statuses each one shows.
    public const ORDER_TABS = [
        'all' => ['label' => 'All', 'statuses' => null],
        'to-process' => ['label' => 'To Process', 'statuses' => ['Pending', 'Processing']],
        'to-ship' => ['label' => 'To Ship', 'statuses' => ['Ready for Pickup', 'Assigned']],
        'shipped' => ['label' => 'Shipped', 'statuses' => ['Picked Up', 'At Sorting Center', 'Assigned for Delivery', 'Out for Delivery', 'Delivery Failed']],
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

    public function updateOrderStatus($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $status = trim(request('status'));

        // Seller can only move orders through these statuses
        $allowedStatuses = [
            'Processing',
            'Ready for Pickup',
            'Cancelled',
        ];

        if (!in_array($status, $allowedStatuses)) {

            return back()->with(
                'error',
                'Invalid seller order status.'
            );
        }

        // Check if the order exists
        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {

            return back()->with(
                'error',
                'Order not found.'
            );
        }

        // Check if this seller has an item in this order
        $hasSellerProduct = DB::table('order_items')
            ->where('order_id', $id)
            ->where('seller_id', $user['id'])
            ->exists();

        if (!$hasSellerProduct) {

            return back()->with(
                'error',
                'This order does not belong to you.'
            );
        }

        // ==============================
        // ENFORCE SELLER STATUS FLOW
        // ==============================

        // The seller only owns an order while it's still with them. Anything
        // past that (rider, Sorting Center, delivered, cancelled, returned)
        // belongs to its own flow and must not be cancelled or rewound here.
        if (!in_array($order->status, ['Pending', 'Processing'], true)) {

            return back()->with(
                'error',
                $order->status === 'Ready for Pickup'
                    ? 'This order is already Ready for Pickup and can no longer be updated by the seller.'
                    : 'This order is already ' . $order->status . ' and can no longer be updated by the seller.'
            );
        }

        // Pending → Processing or Cancelled
        if (
            $order->status === 'Pending' &&
            !in_array($status, ['Processing', 'Cancelled'])
        ) {

            return back()->with(
                'error',
                'Pending orders must be moved to Processing first.'
            );
        }

        // Processing → Ready for Pickup or Cancelled
        if (
            $order->status === 'Processing' &&
            !in_array($status, ['Ready for Pickup', 'Cancelled'])
        ) {

            return back()->with(
                'error',
                'Processing orders must be moved to Ready for Pickup.'
            );
        }

        if ($status === 'Cancelled') {

            $reason = trim((string) request('cancellation_reason'));

            if (empty($reason)) {
                return back()->with('error', 'Please provide a reason for cancelling this order.');
            }
        }

        // ==============================
        // UPDATE ORDER STATUS
        // ==============================

        // Only move it from the status we just validated against, so two
        // overlapping requests can't both act on the same order.
        $updated = DB::table('orders')
            ->where('id', $id)
            ->where('status', $order->status)
            ->update([
                'status' => $status,
                'cancellation_reason' => $status === 'Cancelled' ? $reason : null,
                'cancelled_by' => $status === 'Cancelled' ? 'seller' : null,
                'cancelled_at' => $status === 'Cancelled' ? now() : null,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'This order was just updated. Please refresh and try again.');
        }

        if ($status === 'Cancelled') {
            OrderStock::cancelled((int) $id, $order->status);
        }

        // ==============================
        // BUYER NOTIFICATION
        // ==============================

        if ($status === 'Processing') {

            createNotification(
                (int) $order->buyer_id,
                'Order Processing',
                'Your order #' . $id .
                ' is now being processed by the seller.',
                'order',
                (int) $id
            );

        } elseif ($status === 'Ready for Pickup') {

            createNotification(
                (int) $order->buyer_id,
                'Order Ready for Pickup',
                'Your order #' . $id .
                ' has been packed and is ready for a rider to pick up.',
                'order',
                (int) $id
            );

            notifyAllActiveRiders(
                'New Delivery Available',
                'Order #' . $id . ' is ready for pickup and available to claim.',
                'delivery',
                (int) $id
            );

        } elseif ($status === 'Cancelled') {

            createNotification(
                (int) $order->buyer_id,
                'Order Cancelled by Seller',
                'Your order #' . $id . ' was cancelled by the seller. Reason: ' . $reason,
                'order',
                (int) $id
            );
        }

        // ==============================
        // SELLER NOTIFICATION
        // ==============================
        //
        // Notify the seller that the order status
        // has been updated successfully.
        //

        createNotification(
            (int) $user['id'],
            'Order Status Updated',
            'Order #' . $id .
            ' is now ' . $status . '.',
            'order_status',
            (int) $id
        );

        return back()->with(
            'success',
            'Order status updated to ' . $status . '.'
        );
    }

    public function confirmPickup($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        $hasSellerProduct = DB::table('order_items')
            ->where('order_id', $id)
            ->where('seller_id', $user['id'])
            ->exists();

        if (!$hasSellerProduct) {
            return back()->with('error', 'This order does not belong to you.');
        }

        if (empty($order->rider_id)) {
            return back()->with('error', 'No rider has picked up this order yet.');
        }

        // Only while the hand-over is happening, and only once — the time
        // recorded is when the parcel actually left the seller.
        if (!in_array($order->status, ['Assigned', 'Picked Up'], true)) {
            return back()->with('error', 'This order is not waiting for a rider pickup.');
        }

        if (!empty($order->seller_confirmed_pickup_at)) {
            return back()->with('error', 'You already confirmed this pickup.');
        }

        DB::table('orders')
            ->where('id', $id)
            ->whereNull('seller_confirmed_pickup_at')
            ->update(['seller_confirmed_pickup_at' => now()]);

        return back()->with('success', 'Rider pickup confirmed.');
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

        return view(
            'pages.seller.edit-product',
            compact('user', 'product', 'registeredCategory')
        );
    }

    public function updateProduct($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        // Make sure this product belongs to the logged-in seller
        if ((string) $product->seller_id !== (string) ($user['id'] ?? '')) {
            abort(403);
        }

        $name = trim(request('name'));
        $category = trim(request('category'));

        // Sellers may only sell in the category they registered for.
        $registeredCategory = $this->registeredCategory((int) $user['id']);

        if ($registeredCategory) {
            if ($category !== '' && Categories::slug($category) !== $registeredCategory) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'You can only sell ' . Categories::LIST[$registeredCategory] . ' products — the category your shop is registered for.'
                    );
            }

            $category = $registeredCategory;
        }
        $price = (float) request('price');
        $stock = (int) request('stock');
        $description = trim(request('description'));

        /*
        |--------------------------------------------------------------------------
        | Validate fields
        |--------------------------------------------------------------------------
        */

        if (
            empty($name) ||
            empty($category) ||
            $price <= 0 ||
            $stock < 0 ||
            empty($description)
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please complete all product fields.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | BoomBuy Category System
        |--------------------------------------------------------------------------
        */

        // Old categories (Smartphone, Laptop…) are converted automatically
        // when an existing product is edited.
        $categoryName = Categories::label($category);

        if ($categoryName === null) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please select a valid BoomBuy category.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate product names
        |--------------------------------------------------------------------------
        */

        $existingProduct = Product::where('name', $name)
            ->where('seller_id', $user['id'])
            ->where('id', '!=', $product->id)
            ->first();

        if ($existingProduct) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'You already have a product with this name.'
                );
        }

        // A cheaper option (negative extra price) must still cost more than ₱0
        // at the new base price.
        $lowestAdjustment = (float) $product->variations()->min('price_adjustment');

        if ($product->variations()->exists() && $price + $lowestAdjustment <= 0) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'At this price one of your options would cost ₱0 or less. Raise the price or change that option\'s extra price first.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Keep existing image
        |--------------------------------------------------------------------------
        */

        $imagePath = $product->image;
        $oldImage = $product->image;

        /*
        |--------------------------------------------------------------------------
        | New image uploaded?
        |--------------------------------------------------------------------------
        */

        if (request()->hasFile('image')) {

            $image = request()->file('image');

            if (!$image->isValid()) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'The uploaded image is invalid.'
                    );
            }

            if (!isValidProductImage($image)) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Product image must be a JPG, JPEG, PNG, or WEBP image no larger than 5MB.'
                    );
            }

            $imagePath = $image->store(
                'products',
                'public'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update product
        |--------------------------------------------------------------------------
        */

        $product->update([

            'name' => $name,
            'category' => $categoryName,
            'price' => $price,
            'image' => $imagePath,
            'stock' => $stock,
            'description' => $description,

        ]);

        // The replaced photo is no longer used anywhere.
        if ($oldImage && $oldImage !== $imagePath && !str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('seller.dashboard')
            ->with(
                'success',
                'Product updated successfully!'
            );
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
        $images = $product->variations()->pluck('image')->push($product->image)->filter()->all();

        $product->delete();

        Storage::disk('public')->delete($images);

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

    public function approveReturnRefund($id)
    {
        $user = requireUserRole('seller');
        if (!is_array($user)) return $user;

        $requestData = DB::table('return_refund_requests')
            ->join('order_items', 'return_refund_requests.order_item_id', '=', 'order_items.id')
            ->where('return_refund_requests.id', $id)
            ->where('return_refund_requests.seller_id', $user['id'])
            ->select(
                'return_refund_requests.*',
                'order_items.product_name',
                'order_items.quantity'
            )
            ->first();

        if (!$requestData) {
            return back()->with('error', 'Return/Refund request not found.');
        }

        if ($requestData->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        // Conditional on the status just checked — a double-click can't
        // re-run this step or notify the buyer twice.
        $moved = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'seller_note' => 'Request approved by seller.',
                'updated_at' => now(),
            ]);

        if (!$moved) {
            return back()->with('error', 'This request was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $requestData->buyer_id,
            ucfirst($requestData->request_type) . ' Request Approved',
            'Your ' . strtolower($requestData->request_type) . ' request for ' . $requestData->product_name . ' has been approved.',
            'return_refund',
            (int) $id
        );

        return back()->with(
            'success',
            'Return/Refund request for ' . $requestData->product_name . ' has been approved.'
        );
    }

    public function rejectReturnRefund($id)
    {
        $user = requireUserRole('seller');
        if (!is_array($user)) return $user;

        $requestData = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$requestData) {
            return back()->with('error', 'Return/Refund request not found.');
        }

        if ($requestData->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $sellerNote = trim((string) request('seller_note'));

        // Conditional on the status just checked — a double-click can't
        // re-run this step or notify the buyer twice.
        $moved = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'seller_note' => $sellerNote !== '' ? $sellerNote : 'Request rejected by seller.',
                'updated_at' => now(),
            ]);

        if (!$moved) {
            return back()->with('error', 'This request was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $requestData->buyer_id,
            ucfirst($requestData->request_type) . ' Request Rejected',
            'Your ' . strtolower($requestData->request_type) . ' request was rejected by the seller.' .
            ($sellerNote !== '' ? ' Reason: ' . $sellerNote : ''),
            'return_refund',
            (int) $id
        );

        return back()->with(
            'success',
            'Return/Refund request has been rejected.'
        );
    }

    public function markReturnRefundReturned($id)
    {
        $user = requireUserRole('seller');
        if (!is_array($user)) return $user;

        $requestData = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$requestData) {
            return back()->with('error', 'Return/Refund request not found.');
        }

        if ($requestData->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be marked as returned.');
        }

        if ($requestData->request_type !== 'Return') {
            return back()->with('error', 'This request is not a return request.');
        }

        // Conditional on still being "approved" so a double-click can't
        // restock the same returned item twice.
        $marked = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('status', 'approved')
            ->update([
                'status' => 'returned',
                'seller_note' => 'Item has been marked as returned by the seller.',
                'updated_at' => now(),
            ]);

        if (!$marked) {
            return back()->with('error', 'This request was just updated. Please refresh and try again.');
        }

        // The seller can untick "Add back to stock" for damaged items.
        $restocked = false;

        if (request()->boolean('restock', true)) {

            $item = DB::table('order_items')->where('id', $requestData->order_item_id)->first();

            $restocked = $item && OrderStock::restockItem($item);
        }

        createNotification(
            (int) $requestData->buyer_id,
            'Item Marked as Returned',
            'The seller has confirmed receipt of your returned item.',
            'return_refund',
            (int) $id
        );

        return back()->with(
            'success',
            'Return request has been marked as returned.' .
            ($restocked ? ' The item was added back to your stock.' : '')
        );
    }

    public function startReturnRefundProcessing($id)
    {
        $user = requireUserRole('seller');
        if (!is_array($user)) return $user;

        $requestData = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$requestData) {
            return back()->with('error', 'Return/Refund request not found.');
        }

        if ($requestData->status !== 'approved') {
            return back()->with('error', 'Only approved refund requests can be processed.');
        }

        if ($requestData->request_type !== 'Refund') {
            return back()->with('error', 'This request is not a refund request.');
        }

        // Conditional on the status just checked — a double-click can't
        // re-run this step or notify the buyer twice.
        $moved = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('status', 'approved')
            ->update([
                'status' => 'refund_processing',
                'seller_note' => 'Refund is currently being processed.',
                'updated_at' => now(),
            ]);

        if (!$moved) {
            return back()->with('error', 'This request was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $requestData->buyer_id,
            'Refund Processing',
            'Your refund is now being processed by the seller.',
            'return_refund',
            (int) $id
        );

        return back()->with(
            'success',
            'Refund is now being processed.'
        );
    }

    public function completeReturnRefund($id)
    {
        $user = requireUserRole('seller');
        if (!is_array($user)) return $user;

        $requestData = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('seller_id', $user['id'])
            ->first();

        if (!$requestData) {
            return back()->with('error', 'Return/Refund request not found.');
        }

        if ($requestData->status !== 'refund_processing') {
            return back()->with('error', 'Only refunds that are being processed can be completed.');
        }

        if ($requestData->request_type !== 'Refund') {
            return back()->with('error', 'This request is not a refund request.');
        }

        // Conditional on the status just checked — a double-click can't
        // re-run this step or notify the buyer twice.
        $moved = DB::table('return_refund_requests')
            ->where('id', $id)
            ->where('status', 'refund_processing')
            ->update([
                'status' => 'completed',
                'seller_note' => 'Refund has been completed by the seller.',
                'updated_at' => now(),
            ]);

        if (!$moved) {
            return back()->with('error', 'This request was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $requestData->buyer_id,
            'Refund Completed',
            'Your refund has been completed by the seller.',
            'return_refund',
            (int) $id
        );

        return back()->with(
            'success',
            'Refund has been marked as completed.'
        );
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

    public function restockOrder($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        if ($order->status !== 'Returned to Seller') {
            return back()->with('error', 'Only orders returned to you can be restocked.');
        }

        if (!empty($order->restocked_at)) {
            return back()->with('error', 'This order has already been marked as restocked.');
        }

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->where('seller_id', $user['id'])
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'This order does not belong to you.');
        }

        $result = OrderStock::restore((int) $id, (int) $user['id']);

        if ($result === null) {
            return back()->with('error', 'This order has already been marked as restocked.');
        }

        $restoredCount = $result['restored'];
        $skippedCount = $result['skipped'];

        $message = 'Order #' . $id . ' marked as restocked (' . $restoredCount . ' item(s) added back to inventory).';

        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' item(s) could not be matched to a variation and were skipped — please adjust stock manually.';
        }

        return back()->with('success', $message);
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
