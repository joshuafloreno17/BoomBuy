<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| BOOMBUY - HELPER FUNCTIONS
|--------------------------------------------------------------------------
|
| Wrapped in function_exists() guards. This is what actually prevents the
| "Cannot redeclare function requireUserRole()" fatal error — Laravel can
| sometimes boot the application twice within the same process (this
| happens during `config:cache`, for example, which needs a "fresh" boot
| to build the cached config). Without the guard, a function declared
| directly in a routes file will blow up the second time it's loaded.
|
*/

if (!function_exists('requireUserRole')) {
    function requireUserRole($role)
    {
        $user = session()->get('user');

        if (!$user || ($user['role'] ?? '') !== $role) {
            return redirect()->route('login');
        }

        return $user;
    }
}

if (!function_exists('currentMessagingUser')) {
    // Identifies "who is logged in" for the messaging feature, whether
    // that's a buyer/seller/rider (session('user')) or the admin
    // (session('admin_logged_in'), backed by the admin@boombuy.com row).
    function currentMessagingUser()
    {
        $user = session()->get('user');

        if ($user) {
            return $user;
        }

        if (session()->get('admin_logged_in')) {

            $admin = \App\Models\User::where('email', 'admin@boombuy.com')->first();

            if ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'role' => 'admin',
                ];
            }
        }

        return null;
    }
}

if (!function_exists('cartSummary')) {
    function cartSummary($cart)
    {
        $productIds = array_map(
            fn ($key) => parseCartKey($key)[0],
            array_keys($cart)
        );

        $databaseProducts = \App\Models\Product::whereIn('id', array_unique($productIds))
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $totalItems = 0;

        foreach ($cart as $cartKey => $quantity) {

            [$productId, $variationId] = parseCartKey($cartKey);

            $product = $databaseProducts->get($productId);

            if ($product) {

                $unitPrice = (float) $product->price;

                if ($variationId) {

                    $variation = \App\Models\ProductVariation::find($variationId);

                    if ($variation) {
                        $unitPrice += (float) $variation->price_adjustment;
                    }
                }

                $subtotal += $unitPrice * (int) $quantity;
                $totalItems += (int) $quantity;
            }
        }

        return [
            'subtotal' => number_format($subtotal, 2),
            'total_items' => $totalItems,
            'cart_count' => array_sum($cart),
        ];
    }
}

if (!function_exists('parseCartKey')) {

    // Cart lines are keyed "productId:variationId" so that two different
    // variations (colors, sizes...) of the same product are tracked as
    // separate lines instead of merging into one. variationId is 0 when
    // the product has no variation selected.
    function parseCartKey($key)
    {
        $parts = explode(':', (string) $key);

        return [
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
        ];
    }
}

if (!function_exists('generateAndSendOtp')) {
    function generateAndSendOtp($email, $name)
    {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session()->put('otp_code', $otpCode);
        session()->put('otp_email', $email);
        session()->put('otp_expires_at', now()->addMinutes(10));

        try {
            Mail::to($email)->send(new OtpMail($otpCode, $name));
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}

/*
|--------------------------------------------------------------------------
| RIDER PROFILE PHOTO
|--------------------------------------------------------------------------
*/

Route::post('/rider/profile/photo', function (\Illuminate\Http\Request $request) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $request->validate([
        'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $file = $request->file('profile_photo');

    $filename = 'rider_' . $user['id'] . '_' . time() . '.' . $file->getClientOriginalExtension();

    // Save the actual image
    $file->storeAs(
        'profile-photos',
        $filename,
        'public'
    );

    // Save filename permanently in database
    DB::table('users')
        ->where('id', $user['id'])
        ->update([
            'profile_photo' => $filename,
            'updated_at' => now(),
        ]);

    // Also update current login session
    $user['profile_photo'] = $filename;
    session()->put('user', $user);

    return redirect()
        ->route('rider.profile')
        ->with('success', 'Profile picture updated successfully.');

})->name('rider.profile.photo');


Route::get('/login', function () { return view('pages.login'); })->name('login');


Route::post('/login', function () {

    $email = strtolower(trim(request('email')));
    $password = request('password');

    if (
        empty($email) ||
        empty($password)
    ) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Please enter your email and password.'
            );
    }

    // The Admin account isn't a real "role" a user registers for — it's a
    // fixed set of credentials. Detect it here so Admin can use the same
    // login form as everyone else instead of a separate page.
    if ($email === 'admin@boombuy.com' && $password === 'admin123') {

        session()->put('admin_logged_in', true);

        if (!session()->has('admin_notifications')) {
            session()->put('admin_notifications', []);
        }

        \App\Models\User::firstOrCreate(
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

    $user = DB::table('users')
        ->where('email', $email)
        ->first();

    if (!$user) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    if (!Hash::check($password, $user->password)) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    // Suspended/deactivated accounts cannot log in, regardless of role.
    if (($user->status ?? 'Active') !== 'Active') {

        $statusMessage = $user->status === 'Suspended'
            ? 'Your account has been suspended. Please contact BoomBuy support for assistance.'
            : 'Your account has been deactivated. Please contact BoomBuy support for assistance.';

        return back()
            ->withInput()
            ->with('error', $statusMessage);
    }

    // Sellers, riders, and logistics accounts must have an Approved
    // application before they can log in — this is what actually enforces
    // the ID/document verification we collect at registration.
    if (in_array($user->role, ['seller', 'rider', 'logistics'])) {

        $applicationTable = match ($user->role) {
            'seller' => 'seller_applications',
            'rider' => 'rider_applications',
            'logistics' => 'logistics_applications',
        };

        $application = DB::table($applicationTable)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$application || $application->status !== 'Approved') {

            $message = ($application->status ?? null) === 'Rejected'
                ? 'Your ' . $user->role . ' application was not approved. Please contact support for more information.'
                : 'Your ' . $user->role . ' account is still pending verification. We will notify you once it has been approved.';

            return back()
                ->withInput()
                ->with('error', $message);
        }
    }

    // Buyers registered after the admin-approval feature was added also
    // need an Approved application. Buyers with no application row at all
    // are legacy accounts created before this gate existed — they're left
    // alone rather than retroactively locked out.
    if ($user->role === 'buyer') {

        $application = DB::table('buyer_applications')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if ($application && $application->status !== 'Approved') {

            $message = $application->status === 'Rejected'
                ? 'Your account application was not approved. Please contact support for more information.'
                : 'Your account is still pending administrator verification. We will notify you by email once it has been approved.';

            return back()
                ->withInput()
                ->with('error', $message);
        }
    }

    // Remember Me — keep the session alive for 30 days instead of the
    // default lifetime, so the user isn't logged out after a short while.
    if (request('remember')) {
        config(['session.lifetime' => 60 * 24 * 30]);
        config(['session.expire_on_close' => false]);
    }

    session()->put('user', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'profile_photo' => $user->profile_photo,
    ]);

    switch ($user->role) {

        case 'seller':
            return redirect()
                ->route('seller.dashboard')
                ->with('success', 'Welcome to BoomBuy Seller!');

        case 'rider':
            return redirect()
                ->route('rider.dashboard')
                ->with('success', 'Welcome to BoomBuy Rider!');

        case 'buyer':
            return redirect()
                ->route('buyer.dashboard')
                ->with('success', 'Welcome to BoomBuy!');

        case 'logistics':
            return redirect()
                ->route('logistics.dashboard')
                ->with('success', 'Welcome to BoomBuy Logistics!');

        default:
            session()->forget('user');

            return redirect('/login')
                ->with('error', 'Invalid account role.');
    }

})->name('login.submit');


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// ==========================================================
// REGISTER PAGE
// ==========================================================

Route::get('/register', function () {
    return view('register.choose');
})->name('register');


// ==========================================================
// REGISTER - ROLE SELECTION
// ==========================================================

Route::post('/register', function () {

    $role = strtolower(trim(request('role')));

    if (empty($role)) {
        return back()
            ->withInput()
            ->with('error', 'Please select an account type.');
    }

    switch ($role) {

        case 'buyer':
            return redirect()->route('buyer.register');

        case 'seller':
            return redirect()->route('seller.register');

        case 'rider':
            return redirect()->route('rider.apply');

        default:
            return back()
                ->withInput()
                ->with('error', 'Invalid account role.');
    }

})->name('register.submit');


// ==========================================================
// FORGOT PASSWORD PAGE
// ==========================================================

Route::get('/forgot-password', function () {

    return view('pages.forgot-password');

})->name('password.request');


// ==========================================================
// CHECK FORGOT PASSWORD EMAIL
// ==========================================================

Route::post('/forgot-password', function () {

    $email = strtolower(trim(request('email')));

    if (empty($email)) {

        return back()
            ->withInput()
            ->with('error', 'Please enter your email address.');
    }

    $user = User::where('email', $email)->first();

    if (!$user) {

        return back()
            ->withInput()
            ->with(
                'error',
                'No account was found with that email address.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND A ONE-TIME RESET CODE — proves whoever is resetting the password
    | actually controls this email address, instead of trusting the email
    | field alone. Uses its own session keys (not otp_code/otp_email/etc.)
    | so an in-progress registration OTP in the same browser can't collide
    | with a password-reset OTP.
    |--------------------------------------------------------------------------
    */

    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    session([
        'password_reset_user_id' => $user->id,
        'password_reset_otp' => $otpCode,
        'password_reset_expires_at' => now()->addMinutes(10),
    ]);

    try {
        Mail::to($user->email)->send(new \App\Mail\OtpMail($otpCode, $user->name));
    } catch (\Throwable $e) {
        report($e);

        return back()
            ->withInput()
            ->with('error', 'We could not send the verification code right now. Please try again in a moment.');
    }

    return redirect()
        ->route('password.reset')
        ->with(
            'success',
            'We sent a 6-digit code to your email. Enter it below along with your new password.'
        );

})->name('password.email');


// ==========================================================
// RESET PASSWORD PAGE
// ==========================================================

Route::get('/reset-password', function () {

    if (!session()->has('password_reset_user_id')) {

        return redirect()
            ->route('password.request')
            ->with(
                'error',
                'Please enter your email first.'
            );
    }

    return view('pages.reset-password');

})->name('password.reset');


// ==========================================================
// UPDATE NEW PASSWORD
// ==========================================================

Route::post('/reset-password', function () {

    $userId = session('password_reset_user_id');
    $storedOtp = session('password_reset_otp');
    $expiresAt = session('password_reset_expires_at');

    $inputOtp = trim((string) request('otp_code'));
    $password = request('password');
    $confirmation = request('password_confirmation');

    if (!$userId) {

        return redirect()
            ->route('password.request')
            ->with(
                'error',
                'Password reset session expired. Please try again.'
            );
    }

    if (empty($inputOtp)) {

        return back()
            ->withInput()
            ->with('error', 'Please enter the 6-digit code we emailed you.');
    }

    if (
        empty($storedOtp) ||
        $inputOtp !== $storedOtp ||
        !$expiresAt ||
        now()->greaterThan($expiresAt)
    ) {

        return back()
            ->withInput()
            ->with('error', 'Invalid or expired code. Please request a new one.');
    }

    if (empty($password) || empty($confirmation)) {

        return back()
            ->withInput()
            ->with('error', 'Please complete both password fields.');
    }

    if (strlen($password) < 8) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Password must be at least 8 characters.'
            );
    }

    if ($password !== $confirmation) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Passwords do not match.'
            );
    }

    $user = User::find($userId);

    if (!$user) {

        session()->forget(['password_reset_user_id', 'password_reset_otp', 'password_reset_expires_at']);

        return redirect()
            ->route('password.request')
            ->with(
                'error',
                'Account not found.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD IN DATABASE
    |--------------------------------------------------------------------------
    */

    $user->password = Hash::make($password);
    $user->save();

    /*
    |--------------------------------------------------------------------------
    | CLEAR RESET SESSION
    |--------------------------------------------------------------------------
    */

    session()->forget(['password_reset_user_id', 'password_reset_otp', 'password_reset_expires_at']);

    return redirect()
        ->route('login')
        ->with(
            'success',
            'Password successfully changed. You can now login.'
        );

})->name('password.update');



// Logout
Route::post('/logout', function () {

    session()->forget('user');

    return redirect()
        ->route('login')
        ->with('success', 'You have been logged out.');

})->name('logout');


/*
|--------------------------------------------------------------------------
| BUYER
|--------------------------------------------------------------------------
*/

Route::get('/buyer', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS FROM DATABASE ONLY
    |--------------------------------------------------------------------------
    */

    $databaseProducts = Product::latest()->get();

    $products = $databaseProducts->map(function ($product) {

        $slug = Str::slug($product->name);

        return [

            'id' => $product->id,
            'slug' => $slug,
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'description' => $product->description,
            'image' => $product->image,
            'icon' => $product->image ?? '📦',
            'seller_id' => $product->seller_id,
            'rating' => '5.0',
            'reviews' => 0,

        ];

    })->toArray();

    /*
    |--------------------------------------------------------------------------
    | NEWEST PRODUCTS
    |--------------------------------------------------------------------------
    */

    $newestProducts = array_slice(
        $products,
        0,
        8
    );

    return view(
        'pages.buyer.dashboard',
        compact(
            'user',
            'products',
            'newestProducts'
        )
    );

})->name('buyer.dashboard');

/*
|--------------------------------------------------------------------------
| BUYER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/buyer/profile', function () {

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
        compact('user', 'dbUser', 'totalOrders', 'totalSpent')
    );

})->name('buyer.profile');


Route::post('/buyer/profile', function () {

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

})->name('buyer.profile.update');


Route::post('/buyer/profile/photo', function (\Illuminate\Http\Request $request) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $request->validate([
        'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $file = $request->file('profile_photo');

    $filename = 'buyer_' . $user['id'] . '_' . time() . '.' . $file->getClientOriginalExtension();

    $file->storeAs(
        'profile-photos',
        $filename,
        'public'
    );

    DB::table('users')
        ->where('id', $user['id'])
        ->update([
            'profile_photo' => $filename,
            'updated_at' => now(),
        ]);

    $user['profile_photo'] = $filename;
    session()->put('user', $user);

    return back()->with('success', 'Profile picture updated successfully.');

})->name('buyer.profile.photo');


Route::post('/buyer/profile/password', function () {

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

    return back()->with('success', 'Password changed successfully.');

})->name('buyer.profile.password');

/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
*/

Route::get('/wishlist', function () {

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

    return view(
        'pages.buyer.wishlist',
        compact('user', 'products')
    );

})->name('wishlist.index');


Route::post('/wishlist/toggle/{id}', function ($id) {

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

})->name('wishlist.toggle');

