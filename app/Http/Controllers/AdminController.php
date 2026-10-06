<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\RiderArea;
use App\Models\User;
use App\Support\Categories;
use App\Support\OrderStock;
use App\Support\RiderRelease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ApplicationReviewService;
use App\Exceptions\ActionFailed;
use App\Http\Requests\CommissionRateRequest;
use App\Http\Requests\DeliveryFeeRequest;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Requests\PoliciesRequest;

class AdminController extends Controller
{
    public function showLogin()
    {
        return redirect()->route('login');
    }

    public function login()
    {
        $email = strtolower(trim(request('email')));
        $password = trim(request('password'));

        if (
            $email === 'admin@boombuy.com' &&
            $password === 'admin123'
        ) {

            // Fresh session ID on privilege change (session fixation).
            session()->regenerate();
            session()->put('admin_logged_in', true);

            // Make sure a real `users` row exists for the admin account —
            // notifications and messaging both need a real user_id to attach to.
            User::firstOrCreate(
                ['email' => 'admin@boombuy.com'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'admin',
                ]
            );

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Welcome, Admin!');
        }

        return back()
            ->withInput()
            ->with(
                'error',
                'Invalid admin email or password.'
            );
    }

    public function dashboard()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Counts come straight from the database — nothing here loads whole
        // tables into memory.
        $roleCounts = DB::table('users')
            ->whereIn('role', ['buyer', 'seller', 'rider', 'logistics'])
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $totalUsers = (int) $roleCounts->sum();
        $buyerCount = (int) ($roleCounts['buyer'] ?? 0);
        $sellerCount = (int) ($roleCounts['seller'] ?? 0);
        $riderCount = (int) ($roleCounts['rider'] ?? 0);
        $logisticsCount = (int) ($roleCounts['logistics'] ?? 0);

        // Newest accounts only; the full list is on Manage Accounts.
        $users = DB::table('users')
            ->whereIn('role', ['buyer', 'seller', 'rider', 'logistics'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(fn ($user) => (array) $user)
            ->toArray();

        $statusCounts = DB::table('orders')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrders = (int) $statusCounts->sum();
        $pendingCount = (int) ($statusCounts['Pending'] ?? 0);
        $processingCount = (int) ($statusCounts['Processing'] ?? 0);
        $deliveredCount = (int) ($statusCounts['Delivered'] ?? 0);
        $cancelledCount = (int) ($statusCounts['Cancelled'] ?? 0);

        // Product sales only — the delivery fee is passed on to the rider.
        $totalSales = DB::table('orders')
            ->where('status', 'Delivered')
            ->sum(DB::raw('total_amount - delivery_fee'));

        $totalProducts = Product::count();

        $recentOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $itemsByOrder = DB::table('order_items')
            ->whereIn('order_id', $recentOrders->pluck('id'))
            ->get()
            ->groupBy('order_id');

        $orders = $recentOrders
            ->map(function ($order) use ($itemsByOrder) {
                $order = (array) $order;

                $order['items'] = $itemsByOrder
                    ->get($order['id'], collect())
                    ->map(fn ($item) => (array) $item)
                    ->toArray();

                $order['total'] = (float) ($order['total_amount'] ?? 0);
                $order['buyer_name'] = $order['shipping_name'] ?? 'Unknown Buyer';

                return $order;
            })
            ->toArray();

        $sellerProductsForDashboard = Product::latest()
            ->limit(5)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'image' => $product->image,
                'seller_id' => $product->seller_id,
            ])
            ->toArray();

        /*
        | Today, people and the review queue (dashboard tiles and lists).
        */

        $today = now()->toDateString();

