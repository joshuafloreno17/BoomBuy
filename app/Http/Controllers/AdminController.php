<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationStatusMail;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\RiderArea;
use App\Models\User;
use App\Support\Categories;
use App\Support\OrderStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

            // Initialize Admin notifications
            if (!session()->has('admin_notifications')) {
                session()->put('admin_notifications', []);
            }

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

        /*
        |--------------------------------------------------------------------------
        | ACCOUNTS - DATABASE
        |--------------------------------------------------------------------------
        */

        $users = DB::table('users')
            ->select(
                'id',
                'name',
                'email',
                'role',
                'created_at'
            )
            ->whereIn('role', [
                'buyer',
                'seller',
                'rider',
                'logistics'
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($user) {

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];

            })
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT COUNTS
        |--------------------------------------------------------------------------
        */

        $totalUsers = count($users);

        $buyerCount = count(array_filter($users, function ($user) {
            return ($user['role'] ?? '') === 'buyer';
        }));

        $sellerCount = count(array_filter($users, function ($user) {
            return ($user['role'] ?? '') === 'seller';
        }));

        $riderCount = count(array_filter($users, function ($user) {
            return ($user['role'] ?? '') === 'rider';
        }));

        $logisticsCount = count(array_filter($users, function ($user) {
            return ($user['role'] ?? '') === 'logistics';
        }));


        /*
        |--------------------------------------------------------------------------
        | ORDERS - DATABASE
        |--------------------------------------------------------------------------
        */

        $dbOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->get();

        $totalOrders = $dbOrders->count();

        $pendingCount = DB::table('orders')
            ->where('status', 'Pending')
            ->count();

        $processingCount = DB::table('orders')
            ->where('status', 'Processing')
            ->count();

        $deliveredCount = DB::table('orders')
            ->where('status', 'Delivered')
            ->count();

        $cancelledCount = DB::table('orders')
            ->where('status', 'Cancelled')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | TOTAL SALES
        |--------------------------------------------------------------------------
        */

        // Product sales only — the delivery fee is passed on to the rider.
        $totalSales = DB::table('orders')
            ->where('status', 'Delivered')
            ->sum(DB::raw('total_amount - delivery_fee'));


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        |
        | Seller products are stored in the database.
        | Admin products are still stored in session.
        |
        */

        $sellerProducts = Product::latest()
            ->get()
            ->map(function ($product) {

                return [
                    'id' => $product->id,
                    'slug' => Str::slug($product->name) . '-' . $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'description' => $product->description,
                    'image' => $product->image,
                    'seller_id' => $product->seller_id,
                ];

            })
            ->toArray();

        $adminProducts = session()->get(
            'admin_products',
            []
        );

        $products = array_merge(
            $sellerProducts,
            $adminProducts
        );

        $totalProducts = count($products);


        /*
        |--------------------------------------------------------------------------
        | RECENT ORDERS
        |--------------------------------------------------------------------------
        */

        $orders = $dbOrders
            ->take(5)
            ->map(function ($order) {

                $order = (array) $order;

                $items = DB::table('order_items')
                    ->where('order_id', $order['id'])
                    ->get();

                $order['items'] = $items
                    ->map(function ($item) {

                        $item = (array) $item;

                        $item['subtotal'] =
                            (float) ($item['price'] ?? 0) *
                            (int) ($item['quantity'] ?? 1);

                        return $item;

                    })
                    ->toArray();

                $order['total'] =
                    (float) ($order['total_amount'] ?? 0);

                $order['buyer_name'] =
                    $order['shipping_name']
                    ?? 'Unknown Buyer';

                return $order;

            })
            ->toArray();


        /*
        |--------------------------------------------------------------------------
        | SELLER PRODUCTS FOR DASHBOARD
        |--------------------------------------------------------------------------
        */

        $sellerProductsForDashboard = array_slice(
            $sellerProducts,
            0,
            5
        );


        /*
        |--------------------------------------------------------------------------
        | RETURN ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */

        return view(
            'pages.admin.dashboard',
            compact(
                'users',
                'orders',
                'products',
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
                'totalSales'
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

    public function products()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $products = Product::latest()
            ->get()
            ->map(function ($product) {

                return [
                    'id' => $product->id,
                    'slug' => Str::slug($product->name) . '-' . $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'icon' => $product->image,
                    'is_archived' => (bool) $product->is_archived,
                ];

            })
            ->toArray();

        return view('pages.admin-products', compact('products'));
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

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

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

        /*
        |--------------------------------------------------------------------------
        | ADMIN MANAGE ACCOUNTS
        |--------------------------------------------------------------------------
        |
        | Buyer and Rider accounts are shown normally.
        |
        | Seller accounts are shown ONLY when their seller application
        | has been approved by the admin.
        |
        */

        $users = DB::table('users')
            ->select(
                'id',
                'name',
                'email',
                'role',
                'status',
                'created_at'
            )
            ->where(function ($query) {

                // Buyers and Riders can appear normally
                $query->whereIn('role', [
                    'buyer',
                    'rider'
                ]);

                // Sellers appear only after admin approval
                $query->orWhere(function ($sellerQuery) {

                    $sellerQuery
                        ->where('role', 'seller')
                        ->whereExists(function ($applicationQuery) {

                            $applicationQuery
                                ->select(DB::raw(1))
                                ->from('seller_applications')
                                ->whereColumn(
                                    'seller_applications.user_id',
                                    'users.id'
                                )
                                ->where(
                                    'seller_applications.status',
                                    'Approved'
                                );
                        });
                });

                // Logistics accounts appear only after admin approval, same as sellers
                $query->orWhere(function ($logisticsQuery) {

                    $logisticsQuery
                        ->where('role', 'logistics')
                        ->whereExists(function ($applicationQuery) {

                            $applicationQuery
                                ->select(DB::raw(1))
                                ->from('logistics_applications')
                                ->whereColumn(
                                    'logistics_applications.user_id',
                                    'users.id'
                                )
                                ->where(
                                    'logistics_applications.status',
                                    'Approved'
                                );
                        });
                });

            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($user) {

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status ?? 'Active',
                    'created_at' => $user->created_at,
                ];

            })
            ->toArray();

        return view(
            'pages.admin.accounts',
            compact('users')
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

    public function updateCommission()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $rate = request()->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ])['commission_rate'];

        PlatformSetting::set('commission_rate', (string) $rate);

        return back()->with('success', 'Commission rate updated to ' . $rate . '%.');
    }

    public function updateDeliveryFee()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $fee = request()->validate([
            'delivery_fee' => 'required|numeric|min:0',
        ])['delivery_fee'];

        PlatformSetting::set('delivery_fee', (string) $fee);

        return back()->with('success', 'Rider delivery fee updated to ₱' . $fee . '.');
    }

    public function storeAnnouncement()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        request()->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
        ]);

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

    public function updatePolicies()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        PlatformSetting::set('terms_policy', request('terms_policy', ''));
        PlatformSetting::set('privacy_policy', request('privacy_policy', ''));
        PlatformSetting::set('return_policy', request('return_policy', ''));

        return back()->with('success', 'Platform policies updated successfully.');
    }

    public function deleteAccount($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $user = DB::table('users')
            ->where('id', $id)
            ->whereIn('role', ['buyer', 'seller', 'rider', 'logistics'])
            ->first();

        if (!$user) {
            return back()->with('error', 'Account not found.');
        }

        // A hard delete here cascades through orders, order_items, reviews,
        // and messages (all foreign keys to users are cascadeOnDelete) — it
        // would silently wipe out other people's transaction history too, not
        // just this account's. Deactivating achieves the real intent (this
        // person can no longer use BoomBuy) without destroying shared records.
        DB::table('users')
            ->where('id', $id)
            ->update(['status' => 'Deactivated']);

        createNotification(
            $user->id,
            'Account Deactivated',
            'Your BoomBuy account has been deactivated by an administrator.',
            'account_status'
        );

        return back()->with(
            'success',
            $user->name . '\'s account has been deactivated.'
        );
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

        return back()->with(
            'success',
            $user->name . '\'s account status has been set to ' . $status . '.'
        );
    }

    public function reports()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // The view computes all of its report figures itself, straight from
        // the database (see its top @php block) — nothing extra to pass here.
        return view('pages.admin.reports');
    }

    public function applications()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $sellerApplications = DB::table('seller_applications')
            ->join('users', 'users.id', '=', 'seller_applications.user_id')
            ->select('seller_applications.*', 'users.email as user_email')
            ->orderByDesc('seller_applications.created_at')
            ->get();

        $buyerApplications = DB::table('buyer_applications')
            ->join('users', 'users.id', '=', 'buyer_applications.user_id')
            ->select('buyer_applications.*', 'users.email as user_email')
            ->orderByDesc('buyer_applications.created_at')
            ->get();

        $logisticsApplications = DB::table('logistics_applications')
            ->join('users', 'users.id', '=', 'logistics_applications.user_id')
            ->select('logistics_applications.*', 'users.email as user_email')
            ->orderByDesc('logistics_applications.created_at')
            ->get();

        return view(
            'pages.admin.applications',
            compact('sellerApplications', 'buyerApplications', 'logisticsApplications')
        );
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
            ->where('status', 'Picked Up')
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
            ->whereDate('updated_at', now()->toDateString())
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

    public function approveApplication($type, $id)
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

        // Get application before updating
        $application = DB::table($table)
            ->where('id', $id)
            ->first();

        if (!$application) {

            return back()->with(
                'error',
                'Application not found.'
            );
        }

        // Approve application
        $approvalData = [
            'status' => 'Approved',
            'admin_remarks' => null,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ];

        if ($type === 'seller') {
            $approvalData['business_category'] = request('business_category')
                ?: $application->business_category;
        }

        $updated = DB::table($table)
            ->where('id', $id)
            ->update($approvalData);

        if (!$updated) {

            return back()->with(
                'error',
                'Application could not be approved.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if ($type === 'seller') {

            createNotification(
                (int) $application->user_id,
                'Seller Application Approved',
                'Congratulations! Your seller application has been approved. You can now access your seller account.',
                'seller',
                (int) $application->id
            );

        } elseif ($type === 'buyer') {

            createNotification(
                (int) $application->user_id,
                'Account Approved',
                'Congratulations! Your BoomBuy account has been approved. You can now log in.',
                'buyer',
                (int) $application->id
            );

        } elseif ($type === 'logistics') {

            createNotification(
                (int) $application->user_id,
                'Logistics Application Approved',
                'Congratulations! Your Logistics account has been approved. You can now log in.',
                'logistics',
                (int) $application->id
            );
        }

        $applicant = DB::table('users')->where('id', $application->user_id)->first();

        if ($applicant) {

            try {
                Mail::to($applicant->email)->send(
                    new ApplicationStatusMail(
                        $application->full_name ?? $applicant->name,
                        $type,
                        'Approved'
                    )
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with(
            'success',
            ucfirst($type) . ' application approved.'
        );
    }

    public function rejectApplication($type, $id)
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

        $remarks = trim((string) request('admin_remarks'));

        // Kunin muna ang application bago i-update
        $application = DB::table($table)
            ->where('id', $id)
            ->first();

        if (!$application) {
            return back()->with('error', 'Application not found.');
        }

        // Reject application
        $updated = DB::table($table)
            ->where('id', $id)
            ->update([
                'status' => 'Rejected',
                'admin_remarks' => $remarks !== ''
                    ? $remarks
                    : null,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with(
                'error',
                'Application could not be rejected.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if ($type === 'seller') {

            $message = $remarks !== ''
                ? 'Your seller application was rejected. Admin remarks: ' . $remarks
                : 'Your seller application was rejected. Please review your application and try again.';

            createNotification(
                $application->user_id,
                'Seller Application Rejected',
                $message,
                'seller',
                $application->id
            );

        } elseif ($type === 'buyer') {

            $message = $remarks !== ''
                ? 'Your BoomBuy account application was rejected. Admin remarks: ' . $remarks
                : 'Your BoomBuy account application was rejected. Please contact support for more information.';

            createNotification(
                $application->user_id,
                'Account Application Rejected',
                $message,
                'buyer',
                $application->id
            );

        } elseif ($type === 'logistics') {

            $message = $remarks !== ''
                ? 'Your Logistics application was rejected. Admin remarks: ' . $remarks
                : 'Your Logistics application was rejected. Please review your application and try again.';

            createNotification(
                $application->user_id,
                'Logistics Application Rejected',
                $message,
                'logistics',
                $application->id
            );
        }

        $applicant = DB::table('users')->where('id', $application->user_id)->first();

        if ($applicant) {

            try {
                Mail::to($applicant->email)->send(
                    new ApplicationStatusMail(
                        $application->full_name ?? $applicant->name,
                        $type,
                        'Rejected',
                        $remarks !== '' ? $remarks : null
                    )
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with(
            'success',
            ucfirst($type) . ' application rejected.'
        );
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

    public function orders()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        /*
        |--------------------------------------------------------------------------
        | GET ALL ORDERS FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $dbOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->get();

        $orders = $dbOrders->map(function ($order) {

            $order = (array) $order;

            /*
            |--------------------------------------------------------------------------
            | GET ORDER ITEMS
            |--------------------------------------------------------------------------
            */

            $items = DB::table('order_items')
                ->where('order_id', $order['id'])
                ->get();

            $order['items'] = $items->map(function ($item) {

                $item = (array) $item;

                $item['subtotal'] =
                    (float) ($item['price'] ?? 0) *
                    (int) ($item['quantity'] ?? 1);

                return $item;

            })->toArray();

            /*
            |--------------------------------------------------------------------------
            | ADMIN DISPLAY FIELDS
            |--------------------------------------------------------------------------
            */

            $order['total'] =
                (float) ($order['total_amount'] ?? 0);

            $order['date'] =
                $order['created_at'] ?? null;

            $order['buyer_name'] =
                $order['shipping_name'] ?? 'Unknown Buyer';

            $order['buyer_email'] =
                optional(DB::table('users')->where('id', $order['buyer_id'] ?? null)->first())->email;

            $order['address'] =
                $order['shipping_address'] ?? '';

            $order['phone'] =
                $order['shipping_phone'] ?? '';

            $order['payment'] =
                $order['payment_method'] ?? '';

            /*
            |--------------------------------------------------------------------------
            | BUYER RECEIVED STATUS
            |--------------------------------------------------------------------------
            */

            $order['buyer_received_at'] =
                $order['buyer_received_at'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | GET RIDER NAME
            |--------------------------------------------------------------------------
            */

            $order['rider_name'] = null;

            if (!empty($order['rider_id'])) {

                $rider = DB::table('users')
                    ->where('id', $order['rider_id'])
                    ->first();

                if ($rider) {
                    $order['rider_name'] = $rider->name;
                }
            }

            return $order;

        })->toArray();

        return view(
            'pages.admin.orders',
            compact('orders')
        );
    }

    public function orderDetails($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {
            abort(404);
        }

        $order = (array) $order;

        /*
        |--------------------------------------------------------------------------
        | ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {

            $item = (array) $item;

            $item['subtotal'] =
                (float) ($item['price'] ?? 0) *
                (int) ($item['quantity'] ?? 1);

            return $item;

        })->toArray();

        /*
        |--------------------------------------------------------------------------
        | ADMIN DISPLAY FIELDS
        |--------------------------------------------------------------------------
        */

        $order['total'] =
            (float) ($order['total_amount'] ?? 0);

        $order['buyer_name'] =
            $order['shipping_name'] ?? 'Unknown Buyer';

        $order['buyer_email'] =
            optional(DB::table('users')->where('id', $order['buyer_id'] ?? null)->first())->email;

        $order['address'] =
            $order['shipping_address'] ?? '';

        $order['phone'] =
            $order['shipping_phone'] ?? '';

        $order['payment'] =
            $order['payment_method'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | RIDER
        |--------------------------------------------------------------------------
        */

        $order['rider_name'] = null;

        if (!empty($order['rider_id'])) {

            $rider = DB::table('users')
                ->where('id', $order['rider_id'])
                ->first();

            if ($rider) {
                $order['rider_name'] = $rider->name;
            }
        }

        return view(
            'pages.admin.order-details',
            compact('order')
        );
    }

    public function updateOrderStatus($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $status = trim(request('status'));

        // Matches the real statuses the seller/rider/logistics pipeline
        // actually uses elsewhere in the app — "On the Way" was never a
        // real status anywhere else and could never be reached again once
        // set here.
        $allowedStatuses = [
            'Pending',
            'Processing',
            'Ready for Pickup',
            'Assigned',
            'Picked Up',
            'At Sorting Center',
            'Assigned for Delivery',
            'Out for Delivery',
            'Delivered',
            'Delivery Failed',
            'Returned to Seller',
            'Cancelled',
        ];

        if (!in_array($status, $allowedStatuses)) {

            return back()->with(
                'error',
                'Invalid order status.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE ACTUAL DATABASE ORDER
        |--------------------------------------------------------------------------
        */

        $order = DB::table('orders')
            ->where('id', $id)
            ->first();

        if (!$order) {

            return back()->with(
                'error',
                'Order not found.'
            );
        }

        // Delivered/Cancelled/Returned to Seller are finalized outcomes owned
        // by their own dedicated flows (buyer confirmation, cancellation
        // reasons, the Logistics return flow) — letting this generic admin
        // override reopen them would desync the timestamps and history those
        // flows rely on (e.g. buyer_received_at, sorting_center_received_at).
        if (in_array($order->status, ['Delivered', 'Cancelled', 'Returned to Seller'])) {

            return back()->with(
                'error',
                'This order is finalized (' . $order->status . ') and can no longer be changed here.'
            );
        }

        $update = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'Cancelled') {
            $update['cancelled_by'] = 'admin';
            $update['cancelled_at'] = now();
            $update['cancellation_reason'] = 'Cancelled by an administrator.';
        }

        $updated = DB::table('orders')
            ->where('id', $id)
            ->where('status', $order->status)
            ->update($update);

        if (!$updated) {
            return back()->with('error', 'This order was just updated. Please refresh and try again.');
        }

        // Stock only comes back here if the items were still with the seller;
        // after pickup it returns via the Returned to Seller flow instead.
        if ($status === 'Cancelled') {
            OrderStock::cancelled((int) $id, $order->status);
        }

        createNotification(
            (int) $order->buyer_id,
            'Order Status Updated',
            'Your order #' . $id . ' status was updated to "' . $status . '" by an administrator.',
            'order',
            (int) $id
        );

        /*
        |--------------------------------------------------------------------------
        | KEEP SESSION ORDERS SYNCED IF THEY EXIST
        |--------------------------------------------------------------------------
        */

        $orders = session()->get('orders', []);

        foreach ($orders as $index => $orderData) {

            if ((string) ($orderData['id'] ?? '') === (string) $id) {

                $orders[$index]['status'] = $status;

                break;
            }
        }

        session()->put('orders', $orders);

        return back()->with(
            'success',
            'Order status updated to ' . $status . '.'
        );
    }

    public function notifications()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $adminUser = User::where(
            'email',
            'admin@boombuy.com'
        )->first();

        $notifications = $adminUser
            ? Notification::where(
                'user_id',
                $adminUser->id
            )
            ->orderByDesc('created_at')
            ->get()
            : collect();

        $unreadCount = $notifications
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
                    ->where('email', 'admin@boombuy.com')
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

        $sellers = DB::table('seller_applications')
            ->join('users', 'users.id', '=', 'seller_applications.user_id')
            ->where('seller_applications.status', 'Approved')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.status as account_status',
                'seller_applications.business_category'
            )
            ->orderBy('users.name')
            ->get()
            ->map(function ($seller) {

                $products = Product::where('seller_id', $seller->user_id)->get();

                // business_category is stored as a slug ("electronics") while
                // products.category is stored as its Title Case label
                // ("Electronics") — convert the slug to that same label before
                // comparing, otherwise every product would always "mismatch".
                $registeredCategoryLabel = Categories::LIST[$seller->business_category] ?? $seller->business_category;

                $mismatches = $seller->business_category
                    ? $products->filter(function ($product) use ($registeredCategoryLabel) {
                        return $product->category !== $registeredCategoryLabel;
                    })
                    : collect();

                $flagged = $products->where('is_flagged', true);

                return [
                    'user_id' => $seller->user_id,
                    'name' => $seller->name,
                    'email' => $seller->email,
                    'account_status' => $seller->account_status,
                    'business_category' => $seller->business_category,
                    'total_products' => $products->count(),
                    'mismatches' => $mismatches,
                    'flagged' => $flagged,
                ];
            });

        $totalMismatches = $sellers->sum(fn ($s) => $s['mismatches']->count());
        $totalFlagged = $sellers->sum(fn ($s) => $s['flagged']->count());

        return view(
            'pages.admin.compliance',
            compact('sellers', 'totalMismatches', 'totalFlagged')
        );
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

        $seller = DB::table('users')->where('id', $userId)->first();

        if (!$seller) {
            return back()->with('error', 'Seller not found.');
        }

        $message = trim((string) request('warning_message'));

        createNotification(
            $userId,
            'Compliance Warning',
            $message !== '' ? $message : 'Your seller account has received a compliance warning from BoomBuy admin. Please review your product listings.',
            'compliance_warning'
        );

        return back()->with('success', 'Warning sent to ' . $seller->name . '.');
    }
}