/*
|--------------------------------------------------------------------------
| BUYER ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/buyer/orders', function () {
    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $orders = DB::table('orders')
        ->where('buyer_id', $user['id'])
        ->orderByDesc('created_at')
        ->get();

    $orders = $orders->map(function ($order) use ($user) {

        $order = (array) $order;

        // Get order items
        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) use ($order, $user) {

            $item = (array) $item;

            $item['name'] =
                $item['product_name'] ?? 'Product';

            $item['subtotal'] =
                (float) ($item['price'] ?? 0) *
                (int) ($item['quantity'] ?? 1);

            // Get seller name if seller_id exists
            if (!empty($item['seller_id'])) {

                $seller = DB::table('users')
                    ->where('id', $item['seller_id'])
                    ->first();

                $item['seller_name'] =
                    $seller->name ?? null;
            } else {
                $item['seller_name'] = null;
            }

            // Has this buyer already reviewed this product for this order?
            // Without this, the "already rated" badge could never show and
            // the Rate Product form would keep reappearing after rating.
            $item['review'] = null;

            if (!empty($item['product_id'])) {

                $review = DB::table('product_reviews')
                    ->where('buyer_id', $user['id'])
                    ->where('order_id', $order['id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($review) {
                    $item['review'] = (array) $review;
                }
            }

            return $item;

        })->toArray();

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

    return view(
        'pages.buyer.orders',
        compact('user', 'orders')
    );

})->name('buyer.orders');


Route::post('/buyer/orders/{id}/received', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    // Hanapin ang order at siguraduhing sa buyer talaga ito
    $order = DB::table('orders')
        ->where('id', $id)
        ->where('buyer_id', $user['id'])
        ->first();

    if (!$order) {
        return back()->with('error', 'Order not found.');
    }

    // Puwede lang i-confirm kapag Delivered na
    if ($order->status !== 'Delivered') {
        return back()->with(
            'error',
            'You can only confirm an order after it has been delivered.'
        );
    }

    // Huwag nang ulitin kung na-confirm na
    if (!empty($order->buyer_received_at)) {
        return back()->with(
            'error',
            'This order has already been marked as received.'
        );
    }

    // Mark as received by buyer
    DB::table('orders')
        ->where('id', $id)
        ->update([
            'buyer_received_at' => now(),
            'updated_at' => now(),
        ]);

    // Notify every seller who has items in this order
    $sellerIds = DB::table('order_items')
        ->where('order_id', $id)
        ->distinct()
        ->pluck('seller_id');

    foreach ($sellerIds as $sellerId) {

        createNotification(
            $sellerId,
            'Order Received by Buyer',
            "Order #{$id} has been confirmed as received by the buyer.",
            'order_status',
            $id
        );
    }

    return back()->with(
        'success',
        'Order received successfully!'
    );

})->name('buyer.order.received');


Route::post('/buyer/orders/{id}/cancel', function ($id) {

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

    // A buyer can only cancel before the seller has started processing it —
    // once Processing (or later), only the seller can cancel, since work
    // may already be underway.
    if ($order->status !== 'Pending') {
        return back()->with(
            'error',
            'This order can no longer be cancelled — it is already being processed.'
        );
    }

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => 'Cancelled',
            'cancellation_reason' => 'Cancelled by buyer.',
            'updated_at' => now(),
        ]);

    $sellerIds = DB::table('order_items')
        ->where('order_id', $id)
        ->distinct()
        ->pluck('seller_id');

    foreach ($sellerIds as $sellerId) {

        createNotification(
            $sellerId,
            'Order Cancelled by Buyer',
            "Order #{$id} was cancelled by the buyer before processing.",
            'order_status',
            $id
        );
    }

    return back()->with(
        'success',
        'Your order has been cancelled.'
    );

})->name('buyer.order.cancel');


Route::post('/buyer/orders/{orderId}/review/{productId}', function ($orderId, $productId) {
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
})->name('buyer.product.review');


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

// Admin no longer has a separate login page — the unified /login form
// detects the admin credentials automatically. This route is kept only
// because every admin-gated route redirects here by name when the
// session has expired; it just forwards to the unified form.
Route::get('/admin/login', function () {

    return redirect()->route('login');

})->name('admin.login');


Route::post('/admin/login', function () {

    $email = strtolower(trim(request('email')));
    $password = trim(request('password'));

    if (
        $email === 'admin@boombuy.com' &&
        $password === 'admin123'
    ) {

        session()->put('admin_logged_in', true);

        // Initialize Admin notifications
        if (!session()->has('admin_notifications')) {
            session()->put('admin_notifications', []);
        }

        // Make sure a real `users` row exists for the admin account —
        // notifications and messaging both need a real user_id to attach to.
        \App\Models\User::firstOrCreate(
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

})->name('admin.login.submit');


/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/admin', function () {

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

    $totalSales = DB::table('orders')
        ->where('status', 'Delivered')
        ->sum('total_amount');


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
                'slug' => Str::slug($product->name),
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

})->name('admin.dashboard');

/*
|--------------------------------------------------------------------------
| ADMIN LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/admin/logout', function () {

    session()->forget('admin_logged_in');

    return redirect()
        ->route('admin.login')
        ->with('success', 'Admin logged out successfully.');

})->name('admin.logout');


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/products', function () {

    $category = request('category');

    /*
    |--------------------------------------------------------------------------
    | BOOMBUY CATEGORY MAP
    |--------------------------------------------------------------------------
    */

    $categoryMap = [

        'electronics' => [
            'Electronics',
            'Smartphone',
            'Laptop',
            'Audio',
            'Wearable',
            'Accessories',
            'electronics',
            'smartphone',
            'laptop',
            'audio',
            'wearable',
            'accessories',
        ],

        'womens-fashion' => [
            "Women's Fashion",
            'Women',
            "Women's",
            'womens-fashion',
            'women',
        ],

        'mens-fashion' => [
            "Men's Fashion",
            'Men',
            "Men's",
            'mens-fashion',
            'men',
        ],

        'kids-baby' => [
            'Kids & Baby',
            'Kids',
            'Baby',
            'kids-baby',
            'kids',
            'baby',
        ],

        'home-living' => [
            'Home & Living',
            'Home',
            'home-living',
            'home',
        ],

        'sports-outdoors' => [
            'Sports & Outdoors',
            'Sports',
            'sports-outdoors',
            'sports',
        ],

        'beauty-personal-care' => [
            'Beauty & Personal Care',
            'Beauty',
            'beauty-personal-care',
            'beauty',
        ],

        'food-beverages' => [
            'Food & Beverages',
            'Food',
            'food-beverages',
            'food',
        ],

        'automotive' => [
            'Automotive',
            'automotive',
        ],

        'office-school' => [
            'Office & School',
            'Office',
            'School',
            'office-school',
            'office',
            'school',
        ],

        'pet-supplies' => [
            'Pet Supplies',
            'Pets',
            'Pet',
            'pet-supplies',
            'pet',
        ],

        'toys-games-hobbies' => [
            'Toys, Games & Hobbies',
            'Toys',
            'Games',
            'Hobbies',
            'toys-games-hobbies',
            'toys',
        ],

        'jewelry-accessories' => [
            'Jewelry & Accessories',
            'Jewelry',
            'Accessories',
            'jewelry-accessories',
            'jewelry',
            'accessories',
        ],

        'shoes' => [
            'Shoes',
            'shoes',
        ],

        'tools-home-improvement' => [
            'Tools & Home Improvement',
            'Tools',
            'Home Improvement',
            'tools-home-improvement',
            'tools',
        ],

        'garden-outdoor' => [
            'Garden & Outdoor',
            'Garden',
            'Outdoor',
            'garden-outdoor',
            'garden',
        ],

    ];


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCTS
    |--------------------------------------------------------------------------
    */

    $search = trim((string) request('search'));

    $databaseProducts = Product::latest()
        ->where('is_flagged', false)
        ->where('is_archived', false)
        ->when(
            $category && isset($categoryMap[$category]),
            function ($query) use ($category, $categoryMap) {

                $query->whereIn(
                    'category',
                    $categoryMap[$category]
                );

            }
        )
        ->when(
            $search !== '',
            function ($query) use ($search) {

                $query->where(
                    'name',
                    'like',
                    '%' . $search . '%'
                );

            }
        )
        ->get();


    /*
    |--------------------------------------------------------------------------
    | FORMAT PRODUCTS + REAL REVIEWS
    |--------------------------------------------------------------------------
    */

    $products = $databaseProducts->map(function ($product) {

        // Get actual reviews for this product
        $reviewData = DB::table('product_reviews')
            ->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
            ->first();

        $reviewCount = (int) ($reviewData->review_count ?? 0);

        $averageRating = $reviewCount > 0
            ? round((float) $reviewData->average_rating, 1)
            : 0;


        return [

            'id' => $product->id,
            'slug' => Str::slug($product->name),
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'description' => $product->description,
            'image' => $product->image,
            'icon' => $product->image ?? '📦',
            'seller_id' => $product->seller_id,

            // REAL rating from product_reviews
            'rating' => $averageRating,

            // REAL review count from product_reviews
            'reviews' => $reviewCount,

        ];

    })->toArray();


    /*
    |--------------------------------------------------------------------------
    | WISHLIST STATE (empty for guests)
    |--------------------------------------------------------------------------
    */

    $sessionUser = session()->get('user');

    $wishlistedIds = ($sessionUser && ($sessionUser['role'] ?? '') === 'buyer')
        ? DB::table('wishlists')
            ->where('user_id', $sessionUser['id'])
            ->pluck('product_id')
            ->toArray()
        : [];


    /*
    |--------------------------------------------------------------------------
    | RETURN SHOP PAGE
    |--------------------------------------------------------------------------
    */

    return view(
        'pages.products',
        compact('products', 'wishlistedIds')
    );

})->name('products');


/*
|--------------------------------------------------------------------------
| ADMIN PRODUCTS LIST
|--------------------------------------------------------------------------
*/

Route::get('/admin/products', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $products = Product::latest()
        ->get()
        ->map(function ($product) {

            return [
                'id' => $product->id,
                'slug' => Str::slug($product->name),
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'icon' => $product->image,
            ];

        })
        ->toArray();

    return view('pages.admin-products', compact('products'));

})->name('admin.products');


/*
|--------------------------------------------------------------------------
| ADMIN ADD PRODUCT
|--------------------------------------------------------------------------
*/

Route::get('/admin/products/create', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    return view('pages.admin-add-product');

})->name('admin.products.create');


Route::post('/admin/products/store', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $name = trim(request('name'));
    $category = trim(request('category'));
    $price = (float) request('price');
    $stock = (int) request('stock');
    $image = request()->file('image');
    $description = trim(request('description'));

    if (
        empty($name) ||
        empty($category) ||
        $price <= 0 ||
        $stock < 0 ||
        !$image ||
        empty($description)
    ) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Please complete all product fields.'
            );
    }

    $categoryNames = [
        'smartphone' => 'Smartphone',
        'laptop' => 'Laptop',
        'audio' => 'Audio',
        'wearable' => 'Wearable',
        'accessories' => 'Accessories',
    ];

    $categoryName =
        $categoryNames[strtolower($category)]
        ?? ucfirst($category);

    // Admin-added products aren't tied to a seller, so the duplicate-name
    // check is platform-wide rather than scoped to a seller_id.
    if (Product::where('name', $name)->exists()) {

        return back()
            ->withInput()
            ->with(
                'error',
                'A product with this name already exists.'
            );
    }

    $imagePath = $image->store('products', 'public');

    $product = Product::create([
        'seller_id' => null,
        'name' => $name,
        'category' => $categoryName,
        'price' => $price,
        'stock' => $stock,
        'description' => $description,
        'image' => $imagePath,
    ]);

    return redirect()
        ->route('admin.products')
        ->with(
            'success',
            $product->name . ' has been added successfully!'
        );

})->name('admin.products.store');


/*
|--------------------------------------------------------------------------
| ADMIN ACCOUNTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/accounts', function () {

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

})->name('admin.accounts');


Route::get('/admin/settings', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $announcements = \App\Models\PlatformAnnouncement::orderByDesc('created_at')->get();

    $termsPolicy = \App\Models\PlatformSetting::get('terms_policy');
    $privacyPolicy = \App\Models\PlatformSetting::get('privacy_policy');
    $returnPolicy = \App\Models\PlatformSetting::get('return_policy');
    $commissionRate = \App\Models\PlatformSetting::get('commission_rate', '10');
    $deliveryFee = \App\Models\PlatformSetting::get('delivery_fee', '50');

    return view(
        'pages.admin.settings',
        compact('announcements', 'termsPolicy', 'privacyPolicy', 'returnPolicy', 'commissionRate', 'deliveryFee')
    );

})->name('admin.settings');


Route::post('/admin/settings/commission', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $rate = request()->validate([
        'commission_rate' => 'required|numeric|min:0|max:100',
    ])['commission_rate'];

    \App\Models\PlatformSetting::set('commission_rate', (string) $rate);

    return back()->with('success', 'Commission rate updated to ' . $rate . '%.');

})->name('admin.settings.commission.update');


Route::post('/admin/settings/delivery-fee', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $fee = request()->validate([
        'delivery_fee' => 'required|numeric|min:0',
    ])['delivery_fee'];

    \App\Models\PlatformSetting::set('delivery_fee', (string) $fee);

    return back()->with('success', 'Rider delivery fee updated to ₱' . $fee . '.');

})->name('admin.settings.delivery-fee.update');


Route::post('/admin/settings/announcements', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    request()->validate([
        'title' => 'required|string|max:150',
        'message' => 'required|string|max:2000',
    ]);

    \App\Models\PlatformAnnouncement::create([
        'title' => request('title'),
        'message' => request('message'),
        'is_active' => true,
    ]);

    return back()->with('success', 'Announcement posted successfully.');

})->name('admin.settings.announcements.store');


Route::post('/admin/settings/announcements/{id}/toggle', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $announcement = \App\Models\PlatformAnnouncement::find($id);

    if (!$announcement) {
        return back()->with('error', 'Announcement not found.');
    }

    $announcement->update(['is_active' => !$announcement->is_active]);

    return back()->with(
        'success',
        'Announcement is now ' . ($announcement->is_active ? 'active' : 'hidden') . '.'
    );

})->name('admin.settings.announcements.toggle');


Route::delete('/admin/settings/announcements/{id}', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    \App\Models\PlatformAnnouncement::where('id', $id)->delete();

    return back()->with('success', 'Announcement deleted.');

})->name('admin.settings.announcements.delete');


Route::post('/admin/settings/policies', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    \App\Models\PlatformSetting::set('terms_policy', request('terms_policy', ''));
    \App\Models\PlatformSetting::set('privacy_policy', request('privacy_policy', ''));
    \App\Models\PlatformSetting::set('return_policy', request('return_policy', ''));

    return back()->with('success', 'Platform policies updated successfully.');

})->name('admin.settings.policies.update');


Route::delete('/admin/accounts/{id}', function ($id) {

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

    DB::table('users')
        ->where('id', $id)
        ->delete();

    return back()->with(
        'success',
        $user->name . ' account has been deleted successfully.'
    );

})->name('admin.accounts.delete');


Route::post('/admin/accounts/{id}/status', function ($id) {

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

})->name('admin.accounts.status');


/*
|--------------------------------------------------------------------------
| ADMIN REPORTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/reports', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    // The view computes all of its report figures itself, straight from
    // the database (see its top @php block) — nothing extra to pass here.
    return view('pages.admin.reports');

})->name('admin.reports');

/*
|--------------------------------------------------------------------------
| ADMIN — SELLER, BUYER & LOGISTICS APPLICATIONS
|
| Rider applications moved to Logistics (see logistics.riders) — this
| screen now covers the other three roles that still need Admin approval.
|--------------------------------------------------------------------------
*/

Route::get('/admin/applications', function () {

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

})->name('admin.applications');


/*
|--------------------------------------------------------------------------
| ADMIN — LOGISTICS OVERVIEW (READ-ONLY)
|
| Rider vetting and parcel/sorting-center operations are fully owned by
| the Logistics role (see logistics.riders / logistics.parcels) — Admin
| has no action buttons here, only visibility into what's happening.
|--------------------------------------------------------------------------
*/

Route::get('/admin/logistics', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $riderApplications = DB::table('rider_applications')
        ->join('users', 'users.id', '=', 'rider_applications.user_id')
        ->select('rider_applications.*', 'users.email as user_email', 'users.status as account_status')
        ->orderByDesc('rider_applications.created_at')
        ->get();

    $riderAreas = \App\Models\RiderArea::whereIn(
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

})->name('admin.logistics');