        $ordersToday = DB::table('orders')
            ->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(total_amount - delivery_fee), 0) as value')
            ->first();

        $commissionRate = (float) PlatformSetting::get('commission_rate', '10');

        $deliveredTodaySales = (float) DB::table('orders')
            ->where('status', 'Delivered')
            ->whereRaw('DATE(COALESCE(delivered_at, updated_at)) = ?', [$today])
            ->sum(DB::raw('total_amount - delivery_fee'));

        $commissionToday = round($deliveredTodaySales * $commissionRate / 100, 2);

        // A seller counts once approved (same rule as the Applications page) and not suspended.
        $activeSellers = DB::table('users')
            ->where('role', 'seller')
            ->where('status', 'Active')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('seller_applications')
                ->whereColumn('seller_applications.user_id', 'users.id')
                ->where('seller_applications.status', 'Approved'))
            ->count();
        $activeRiders = DB::table('users')->where('role', 'rider')->where('status', 'Active')->count();

        $ridersOnRoad = DB::table('orders')
            ->where('status', 'Out for Delivery')
            ->whereNotNull('delivery_rider_id')
            ->distinct()
            ->count('delivery_rider_id');

        $pendingByType = [];
        $reviewQueue = collect();

        foreach (array_keys(self::APPLICATION_TYPES) as $type) {
            $table = $type . '_applications';
            $hasBusiness = $type !== 'buyer';

            $pendingByType[$type] = DB::table($table)->where('status', 'Pending Verification')->count();

            $reviewQueue = $reviewQueue->concat(
                DB::table($table)
                    ->where('status', 'Pending Verification')
                    ->orderBy('created_at')
                    ->limit(5)
                    ->get()
                    ->map(fn ($row) => [
                        'type' => $type,
                        'name' => $hasBusiness && !empty($row->business_name) ? $row->business_name : $row->full_name,
                        'detail' => $type === 'seller' && !empty($row->business_category)
                            ? $row->business_category
                            : ($hasBusiness ? $row->full_name : ($row->address ?? '')),
                        'created_at' => $row->created_at,
                    ])
            );
        }

        $reviewQueue = $reviewQueue->sortBy('created_at')->take(5)->values();
        $pendingApplications = array_sum($pendingByType);

        $flaggedProducts = DB::table('products')->where('is_flagged', true)->where('is_archived', false)->count();
        $pendingComplaints = DB::table('complaints')->where('status', 'Pending')->count();
        $suspendedSellers = DB::table('users')->where('role', 'seller')->where('status', 'Suspended')->count();

        return view(
            'pages.admin.dashboard',
            compact(
                'users',
                'orders',
                'sellerProductsForDashboard',

                'totalUsers',

                'buyerCount',
                'sellerCount',
                'riderCount',
                'logisticsCount',

                'totalOrders',

                'pendingCount',
                'processingCount',
                'deliveredCount',
                'cancelledCount',

                'totalProducts',
                'totalSales',

                'ordersToday',
                'commissionRate',
                'commissionToday',
                'activeSellers',
                'activeRiders',
                'ridersOnRoad',
                'pendingByType',
                'reviewQueue',
                'pendingApplications',
                'flaggedProducts',
                'pendingComplaints',
                'suspendedSellers'
            )
        );
    }

    public function logout()
    {
        session()->forget('admin_logged_in');
        session()->regenerate(true);

        return redirect()
            ->route('admin.login')
            ->with('success', 'Admin logged out successfully.');
    }

    // Filter cards on the admin Products page.
    public const PRODUCT_STATES = [
        'all' => 'All Products',
        'active' => 'Active',
        'out' => 'Out of Stock',
        'archived' => 'Archived',
        'flagged' => 'Flagged',
    ];

    public function products()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $search = trim((string) request('q', ''));
        $category = in_array(request('category'), Categories::LIST, true) ? request('category') : 'all';
        $state = array_key_exists((string) request('state'), self::PRODUCT_STATES) ? request('state') : 'all';

        // What a buyer can actually buy: the variations' stock when the
        // product has options, otherwise the product's own stock.
        $sellable = Product::SELLABLE_STOCK_SQL;

        $filtered = function () use ($search, $category) {
            return Product::query()
                ->leftJoin('users as sellers', 'sellers.id', '=', 'products.seller_id')
                ->when($search !== '', function ($query) use ($search) {
                    $like = '%' . $search . '%';

                    $query->where(function ($q) use ($like, $search) {
                        $q->where('products.name', 'like', $like)
                            ->orWhere('sellers.name', 'like', $like);

                        if (ctype_digit(ltrim($search, '#'))) {
                            $q->orWhere('products.id', (int) ltrim($search, '#'));
                        }
                    });
                })
                ->when($category !== 'all', fn ($q) => $q->where('products.category', $category));
        };

        $applyState = function ($query, string $state) use ($sellable) {
            return match ($state) {
                'active' => $query->where('products.is_archived', false)->where('products.is_flagged', false),
                'out' => $query->where('products.is_archived', false)->where('products.is_flagged', false)->whereRaw($sellable . ' <= 0'),
                'archived' => $query->where('products.is_archived', true),
                'flagged' => $query->where('products.is_flagged', true),
                default => $query,
            };
        };

        // Card counts follow the current search and category.
        $stateCounts = [];

        foreach (array_keys(self::PRODUCT_STATES) as $key) {
            $stateCounts[$key] = $applyState($filtered(), $key)->count();
        }

        $products = $applyState($filtered(), $state)
            ->select('products.*', 'sellers.name as seller_name')
            ->selectRaw($sellable . ' as sellable_stock')
            ->withCount('variations')
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->sellable_stock,
                'options' => (int) $product->variations_count,
                'image' => $product->image,
                'is_archived' => (bool) $product->is_archived,
                'is_flagged' => (bool) $product->is_flagged,
                'flag_reason' => $product->flag_reason,
                'seller_name' => $product->seller_name,
                'seller_id' => $product->seller_id,
            ]);

        return view('pages.admin-products', [
            'products' => $products,
            'search' => $search,
            'category' => $category,
            'state' => $state,
            'states' => self::PRODUCT_STATES,
            'stateCounts' => $stateCounts,
            'categories' => Categories::LIST,
        ]);
    }

    // No "Add Product" for the admin: every product must belong to a seller
    // who can fulfil its orders. The admin moderates (edit/flag/archive/delete).

    public function editProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        return view('pages.admin-edit-product', compact('product'));
    }

    public function updateProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        $name = trim(request('name'));
        $category = trim(request('category'));
        $price = (float) request('price');
        $stock = (int) request('stock');
        $description = trim(request('description'));
        $image = request()->file('image');

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

        // Old categories (Smartphone, Laptop…) are converted automatically.
        $categoryName = Categories::label($category);

        if ($categoryName === null) {
            return back()
                ->withInput()
                ->with('error', 'Please select a valid product category.');
        }

        // Same rule sellers follow: unique within the product's own store.
        if (Product::where('name', $name)->where('seller_id', $product->seller_id)->where('id', '!=', $product->id)->exists()) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'This seller already has a product with this name.'
                );
        }

        // Every option has to stay above ₱0 at the new base price.
        if ($product->variations()->exists() && $price + (float) $product->variations()->min('price_adjustment') <= 0) {
            return back()
                ->withInput()
                ->with('error', 'At this price one of the product\'s options would cost ₱0 or less.');
        }

        $updateData = [
            'name' => $name,
            'category' => $categoryName,
            'price' => $price,
            'stock' => $stock,
            'description' => $description,
        ];

        if ($image) {

            if (!isValidProductImage($image)) {
                return back()
                    ->withInput()
                    ->with('error', 'Product image must be a JPG, JPEG, PNG, or WEBP image no larger than 5MB.');
            }

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $updateData['image'] = $image->store('products', 'public');
        }

        $product->update($updateData);

        return redirect()
            ->route('admin.products')
            ->with(
                'success',
                $product->name . ' has been updated successfully!'
            );
    }

    public function deleteProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        // order_items.product_id cascades on delete — if any order ever
        // included this product, deleting it would silently wipe those
        // buyers' order lines (and any reviews) out from under real orders.
        // Archiving is the safe equivalent: it hides the product without
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

        // The cover, extra photos and option photos all go with it.
        $images = $product->variations()->pluck('image')
            ->merge($product->images()->pluck('path'))
            ->push($product->image)->filter()->all();

        $product->delete();

        \App\Support\ProductPhotos::deleteUnused($images);

        return redirect()
            ->route('admin.products')
            ->with(
                'success',
                $productName . ' deleted successfully!'
            );
    }

    public function archiveProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

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
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            abort(404);
        }

        $product->update(['is_archived' => false]);

        return back()->with(
            'success',
            $product->name . ' has been restored to the storefront.'
        );
    }

    public function accounts()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $roles = ['buyer', 'seller', 'rider', 'logistics'];

        $role = in_array(request('role'), $roles) ? request('role') : 'all';
        $status = in_array(request('status'), ['Active', 'Suspended', 'Deactivated']) ? request('status') : 'all';
        $search = trim((string) request('q', ''));

        // Buyers and riders show up right away; sellers and logistics only
        // once their application is approved (pending ones live under
        // Applications).
        $visible = DB::table('users')
            ->where(function ($query) {
                $query->whereIn('role', ['buyer', 'rider']);

                foreach (['seller', 'logistics'] as $approvedRole) {
                    $query->orWhere(function ($roleQuery) use ($approvedRole) {
                        $roleQuery
                            ->where('role', $approvedRole)
                            ->whereExists(function ($applicationQuery) use ($approvedRole) {
                                $applicationQuery
                                    ->select(DB::raw(1))
                                    ->from($approvedRole . '_applications')
                                    ->whereColumn($approvedRole . '_applications.user_id', 'users.id')
                                    ->where($approvedRole . '_applications.status', 'Approved');
                            });
                    });
                }
            });

        $roleCounts = (clone $visible)
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $users = (clone $visible)
            ->when($role !== 'all', fn ($q) => $q->where('role', $role))
            ->when($status !== 'all', fn ($q) => $q->where(DB::raw("COALESCE(status, 'Active')"), $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';

                $q->where(function ($inner) use ($like, $search) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->select('id', 'name', 'email', 'phone', 'role', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view(
            'pages.admin.accounts',
            compact('users', 'roleCounts', 'role', 'status', 'search')
        );
    }

    public function settings()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $announcements = PlatformAnnouncement::orderByDesc('created_at')->get();

        $termsPolicy = PlatformSetting::get('terms_policy');
        $privacyPolicy = PlatformSetting::get('privacy_policy');
        $returnPolicy = PlatformSetting::get('return_policy');
        $commissionRate = PlatformSetting::get('commission_rate', '10');
        $deliveryFee = PlatformSetting::get('delivery_fee', '50');

        return view(
            'pages.admin.settings',
            compact('announcements', 'termsPolicy', 'privacyPolicy', 'returnPolicy', 'commissionRate', 'deliveryFee')
        );
    }

    public function updateCommission(CommissionRateRequest $request)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $rate = $request->validated('commission_rate');

        PlatformSetting::set('commission_rate', (string) $rate);

        return back()->with('success', 'Commission rate updated to ' . $rate . '%.');
    }

    public function updateDeliveryFee(DeliveryFeeRequest $request)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        foreach (\App\Support\DeliveryFee::ZONE_SETTINGS as [$key]) {
            PlatformSetting::set($key, (string) $request->validated($key));
        }

        return back()->with('success', 'Delivery fees updated.');
    }

    public function storeAnnouncement(AnnouncementRequest $request)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        PlatformAnnouncement::create([
            'title' => request('title'),
            'message' => request('message'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function toggleAnnouncement($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $announcement = PlatformAnnouncement::find($id);

        if (!$announcement) {
            return back()->with('error', 'Announcement not found.');
        }

        $announcement->update(['is_active' => !$announcement->is_active]);

        return back()->with(
            'success',
            'Announcement is now ' . ($announcement->is_active ? 'active' : 'hidden') . '.'
        );
    }

    public function deleteAnnouncement($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        PlatformAnnouncement::where('id', $id)->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function updatePolicies(PoliciesRequest $request)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        PlatformSetting::set('terms_policy', trim((string) request('terms_policy', '')));
        PlatformSetting::set('privacy_policy', trim((string) request('privacy_policy', '')));
        PlatformSetting::set('return_policy', trim((string) request('return_policy', '')));

        return back()->with('success', 'Platform policies updated successfully.');
    }

    public function updateAccountStatus($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $status = request('status');

        if (!in_array($status, ['Active', 'Suspended', 'Deactivated'])) {
            return back()->with('error', 'Invalid account status.');
        }

        $user = DB::table('users')
            ->where('id', $id)
            ->whereIn('role', ['buyer', 'seller', 'rider', 'logistics'])
            ->first();

        if (!$user) {
            return back()->with('error', 'Account not found.');
        }

        DB::table('users')
            ->where('id', $id)
            ->update(['status' => $status]);

        createNotification(
            $user->id,
            'Account Status Updated',
            "Your BoomBuy account status was changed to \"{$status}\" by an administrator.",
            'account_status'
        );

        $note = '';

        if ($status !== 'Active' && $user->role === 'rider') {
            // Hand their parcels to other riders instead of leaving them stuck.
            $note = RiderRelease::summary(RiderRelease::release((int) $user->id));
        }

        if ($status !== 'Active' && $user->role === 'seller') {
            // Their listings leave the shop automatically; orders they still
            // have to pack can't move until they're back or are cancelled.
            $openOrders = DB::table('orders')
                ->whereIn('status', ['Pending', 'Processing'])
                ->whereExists(function ($q) use ($user) {
                    $q->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.seller_id', $user->id);
                })
                ->count();

            $note = ' Their products are hidden from the shop.'
                . ($openOrders > 0
                    ? ' They still have ' . $openOrders . ' open order(s) — cancel those from Orders if the seller will not be back soon.'
                    : '');
        }

        return back()->with(
            'success',
            $user->name . '\'s account status has been set to ' . $status . '.' . $note
        );
    }

    public function reports()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $statusCounts = DB::table('orders')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrders = (int) $statusCounts->sum();
        $completedOrders = (int) ($statusCounts['Delivered'] ?? 0);

        // Delivered orders only, without the delivery fee (that goes to the
        // rider) — the same figure as the dashboard's Total Sales.
        $totalRevenue = (float) DB::table('orders')
            ->where('status', 'Delivered')
            ->sum(DB::raw('total_amount - delivery_fee'));

        $averageOrder = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;

        /*
        | Commission: same rule as the seller's own Reports page — delivered
        | items only, minus the vouchers each seller funds, times the rate.
        */
        $commissionRate = (float) PlatformSetting::get('commission_rate', '10');

        $voucherDiscountsBySeller = DB::table('orders')
            ->join('vouchers', 'vouchers.code', '=', 'orders.voucher_code')
            ->where('orders.status', 'Delivered')
            ->whereNotNull('vouchers.seller_id')
            ->select('vouchers.seller_id', DB::raw('SUM(orders.discount_amount) as discounts'))
            ->groupBy('vouchers.seller_id')
            ->pluck('discounts', 'seller_id');

        $sellerSales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('users', 'users.id', '=', 'order_items.seller_id')
            ->where('orders.status', 'Delivered')
            ->selectRaw('order_items.seller_id, users.name as seller_name, SUM(order_items.price * order_items.quantity) as sales')
            ->groupBy('order_items.seller_id', 'users.name')
            ->get()
            ->map(function ($row) use ($commissionRate, $voucherDiscountsBySeller) {
                $sales = max(0, (float) $row->sales - (float) ($voucherDiscountsBySeller[$row->seller_id] ?? 0));
                $commission = $sales * ($commissionRate / 100);

                return [
                    'seller_id' => $row->seller_id,
                    'seller_name' => $row->seller_name,
                    'sales' => $sales,
                    'commission' => $commission,
                    'payout' => $sales - $commission,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $totalSellerSales = $sellerSales->sum('sales');
        $totalCommission = $sellerSales->sum('commission');
        $totalPayouts = $sellerSales->sum('payout');

        // Units sold per product (orders that weren't cancelled or sent back),
        // in one grouped query instead of one per product.
        $unitsByProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['Cancelled', 'Returned to Seller'])
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as units'))
            ->groupBy('order_items.product_id')
            ->pluck('units', 'product_id');

        $sellerNames = DB::table('users')->where('role', 'seller')->pluck('name', 'id');

        $productPerformance = Product::query()
            ->whereNotNull('seller_id')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'category', 'price', 'seller_id'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'units_sold' => (int) ($unitsByProduct[$product->id] ?? 0),
                'seller_id' => $product->seller_id,
                'seller_name' => $sellerNames[$product->seller_id] ?? null,
            ])
            ->sortByDesc('units_sold')
            ->values();

        $categorySales = [];

        foreach ($productPerformance as $product) {
            $category = $product['category'] ?: 'Other';
            $categorySales[$category] = ($categorySales[$category] ?? 0) + $product['units_sold'];
        }

        arsort($categorySales);

        $totalCategoryUnits = array_sum($categorySales);

        // This year's sales by month (cancelled/returned orders left out).
        $year = (int) now()->year;
        $monthlySales = array_fill(1, 12, 0.0);

        DB::table('orders')
            ->whereNotIn('status', ['Cancelled', 'Returned to Seller'])
            ->whereYear('created_at', $year)
            ->get(['created_at', 'total_amount', 'delivery_fee'])
            ->each(function ($order) use (&$monthlySales) {
                $month = (int) date('n', strtotime($order->created_at));
                $monthlySales[$month] += (float) $order->total_amount - (float) $order->delivery_fee;
            });

        $maxMonthlySales = max($monthlySales);

        $recentOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $namesByOrder = DB::table('order_items')
            ->whereIn('order_id', $recentOrders->pluck('id'))
            ->get(['order_id', 'product_name'])
            ->groupBy('order_id');

        $recentOrders->each(function ($order) use ($namesByOrder) {
            $order->product_names = $namesByOrder->get($order->id, collect())->pluck('product_name')->all();
        });

        return view('pages.admin.reports', compact(
            'totalOrders',
            'completedOrders',
            'totalRevenue',
            'averageOrder',
            'commissionRate',
            'sellerSales',
            'totalSellerSales',
            'totalCommission',
            'totalPayouts',
            'productPerformance',
            'categorySales',
            'totalCategoryUnits',
            'year',
            'monthlySales',
            'maxMonthlySales',
            'recentOrders'
        ));
    }

    // Registration types the admin reviews (riders are reviewed by Logistics;
    // buyers are approved automatically when they sign up).
    public const APPLICATION_TYPES = [
        'seller' => 'Sellers',
        'logistics' => 'Logistics',
    ];

    // Chip order: All, then the queue that needs action, then decided ones.
    public const APPLICATION_STATUSES = [
        'all' => ['label' => 'All', 'status' => null],
        'pending' => ['label' => 'Pending', 'status' => 'Pending Verification'],
        'approved' => ['label' => 'Approved', 'status' => 'Approved'],
        'rejected' => ['label' => 'Rejected', 'status' => 'Rejected'],
    ];

    public function applications()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Pending counts per type, so the admin sees where work is waiting.
        $pendingCounts = [];

        foreach (array_keys(self::APPLICATION_TYPES) as $key) {
            $pendingCounts[$key] = DB::table($key . '_applications')
                ->where('status', 'Pending Verification')
                ->count();
        }

        // Default to the first type that has something to review.
        $type = request('type');

        if (!array_key_exists((string) $type, self::APPLICATION_TYPES)) {
            $type = collect($pendingCounts)->filter()->keys()->first() ?? 'seller';
        }

        // Opens on Pending while something is waiting for review, else All.
        $statusKey = array_key_exists((string) request('status'), self::APPLICATION_STATUSES)
            ? request('status')
            : ($pendingCounts[$type] > 0 ? 'pending' : 'all');

        $search = trim((string) request('q', ''));
        $table = $type . '_applications';

        $base = DB::table($table)
            ->join('users', 'users.id', '=', $table . '.user_id')
            ->when($search !== '', function ($query) use ($search, $table, $type) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like, $table, $type) {
                    $q->where($table . '.full_name', 'like', $like)
                        ->orWhere('users.email', 'like', $like)
                        ->orWhere($table . '.phone', 'like', $like);

                    if ($type !== 'buyer') {
                        $q->orWhere($table . '.business_name', 'like', $like);
                    }
                });
            });

        $statusCounts = (clone $base)
            ->select($table . '.status', DB::raw('COUNT(*) as total'))
            ->groupBy($table . '.status')
            ->pluck('total', 'status');

        $applications = (clone $base)
            ->when(
                self::APPLICATION_STATUSES[$statusKey]['status'],
                fn ($q, $status) => $q->where($table . '.status', $status)
            )
            ->select($table . '.*', 'users.email as user_email')
            // Oldest pending first (first come, first served); newest first otherwise.
            ->orderBy($table . '.created_at', $statusKey === 'pending' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.applications', [
            'applications' => $applications,
            'type' => $type,
            'types' => self::APPLICATION_TYPES,
            'statusKey' => $statusKey,
            'statuses' => self::APPLICATION_STATUSES,
            'statusCounts' => $statusCounts,
            'pendingCounts' => $pendingCounts,
            'search' => $search,
            'categories' => Categories::LIST,
        ]);
    }

    public function logistics()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $riderApplications = DB::table('rider_applications')
            ->join('users', 'users.id', '=', 'rider_applications.user_id')
            ->select('rider_applications.*', 'users.email as user_email', 'users.status as account_status')
            ->orderByDesc('rider_applications.created_at')
            ->get();

        $riderAreas = RiderArea::whereIn(
            'rider_id',
            $riderApplications->pluck('user_id')
        )->get()->groupBy('rider_id');

        $awaitingConfirmation = DB::table('orders')
            ->where('status', 'Dropped Off')
            ->orderBy('updated_at')
            ->get();

        $awaitingAssignment = DB::table('orders')
            ->where('status', 'At Sorting Center')
            ->orderBy('sorting_center_received_at')
            ->get();

        $failedDeliveries = DB::table('orders')
            ->where('status', 'Delivery Failed')
            ->orderByDesc('delivery_failed_at')
            ->get();

        $returnedToSeller = DB::table('orders')
            ->where('status', 'Returned to Seller')
            ->orderByDesc('updated_at')
            ->get();

        $activeRiders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('users.status', 'Active')
            ->where('rider_applications.status', 'Approved')
            ->distinct('users.id')
            ->count('users.id');

        $deliveredToday = DB::table('orders')
            ->where('status', 'Delivered')
            ->whereDate('delivered_at', now()->toDateString())
            ->count();

        return view(
            'pages.admin.logistics',
            compact(
                'riderApplications',
                'riderAreas',
                'awaitingConfirmation',
                'awaitingAssignment',
                'failedDeliveries',
                'returnedToSeller',
                'activeRiders',
                'deliveredToday'
            )
        );
    }

    public function approveApplication($type, $id, ApplicationReviewService $reviews)
    {
        return $this->reviewApplication($type, fn () => $reviews->approve($type, (int) $id, request('business_category')));
    }

    public function rejectApplication($type, $id, ApplicationReviewService $reviews)
    {
        return $this->reviewApplication($type, fn () => $reviews->reject($type, (int) $id, (string) request('admin_remarks')));
    }

    /** Admin only; unknown application types are a 404. */
    private function reviewApplication(string $type, callable $decide)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        if (!ApplicationReviewService::isType($type)) {
            abort(404);
        }

        try {
            return back()->with('success', $decide());
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function applicationDocument($type, $id, $field)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        if (!in_array($type, ['seller', 'buyer', 'logistics'])) {
            abort(404);
        }

        $table = match ($type) {
            'seller' => 'seller_applications',
            'buyer' => 'buyer_applications',
            'logistics' => 'logistics_applications',
        };

        $allowedFields = match ($type) {
            'seller' => ['national_id', 'business_permit'],
            'buyer' => ['id_photo'],
            'logistics' => ['id_photo', 'business_permit'],
        };

        if (!in_array($field, $allowedFields)) {
            abort(404);
        }

        $application = DB::table($table)->where('id', $id)->first();

        if (!$application || empty($application->$field)) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($application->$field)) {
            abort(404);
        }

        return Storage::disk('local')->response($application->$field);
    }

    // Tabs on the admin Orders page => the order statuses each one shows.
    public const ORDER_TABS = [
        'all' => ['label' => 'All', 'statuses' => null],
        'to-process' => ['label' => 'To Process', 'statuses' => ['Pending', 'Processing']],
        'in-transit' => ['label' => 'In Transit', 'statuses' => ['Dropped Off', 'At Sorting Center', 'In Transit', 'Assigned for Delivery', 'Out for Delivery', 'Ready to Collect']],
        'failed' => ['label' => 'Failed Delivery', 'statuses' => ['Delivery Failed']],
        'delivered' => ['label' => 'Delivered', 'statuses' => ['Delivered']],
        'closed' => ['label' => 'Cancelled / Returned', 'statuses' => ['Cancelled', 'Returning', 'Return Ready', 'Returned to Seller']],
    ];

    // The admin may only cancel while the items are still with the seller —
    // once it's at a Sorting Center it goes back through the Logistics return flow.
    public const ADMIN_CANCELLABLE = ['Pending', 'Processing'];

    public function orders()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $tab = request('tab', 'all');

        if (!array_key_exists($tab, self::ORDER_TABS)) {
            $tab = 'all';
        }

        $search = trim((string) request('q', ''));

        // Search matches the order number, the buyer's name/phone on the
        // order, or the buyer's account email.
        $searched = DB::table('orders')
            ->leftJoin('users as buyers', 'buyers.id', '=', 'orders.buyer_id')
            ->when($search !== '', function ($query) use ($search) {
                $number = ltrim($search, '#');
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($number, $like) {
                    if (ctype_digit($number)) {
                        $q->orWhere('orders.id', (int) $number);
                    }

                    $q->orWhere('orders.shipping_name', 'like', $like)
                        ->orWhere('orders.shipping_phone', 'like', $like)
                        ->orWhere('buyers.email', 'like', $like);
                });
            });

        // Per-tab counts for the current search.
        $statusCounts = (clone $searched)
            ->select('orders.status', DB::raw('COUNT(*) as total'))
            ->groupBy('orders.status')
            ->pluck('total', 'orders.status');

        $tabCounts = [];

        foreach (self::ORDER_TABS as $key => $definition) {
            $tabCounts[$key] = $definition['statuses'] === null
                ? (int) $statusCounts->sum()
                : (int) collect($definition['statuses'])->sum(fn ($s) => $statusCounts[$s] ?? 0);
        }

        $orders = (clone $searched)
            ->when(self::ORDER_TABS[$tab]['statuses'], fn ($q, $statuses) => $q->whereIn('orders.status', $statuses))
            ->select('orders.*', 'buyers.email as buyer_email')
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->paginate(20)
            ->withQueryString();

        // One query each for the page's items and riders, instead of per order.
        $orderIds = $orders->pluck('id');

        $itemsByOrder = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->get()
            ->groupBy('order_id');

        $riderNames = DB::table('users')
            ->whereIn('id', $orders->pluck('rider_id')->merge($orders->pluck('delivery_rider_id'))->filter()->unique())
            ->pluck('name', 'id');

        $orders->getCollection()->transform(function ($order) use ($itemsByOrder, $riderNames) {
            $order->items = $itemsByOrder->get($order->id, collect());
            $order->pickup_rider_name = $riderNames[$order->rider_id] ?? null;
            $order->delivery_rider_name = $riderNames[$order->delivery_rider_id] ?? null;

            return $order;
        });

        return view('pages.admin.orders', [
            'orders' => $orders,
            'tabs' => self::ORDER_TABS,
            'tab' => $tab,
            'tabCounts' => $tabCounts,
            'search' => $search,
        ]);
    }

    public function orderDetails($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $order = DB::table('orders')
            ->leftJoin('users as buyers', 'buyers.id', '=', 'orders.buyer_id')
            ->where('orders.id', $id)
            ->select('orders.*', 'buyers.email as buyer_email')
            ->first();

        if (!$order) {
            abort(404);
        }

        $order->items = DB::table('order_items')
            ->where('order_id', $order->id)
            ->get();

        $riderNames = DB::table('users')
            ->whereIn('id', array_filter([$order->rider_id, $order->delivery_rider_id]))
            ->pluck('name', 'id');

        $order->pickup_rider_name = $riderNames[$order->rider_id] ?? null;
        $order->delivery_rider_name = $riderNames[$order->delivery_rider_id] ?? null;

        $sellerNames = DB::table('users')
            ->whereIn('id', $order->items->pluck('seller_id')->filter()->unique())
            ->pluck('name', 'id');

        $canCancel = in_array($order->status, self::ADMIN_CANCELLABLE);

        return view(
            'pages.admin.order-details',
            compact('order', 'sellerNames', 'canCancel')
        );
    }

    // Admins don't push orders through the pipeline by hand — each step is
    // owned by the seller, rider or Logistics flow that records its own
    // timestamps. What an admin can do is stop an order before it ships.
    public function cancelOrder($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $reason = trim((string) request('reason'));

        if ($reason === '') {
            return back()->with('error', 'Please give a reason for cancelling this order.');
        }

        $reason = Str::limit($reason, 500, '');

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        if (!in_array($order->status, self::ADMIN_CANCELLABLE)) {
            return back()->with(
                'error',
                'Only orders that are still with the seller (Pending or Processing) can be cancelled.'
            );
        }

        $updated = DB::table('orders')
            ->where('id', $id)
            ->where('status', $order->status)
            ->update([
                'status' => 'Cancelled',
                'cancelled_by' => 'admin',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cancelled by an administrator: ' . $reason,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'This order was just updated. Please refresh and try again.');
        }

        OrderStock::cancelled((int) $id, $order->status);
        \App\Support\OrderTimeline::log((int) $id, 'Cancelled', 'Cancelled by BoomBuy', $reason);
        \App\Support\ChatAutomation::orderUpdate((int) $id, 'cancelled');

        createNotification(
            (int) $order->buyer_id,
            'Order Cancelled',
            'Your order #' . $id . ' was cancelled by BoomBuy. Reason: ' . $reason,
            'order',
            (int) $id
        );

        notifyOrderSellers(
            (int) $id,
            'Order Cancelled by Admin',
            'Order #' . $id . ' was cancelled by an administrator. Reason: ' . $reason
                . ' The items have been returned to your stock.'
        );

        return back()->with('success', 'Order #' . $id . ' has been cancelled.');
    }

    public function notifications()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $adminUser = \App\Support\SupportAccount::user();

        $notifications = Notification::where('user_id', $adminUser->id ?? 0)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Notification::where('user_id', $adminUser->id ?? 0)
            ->whereNull('read_at')
            ->count();

        return view(
            'pages.admin.notifications',
            compact(
                'notifications',
                'unreadCount'
            )
        );
    }

    public function markNotificationRead($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        Notification::where('id', $id)
            ->where('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('email', \App\Support\SupportAccount::EMAIL)
                    ->limit(1);
            })
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return back();
    }

    public function complaints()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $complaints = Complaint::with('complainant')
            ->orderByDesc('created_at')
            ->get();

        $pendingCount = $complaints->where('status', 'Pending')->count();

        return view(
            'pages.admin.complaints',
            compact('complaints', 'pendingCount')
        );
    }

    public function updateComplaintStatus($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $status = request('status');

        if (!in_array($status, ['Pending', 'Under Review', 'Resolved', 'Dismissed'])) {
            return back()->with('error', 'Invalid status.');
        }

        $complaint = Complaint::find($id);

        if (!$complaint) {
            return back()->with('error', 'Complaint not found.');
        }

        $complaint->update([
            'status' => $status,
            'admin_notes' => request('admin_notes', $complaint->admin_notes),
            'resolved_at' => in_array($status, ['Resolved', 'Dismissed']) ? now() : null,
        ]);

        createNotification(
            $complaint->complainant_id,
            'Complaint Update',
            "Your complaint \"{$complaint->subject}\" is now marked as \"{$status}\".",
            'complaint',
            $complaint->id
        );

        return back()->with('success', 'Complaint updated successfully.');
    }

    public function compliance()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $search = trim((string) request('q', ''));

        // A search for a category name ("shoes") matches sellers registered for it.
        $categoryMatches = collect(Categories::LIST)
            ->filter(fn ($label) => $search !== '' && stripos($label, $search) !== false)
            ->keys();

        $allSellers = DB::table('seller_applications')
            ->join('users', 'users.id', '=', 'seller_applications.user_id')
            ->where('seller_applications.status', 'Approved')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.status as account_status',
                'seller_applications.business_name',
                'seller_applications.business_category'
            )
            ->get();

        // Every seller's products in one query, instead of one per seller.
        $productsBySeller = Product::whereIn('seller_id', $allSellers->pluck('user_id'))
            ->get()
            ->groupBy('seller_id');

        $allSellers = $allSellers->map(function ($seller) use ($productsBySeller) {

            $products = $productsBySeller->get($seller->user_id, collect());

            // business_category is stored as a slug ("electronics") while
            // products.category is stored as its label ("Electronics").
            $registeredLabel = Categories::LIST[$seller->business_category] ?? $seller->business_category;

            $flagged = $products->where('is_flagged', true)->values();

            // Already-flagged products are listed once, under Flagged.
            $mismatches = $seller->business_category
                ? $products->filter(fn ($p) => !$p->is_flagged && $p->category !== $registeredLabel)->values()
                : collect();

            return [
                'user_id' => $seller->user_id,
                'name' => $seller->name,
                'shop' => $seller->business_name ?: $seller->name,
                'email' => $seller->email,
                'account_status' => $seller->account_status ?? 'Active',
                'business_category' => $seller->business_category,
                'category_label' => $registeredLabel ?: 'Not specified',
                'total_products' => $products->count(),
                'mismatches' => $mismatches,
                'flagged' => $flagged,
                'has_issues' => $mismatches->isNotEmpty() || $flagged->isNotEmpty(),
            ];
        });

        $totalSellers = $allSellers->count();
        $totalMismatches = $allSellers->sum(fn ($s) => $s['mismatches']->count());
        $totalFlagged = $allSellers->sum(fn ($s) => $s['flagged']->count());
        $sellersWithIssues = $allSellers->where('has_issues', true)->count();

        // Opens on "Needs attention" while anything needs it, else everyone.
        $view = in_array(request('view'), ['all', 'issues'], true)
            ? request('view')
            : ($sellersWithIssues > 0 ? 'issues' : 'all');

        $sellers = $allSellers
            ->when($view === 'issues', fn ($c) => $c->where('has_issues', true))
            ->when($search !== '', function ($c) use ($search, $categoryMatches) {
                return $c->filter(function ($s) use ($search, $categoryMatches) {
                    return stripos($s['name'], $search) !== false
                        || stripos($s['shop'], $search) !== false
                        || stripos($s['email'], $search) !== false
                        || $categoryMatches->contains($s['business_category']);
                });
            })
            // Problems first, then alphabetical by shop.
            ->sortBy(fn ($s) => ($s['has_issues'] ? '0' : '1') . strtolower($s['shop']))
            ->values();

        return view(
            'pages.admin.compliance',
            compact('sellers', 'totalSellers', 'sellersWithIssues', 'totalMismatches', 'totalFlagged', 'view', 'search')
        );
    }

    /** Compliance → Riders: stuck deliveries, failed attempts, complaints. */
    public function complianceRiders()
    {
        return $this->peopleCompliance('riders', \App\Support\PeopleCompliance::riders());
    }

    /** Compliance → Buyers: COD pauses for cancelling/refusing, complaints. */
    public function complianceBuyers()
    {
        return $this->peopleCompliance('buyers', \App\Support\PeopleCompliance::buyers());
    }

    private function peopleCompliance(string $who, \Illuminate\Support\Collection $everyone)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $search = trim((string) request('q', ''));
        $withIssues = $everyone->where('has_issues', true)->count();

        // Opens on "Needs attention" while anything needs it, else everyone.
        $view = in_array(request('view'), ['all', 'issues'], true)
            ? request('view')
            : ($withIssues > 0 ? 'issues' : 'all');

        $people = $everyone
            ->when($view === 'issues', fn ($c) => $c->where('has_issues', true))
            ->when($search !== '', fn ($c) => $c->filter(fn ($p) => stripos($p['name'], $search) !== false
                || stripos($p['email'], $search) !== false
                || stripos($p['area'] ?? '', $search) !== false))
            ->sortBy(fn ($p) => ($p['has_issues'] ? '0' : '1') . strtolower($p['name']))
            ->values();

        $total = $everyone->count();

        return view('pages.admin.compliance-people', compact('who', 'people', 'total', 'withIssues', 'view', 'search'));
    }

    public function flagProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            return back()->with('error', 'Product not found.');
        }

        $reason = trim((string) request('flag_reason'));

        $product->update([
            'is_flagged' => true,
            'flag_reason' => $reason !== '' ? $reason : 'Flagged by admin for review.',
        ]);

        if ($product->seller_id) {

            createNotification(
                $product->seller_id,
                'Product Flagged: ' . $product->name,
                'Your product "' . $product->name . '" has been flagged and removed from the storefront. Reason: ' . $product->flag_reason,
                'compliance_warning',
                $product->id
            );
        }

        return back()->with('success', 'Product flagged and hidden from the storefront.');
    }

    public function unflagProduct($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $product = Product::find($id);

        if (!$product) {
            return back()->with('error', 'Product not found.');
        }

        $product->update(['is_flagged' => false, 'flag_reason' => null]);

        return back()->with('success', 'Product unflagged and restored to the storefront.');
    }

    public function warnSeller($userId)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Sellers, riders and buyers can all be warned from Compliance.
        $person = DB::table('users')->where('id', $userId)->whereIn('role', ['seller', 'rider', 'buyer'])->first();

        if (!$person) {
            return back()->with('error', 'Account not found.');
        }

        $message = trim((string) request('warning_message'));

        $standard = match ($person->role) {
            'rider' => 'Your rider account has received a compliance warning from BoomBuy admin. Please keep your deliveries moving and update their status on time.',
            'buyer' => 'Your BoomBuy account has received a warning from the admin about cancelled or refused orders. Repeated issues may lead to your account being suspended.',
            default => 'Your seller account has received a compliance warning from BoomBuy admin. Please review your product listings.',
        };

        createNotification(
            $userId,
            'Compliance Warning',
            $message !== '' ? $message : $standard,
            'compliance_warning'
        );

        return back()->with('success', 'Warning sent to ' . $person->name . '.');
    }
}