Route::post('/admin/applications/{type}/{id}/approve', function ($type, $id) {

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
                new \App\Mail\ApplicationStatusMail(
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

})->name('admin.applications.approve');


Route::post('/admin/applications/{type}/{id}/reject', function ($type, $id) {

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
                new \App\Mail\ApplicationStatusMail(
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

})->name('admin.applications.reject');


Route::get('/admin/applications/{type}/{id}/document/{field}', function ($type, $id, $field) {

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

})->name('admin.applications.document');

/*
|--------------------------------------------------------------------------
| ADMIN ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/admin/orders', function () {

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

})->name('admin.orders');

Route::get('/admin/order/{id}', function ($id) {

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

})->name('admin.order.details');

Route::post('/admin/order/{id}/status', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $status = trim(request('status'));

    $allowedStatuses = [
        'Pending',
        'Processing',
        'Ready for Pickup',
        'Picked Up',
        'On the Way',
        'Out for Delivery',
        'Delivered',
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

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

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

})->name('admin.order.status');



/*
|--------------------------------------------------------------------------
| SELLER
|--------------------------------------------------------------------------
*/

Route::get('/seller', function () {

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
    )->where('is_archived', false)->latest()->get();

    $archivedProducts = Product::where(
        'seller_id',
        $user['id']
    )->where('is_archived', true)->latest()->get();


    /*
    |--------------------------------------------------------------------------
    | ALL ORDERS
    |--------------------------------------------------------------------------
    */

    $allOrders = session()->get('orders', []);

    $sellerOrders = [];

    /*
    |--------------------------------------------------------------------------
    | FIND ORDERS THAT CONTAIN THIS SELLER'S PRODUCTS
    |--------------------------------------------------------------------------
    */

    foreach ($allOrders as $order) {

        $sellerItems = [];

        foreach (($order['items'] ?? []) as $item) {

            if (
                ($item['seller_id'] ?? null) ===
                ($user['id'] ?? null)
            ) {

                $sellerItems[] = $item;
            }
        }

        /*
        | If this order contains the seller's products,
        | include it in the seller's orders.
        */

        if (!empty($sellerItems)) {

            $sellerOrder = $order;

            // Only show this seller's items
            $sellerOrder['items'] = $sellerItems;

            // Calculate seller's portion of the order
            $sellerOrder['seller_total'] =
                array_sum(
                    array_column(
                        $sellerItems,
                        'subtotal'
                    )
                );

            $sellerOrders[] = $sellerOrder;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SELLER STATISTICS
    |--------------------------------------------------------------------------
    */

    // Total Products
    $totalProducts = count($products);

    // Total Orders
    $totalOrders = count($sellerOrders);

    // Pending Orders
    $pendingOrders = 0;

    // Total Sales
    $totalSales = 0;


    foreach ($sellerOrders as $order) {

        $status =
            $order['status'] ?? 'Pending';


        /*
        |--------------------------------------------------------------------------
        | PENDING / PROCESSING
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'Pending' ||
            $status === 'Processing'
        ) {

            $pendingOrders++;
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERED SALES ONLY
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Use seller_total instead of order['total']
        | because order['total'] may include products
        | belonging to other sellers.
        |
        */

        if ($status === 'Delivered') {

            $totalSales +=
                (float) (
                    $order['seller_total'] ?? 0
                );
        }
    }


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
            'sellerOrders',
            'totalProducts',
            'totalOrders',
            'pendingOrders',
            'totalSales',
            'orderStatusBreakdown',
            'salesTrend'
        )
    );


})->name('seller.dashboard');

/*
|--------------------------------------------------------------------------
| SELLER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/seller/profile', function () {

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

    return view(
        'pages.seller.profile',
        compact('user', 'dbUser', 'application', 'totalProducts', 'totalSales')
    );

})->name('seller.profile');


Route::post('/seller/profile', function () {

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

})->name('seller.profile.update');


Route::post('/seller/profile/photo', function (\Illuminate\Http\Request $request) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $request->validate([
        'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $file = $request->file('profile_photo');

    $filename = 'seller_' . $user['id'] . '_' . time() . '.' . $file->getClientOriginalExtension();

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

})->name('seller.profile.photo');


Route::post('/seller/profile/password', function () {

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

    return back()->with('success', 'Password changed successfully.');

})->name('seller.profile.password');

/*
|--------------------------------------------------------------------------
| SELLER — CUSTOMER FEEDBACK / REVIEWS
|--------------------------------------------------------------------------
*/

Route::get('/seller/reviews', function () {

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

})->name('seller.reviews');


Route::post('/seller/reviews/{id}/reply', function ($id) {

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

})->name('seller.reviews.reply');


Route::get('/seller/reports', function () {

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

    $totalSales = (float) $deliveredQuery->sum(DB::raw('order_items.price * order_items.quantity'));

    $totalOrders = (clone $baseQuery)->distinct('order_items.order_id')->count('order_items.order_id');

    $deliveredOrders = (clone $deliveredQuery)->distinct('order_items.order_id')->count('order_items.order_id');

    $averageOrder = $deliveredOrders > 0 ? $totalSales / $deliveredOrders : 0;

    $commissionRate = (float) \App\Models\PlatformSetting::get('commission_rate', '10');
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
            'productSales',
            'dailySales'
        )
    );

})->name('seller.reports');


Route::get('/seller/vouchers', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $vouchers = \App\Models\Voucher::where('seller_id', $user['id'])
        ->orderByDesc('created_at')
        ->get();

    return view(
        'pages.seller.vouchers',
        compact('user', 'vouchers')
    );

})->name('seller.vouchers');


Route::post('/seller/vouchers', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    request()->validate([
        'code' => 'required|string|max:30|unique:vouchers,code',
        'discount_type' => 'required|in:percentage,fixed',
        'discount_value' => 'required|numeric|min:0.01',
        'min_order_amount' => 'nullable|numeric|min:0',
        'max_uses' => 'nullable|integer|min:1',
        'expires_at' => 'nullable|date',
    ]);

    \App\Models\Voucher::create([
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

})->name('seller.vouchers.store');


Route::post('/seller/vouchers/{id}/toggle', function ($id) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $voucher = \App\Models\Voucher::where('id', $id)->where('seller_id', $user['id'])->first();

    if (!$voucher) {
        abort(404);
    }

    $voucher->update(['is_active' => !$voucher->is_active]);

    return back()->with('success', 'Voucher updated.');

})->name('seller.vouchers.toggle');


Route::delete('/seller/vouchers/{id}', function ($id) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    \App\Models\Voucher::where('id', $id)->where('seller_id', $user['id'])->delete();

    return back()->with('success', 'Voucher deleted.');

})->name('seller.vouchers.delete');


Route::get('/seller/products/{id}/variations', function ($id) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

    if (!$product) {
        abort(404);
    }

    $variations = \App\Models\ProductVariation::where('product_id', $id)
        ->orderBy('variation_type')
        ->orderBy('variation_value')
        ->get();

    return view(
        'pages.seller.variations',
        compact('user', 'product', 'variations')
    );

})->name('seller.products.variations');


Route::post('/seller/products/{id}/variations', function ($id) {

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
        'price_adjustment' => 'nullable|numeric',
        'stock' => 'required|integer|min:0',
        'image' => 'nullable|image|max:4096',
    ]);

    $imagePath = null;

    if (request()->hasFile('image')) {
        $imagePath = request()->file('image')->store('variations', 'public');
    }

    \App\Models\ProductVariation::create([
        'product_id' => $product->id,
        'variation_type' => request('variation_type'),
        'variation_value' => request('variation_value'),
        'price_adjustment' => request('price_adjustment', 0),
        'stock' => request('stock'),
        'image' => $imagePath,
    ]);

    return back()->with('success', 'Variation added successfully.');

})->name('seller.products.variations.store');


Route::delete('/seller/products/{id}/variations/{variationId}', function ($id, $variationId) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $product = Product::where('id', $id)->where('seller_id', $user['id'])->first();

    if (!$product) {
        abort(404);
    }

    $variation = \App\Models\ProductVariation::where('id', $variationId)
        ->where('product_id', $product->id)
        ->first();

    if ($variation) {

        if ($variation->image) {
            Storage::disk('public')->delete($variation->image);
        }

        $variation->delete();
    }

    return back()->with('success', 'Variation removed.');

})->name('seller.products.variations.delete');

/*
|--------------------------------------------------------------------------
| SELLER ADD PRODUCT
|--------------------------------------------------------------------------
*/

Route::get('/seller/products/create', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    return view('pages.seller.add-product', compact('user'));

})->name('seller.products.create');


Route::get('/seller/add-product', function () {

    return redirect()->route('seller.products.create');

});



/*
|--------------------------------------------------------------------------
| SELLER SAVE PRODUCT
|--------------------------------------------------------------------------
*/

Route::post('/seller/products/store', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $name = trim(request('name'));
    $category = trim(request('category'));
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
    $categoryNames = [
        'electronics' => 'Electronics',
        'womens-fashion' => "Women's Fashion",
        'mens-fashion' => "Men's Fashion",
        'kids-baby' => 'Kids & Baby',
        'home-living' => 'Home & Living',
        'sports-outdoors' => 'Sports & Outdoors',
        'beauty-personal-care' => 'Beauty & Personal Care',
        'food-beverages' => 'Food & Beverages',
        'automotive' => 'Automotive',
        'office-school' => 'Office & School',
        'pet-supplies' => 'Pet Supplies',
        'toys-games-hobbies' => 'Toys, Games & Hobbies',
        'jewelry-accessories' => 'Jewelry & Accessories',
        'shoes' => 'Shoes',
        'tools-home-improvement' => 'Tools & Home Improvement',
        'garden-outdoor' => 'Garden & Outdoor',
    ];

    if (!array_key_exists(strtolower($category), $categoryNames)) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Please select a valid product category.'
            );
    }

    $categoryName = $categoryNames[strtolower($category)];

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

    $extension = strtolower(
        $image->getClientOriginalExtension()
    );

    if (!in_array($extension, [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ])) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Product image must be JPG, JPEG, PNG, or WEBP.'
            );
    }

    if ($image->getSize() > 5 * 1024 * 1024) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Product image must not be larger than 5MB.'
            );
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

    foreach ($submittedVariations as $variation) {

        $type = trim($variation['type'] ?? '');
        $value = trim($variation['value'] ?? '');

        if (empty($type) || empty($value)) {
            continue;
        }

        \App\Models\ProductVariation::create([
            'product_id' => $product->id,
            'variation_type' => $type,
            'variation_value' => $value,
            'price_adjustment' => (float) ($variation['price_adjustment'] ?? 0),
            'stock' => (int) ($variation['stock'] ?? 0),
        ]);
    }

    return redirect()
        ->route('seller.dashboard')
        ->with(
            'success',
            $name . ' has been added to your store!'
        );

})->name('seller.products.store');


/*
|--------------------------------------------------------------------------
| SELLER ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/seller/orders', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ORDERS THAT CONTAIN THIS SELLER'S PRODUCTS
    |--------------------------------------------------------------------------
    */

    $orders = DB::table('orders')
        ->join(
            'order_items',
            'orders.id',
            '=',
            'order_items.order_id'
        )
        ->where('order_items.seller_id', $user['id'])
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
        ->distinct()
        ->orderByDesc('orders.created_at')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | ADD SELLER ITEMS TO EACH ORDER
    |--------------------------------------------------------------------------
    */

    foreach ($orders as $order) {

        $order->items = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('seller_id', $user['id'])
            ->get();

        $order->seller_total = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    $returnRequests = DB::table('return_refund_requests')
        ->join('orders', 'orders.id', '=', 'return_refund_requests.order_id')
        ->join('order_items', 'order_items.id', '=', 'return_refund_requests.order_item_id')
        ->where('return_refund_requests.seller_id', $user['id'])
        ->select(
            'return_refund_requests.*',
            'orders.shipping_name',
            'orders.shipping_phone',
            'order_items.product_name'
        )
        ->orderByDesc('return_refund_requests.created_at')
        ->get();

    return view(
        'pages.seller.orders',
        compact('user', 'orders', 'returnRequests')
    );

})->name('seller.orders');


/*
|--------------------------------------------------------------------------
| SELLER ORDER DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/seller/order/{id}', function ($id) {

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

    return view(
        'pages.seller.order-details',
        compact('user', 'order', 'assignedRider')
    );

})->name('seller.order.details');


Route::get('/seller/order/{id}/waybill', function ($id) {

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

})->name('seller.order.waybill');


/*
|--------------------------------------------------------------------------
| SELLER UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

Route::post('/seller/order/{id}/status', function ($id) {

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

    // Once Ready for Pickup, seller can no longer update it
    if ($order->status === 'Ready for Pickup') {

        return back()->with(
            'error',
            'This order is already Ready for Pickup and can no longer be updated by the seller.'
        );
    }

    // Delivered orders cannot be updated by seller
    if ($order->status === 'Delivered') {

        return back()->with(
            'error',
            'Delivered orders cannot be updated by the seller.'
        );
    }

    // ==============================
    // UPDATE ORDER STATUS
    // ==============================

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => $status,
            'cancellation_reason' => $status === 'Cancelled' ? $reason : null,
            'updated_at' => now(),
        ]);

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

})->name('seller.order.status');


Route::post('/seller/order/{id}/confirm-pickup', function ($id) {

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

    DB::table('orders')->where('id', $id)->update([
        'seller_confirmed_pickup_at' => now(),
    ]);

    return back()->with('success', 'Rider pickup confirmed.');

})->name('seller.order.confirm-pickup');

/*
|--------------------------------------------------------------------------
| RIDER
|--------------------------------------------------------------------------
*/

Route::get('/rider', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;


    // ==========================================
    // AVAILABLE ORDERS
    // ==========================================

    $availableOrders = DB::table('orders')
        ->where('status', 'Ready for Pickup')
        ->whereNull('rider_id')
        ->orderByDesc('created_at')
        ->get();

    $availableOrders = $availableOrders->map(function ($order) {

        $order = (array) $order;

        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {
            return (array) $item;
        })->toArray();

        $order['total'] = (float) $order['total_amount'];
        $order['buyer_name'] = $order['shipping_name'];
        $order['address'] = $order['shipping_address'];
        $order['phone'] = $order['shipping_phone'];
        $order['payment'] = $order['payment_method'];

        return $order;

    })->toArray();


    // ==========================================
    // MY DELIVERIES
    // ==========================================

    $myDeliveries = DB::table('orders')
        ->where('rider_id', $riderId)
        ->orderByDesc('created_at')
        ->get();

    $myDeliveries = $myDeliveries->map(function ($order) {

        $order = (array) $order;

        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {
            return (array) $item;
        })->toArray();

        $order['total'] = (float) $order['total_amount'];
        $order['buyer_name'] = $order['shipping_name'];
        $order['address'] = $order['shipping_address'];
        $order['phone'] = $order['shipping_phone'];
        $order['payment'] = $order['payment_method'];

        return $order;

    })->toArray();


    // ==========================================
    // ITEMS FOR DELIVERY (assigned by the Sorting Center for the
    // final-mile leg — may be a different rider than the one who
    // picked the parcel up from the seller)
    // ==========================================

    $myDeliveryAssignments = DB::table('orders')
        ->where('delivery_rider_id', $riderId)
        ->whereIn('status', ['Assigned for Delivery', 'Out for Delivery'])
        ->orderByDesc('updated_at')
        ->get();

    $myDeliveryAssignments = $myDeliveryAssignments->map(function ($order) {

        $order = (array) $order;

        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {
            return (array) $item;
        })->toArray();

        $order['total'] = (float) $order['total_amount'];
        $order['buyer_name'] = $order['shipping_name'];
        $order['address'] = $order['shipping_address'];
        $order['phone'] = $order['shipping_phone'];
        $order['payment'] = $order['payment_method'];

        return $order;

    })->toArray();


    // ==========================================
    // OUT FOR DELIVERY
    // ==========================================

    $inTransit = array_values(
        array_filter(
            $myDeliveries,
            function ($order) {

                return ($order['status'] ?? '') === 'Out for Delivery';

            }
        )
    );


    // ==========================================
    // DELIVERED
    // ==========================================

    $delivered = array_values(
        array_filter(
            $myDeliveries,
            function ($order) {

                return ($order['status'] ?? '') === 'Delivered';

            }
        )
    );


    // ==========================================
    // TOTAL COUNT
    // ==========================================

    $totalCount =
        count($availableOrders) +
        count($myDeliveries);


    // ==========================================
    // RIDER DASHBOARD
    // ==========================================

    return view(
        'pages.rider.dashboard',
        compact(
            'user',
            'availableOrders',
            'myDeliveries',
            'myDeliveryAssignments',
            'inTransit',
            'delivered',
            'totalCount'
        )
    );

})->name('rider.dashboard');


/*
|--------------------------------------------------------------------------
| RIDER DELIVERIES
|--------------------------------------------------------------------------
*/

Route::get('/rider/deliveries', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;

    // ==============================
    // AVAILABLE + MY DELIVERIES
    // ==============================

    $orders = DB::table('orders')
        ->where(function ($query) use ($riderId) {

            // Orders available for any rider
            $query->where(function ($q) {
                $q->whereIn('status', [
                    'Ready for Pickup',
                    'Shipped'
                ])
                ->whereNull('rider_id');
            })

            // OR orders already assigned to this rider (pickup leg)
            ->orWhere('rider_id', $riderId)

            // OR orders assigned to this rider by the Sorting Center (delivery leg)
            ->orWhere('delivery_rider_id', $riderId);

        })
        ->orderByDesc('created_at')
        ->get();


    $deliveries = $orders->map(function ($order) {

        $order = (array) $order;

        // Get order items
        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {
            return (array) $item;
        })->toArray();

        // Fields expected by Rider Blade
        $order['total'] =
            (float) $order['total_amount'];

        $order['buyer_name'] =
            $order['shipping_name'];

        $order['address'] =
            $order['shipping_address'];

        $order['phone'] =
            $order['shipping_phone'];

        $order['payment'] =
            $order['payment_method'];

        return $order;

    })->toArray();


    return view(
        'pages.rider.deliveries',
        compact(
            'user',
            'deliveries'
        )
    );

})->name('rider.deliveries');

/*
|--------------------------------------------------------------------------
| RIDER CLAIM DELIVERY
|--------------------------------------------------------------------------
*/

Route::post('/rider/delivery/{id}/claim', function ($id) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;

    $order = DB::table('orders')
        ->where('id', $id)
        ->first();

    if (!$order) {
        return back()->with(
            'error',
            'Delivery not found.'
        );
    }

    // Order must be Ready for Pickup
    if ($order->status !== 'Ready for Pickup') {

        return back()->with(
            'error',
            'This order is not ready for pickup.'
        );
    }

    // Prevent another rider from claiming it
    if (!empty($order->rider_id)) {

        return back()->with(
            'error',
            'This order has already been assigned to another rider.'
        );
    }

    // Atomic first-come-first-served claim: this UPDATE only affects a row
    // if it's STILL unclaimed and Ready for Pickup at the moment it runs,
    // so if two riders click "claim" at the same time, only one of these
    // queries actually changes a row — the database itself is the lock,
    // no separate read-then-write race is possible.
    $claimed = DB::table('orders')
        ->where('id', $id)
        ->where('status', 'Ready for Pickup')
        ->whereNull('rider_id')
        ->update([
            'rider_id' => $riderId,
            'status' => 'Assigned',
            'updated_at' => now(),
        ]);

    if (!$claimed) {

        return back()->with(
            'error',
            'This order was just claimed by another rider. Please choose a different delivery.'
        );
    }

// ==============================
// BUYER NOTIFICATION
// ==============================

createNotification(
    (int) $order->buyer_id,
    'Rider Assigned',
    'A rider has accepted your order #' . $id .
    ' and will pick it up from the seller shortly.',
    'order',
    (int) $id
);

// ==============================
// RIDER NOTIFICATION
// ==============================

createNotification(
    (int) $riderId,
    'Delivery Accepted',
    'You accepted Order #' . $id .
    '. Proceed to the seller\'s location, verify the order, then confirm pickup.',
    'delivery',
    (int) $id
);

    return back()->with(
        'success',
        'Delivery accepted! Proceed to the seller\'s location and confirm pickup once you have the order.'
    );

})->name('rider.delivery.claim');


Route::post('/rider/delivery/{id}/confirm-pickup', function ($id) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;

    $order = DB::table('orders')->where('id', $id)->first();

    if (!$order) {
        return back()->with('error', 'Delivery not found.');
    }

    if ((int) $order->rider_id !== (int) $riderId) {
        return back()->with('error', 'This delivery is not assigned to you.');
    }

    if ($order->status !== 'Assigned') {
        return back()->with('error', 'This order has already been picked up.');
    }

    DB::table('orders')
        ->where('id', $id)
        ->where('rider_id', $riderId)
        ->where('status', 'Assigned')
        ->update([
            'status' => 'Picked Up',
            'updated_at' => now(),
        ]);

    createNotification(
        (int) $order->buyer_id,
        'Order Picked Up',
        'Your order #' . $id . ' has been picked up by the rider and is now on its way.',
        'order',
        (int) $id
    );

    notifyLogisticsUsers(
        'Parcel En Route',
        'Order #' . $id . ' has been picked up by a rider and is on its way to the Sorting Center.',
        'parcel',
        (int) $id
    );

    return back()->with('success', 'Pickup confirmed! You can now mark the order Out for Delivery.');

})->name('rider.delivery.confirm-pickup');

/*
|--------------------------------------------------------------------------
| RIDER DELIVERY DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/rider/delivery/{id}', function ($id) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;

    // GET ORDER FROM DATABASE
    $delivery = DB::table('orders')
        ->where('id', $id)
        ->first();

    if (!$delivery) {
        abort(404);
    }

    // Check if this order is available for pickup
    $isAvailable =
        $delivery->status === 'Ready for Pickup'
        && empty($delivery->rider_id);

    // Check if this order belongs to the logged-in rider — either as the
    // pickup-leg rider (rider_id) or, once the Sorting Center has assigned
    // it, as the final-mile delivery rider (delivery_rider_id).
    $isAssigned =
        (int) $delivery->rider_id === (int) $riderId
        || (int) ($delivery->delivery_rider_id ?? 0) === (int) $riderId;

    if (!$isAvailable && !$isAssigned) {
        abort(404);
    }

    // Get order items
    $items = DB::table('order_items')
        ->where('order_id', $delivery->id)
        ->get();

    // Convert order to array
    $delivery = (array) $delivery;

    // Add data needed by the Blade
    $delivery['items'] = $items->map(function ($item) {
        return (array) $item;
    })->toArray();

    $delivery['total'] = (float) $delivery['total_amount'];

    $delivery['buyer_name'] = $delivery['shipping_name'];

    $delivery['address'] = $delivery['shipping_address'];

    $delivery['phone'] = $delivery['shipping_phone'];

    $delivery['payment'] = $delivery['payment_method'];

    return view(
        'pages.rider.delivery-details',
        compact('user', 'delivery')
    );

})->name('rider.delivery.details');


/*
|--------------------------------------------------------------------------
| RIDER UPDATE DELIVERY STATUS
|--------------------------------------------------------------------------
*/

Route::post('/rider/delivery/{id}/status', function ($id) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = $user['id'] ?? null;

    $status = trim(request('status'));

    $allowedStatuses = [
        'Out for Delivery',
        'Delivered',
        'Delivery Failed',
    ];

    if (!in_array($status, $allowedStatuses)) {
        return back()->with(
            'error',
            'Invalid delivery status.'
        );
    }

    $order = DB::table('orders')
        ->where('id', $id)
        ->first();

    if (!$order) {
        return back()->with(
            'error',
            'Delivery not found.'
        );
    }

    // Make sure this delivery belongs to this rider — the final-mile leg is
    // owned by whoever the Sorting Center assigned (delivery_rider_id); older
    // orders placed before the Sorting Center hop existed fall back to rider_id.
    $effectiveRiderId = $order->delivery_rider_id ?? $order->rider_id;

    if ((int) $effectiveRiderId !== (int) $riderId) {
        return back()->with(
            'error',
            'This delivery is not assigned to you.'
        );
    }

    // Assigned for Delivery → Out for Delivery
    if ($order->status === 'Assigned for Delivery') {

        if ($status !== 'Out for Delivery') {
            return back()->with(
                'error',
                'This order must be moved to Out for Delivery first.'
            );
        }

    }

    // Out for Delivery → Delivered or Delivery Failed
    elseif ($order->status === 'Out for Delivery') {

        if (!in_array($status, ['Delivered', 'Delivery Failed'])) {
            return back()->with(
                'error',
                'Out for Delivery orders can only be marked as Delivered or Delivery Failed.'
            );
        }

        if ($status === 'Delivery Failed') {

            $reason = trim((string) request('failure_reason'));

            if (empty($reason)) {
                return back()->with('error', 'Please provide a reason for the failed delivery.');
            }

            DB::table('orders')->where('id', $id)->update([
                'status' => 'Delivery Failed',
                'failure_reason' => $reason,
                'delivery_failed_at' => now(),
                'delivery_attempts' => $order->delivery_attempts + 1,
                'updated_at' => now(),
            ]);

            createNotification(
                (int) $order->buyer_id,
                'Delivery Attempt Failed',
                'We were unable to deliver your order #' . $id . '. Reason: ' . $reason . '. It will be rescheduled shortly.',
                'order',
                (int) $id
            );

            return back()->with('success', 'Delivery marked as failed. The Sorting Center will reschedule or return this parcel.');

        }

    }

    // Prevent invalid updates
    else {

        return back()->with(
            'error',
            'This order cannot be updated from its current status.'
        );

    }

    // Update order status
    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

    // ==============================
    // BUYER NOTIFICATIONS
    // ==============================

    if ($status === 'Out for Delivery') {

        createNotification(
            (int) $order->buyer_id,
            'Order Out for Delivery',
            'Your order #' . $id .
            ' is now out for delivery.',
            'order',
            (int) $id
        );

    } elseif ($status === 'Delivered') {

        createNotification(
            (int) $order->buyer_id,
            'Order Delivered',
            'Your order #' . $id .
            ' has been delivered successfully.',
            'order',
            (int) $id
        );
    }

    // ==============================
    // RIDER NOTIFICATION
    // ==============================

    if ($status === 'Out for Delivery') {

        createNotification(
            (int) $riderId,
            'Delivery Out for Delivery',
            'Order #' . $id .
            ' is now out for delivery.',
            'delivery_status',
            (int) $id
        );

    } elseif ($status === 'Delivered') {

        createNotification(
            (int) $riderId,
            'Delivery Completed',
            'Order #' . $id .
            ' has been successfully delivered.',
            'delivery_status',
            (int) $id
        );
    }

    return back()->with(
        'success',
        'Delivery status updated to ' . $status . '!'
    );

})->name('rider.delivery.status');

/*
|--------------------------------------------------------------------------
| RIDER PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/rider/profile', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    return view(
        'pages.rider.profile',
        compact('user')
    );

})->name('rider.profile');


Route::get('/rider/profit', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $from = request('from') ?: now()->subDays(30)->format('Y-m-d');
    $to = request('to') ?: now()->format('Y-m-d');

    $deliveryFee = (float) \App\Models\PlatformSetting::get('delivery_fee', '50');

    // Credit whoever actually completed the final-mile leg: if the Sorting
    // Center assigned a (possibly different) delivery_rider_id, that rider
    // earns the fee, not the one who only handled the seller pickup leg.
    $completedDeliveries = DB::table('orders')
        ->where(function ($query) use ($user) {
            $query->where('delivery_rider_id', $user['id'])
                ->orWhere(function ($q) use ($user) {
                    $q->whereNull('delivery_rider_id')
                        ->where('rider_id', $user['id']);
                });
        })
        ->where('status', 'Delivered')
        ->whereDate('updated_at', '>=', $from)
        ->whereDate('updated_at', '<=', $to)
        ->orderByDesc('updated_at')
        ->get();

    $totalDeliveries = $completedDeliveries->count();
    $totalProfit = $totalDeliveries * $deliveryFee;

    $dailyProfit = $completedDeliveries
        ->groupBy(function ($order) {
            return \Illuminate\Support\Carbon::parse($order->updated_at)->format('Y-m-d');
        })
        ->map(function ($orders, $day) use ($deliveryFee) {
            return [
                'day' => $day,
                'deliveries' => $orders->count(),
                'profit' => $orders->count() * $deliveryFee,
            ];
        })
        ->sortKeysDesc()
        ->values();

    return view(
        'pages.rider.profit',
        compact(
            'user',
            'from',
            'to',
            'deliveryFee',
            'totalDeliveries',
            'totalProfit',
            'completedDeliveries',
            'dailyProfit'
        )
    );

})->name('rider.profit');


Route::get('/rider/deliveries/history', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $from = request('from') ?: now()->subDays(30)->format('Y-m-d');
    $to = request('to') ?: now()->format('Y-m-d');

    // Credit whoever actually completed the final-mile leg, same as the
    // Profit page: if the Sorting Center assigned a (possibly different)
    // delivery_rider_id, that delivery belongs in this rider's history too.
    $history = DB::table('orders')
        ->where(function ($query) use ($user) {
            $query->where('delivery_rider_id', $user['id'])
                ->orWhere(function ($q) use ($user) {
                    $q->whereNull('delivery_rider_id')
                        ->where('rider_id', $user['id']);
                });
        })
        ->where('status', 'Delivered')
        ->whereDate('updated_at', '>=', $from)
        ->whereDate('updated_at', '<=', $to)
        ->orderByDesc('updated_at')
        ->get();

    $history = $history->map(function ($order) {

        $order = (array) $order;

        $order['items'] = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

        $order['buyer_name'] = $order['shipping_name'] ?? 'Buyer';
        $order['address'] = $order['shipping_address'] ?? 'N/A';

        return $order;

    });

    return view('pages.rider.delivery-history', compact('user', 'history', 'from', 'to'));

})->name('rider.deliveries.history');




/*
|--------------------------------------------------------------------------
| STORE / HOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    $user = session()->get('user');

    // Logged-in Buyer
    if ($user && ($user['role'] ?? '') === 'buyer') {
        return redirect()->route('buyer.dashboard');
    }

    // Logged-in Seller
    if ($user && ($user['role'] ?? '') === 'seller') {
        return redirect()->route('seller.dashboard');
    }

    // Logged-in Rider
    if ($user && ($user['role'] ?? '') === 'rider') {
        return redirect()->route('rider.dashboard');
    }

    // Logged-in Logistics
    if ($user && ($user['role'] ?? '') === 'logistics') {
        return redirect()->route('logistics.dashboard');
    }


    /*
    |--------------------------------------------------------------------------
    | FEATURED PRODUCTS FOR THE LANDING PAGE
    |--------------------------------------------------------------------------
    */

    $featuredProducts = Product::latest()->take(4)->get();


    // Guest → Landing Page
    return view('welcome', compact('featuredProducts'));

})->name('home');



/*
|--------------------------------------------------------------------------
| PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/product-details/{id}', function ($id) {

    // First: try product ID
    $product = Product::find($id);

    // If not an ID, try product name as slug
    if (!$product) {

        $slug = Str::slug($id);

        $products = Product::all();

        $product = $products->first(function ($item) use ($slug) {
            return Str::slug($item->name) === $slug;
        });
    }

    if (!$product) {
        abort(404);
    }

    // REAL reviews for this product — no reviews yet means no orders
    // have been delivered and rated for it, which is expected/honest.
    $reviews = DB::table('product_reviews')
        ->join('users', 'users.id', '=', 'product_reviews.buyer_id')
        ->where('product_reviews.product_id', $product->id)
        ->select(
            'product_reviews.rating',
            'product_reviews.review',
            'product_reviews.seller_reply',
            'product_reviews.created_at',
            'users.name as buyer_name'
        )
        ->orderByDesc('product_reviews.created_at')
        ->get();

    $reviewCount = $reviews->count();

    $averageRating = $reviewCount > 0
        ? round($reviews->avg('rating'), 1)
        : 0;

    // A handful of other products from the same category
    $relatedProducts = Product::where('category', $product->category)
        ->where('id', '!=', $product->id)
        ->latest()
        ->take(4)
        ->get();

    // Wishlist state (false for guests)
    $sessionUser = session()->get('user');

    $isWishlisted = ($sessionUser && ($sessionUser['role'] ?? '') === 'buyer')
        ? DB::table('wishlists')
            ->where('user_id', $sessionUser['id'])
            ->where('product_id', $product->id)
            ->exists()
        : false;

    $variations = \App\Models\ProductVariation::where('product_id', $product->id)->get();

    return view(
        'pages.product-details',
        compact(
            'product',
            'reviews',
            'reviewCount',
            'averageRating',
            'relatedProducts',
            'isWishlisted',
            'variations'
        )
    );

})->name('product.details');

/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/


// Add to cart
Route::post('/cart/add/{id}', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND PRODUCT FROM DATABASE
    |--------------------------------------------------------------------------
    */

    $product = Product::find($id);

    if (!$product) {
        return back()->with(
            'error',
            'Product not found.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE THE SELECTED VARIATION (IF ANY)
    |--------------------------------------------------------------------------
    |
    | A product with variations tracks its real sellable stock per
    | variation (see the "N in stock" shown per color/size on the product
    | page) — the base product's own stock column only matters when there
    | is no variation involved.
    |
    */

    $variationId = (int) request('variation_id', 0);
    $variation = null;

    if ($variationId) {

        $variation = \App\Models\ProductVariation::where('id', $variationId)
            ->where('product_id', $product->id)
            ->first();

        if (!$variation) {
            $variationId = 0;
        }
    }

    $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

    /*
    |--------------------------------------------------------------------------
    | CHECK STOCK
    |--------------------------------------------------------------------------
    */

    if ($effectiveStock <= 0) {
        return back()->with(
            'error',
            'This product is currently out of stock.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET CURRENT CART
    |--------------------------------------------------------------------------
    |
    | Keyed "productId:variationId" so that different variations (e.g. two
    | different colors) of the same product land on separate cart lines
    | instead of merging into one.
    |
    */

    $cartKey = $product->id . ':' . $variationId;

    $cart = session()->get('cart', []);

    $currentQuantity = (int) ($cart[$cartKey] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | PREVENT EXCEEDING STOCK
    |--------------------------------------------------------------------------
    */

    if ($currentQuantity >= $effectiveStock) {
        return back()->with(
            'error',
            'You cannot add more than the available stock.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADD PRODUCT TO CART
    |--------------------------------------------------------------------------
    */

    $requestedQty = max(1, (int) request('quantity', 1));

    $cart[$cartKey] = $currentQuantity + $requestedQty;

    /*
    |--------------------------------------------------------------------------
    | SAVE CART
    |--------------------------------------------------------------------------
    */

    session()->put('cart', $cart);

    return back()->with(
        'success',
        '✓ Added to cart!'
    );

})->name('cart.add');

/*
|--------------------------------------------------------------------------
| BUY NOW
|--------------------------------------------------------------------------
|
| FIX: this route was previously registered twice (identical duplicate)
| under the same name 'buy.now'. Kept a single, combined version here:
| validates quantity from the request like the original first version,
| defaulting to 1 if not supplied — same behaviour either caller relied on.
|
*/

Route::post('/buy-now/{id}', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    // Find the actual product from the database
    $product = Product::find($id);

    if (!$product) {
        return back()->with('error', 'Product not found.');
    }

    $quantity = (int) request('quantity', 1);

    if ($quantity < 1) {
        $quantity = 1;
    }

    $variationId = (int) request('variation_id', 0);
    $variation = null;

    if ($variationId) {

        $variation = \App\Models\ProductVariation::where('id', $variationId)
            ->where('product_id', $product->id)
            ->first();

        if (!$variation) {
            $variationId = 0;
        }
    }

    $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

    // Check available stock
    if ($quantity > $effectiveStock) {
        return back()->with('error', 'Not enough stock available.');
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE BUY NOW PRODUCT
    |--------------------------------------------------------------------------
    */

    session()->put('buy_now', [
        $product->id . ':' . $variationId => $quantity,
    ]);

    return redirect()
        ->route('checkout');

})->name('buy.now');


// Update cart
Route::post('/cart/update/{key}', function ($key) {

    $cart =
        session()->get('cart', []);

    if (!isset($cart[$key])) {
        return back();
    }

    $action =
        request('action');

    [$productId, $variationId] = parseCartKey($key);

    $product = Product::find($productId);
    $variation = $variationId ? \App\Models\ProductVariation::find($variationId) : null;

    $blocked = false;

    if ($action === 'increase') {

        $effectiveStock = $variation ? (int) $variation->stock : (int) ($product->stock ?? 0);

        if ($cart[$key] >= $effectiveStock) {
            $blocked = true;
        } else {
            $cart[$key]++;
        }

    } elseif ($action === 'decrease') {

        $cart[$key]--;

        if ($cart[$key] <= 0) {

            unset($cart[$key]);
        }
    }

    session()->put(
        'cart',
        $cart
    );

    if (request()->wantsJson()) {

        $newQuantity = $cart[$key] ?? 0;

        $unitPrice = $product ? (float) $product->price : 0;

        if ($variation) {
            $unitPrice += (float) $variation->price_adjustment;
        }

        return response()->json(array_merge(
            [
                'removed' => $newQuantity <= 0,
                'quantity' => $newQuantity,
                'item_total' => $product
                    ? number_format($unitPrice * $newQuantity, 2)
                    : '0.00',
                'blocked' => $blocked,
                'message' => $blocked ? 'No more stock available for this product.' : null,
            ],
            cartSummary($cart)
        ));
    }

    if ($blocked) {
        return back()->with('error', 'No more stock available for this product.');
    }

    return back();

})->name('cart.update');


// Remove from cart
Route::post('/cart/remove/{key}', function ($key) {

    $cart =
        session()->get('cart', []);

    if (isset($cart[$key])) {

        unset($cart[$key]);
    }

    session()->put(
        'cart',
        $cart
    );

    if (request()->wantsJson()) {

        return response()->json(array_merge(
            ['removed' => true],
            cartSummary($cart)
        ));
    }

    return back()->with(
        'success',
        'Product removed from cart.'
    );

})->name('cart.remove');


// Cart page
Route::get('/cart', function () {

    $cart =
        session()->get('cart', []);

    $appliedVoucher = null;
    $voucherError = null;

    $voucherCode = session()->get('applied_voucher');

    if ($voucherCode) {

        $summary = cartSummary($cart);
        $subtotal = (float) str_replace(',', '', $summary['subtotal']);

        $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();

        if ($voucher && $voucher->isValidFor($subtotal)) {
            $appliedVoucher = $voucher;
        } else {
            session()->forget('applied_voucher');
        }
    }

    return view(
        'pages.cart',
        compact('cart', 'appliedVoucher', 'voucherError')
    );

})->name('cart');


Route::post('/cart/voucher/apply', function () {

    $cart = session()->get('cart', []);
    $code = strtoupper(trim((string) request('voucher_code')));

    if ($code === '') {
        return back()->with('error', 'Please enter a voucher code.');
    }

    $voucher = \App\Models\Voucher::where('code', $code)->first();

    if (!$voucher) {
        return back()->with('error', 'Invalid voucher code.');
    }

    $summary = cartSummary($cart);
    $subtotal = (float) str_replace(',', '', $summary['subtotal']);

    if (!$voucher->isValidFor($subtotal)) {
        return back()->with('error', 'This voucher is expired, fully used, or your order does not meet its minimum amount.');
    }

    session()->put('applied_voucher', $voucher->code);

    return back()->with('success', 'Voucher "' . $voucher->code . '" applied!');

})->name('cart.voucher.apply');


Route::post('/cart/voucher/remove', function () {

    session()->forget('applied_voucher');

    return back()->with('success', 'Voucher removed.');

})->name('cart.voucher.remove');


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

Route::get('/checkout', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK BUY NOW FIRST
    |--------------------------------------------------------------------------
    */

    $buyNow = session()->get('buy_now', []);

    if (!empty($buyNow)) {

        $cart = $buyNow;

    } else {

        $cart = session()->get('cart', []);

        if (empty($cart)) {

            return redirect()
                ->route('cart')
                ->with(
                    'error',
                    'Your cart is empty.'
                );
        }

        /*
        |----------------------------------------------------------------------
        | ONLY CHECK OUT THE ITEMS THE BUYER ACTUALLY SELECTED
        |----------------------------------------------------------------------
        |
        | The cart page lets a buyer tick which lines to include. If a
        | selection was passed, narrow $cart down to just those keys and
        | remember the choice in the session so the place-order step (a
        | separate request) checks out the same subset and leaves the rest
        | of the cart untouched.
        |
        */

        $requestedItems = request('items');

        if ($requestedItems !== null) {

            $selectedKeys = array_filter(explode(',', $requestedItems));

            $cart = array_intersect_key(
                $cart,
                array_flip($selectedKeys)
            );

            if (empty($cart)) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'Please select at least one item to check out.'
                    );
            }

            session()->put('checkout_selection', array_keys($cart));

        } elseif (session()->has('checkout_selection')) {

            $cart = array_intersect_key(
                $cart,
                array_flip(session()->get('checkout_selection'))
            );

            if (empty($cart)) {
                session()->forget('checkout_selection');
                $cart = session()->get('cart', []);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET PRODUCTS USING DATABASE IDs
    |--------------------------------------------------------------------------
    */

    $productIds = array_map(
        fn ($key) => parseCartKey($key)[0],
        array_keys($cart)
    );

    $products = Product::whereIn('id', array_unique($productIds))
        ->get()
        ->keyBy('id');

    /*
    |--------------------------------------------------------------------------
    | CHECK CART PRODUCTS
    |--------------------------------------------------------------------------
    */

    foreach ($cart as $cartKey => $quantity) {

        [$productId] = parseCartKey($cartKey);

        if (!$products->has($productId)) {

            return redirect()
                ->route('cart')
                ->with(
                    'error',
                    'One of the products could not be found.'
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | APPLIED VOUCHER (carried over from the cart page)
    |--------------------------------------------------------------------------
    */

    $appliedVoucher = null;
    $voucherCode = session()->get('applied_voucher');

    if ($voucherCode) {

        $checkoutSubtotal = 0;

        foreach ($cart as $cartKey => $quantity) {

            [$productId, $variationId] = parseCartKey($cartKey);

            if ($products->has($productId)) {

                $unitPrice = (float) $products[$productId]->price;

                if ($variationId) {
                    $variation = \App\Models\ProductVariation::find($variationId);
                    if ($variation) {
                        $unitPrice += (float) $variation->price_adjustment;
                    }
                }

                $checkoutSubtotal += $unitPrice * (int) $quantity;
            }
        }

        $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();

        if ($voucher && $voucher->isValidFor($checkoutSubtotal)) {
            $appliedVoucher = $voucher;
        } else {
            session()->forget('applied_voucher');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO CHECKOUT PAGE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PRE-FILL SAVED ADDRESS/PHONE FROM THE BUYER'S PROFILE
    |--------------------------------------------------------------------------
    */

    $savedBuyer = User::find($user['id']);
    $savedAddress = $savedBuyer->address ?? '';
    $savedPhone = $savedBuyer->phone ?? '';

    return view(
        'pages.checkout',
        compact(
            'user',
            'cart',
            'products',
            'appliedVoucher',
            'savedAddress',
            'savedPhone'
        )
    );

})->name('checkout');



/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/

Route::post('/checkout/place-order', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | GET BUY NOW OR CART
    |--------------------------------------------------------------------------
    */

    $buyNow = session()->get('buy_now', []);

    if (!empty($buyNow)) {

        $cart = $buyNow;
        $isBuyNow = true;

    } else {

        $cart = session()->get('cart', []);
        $isBuyNow = false;

        if (empty($cart)) {

            return redirect()
                ->route('cart')
                ->with(
                    'error',
                    'Your cart is empty.'
                );
        }

        // Only place an order for the lines the buyer actually selected on
        // the cart page — everything else stays in the cart untouched.
        if (session()->has('checkout_selection')) {

            $cart = array_intersect_key(
                $cart,
                array_flip(session()->get('checkout_selection'))
            );

            if (empty($cart)) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'Please select at least one item to check out.'
                    );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECKOUT INFORMATION
    |--------------------------------------------------------------------------
    */

    $address = trim(request('address'));
    $phone = trim(request('phone'));
    $payment = trim(request('payment'));

    if (
        empty($address) ||
        empty($phone) ||
        empty($payment)
    ) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Please complete all checkout information.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD ORDER ITEMS USING PRODUCT IDs
    |--------------------------------------------------------------------------
    */

    $orderItems = [];
    $total = 0;

    foreach ($cart as $cartKey => $quantity) {

        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            continue;
        }

        [$productId, $variationId] = parseCartKey($cartKey);

        /*
        |--------------------------------------------------------------------------
        | FIND PRODUCT BY DATABASE ID
        |--------------------------------------------------------------------------
        */

        $product = Product::find($productId);

        if (!$product) {

            return redirect()
                ->route('cart')
                ->with(
                    'error',
                    'One of the products could not be found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | RESOLVE SELECTED VARIATION (IF ANY)
        |--------------------------------------------------------------------------
        */

        $variation = null;
        $variationLabel = null;

        if ($variationId) {

            $variation = \App\Models\ProductVariation::where('id', $variationId)
                ->where('product_id', $product->id)
                ->first();

            if ($variation) {
                $variationLabel = $variation->variation_type . ': ' . $variation->variation_value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK STOCK
        |--------------------------------------------------------------------------
        |
        | A variation tracks its own sellable stock (see the "N in stock"
        | shown per color/size on the product page) — the base product's
        | stock column only applies when there is no variation.
        |
        */

        $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

        if ($effectiveStock < $quantity) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    $product->name .
                    ($variationLabel ? " ({$variationLabel})" : '') .
                    ' does not have enough stock.'
                );
        }

        $unitPrice = (float) $product->price;

        if ($variation) {
            $unitPrice += (float) $variation->price_adjustment;
        }

        /*
        |--------------------------------------------------------------------------
        | CALCULATE SUBTOTAL
        |--------------------------------------------------------------------------
        */

        $subtotal =
            $unitPrice * $quantity;

        /*
        |--------------------------------------------------------------------------
        | ADD ORDER ITEM
        |--------------------------------------------------------------------------
        */

        $orderItems[] = [

            'product_id' =>
                $product->id,

            'seller_id' =>
                $product->seller_id,

            'product_name' =>
                $product->name,

            // Transient — used below to decrement the right stock row,
            // not a real order_items column.
            'variation_id' =>
                $variation->id ?? null,

            'variation_label' =>
                $variationLabel,

            'price' =>
                $unitPrice,

            'quantity' =>
                $quantity,
        ];

        $total += $subtotal;
    }

    /*
    |--------------------------------------------------------------------------
    | NO VALID PRODUCTS
    |--------------------------------------------------------------------------
    */

    if (empty($orderItems)) {

        return redirect()
            ->route('cart')
            ->with(
                'error',
                'No valid products found.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY VOUCHER (if one was applied on the cart page)
    |--------------------------------------------------------------------------
    */

    $voucherCode = session()->get('applied_voucher');
    $discountAmount = 0;
    $voucher = null;

    if ($voucherCode) {

        $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();

        if ($voucher && $voucher->isValidFor($total)) {
            $discountAmount = $voucher->calculateDiscount($total);
        } else {
            $voucher = null;
            $voucherCode = null;
        }
    }

    $total = max(0, $total - $discountAmount);

    /*
    |--------------------------------------------------------------------------
    | CREATE ORDER
    |--------------------------------------------------------------------------
    */

    $orderId = DB::table('orders')->insertGetId([

        'buyer_id' =>
            $user['id'],

        'total_amount' =>
            $total,

        'voucher_code' =>
            $voucherCode,

        'discount_amount' =>
            $discountAmount,

        // Seller workflow starts here
        'status' =>
            'Pending',

        'shipping_name' =>
            $user['name'] ?? 'Buyer',

        'shipping_phone' =>
            $phone,

        'shipping_address' =>
            $address,

        'payment_method' =>
            $payment,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),

    ]);

    /*
    |--------------------------------------------------------------------------
    | RECORD VOUCHER USE
    |--------------------------------------------------------------------------
    */

    if ($voucher) {
        $voucher->increment('used_count');
    }

    session()->forget('applied_voucher');

    /*
    |--------------------------------------------------------------------------
    | BUYER NOTIFICATION
    |--------------------------------------------------------------------------
    */

    createNotification(
        (int) $user['id'],
        'Order Confirmed',
        'Your order #' . $orderId .
        ' has been placed successfully and is now Pending.',
        'order',
        (int) $orderId
    );

    /*
    |--------------------------------------------------------------------------
    | CREATE ORDER ITEMS + REDUCE STOCK
    |--------------------------------------------------------------------------
    */

    foreach ($orderItems as $item) {

        DB::table('order_items')->insert([

            'order_id' =>
                $orderId,

            'product_id' =>
                $item['product_id'],

            'seller_id' =>
                $item['seller_id'],

            'product_name' =>
                $item['product_name'],

            'variation_label' =>
                $item['variation_label'] ?? null,

            'price' =>
                $item['price'],

            'quantity' =>
                $item['quantity'],

            'created_at' =>
                now(),

            'updated_at' =>
                now(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | REDUCE STOCK
        |--------------------------------------------------------------------------
        |
        | A variation carries its own stock — reduce that instead of the
        | base product's when one was selected.
        |
        */

        if (!empty($item['variation_id'])) {

            \App\Models\ProductVariation::where(
                'id',
                $item['variation_id']
            )->decrement(
                'stock',
                $item['quantity']
            );

        } else {

            Product::where(
                'id',
                $item['product_id']
            )->decrement(
                'stock',
                $item['quantity']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SELLER NOTIFICATIONS
    |--------------------------------------------------------------------------
    |
    | Get unique sellers from the products included
    | in this order. Each seller receives one notification.
    |
    */

    $sellerIds = collect($orderItems)
        ->pluck('seller_id')
        ->filter()
        ->unique()
        ->values();

    foreach ($sellerIds as $sellerId) {

        createNotification(
            (int) $sellerId,
            'New Order Received',
            'You have a new order #' .
            $orderId .
            ' that is waiting for processing.',
            'order',
            (int) $orderId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR SOURCE
    |--------------------------------------------------------------------------
    */

    if ($isBuyNow) {

        session()->forget('buy_now');

    } else {

        // Remove only the lines that were just ordered — anything the
        // buyer left unchecked on the cart page stays there.
        $remainingCart = session()->get('cart', []);

        foreach (array_keys($cart) as $orderedKey) {
            unset($remainingCart[$orderedKey]);
        }

        session()->put('cart', $remainingCart);
        session()->forget('checkout_selection');
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER SUCCESS
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route(
            'orders.success',
            $orderId
        )
        ->with(
            'success',
            'Order placed successfully!'
        );

})->name('checkout.place');


/*
|--------------------------------------------------------------------------
| ORDER SUCCESS
|--------------------------------------------------------------------------
*/

Route::get('/orders/success/{id}', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ORDER FROM DATABASE
    |--------------------------------------------------------------------------
    */

    $order = DB::table('orders')
        ->where('id', $id)
        ->where('buyer_id', $user['id'])
        ->first();

    if (!$order) {
        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | GET ORDER ITEMS
    |--------------------------------------------------------------------------
    */

    $items = DB::table('order_items')
        ->where('order_id', $order->id)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | CONVERT ORDER TO ARRAY
    |--------------------------------------------------------------------------
    */

    $order = (array) $order;

    /*
    |--------------------------------------------------------------------------
    | CONVERT ITEMS TO ARRAYS
    |--------------------------------------------------------------------------
    */

    $order['items'] = $items
        ->map(function ($item) {
            return (array) $item;
        })
        ->toArray();

    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY WITH EXISTING ORDER SUCCESS BLADE
    |--------------------------------------------------------------------------
    */

    $order['total'] =
        (float) $order['total_amount'];

    $order['date'] =
        $order['created_at'];

    $order['buyer_name'] =
        $order['shipping_name'];

    $order['address'] =
        $order['shipping_address'];

    $order['phone'] =
        $order['shipping_phone'];

    $order['payment'] =
        $order['payment_method'];

    /*
    |--------------------------------------------------------------------------
    | DISPLAY ORDER SUCCESS
    |--------------------------------------------------------------------------
    */

    return view(
        'pages.order-success',
        compact('order')
    );

})->name('orders.success');



/*
|--------------------------------------------------------------------------
| SELLER EDIT PRODUCT
|--------------------------------------------------------------------------
*/

Route::get('/seller/products/{id}/edit', function ($id) {

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

    return view(
        'pages.seller.edit-product',
        compact('user', 'product')
    );

})->name('seller.products.edit');

/*
|--------------------------------------------------------------------------
| SELLER UPDATE PRODUCT
|--------------------------------------------------------------------------
*/

Route::put('/seller/products/{id}', function ($id) {

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

    $categoryNames = [

        'electronics' => 'Electronics',
        'womens-fashion' => "Women's Fashion",
        'mens-fashion' => "Men's Fashion",
        'kids-baby' => 'Kids & Baby',
        'home-living' => 'Home & Living',
        'sports-outdoors' => 'Sports & Outdoors',
        'beauty-personal-care' => 'Beauty & Personal Care',
        'food-beverages' => 'Food & Beverages',
        'automotive' => 'Automotive',
        'office-school' => 'Office & School',
        'pet-supplies' => 'Pet Supplies',
        'toys-games-hobbies' => 'Toys, Games & Hobbies',
        'jewelry-accessories' => 'Jewelry & Accessories',
        'shoes' => 'Shoes',
        'tools-home-improvement' => 'Tools & Home Improvement',
        'garden-outdoor' => 'Garden & Outdoor',

    ];

    /*
    |--------------------------------------------------------------------------
    | Old Category Compatibility
    |--------------------------------------------------------------------------
    |
    | Existing products that still use the old categories
    | will automatically be converted when edited.
    |
    */

    $oldCategoryAliases = [

        'Smartphone' => 'electronics',
        'smartphone' => 'electronics',

        'Laptop' => 'electronics',
        'laptop' => 'electronics',

        'Audio' => 'electronics',
        'audio' => 'electronics',

        'Wearable' => 'electronics',
        'wearable' => 'electronics',

        'Accessories' => 'jewelry-accessories',
        'accessories' => 'jewelry-accessories',

    ];

    /*
    |--------------------------------------------------------------------------
    | Convert submitted category
    |--------------------------------------------------------------------------
    */

    if (isset($categoryNames[$category])) {

        $categoryName = $categoryNames[$category];

    } elseif (isset($oldCategoryAliases[$category])) {

        $categoryName = $categoryNames[
            $oldCategoryAliases[$category]
        ];

    } else {

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

    /*
    |--------------------------------------------------------------------------
    | Keep existing image
    |--------------------------------------------------------------------------
    */

    $imagePath = $product->image;

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

        $extension = strtolower(
            $image->getClientOriginalExtension()
        );

        if (!in_array($extension, [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ])) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Product image must be JPG, JPEG, PNG, or WEBP.'
                );
        }

        if ($image->getSize() > 5 * 1024 * 1024) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Product image must not be larger than 5MB.'
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

})->name('seller.products.update');



/*
|--------------------------------------------------------------------------
| SELLER DELETE PRODUCT
|--------------------------------------------------------------------------
*/

Route::delete('/seller/products/{id}', function ($id) {

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

    $productName = $product->name;

    $product->delete();

    return redirect()
        ->route('seller.dashboard')
        ->with(
            'success',
            $productName . ' deleted successfully!'
        );

})->name('seller.products.delete');


Route::post('/seller/products/{id}/archive', function ($id) {

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

})->name('seller.products.archive');


Route::post('/seller/products/{id}/unarchive', function ($id) {

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

})->name('seller.products.unarchive');



Route::get('/categories', function () {
    return view('categories');
})->name('categories');


Route::post('/buyer/order/{orderId}/return-refund', function ($orderId) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $order = DB::table('orders')
        ->where('id', $orderId)
        ->where('buyer_id', $user['id'])
        ->first();

    if (!$order) {
        return back()->with('error', 'Order not found.');
    }

    // Must be delivered first
    if ($order->status !== 'Delivered') {
        return back()->with(
            'error',
            'Only delivered orders can be returned or refunded.'
        );
    }

    // Buyer must confirm that the order was received first
    if (empty($order->buyer_received_at)) {
        return back()->with(
            'error',
            'Please confirm that you received the order before requesting a return or refund.'
        );
    }

    $orderItemId = (int) request('order_item_id');
    $requestType = trim(request('request_type'));
    $reason = trim(request('reason'));
    $message = trim(request('message'));

    if (!in_array($requestType, ['Return', 'Refund'])) {
        return back()->with('error', 'Invalid request type.');
    }

    if (empty($reason)) {
        return back()->with('error', 'Please select a reason.');
    }

    $item = DB::table('order_items')
        ->where('id', $orderItemId)
        ->where('order_id', $orderId)
        ->first();

    if (!$item) {
        return back()->with('error', 'Order item not found.');
    }

    $existingRequest = DB::table('return_refund_requests')
        ->where('order_id', $orderId)
        ->where('order_item_id', $orderItemId)
        ->whereIn('status', [
            'pending',
            'approved',
            'returned',
            'refund_processing'
        ])
        ->exists();

    if ($existingRequest) {
        return back()->with(
            'error',
            'A return/refund request already exists for this item.'
        );
    }

    $refundAmount =
        (float) $item->price *
        (int) $item->quantity;

    /*
    |--------------------------------------------------------------------------
    | CREATE RETURN / REFUND REQUEST
    |--------------------------------------------------------------------------
    */

    DB::table('return_refund_requests')->insert([
        'order_id' =>
            $orderId,

        'order_item_id' =>
            $orderItemId,

        'buyer_id' =>
            $user['id'],

        'seller_id' =>
            $item->seller_id,

        'request_type' =>
            $requestType,

        'reason' =>
            $reason,

        'message' =>
            $message ?: null,

        'evidence' =>
            null,

        'status' =>
            'pending',

        'refund_amount' =>
            $refundAmount,

        'seller_note' =>
            null,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ]);

    /*
    |--------------------------------------------------------------------------
    | SELLER NOTIFICATION
    |--------------------------------------------------------------------------
    */

    if (!empty($item->seller_id)) {

        createNotification(
            (int) $item->seller_id,
            'New Return / Refund Request',
            'A buyer submitted a ' .
            strtolower($requestType) .
            ' request for Order #' .
            $orderId .
            '.',
            'return_refund',
            (int) $orderId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    return back()->with(
        'success',
        'Your ' .
        strtolower($requestType) .
        ' request has been submitted successfully.'
    );

})->name('buyer.return-refund.store');


// =====================================================
// SELLER - RETURN / REFUND REQUESTS
// =====================================================

Route::post('/seller/return-refund/{id}/approve', function ($id) {
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'approved',
            'seller_note' => 'Request approved by seller.',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Return/Refund request for ' . $requestData->product_name . ' has been approved.'
    );
})->name('seller.return-refund.approve');


Route::post('/seller/return-refund/{id}/reject', function ($id) {
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'rejected',
            'seller_note' => $sellerNote !== '' ? $sellerNote : 'Request rejected by seller.',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Return/Refund request has been rejected.'
    );
})->name('seller.return-refund.reject');


// =====================================================
// SELLER RETURN / REFUND — MARK AS RETURNED
// =====================================================

Route::post('/seller/return-refund/{id}/returned', function ($id) {
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'returned',
            'seller_note' => 'Item has been marked as returned by the seller.',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Return request has been marked as returned.'
    );
})->name('seller.return-refund.returned');


// =====================================================
// SELLER REFUND — START REFUND
// =====================================================

Route::post('/seller/return-refund/{id}/refund-processing', function ($id) {
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'refund_processing',
            'seller_note' => 'Refund is currently being processed.',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Refund is now being processed.'
    );
})->name('seller.return-refund.processing');


// =====================================================
// SELLER REFUND — COMPLETE REFUND
// =====================================================

Route::post('/seller/return-refund/{id}/complete', function ($id) {
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'completed',
            'seller_note' => 'Refund has been completed by the seller.',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Refund has been marked as completed.'
    );
})->name('seller.return-refund.complete');

// =========================
// RIDER APPLICATION
// =========================

Route::get('/rider/apply', function () {

    $application = null;
    $checkedEmail = trim((string) request('email'));

    // Pending/Rejected riders can't log in yet, so there's no session to
    // read their status from — let them look it up by the email they
    // applied with instead of always showing a blank form.
    if (!empty($checkedEmail)) {

        $applicantUser = DB::table('users')
            ->where('email', strtolower($checkedEmail))
            ->where('role', 'rider')
            ->first();

        if ($applicantUser) {

            $application = DB::table('rider_applications')
                ->where('user_id', $applicantUser->id)
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$application) {
            session()->flash('error', 'No rider application was found for that email address.');
        }
    }

    return view('pages.rider.apply', compact('application', 'checkedEmail'));
})->name('rider.apply');


// =========================
// RIDER APPLICATION SUBMIT
// =========================

Route::post('/rider/apply', function (\Illuminate\Http\Request $request) {

    $validated = $request->validate([
        'last_name' => ['required', 'string', 'max:255'],
        'first_name' => ['required', 'string', 'max:255'],
        'middle_initial' => ['nullable', 'string', 'max:5'],
        'sex' => ['required', 'in:Male,Female'],
        'birthdate' => ['required', 'date', 'before:today'],

        'phone' => [
            'required',
            'string',
            'max:30',
            'unique:users,phone'
        ],

        'province' => ['required', 'string', 'max:255'],
        'city_municipality' => ['required', 'string', 'max:255'],
        'barangay' => ['required', 'string', 'max:255'],
        'street_address' => ['required', 'string', 'max:255'],

        'vehicle_type' => [
            'required',
            'in:Motorcycle,Car,Van'
        ],

        'vehicle_model' => [
            'required',
            'string',
            'max:255'
        ],

        'plate_number' => [
            'required',
            'string',
            'max:50'
        ],

        'email' => [
            'required',
            'email',
            'max:255',
            'unique:users,email'
        ],

        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed'
        ],

        'national_id' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240'
        ],

        'drivers_license' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240'
        ],

        'profile_selfie' => [
            'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:10240'
        ],

        'proof_of_address' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240'
        ],

        'or_cr' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240'
        ],

        'terms' => ['accepted'],

    ], [

        'terms.accepted' =>
            'Please agree to the Terms & Conditions and Privacy Policy.',

    ]);

    /*
    |--------------------------------------------------------------------------
    | STORE RIDER REGISTRATION TEMPORARILY
    |--------------------------------------------------------------------------
    */

    $folder = 'rider-applications/' . \Illuminate\Support\Str::uuid();

    $nationalIdPath = $request
        ->file('national_id')
        ->store($folder, 'local');

    $driversLicensePath = $request
        ->file('drivers_license')
        ->store($folder, 'local');

    $selfiePath = $request
        ->file('profile_selfie')
        ->store($folder, 'local');

    $proofOfAddressPath = $request
        ->file('proof_of_address')
        ->store($folder, 'local');

    $orCrPath = $request
        ->file('or_cr')
        ->store($folder, 'local');

    $riderFullName = formatFullName(
        $validated['first_name'],
        $validated['middle_initial'] ?? null,
        $validated['last_name']
    );

    $riderAddress = $validated['street_address'] . ', '
        . $validated['barangay'] . ', '
        . $validated['city_municipality'] . ', '
        . $validated['province'];

    $riderAge = calculateAge($validated['birthdate']);


    /*
    |--------------------------------------------------------------------------
    | SAVE PENDING RIDER DATA IN SESSION
    |--------------------------------------------------------------------------
    */

    session()->put('pending_registration', [

    'full_name' => $riderFullName,

    // Compatible sa existing OTP verification
    'name' => $riderFullName,

    'last_name' => $validated['last_name'],
    'first_name' => $validated['first_name'],
    'middle_initial' => $validated['middle_initial'] ?? null,
    'sex' => $validated['sex'],
    'birthdate' => $validated['birthdate'],
    'age' => $riderAge,

    'phone' => $validated['phone'],
    'address' => $riderAddress,
    'province' => $validated['province'],
    'city_municipality' => $validated['city_municipality'],
    'barangay' => $validated['barangay'],
    'street_address' => $validated['street_address'],

    'vehicle_type' => $validated['vehicle_type'],
    'vehicle_model' => $validated['vehicle_model'],
    'plate_number' => $validated['plate_number'],

    'email' => $validated['email'],

    'password' => \Illuminate\Support\Facades\Hash::make(
        $validated['password']
    ),

    'national_id' => $nationalIdPath,
    'drivers_license' => $driversLicensePath,
    'profile_selfie' => $selfiePath,
    'proof_of_address' => $proofOfAddressPath,
    'or_cr' => $orCrPath,

    // IMPORTANT
    'role' => 'rider',

    'application_folder' => $folder,

]);


/*
|--------------------------------------------------------------------------
| SEND OTP
|--------------------------------------------------------------------------
*/

$otpSent = generateAndSendOtp(
    $validated['email'],
    $riderFullName
);


if (!$otpSent) {

    session()->forget('pending_registration');

    return back()
        ->withInput()
        ->with(
            'error',
            'Unable to send OTP. Please check your email and try again.'
        );
}


/*
|--------------------------------------------------------------------------
| REDIRECT TO OTP PAGE
|--------------------------------------------------------------------------
*/

return redirect()
    ->route('otp.verify')
    ->with(
        'success',
        'OTP sent successfully! Please check your email.'
    );

})->name('rider.apply.submit');


// Buyer Registration Page
Route::get('/buyer/register', function () {
    return view('pages.buyer.register');
})->name('buyer.register');


// Buyer Registration Submit
Route::post('/buyer/register', function (\Illuminate\Http\Request $request) {

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

})->name('buyer.register.submit');


// Seller Registration Page
Route::get('/seller/register', function () {
    return view('pages.seller.register');
})->name('seller.register');


// Seller Registration Submit
Route::post('/seller/register', function (\Illuminate\Http\Request $request) {

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

})->name('seller.register.submit');


// Logistics Registration Page
Route::get('/logistics/register', function () {
    return view('pages.logistics.register');
})->name('logistics.register');


// Logistics Registration Submit
Route::post('/logistics/register', function (\Illuminate\Http\Request $request) {

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
        empty($businessName)
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

    if (User::where('email', $email)->exists()) {
        return back()
            ->withInput()
            ->with('error', 'Email is already registered.');
    }

    if (User::where('phone', $phone)->exists()) {
        return back()
            ->withInput()
            ->with('error', 'This phone number is already registered.');
    }

    $request->validate([
        'id_photo' => [
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
        'id_photo' => 'valid ID',
        'business_permit' => 'business/DTI permit',
    ]);

    $folder = 'logistics-applications/' . (string) Str::uuid();

    $idPhotoPath = $request->file('id_photo')->store($folder, 'local');
    $businessPermitPath = $request->file('business_permit')->store($folder, 'local');

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
        'role' => 'logistics',
        'business_name' => $businessName,
        'id_photo' => $idPhotoPath,
        'business_permit' => $businessPermitPath,
    ]);

    if (!generateAndSendOtp($email, $name)) {
        return back()
            ->withInput()
            ->with('error', 'We could not send the verification code right now. Please try again in a moment.');
    }

    return redirect()
        ->route('otp.show')
        ->with('success', 'We sent a 6-digit code to your email.');

})->name('logistics.register.submit');


/*
|--------------------------------------------------------------------------
| LOGISTICS DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/logistics', function () {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $application = DB::table('logistics_applications')
        ->where('user_id', $user['id'])
        ->orderByDesc('created_at')
        ->first();

    $parcelsForSorting = DB::table('orders')
        ->whereIn('status', ['Picked Up', 'At Sorting Center'])
        ->count();

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
        'pages.logistics.dashboard',
        compact('user', 'application', 'parcelsForSorting', 'activeRiders', 'deliveredToday')
    );

})->name('logistics.dashboard');


/*
|--------------------------------------------------------------------------
| LOGISTICS PROFILE
|--------------------------------------------------------------------------
*/

Route::get('/logistics/profile', function () {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $dbUser = User::find($user['id']);

    $application = DB::table('logistics_applications')
        ->where('user_id', $user['id'])
        ->orderByDesc('created_at')
        ->first();

    return view('pages.logistics.profile', compact('user', 'dbUser', 'application'));

})->name('logistics.profile');


Route::post('/logistics/profile', function () {

    $user = requireUserRole('logistics');

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

})->name('logistics.profile.update');


Route::post('/logistics/profile/photo', function (\Illuminate\Http\Request $request) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $request->validate([
        'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $file = $request->file('profile_photo');

    $filename = 'logistics_' . $user['id'] . '_' . time() . '.' . $file->getClientOriginalExtension();

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

})->name('logistics.profile.photo');


Route::post('/logistics/profile/password', function () {

    $user = requireUserRole('logistics');

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

    return back()->with('success', 'Password changed successfully.');

})->name('logistics.profile.password');


/*
|--------------------------------------------------------------------------
| LOGISTICS — RIDER MANAGEMENT
|--------------------------------------------------------------------------
*/

Route::get('/logistics/riders', function () {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $riderApplications = DB::table('rider_applications')
        ->join('users', 'users.id', '=', 'rider_applications.user_id')
        ->select('rider_applications.*', 'users.email as user_email', 'users.status as account_status')
        ->orderByDesc('rider_applications.created_at')
        ->get();

    $riderAreas = \App\Models\RiderArea::whereIn(
        'rider_id',
        $riderApplications->pluck('user_id')
    )->get()->groupBy('rider_id');

    return view('pages.logistics.riders', compact('user', 'riderApplications', 'riderAreas'));

})->name('logistics.riders');


Route::post('/logistics/riders/{riderId}/areas', function ($riderId) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $province = trim(request('province'));
    $cityMunicipality = trim(request('city_municipality'));

    if (empty($province) || empty($cityMunicipality)) {
        return back()->with('error', 'Please select a province and city/municipality.');
    }

    $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->first();

    if (!$rider) {
        return back()->with('error', 'Rider not found.');
    }

    \App\Models\RiderArea::firstOrCreate([
        'rider_id' => $riderId,
        'province' => $province,
        'city_municipality' => $cityMunicipality,
    ]);

    return back()->with('success', 'Area assigned to ' . $rider->name . '.');

})->name('logistics.riders.areas.store');


Route::post('/logistics/riders/areas/{areaId}/delete', function ($areaId) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    \App\Models\RiderArea::where('id', $areaId)->delete();

    return back()->with('success', 'Area removed.');

})->name('logistics.riders.areas.delete');


/*
|--------------------------------------------------------------------------
| LOGISTICS — PARCEL SORTING
|--------------------------------------------------------------------------
*/

Route::get('/logistics/parcels', function () {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    // Picked up by a rider from the seller, en route to this Sorting Center
    $awaitingConfirmation = DB::table('orders')
        ->where('status', 'Picked Up')
        ->orderBy('updated_at')
        ->get();

    // Physically received at the Sorting Center, waiting to be assigned
    // to a rider for the final-mile delivery leg.
    $awaitingAssignment = DB::table('orders')
        ->where('status', 'At Sorting Center')
        ->orderBy('sorting_center_received_at')
        ->get();

    $activeRiders = DB::table('users')
        ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
        ->where('users.role', 'rider')
        ->where('users.status', 'Active')
        ->where('rider_applications.status', 'Approved')
        ->select('users.id', 'users.name')
        ->distinct()
        ->get();

    $riderAreas = \App\Models\RiderArea::whereIn('rider_id', $activeRiders->pluck('id'))->get();

    // For each parcel awaiting assignment, suggest riders whose assigned
    // area name appears in the shipping address — a simple, honest match
    // since orders only store a single free-text shipping address string.
    $suggestedRidersByOrder = [];

    foreach ($awaitingAssignment as $order) {

        $matches = $riderAreas->filter(function ($area) use ($order) {
            return stripos($order->shipping_address, $area->city_municipality) !== false
                || stripos($order->shipping_address, $area->province) !== false;
        })->pluck('rider_id')->unique();

        $suggestedRidersByOrder[$order->id] = $activeRiders->whereIn('id', $matches)->values();
    }

    $failedDeliveries = DB::table('orders')
        ->where('status', 'Delivery Failed')
        ->orderByDesc('delivery_failed_at')
        ->get();

    return view(
        'pages.logistics.parcels',
        compact('user', 'awaitingConfirmation', 'awaitingAssignment', 'activeRiders', 'suggestedRidersByOrder', 'failedDeliveries')
    );

})->name('logistics.parcels');


Route::post('/logistics/parcels/{id}/confirm-received', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $order = DB::table('orders')->where('id', $id)->first();

    if (!$order) {
        return back()->with('error', 'Parcel not found.');
    }

    if ($order->status !== 'Picked Up') {
        return back()->with('error', 'This parcel is not awaiting Sorting Center confirmation.');
    }

    DB::table('orders')->where('id', $id)->update([
        'status' => 'At Sorting Center',
        'sorting_center_received_at' => now(),
        'updated_at' => now(),
    ]);

    createNotification(
        (int) $order->buyer_id,
        'Parcel at Sorting Center',
        'Your order #' . $id . ' has arrived at the sorting facility and will be assigned to a rider for delivery shortly.',
        'order',
        (int) $id
    );

    return back()->with('success', 'Parcel #' . $id . ' confirmed as received.');

})->name('logistics.parcels.confirm-received');


Route::post('/logistics/parcels/{id}/assign', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = request('rider_id');

    if (empty($riderId)) {
        return back()->with('error', 'Please select a rider to assign this parcel to.');
    }

    $order = DB::table('orders')->where('id', $id)->first();

    if (!$order) {
        return back()->with('error', 'Parcel not found.');
    }

    if ($order->status !== 'At Sorting Center') {
        return back()->with('error', 'This parcel is not awaiting assignment.');
    }

    $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->first();

    if (!$rider) {
        return back()->with('error', 'Selected rider not found.');
    }

    DB::table('orders')->where('id', $id)->update([
        'delivery_rider_id' => $riderId,
        'status' => 'Assigned for Delivery',
        'updated_at' => now(),
    ]);

    createNotification(
        (int) $riderId,
        'New Delivery Assignment',
        'You have been assigned to deliver Order #' . $id . '. Please pick it up from the Sorting Center.',
        'delivery',
        (int) $id
    );

    createNotification(
        (int) $order->buyer_id,
        'Rider Assigned for Delivery',
        'Your order #' . $id . ' has been assigned to a rider and will be out for delivery soon.',
        'order',
        (int) $id
    );

    return back()->with('success', 'Parcel #' . $id . ' assigned to ' . $rider->name . '.');

})->name('logistics.parcels.assign');


Route::post('/logistics/parcels/{id}/reschedule', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $riderId = request('rider_id');

    if (empty($riderId)) {
        return back()->with('error', 'Please select a rider to reschedule this delivery to.');
    }

    $order = DB::table('orders')->where('id', $id)->first();

    if (!$order) {
        return back()->with('error', 'Parcel not found.');
    }

    if ($order->status !== 'Delivery Failed') {
        return back()->with('error', 'This parcel is not marked as a failed delivery.');
    }

    if ($order->delivery_attempts >= 2) {
        return back()->with('error', 'This parcel has reached the maximum delivery attempts. Please return it to the seller instead.');
    }

    $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->first();

    if (!$rider) {
        return back()->with('error', 'Selected rider not found.');
    }

    DB::table('orders')->where('id', $id)->update([
        'delivery_rider_id' => $riderId,
        'status' => 'Assigned for Delivery',
        'updated_at' => now(),
    ]);

    createNotification(
        (int) $riderId,
        'Delivery Rescheduled',
        'Order #' . $id . ' has been rescheduled to you for another delivery attempt.',
        'delivery',
        (int) $id
    );

    createNotification(
        (int) $order->buyer_id,
        'Delivery Rescheduled',
        'We will attempt to deliver your order #' . $id . ' again shortly.',
        'order',
        (int) $id
    );

    return back()->with('success', 'Parcel #' . $id . ' rescheduled to ' . $rider->name . '.');

})->name('logistics.parcels.reschedule');


Route::post('/logistics/parcels/{id}/return-to-seller', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $order = DB::table('orders')->where('id', $id)->first();

    if (!$order) {
        return back()->with('error', 'Parcel not found.');
    }

    if ($order->status !== 'Delivery Failed') {
        return back()->with('error', 'This parcel is not marked as a failed delivery.');
    }

    DB::table('orders')->where('id', $id)->update([
        'status' => 'Returned to Seller',
        'updated_at' => now(),
    ]);

    createNotification(
        (int) $order->buyer_id,
        'Order Returned to Seller',
        'After repeated failed delivery attempts, order #' . $id . ' has been returned to the seller.',
        'order',
        (int) $id
    );

    $sellerIds = DB::table('order_items')
        ->where('order_id', $id)
        ->distinct()
        ->pluck('seller_id');

    foreach ($sellerIds as $sellerId) {
        createNotification(
            (int) $sellerId,
            'Order Returned to You',
            'Order #' . $id . ' could not be delivered after repeated attempts and has been returned to you. Please restock the items once received.',
            'order',
            (int) $id
        );
    }

    return back()->with('success', 'Parcel #' . $id . ' has been returned to the seller.');

})->name('logistics.parcels.return-to-seller');


Route::post('/seller/order/{id}/restock', function ($id) {

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

    $restoredCount = 0;
    $skippedCount = 0;

    foreach ($items as $item) {

        // Plain products carry no variation_label — restore the base
        // product's stock directly.
        if (empty($item->variation_label)) {

            Product::where('id', $item->product_id)
                ->increment('stock', $item->quantity);

            $restoredCount++;

            continue;
        }

        // Variation items only stored a display label ("Color: Red") at
        // checkout, not a variation_id — but that label is built from the
        // variation's own type/value, so it can be parsed back and matched
        // against this product's variations to find the right stock row.
        [$variationType, $variationValue] = array_pad(
            explode(': ', $item->variation_label, 2),
            2,
            null
        );

        $variation = \App\Models\ProductVariation::where('product_id', $item->product_id)
            ->where('variation_type', $variationType)
            ->where('variation_value', $variationValue)
            ->first();

        if ($variation) {

            $variation->increment('stock', $item->quantity);

            $restoredCount++;

        } else {

            // The variation no longer exists (e.g. removed by the seller
            // since this order was placed) — nothing to safely restock.
            $skippedCount++;
        }
    }

    DB::table('orders')
        ->where('id', $id)
        ->update(['restocked_at' => now()]);

    $message = 'Order #' . $id . ' marked as restocked (' . $restoredCount . ' item(s) added back to inventory).';

    if ($skippedCount > 0) {
        $message .= ' ' . $skippedCount . ' item(s) could not be matched to a variation and were skipped — please adjust stock manually.';
    }

    return back()->with('success', $message);

})->name('seller.order.restock');


Route::post('/logistics/riders/{id}/approve', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $application = DB::table('rider_applications')->where('id', $id)->first();

    if (!$application) {
        return back()->with('error', 'Application not found.');
    }

    DB::table('rider_applications')->where('id', $id)->update([
        'status' => 'Approved',
        'admin_remarks' => null,
        'reviewed_at' => now(),
        'reviewed_by' => $user['id'],
        'updated_at' => now(),
    ]);

    createNotification(
        (int) $application->user_id,
        'Rider Application Approved',
        'Congratulations! Your rider application has been approved. You can now access your rider account.',
        'rider',
        (int) $application->id
    );

    $applicant = DB::table('users')->where('id', $application->user_id)->first();

    if ($applicant) {
        try {
            Mail::to($applicant->email)->send(
                new \App\Mail\ApplicationStatusMail(
                    $application->full_name ?? $applicant->name,
                    'rider',
                    'Approved'
                )
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    return back()->with('success', 'Rider application approved.');

})->name('logistics.riders.approve');


Route::post('/logistics/riders/{id}/reject', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $remarks = trim((string) request('admin_remarks'));

    $application = DB::table('rider_applications')->where('id', $id)->first();

    if (!$application) {
        return back()->with('error', 'Application not found.');
    }

    DB::table('rider_applications')->where('id', $id)->update([
        'status' => 'Rejected',
        'admin_remarks' => $remarks !== '' ? $remarks : null,
        'reviewed_at' => now(),
        'reviewed_by' => $user['id'],
        'updated_at' => now(),
    ]);

    $message = $remarks !== ''
        ? 'Your rider application was rejected. Admin remarks: ' . $remarks
        : 'Your rider application was rejected. Please review your application and try again.';

    createNotification(
        $application->user_id,
        'Rider Application Rejected',
        $message,
        'rider',
        $application->id
    );

    $applicant = DB::table('users')->where('id', $application->user_id)->first();

    if ($applicant) {
        try {
            Mail::to($applicant->email)->send(
                new \App\Mail\ApplicationStatusMail(
                    $application->full_name ?? $applicant->name,
                    'rider',
                    'Rejected',
                    $remarks !== '' ? $remarks : null
                )
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    return back()->with('success', 'Rider application rejected.');

})->name('logistics.riders.reject');


Route::get('/logistics/riders/{id}/document/{field}', function ($id, $field) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $allowedFields = ['national_id', 'drivers_license', 'profile_selfie', 'proof_of_address', 'or_cr'];

    if (!in_array($field, $allowedFields)) {
        abort(404);
    }

    $application = DB::table('rider_applications')->where('id', $id)->first();

    if (!$application || empty($application->$field)) {
        abort(404);
    }

    if (!Storage::disk('local')->exists($application->$field)) {
        abort(404);
    }

    return Storage::disk('local')->response($application->$field);

})->name('logistics.riders.document');


Route::post('/logistics/riders/{userId}/toggle-status', function ($userId) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $status = request('status');

    if (!in_array($status, ['Active', 'Deactivated'])) {
        return back()->with('error', 'Invalid account status.');
    }

    $rider = DB::table('users')->where('id', $userId)->where('role', 'rider')->first();

    if (!$rider) {
        return back()->with('error', 'Rider account not found.');
    }

    DB::table('users')->where('id', $userId)->update(['status' => $status]);

    createNotification(
        $rider->id,
        'Account Status Updated',
        "Your BoomBuy rider account status was changed to \"{$status}\" by the Logistics Center.",
        'account_status'
    );

    return back()->with('success', $rider->name . '\'s account has been set to ' . $status . '.');

})->name('logistics.riders.toggle-status');


// Verify OTP Page
Route::get('/verify-otp', function () {

    if (!session()->has('pending_registration')) {
        return redirect()->route('register');
    }

    return view('pages.verify-otp');

})->name('otp.show');


// Verify OTP Submit
Route::post('/verify-otp', function () {

    $pending = session()->get('pending_registration');

    if (!$pending) {
        return redirect()
            ->route('register')
            ->with(
                'error',
                'Your registration session expired. Please register again.'
            );
    }

    $inputCode = trim(request('otp_code'));

    $storedCode = session()->get('otp_code');
    $expiresAt = session()->get('otp_expires_at');

    /*
    |--------------------------------------------------------------------------
    | VERIFY OTP
    |--------------------------------------------------------------------------
    */

    if (
        empty($storedCode) ||
        $storedCode !== $inputCode ||
        !$expiresAt ||
        now()->greaterThan($expiresAt)
    ) {
        return back()->with(
            'error',
            'Invalid or expired code.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER ACCOUNT
    |--------------------------------------------------------------------------
    */
$role = $pending['role']
    ?? (!empty($pending['vehicle_type']) ? 'rider' : 'buyer');

$user = User::create([
    'name' => $pending['name'] ?? $pending['full_name'],
    'email' => $pending['email'],
    'password' => $pending['password'],
    'role' => $role,
    'phone' => $pending['phone'] ?? null,
    'address' => $pending['address'] ?? null,
    'is_verified' => true,
    'last_name' => $pending['last_name'] ?? null,
    'first_name' => $pending['first_name'] ?? null,
    'middle_initial' => $pending['middle_initial'] ?? null,
    'sex' => $pending['sex'] ?? null,
    'birthdate' => $pending['birthdate'] ?? null,
    'age' => $pending['age'] ?? null,
    'id_photo' => $pending['id_photo'] ?? null,
    'province' => $pending['province'] ?? null,
    'city_municipality' => $pending['city_municipality'] ?? null,
    'barangay' => $pending['barangay'] ?? null,
    'street_address' => $pending['street_address'] ?? null,
]);


    /*
    |--------------------------------------------------------------------------
    | SELLER APPLICATION
    |--------------------------------------------------------------------------
    */

    if ($role === 'seller') {

        DB::table('seller_applications')->insert([

            'user_id' => $user->id,

            'full_name' =>
                $pending['name'] ?? $pending['full_name'],

            'business_name' =>
                $pending['business_name'] ?? null,

            'phone' =>
                $pending['phone'] ?? null,

            'address' =>
                $pending['address'] ?? null,

            'national_id' =>
                $pending['national_id'] ?? null,

            'business_permit' =>
                $pending['business_permit'] ?? null,

            'business_category' =>
                $pending['business_category'] ?? null,

            'status' =>
                'Pending Verification',

            'admin_remarks' => null,

            'reviewed_at' => null,

            'reviewed_by' => null,

            'created_at' => now(),

            'updated_at' => now(),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RIDER APPLICATION
    |--------------------------------------------------------------------------
    */

    if ($role === 'rider') {

        DB::table('rider_applications')->insert([

            'user_id' => $user->id,

            'full_name' =>
                $pending['full_name'] ?? $pending['name'],

            'phone' =>
                $pending['phone'],

            'address' =>
                $pending['address'],

            'vehicle_type' =>
                $pending['vehicle_type'],

            'vehicle_model' =>
                $pending['vehicle_model'],

            'plate_number' =>
                $pending['plate_number'],

            'national_id' =>
                $pending['national_id'],

            'drivers_license' =>
                $pending['drivers_license'],

            'profile_selfie' =>
                $pending['profile_selfie'],

            'proof_of_address' =>
                $pending['proof_of_address'],

            'or_cr' =>
                $pending['or_cr'],

            'status' =>
                'Pending Verification',

            'admin_remarks' =>
                null,

            'reviewed_at' =>
                null,

            'reviewed_by' =>
                null,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),

        ]);

        notifyLogisticsUsers(
            'New Rider Application',
            ($pending['full_name'] ?? $pending['name']) . ' has applied to become a rider and is awaiting review.',
            'rider'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGISTICS APPLICATION
    |--------------------------------------------------------------------------
    */

    if ($role === 'logistics') {

        DB::table('logistics_applications')->insert([
            'user_id' => $user->id,
            'full_name' => $pending['name'] ?? null,
            'business_name' => $pending['business_name'] ?? null,
            'phone' => $pending['phone'] ?? null,
            'address' => $pending['address'] ?? null,
            'id_photo' => $pending['id_photo'] ?? null,
            'business_permit' => $pending['business_permit'] ?? null,
            'status' => 'Pending Verification',
            'admin_remarks' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR OTP SESSION
    |--------------------------------------------------------------------------
    */

    session()->forget([
        'pending_registration',
        'otp_code',
        'otp_email',
        'otp_expires_at'
    ]);


    /*
    |--------------------------------------------------------------------------
    | SELLER
    |--------------------------------------------------------------------------
    */

    if ($user->role === 'seller') {

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your seller account has been created! We\'re verifying your documents — you\'ll be able to log in once approved.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RIDER
    |--------------------------------------------------------------------------
    */

    if ($user->role === 'rider') {

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your rider account has been created! Your application is now pending Admin verification. You can log in once approved.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGISTICS
    |--------------------------------------------------------------------------
    */

    if ($user->role === 'logistics') {

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your Logistics account has been created! We\'re verifying your documents — you\'ll be able to log in once approved.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BUYER APPLICATION
    |--------------------------------------------------------------------------
    */

    if ($user->role === 'buyer') {

        DB::table('buyer_applications')->insert([

            'user_id' => $user->id,
            'full_name' => $pending['name'] ?? $user->name,
            'phone' => $pending['phone'] ?? null,
            'address' => $pending['address'] ?? null,
            'id_photo' => $pending['id_photo'] ?? null,
            'status' => 'Pending Verification',
            'admin_remarks' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),

        ]);

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your account has been created! Please wait for the administrator\'s approval — we\'ll notify you by email once your account is verified.'
            );
    }

})->name('otp.verify');


// Resend OTP
Route::post('/resend-otp', function () {

    $pending = session()->get('pending_registration');

    if (!$pending) {
        return redirect()
            ->route('register')
            ->with(
                'error',
                'Your registration session expired. Please register again.'
            );
    }

    $name = $pending['name']
        ?? $pending['full_name']
        ?? 'BoomBuy User';

    if (!generateAndSendOtp(
        $pending['email'],
        $name
    )) {

        return back()->with(
            'error',
            'We could not resend the verification code right now. Please try again in a moment.'
        );
    }

    return back()->with(
        'success',
        'A new code has been sent to your email.'
    );

})->name('otp.resend');


Route::get('/notifications', function () {

    $user = session()->get('user');

    if (!$user) {
        return redirect()->route('login');
    }

    $notifications = \App\Models\Notification::where(
        'user_id',
        $user['id']
    )
    ->orderByDesc('created_at')
    ->get();

    return view(
        'pages.notifications',
        compact('notifications')
    );

})->name('notifications');


Route::post('/notifications/{id}/read', function ($id) {

    $user = session()->get('user');

    if (!$user) {
        return redirect()->route('login');
    }

    \App\Models\Notification::where('id', $id)
        ->where('user_id', $user['id'])
        ->whereNull('read_at')
        ->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

    return back();

})->name('notifications.read');


// =========================
// ADMIN NOTIFICATIONS
// =========================

Route::get('/admin/notifications', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $adminUser = \App\Models\User::where(
        'email',
        'admin@boombuy.com'
    )->first();

    $notifications = $adminUser
        ? \App\Models\Notification::where(
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

})->name('admin.notifications');


Route::post('/admin/notifications/{id}/read', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    \App\Models\Notification::where('id', $id)
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

})->name('admin.notifications.read');


Route::get('/seller/notifications', function () {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $notifications = \App\Models\Notification::where(
        'user_id',
        $user['id']
    )
    ->orderByDesc('created_at')
    ->get();

    return view(
        'pages.seller.notifications',
        compact('user', 'notifications')
    );

})->name('seller.notifications');


Route::post('/seller/notifications/{id}/read', function ($id) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    \App\Models\Notification::where('id', $id)
        ->where('user_id', $user['id'])
        ->whereNull('read_at')
        ->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

    return back();

})->name('seller.notifications.read');


Route::get('/logistics/notifications', function () {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    $notifications = \App\Models\Notification::where(
        'user_id',
        $user['id']
    )
    ->orderByDesc('created_at')
    ->get();

    return view(
        'pages.logistics.notifications',
        compact('user', 'notifications')
    );

})->name('logistics.notifications');


Route::post('/logistics/notifications/{id}/read', function ($id) {

    $user = requireUserRole('logistics');

    if (!is_array($user)) {
        return $user;
    }

    \App\Models\Notification::where('id', $id)
        ->where('user_id', $user['id'])
        ->whereNull('read_at')
        ->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

    return back();

})->name('logistics.notifications.read');


Route::get('/rider/notifications', function () {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $notifications = \App\Models\Notification::where(
        'user_id',
        $user['id']
    )
    ->orderByDesc('created_at')
    ->get();

    return view(
        'pages.rider.notifications',
        compact('user', 'notifications')
    );

})->name('rider.notifications');

Route::post('/rider/notifications/{id}/read', function ($id) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    \App\Models\Notification::where('id', $id)
        ->where('user_id', $user['id'])
        ->whereNull('read_at')
        ->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

    return back();

})->name('rider.notifications.read');


/*
|--------------------------------------------------------------------------
| COMPLAINTS & DISPUTES
|--------------------------------------------------------------------------
|
| Any logged-in buyer, seller, or rider can file a complaint (about
| another user, an order, or a general platform issue). Admin reviews
| and resolves them from the admin panel.
|
*/

Route::get('/complaints', function () {

    $user = session()->get('user');

    if (!$user) {
        return redirect()->route('login');
    }

    $myComplaints = \App\Models\Complaint::where('complainant_id', $user['id'])
        ->orderByDesc('created_at')
        ->get();

    $myOrders = \Illuminate\Support\Facades\DB::table('orders')
        ->where('buyer_id', $user['id'])
        ->orderByDesc('created_at')
        ->get();

    return view(
        'pages.complaints',
        compact('user', 'myComplaints', 'myOrders')
    );

})->name('complaints.index');


Route::post('/complaints', function () {

    $user = session()->get('user');

    if (!$user) {
        return redirect()->route('login');
    }

    request()->validate([
        'subject' => 'required|string|max:150',
        'description' => 'required|string|max:2000',
        'order_id' => 'nullable|integer',
        'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
    ]);

    $evidencePath = null;

    if (request()->hasFile('evidence')) {
        $evidencePath = request()->file('evidence')->store('complaints', 'public');
    }

    \App\Models\Complaint::create([
        'complainant_id' => $user['id'],
        'complainant_role' => $user['role'],
        'order_id' => request('order_id') ?: null,
        'subject' => request('subject'),
        'description' => request('description'),
        'evidence' => $evidencePath,
        'status' => 'Pending',
    ]);

    return back()->with('success', 'Your complaint has been submitted. Our team will review it shortly.');

})->name('complaints.store');


Route::get('/admin/complaints', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $complaints = \App\Models\Complaint::with('complainant')
        ->orderByDesc('created_at')
        ->get();

    $pendingCount = $complaints->where('status', 'Pending')->count();

    return view(
        'pages.admin.complaints',
        compact('complaints', 'pendingCount')
    );

})->name('admin.complaints');


Route::post('/admin/complaints/{id}/status', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $status = request('status');

    if (!in_array($status, ['Pending', 'Under Review', 'Resolved', 'Dismissed'])) {
        return back()->with('error', 'Invalid status.');
    }

    $complaint = \App\Models\Complaint::find($id);

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

})->name('admin.complaints.update');


/*
|--------------------------------------------------------------------------
| MESSAGES / CHAT
|--------------------------------------------------------------------------
|
| A simple inbox-and-thread messaging system between any two BoomBuy
| accounts (buyer, seller, rider, or admin). Not real-time — messages
| load on page visit/refresh, same as the rest of this app.
|
*/

Route::get('/messages', function () {

    $me = currentMessagingUser();

    if (!$me) {
        return redirect()->route('login');
    }

    $conversations = \App\Models\Message::where('sender_id', $me['id'])
        ->orWhere('recipient_id', $me['id'])
        ->orderByDesc('created_at')
        ->get()
        ->groupBy(function ($message) use ($me) {
            return $message->sender_id === $me['id']
                ? $message->recipient_id
                : $message->sender_id;
        })
        ->map(function ($messages, $partnerId) use ($me) {

            $partner = \App\Models\User::find($partnerId);
            $lastMessage = $messages->first();

            $unreadCount = $messages
                ->where('recipient_id', $me['id'])
                ->whereNull('read_at')
                ->count();

            return [
                'partner_id' => $partnerId,
                'partner_name' => $partner->name ?? 'Deleted User',
                'partner_role' => $partner->role ?? '',
                'last_message' => $lastMessage->message,
                'last_message_at' => $lastMessage->created_at,
                'unread_count' => $unreadCount,
            ];
        })
        ->sortByDesc('last_message_at')
        ->values();

    return view(
        'pages.messages.inbox',
        compact('me', 'conversations')
    );

})->name('messages.index');


Route::get('/messages/{userId}', function ($userId) {

    $me = currentMessagingUser();

    if (!$me) {
        return redirect()->route('login');
    }

    $partner = \App\Models\User::find($userId);

    if (!$partner) {
        return redirect()->route('messages.index')->with('error', 'User not found.');
    }

    $thread = \App\Models\Message::where(function ($query) use ($me, $userId) {
            $query->where('sender_id', $me['id'])->where('recipient_id', $userId);
        })
        ->orWhere(function ($query) use ($me, $userId) {
            $query->where('sender_id', $userId)->where('recipient_id', $me['id']);
        })
        ->orderBy('created_at')
        ->get();

    // Mark incoming messages as read
    \App\Models\Message::where('sender_id', $userId)
        ->where('recipient_id', $me['id'])
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

    return view(
        'pages.messages.thread',
        compact('me', 'partner', 'thread')
    );

})->name('messages.thread');


Route::post('/messages/{userId}', function ($userId) {

    $me = currentMessagingUser();

    if (!$me) {
        return redirect()->route('login');
    }

    request()->validate([
        'message' => 'required|string|max:2000',
    ]);

    $partner = \App\Models\User::find($userId);

    if (!$partner) {
        return back()->with('error', 'User not found.');
    }

    \App\Models\Message::create([
        'sender_id' => $me['id'],
        'recipient_id' => $userId,
        'message' => request('message'),
    ]);

    createNotification(
        $partner->id,
        'New Message from ' . $me['name'],
        request('message'),
        'message',
        $me['id']
    );

    return redirect()->route('messages.thread', $userId);

})->name('messages.store');


/*
|--------------------------------------------------------------------------
| SELLER COMPLIANCE MONITORING
|--------------------------------------------------------------------------
|
| Cross-checks each approved seller's products against the business
| category they were approved for (set by admin during application
| approval), and lets admin flag individual products as prohibited or
| inappropriate — flagged products are hidden from the storefront
| immediately (see /products route) — plus issue a warning notification
| or jump straight to suspending the seller's account.
|
*/

Route::get('/admin/compliance', function () {

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

            $products = \App\Models\Product::where('seller_id', $seller->user_id)->get();

            // business_category is stored as a slug ("electronics") while
            // products.category is stored as its Title Case label
            // ("Electronics") — convert the slug to that same label before
            // comparing, otherwise every product would always "mismatch".
            $categoryNames = [
                'electronics' => 'Electronics',
                'womens-fashion' => "Women's Fashion",
                'mens-fashion' => "Men's Fashion",
                'kids-baby' => 'Kids & Baby',
                'home-living' => 'Home & Living',
                'sports-outdoors' => 'Sports & Outdoors',
                'beauty-personal-care' => 'Beauty & Personal Care',
                'food-beverages' => 'Food & Beverages',
                'automotive' => 'Automotive',
                'office-school' => 'Office & School',
                'pet-supplies' => 'Pet Supplies',
                'toys-games-hobbies' => 'Toys, Games & Hobbies',
                'jewelry-accessories' => 'Jewelry & Accessories',
                'shoes' => 'Shoes',
                'tools-home-improvement' => 'Tools & Home Improvement',
                'garden-outdoor' => 'Garden & Outdoor',
            ];

            $registeredCategoryLabel = $categoryNames[$seller->business_category] ?? $seller->business_category;

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

})->name('admin.compliance');


Route::post('/admin/compliance/products/{id}/flag', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $product = \App\Models\Product::find($id);

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

})->name('admin.compliance.flag');


Route::post('/admin/compliance/products/{id}/unflag', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $product = \App\Models\Product::find($id);

    if (!$product) {
        return back()->with('error', 'Product not found.');
    }

    $product->update(['is_flagged' => false, 'flag_reason' => null]);

    return back()->with('success', 'Product unflagged and restored to the storefront.');

})->name('admin.compliance.unflag');


Route::post('/admin/compliance/warn/{userId}', function ($userId) {

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

})->name('admin.compliance.warn');