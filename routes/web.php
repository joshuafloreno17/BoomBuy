<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
/*
|--------------------------------------------------------------------------
| BOOMBUY - HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function requireUserRole($role)
{
    $user = session()->get('user');

    if (!$user || ($user['role'] ?? '') !== $role) {
        return redirect()->route('login');
    }

    return $user;
}

function cartSummary($cart)
{
    $databaseProducts = \App\Models\Product::whereIn('id', array_keys($cart))
        ->get()
        ->keyBy('id');

    $subtotal = 0;
    $totalItems = 0;

    foreach ($cart as $productId => $quantity) {

        $product = $databaseProducts->get($productId);

        if ($product) {
            $subtotal += (float) $product->price * (int) $quantity;
            $totalItems += (int) $quantity;
        }
    }

    return [
        'subtotal' => number_format($subtotal, 2),
        'total_items' => $totalItems,
        'cart_count' => array_sum($cart),
    ];
}

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



// Default BoomBuy products
function defaultProducts()
{
    return [

        [
            'slug' => 'nova-x5-pro',
            'name' => 'Nova X5 Pro',
            'category' => 'Smartphone',
            'price' => 18999,
            'icon' => '📱',
            'rating' => '4.8',
            'reviews' => 124,
            'description' => 'Powerful smartphone with a vibrant display and long-lasting battery.',
            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)',
            'specs' => [
                'Display' => '6.7-inch AMOLED',
                'Processor' => 'Octa-Core',
                'RAM' => '12GB',
                'Storage' => '256GB',
                'Battery' => '5000mAh',
                'Camera' => '50MP Main Camera',
            ],
        ],

        [
            'slug' => 'airbook-14',
            'name' => 'AirBook 14',
            'category' => 'Laptop',
            'price' => 34990,
            'icon' => '💻',
            'rating' => '4.7',
            'reviews' => 89,
            'description' => 'Lightweight laptop designed for work, school and everyday use.',
            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)',
            'specs' => [
                'Display' => '14-inch Full HD',
                'Processor' => 'Intel Core i5',
                'RAM' => '16GB',
                'Storage' => '512GB SSD',
                'Battery' => 'Up to 10 hours',
                'Weight' => '1.4 kg',
            ],
        ],

        [
            'slug' => 'soundcore-pro',
            'name' => 'SoundCore Pro',
            'category' => 'Audio',
            'price' => 2799,
            'icon' => '🎧',
            'rating' => '4.9',
            'reviews' => 216,
            'description' => 'Wireless headphones with clear sound and deep bass.',
            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)',
            'specs' => [
                'Type' => 'Wireless Headphones',
                'Connection' => 'Bluetooth 5.3',
                'Battery' => '40 Hours',
                'Driver' => '40mm',
                'Microphone' => 'Built-in',
                'Charging' => 'USB-C',
            ],
        ],

        [
            'slug' => 'fitwatch-s2',
            'name' => 'FitWatch S2',
            'category' => 'Wearable',
            'price' => 3499,
            'icon' => '⌚',
            'rating' => '4.6',
            'reviews' => 73,
            'description' => 'Smart wearable with fitness tracking and smart functions.',
            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)',
            'specs' => [
                'Display' => '1.8-inch AMOLED',
                'Battery' => '7 Days',
                'Water Resistance' => '5 ATM',
                'Connectivity' => 'Bluetooth 5.2',
                'Sensors' => 'Heart Rate + SpO2',
                'Compatibility' => 'Android / iOS',
            ],
        ],

        [
            'slug' => 'gamepad-x',
            'name' => 'GamePad X',
            'category' => 'Accessories',
            'price' => 2199,
            'icon' => '🎮',
            'rating' => '4.8',
            'reviews' => 61,
            'description' => 'Comfortable wireless controller designed for gaming.',
            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)',
            'specs' => [
                'Connection' => 'Wireless',
                'Battery' => '20 Hours',
                'Compatibility' => 'PC / Android',
                'Charging' => 'USB-C',
                'Vibration' => 'Dual Vibration',
                'Buttons' => 'Programmable',
            ],
        ],

        [
            'slug' => 'mechakeys-75',
            'name' => 'MechaKeys 75',
            'category' => 'Accessories',
            'price' => 3299,
            'icon' => '⌨️',
            'rating' => '4.7',
            'reviews' => 95,
            'description' => 'Compact mechanical keyboard built for productivity and gaming.',
            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)',
            'specs' => [
                'Layout' => '75%',
                'Switches' => 'Mechanical',
                'Connection' => 'USB-C',
                'Lighting' => 'RGB',
                'Keycaps' => 'PBT',
                'Compatibility' => 'Windows / Mac',
            ],
        ],

        [
            'slug' => 'glide-mouse-x',
            'name' => 'Glide Mouse X',
            'category' => 'Accessories',
            'price' => 1499,
            'icon' => '🖱️',
            'rating' => '4.6',
            'reviews' => 54,
            'description' => 'Lightweight wireless mouse with a precise sensor.',
            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)',
            'specs' => [
                'Sensor' => '12,000 DPI',
                'Connection' => 'Wireless',
                'Battery' => '70 Hours',
                'Weight' => '68g',
                'Buttons' => '6 Buttons',
                'Compatibility' => 'Windows / Mac',
            ],
        ],

        [
            'slug' => 'minisound-go',
            'name' => 'MiniSound Go',
            'category' => 'Audio',
            'price' => 1899,
            'icon' => '🔊',
            'rating' => '4.7',
            'reviews' => 108,
            'description' => 'Portable Bluetooth speaker made for music anywhere.',
            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)',
            'specs' => [
                'Connection' => 'Bluetooth 5.3',
                'Battery' => '15 Hours',
                'Power' => '20W',
                'Water Resistance' => 'IPX7',
                'Charging' => 'USB-C',
                'Range' => '15 meters',
            ],
        ],

    ];
}


Route::get('/login', function () { return view('pages.login'); })->name('login');


Route::post('/login', function () {

    $email = strtolower(trim(request('email')));
    $password = request('password');
    $role = strtolower(trim(request('role')));

    if (
        empty($email) ||
        empty($password) ||
        empty($role)
    ) {
        return back()
            ->withInput()
            ->with(
                'error',
                'Please enter your email, password, and select your role.'
            );
    }

    $allowedRoles = [
        'buyer',
        'seller',
        'rider'
    ];

    if (!in_array($role, $allowedRoles)) {
        return back()
            ->withInput()
            ->with('error', 'Invalid account role.');
    }

    $user = DB::table('users')
        ->where('email', $email)
        ->first();

    if (!$user) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    if (($user->role ?? '') !== $role) {
        return back()
            ->withInput()
            ->with(
                'error',
                'The selected role does not match this account.'
            );
    }

    if (!Hash::check($password, $user->password)) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    // Sellers and riders must have an Approved application before they
    // can log in — this is what actually enforces the ID/document
    // verification we collect at registration.
    if (in_array($user->role, ['seller', 'rider'])) {

        $applicationTable = $user->role === 'seller'
            ? 'seller_applications'
            : 'rider_applications';

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

/// Register - Role Selection
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
    | SAVE RESET USER TEMPORARILY
    |--------------------------------------------------------------------------
    */

    session([
        'reset_user_id' => $user->id
    ]);

    return redirect()
        ->route('password.reset')
        ->with(
            'success',
            'Account found. You can now create a new password.'
        );

})->name('password.email');


// ==========================================================
// RESET PASSWORD PAGE
// ==========================================================

Route::get('/reset-password', function () {

    if (!session()->has('reset_user_id')) {

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

    $userId = session('reset_user_id');

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

    if (empty($password) || empty($confirmation)) {

        return back()
            ->with('error', 'Please complete both password fields.');
    }

    if (strlen($password) < 6) {

        return back()
            ->with(
                'error',
                'Password must be at least 6 characters.'
            );
    }

    if ($password !== $confirmation) {

        return back()
            ->with(
                'error',
                'Passwords do not match.'
            );
    }

    $user = User::find($userId);

    if (!$user) {

        session()->forget('reset_user_id');

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

    session()->forget('reset_user_id');

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
    |
    | The Buyer page now gets products directly from
    | the products table.
    |
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

    $orders = $orders->map(function ($order) {

        $order = (array) $order;

        // Get order items
        $items = DB::table('order_items')
            ->where('order_id', $order['id'])
            ->get();

        $order['items'] = $items->map(function ($item) {

            $item = (array) $item;

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

    return back()->with(
        'success',
        'Order received successfully!'
    );

})->name('buyer.order.received');


Route::post('/buyer/orders/{id}/rate', function ($id) {

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

    // Puwede lang mag-rate kapag Delivered na
    if ($order->status !== 'Delivered') {
        return back()->with(
            'error',
            'You can only rate an order after it has been delivered.'
        );
    }

    // Puwede lang mag-rate kapag na-confirm nang received
    if (empty($order->buyer_received_at)) {
        return back()->with(
            'error',
            'Please confirm that you received the order first.'
        );
    }

    $productId = (int) request('product_id');
    $rating = (int) request('rating');
    $review = trim((string) request('review'));

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        return back()->with(
            'error',
            'Please select a rating from 1 to 5 stars.'
        );
    }

    // Siguraduhing kasama talaga sa order ang product
    $orderItem = DB::table('order_items')
        ->where('order_id', $order->id)
        ->where('product_id', $productId)
        ->first();

    if (!$orderItem) {
        return back()->with(
            'error',
            'This product is not part of your order.'
        );
    }

    // Huwag payagan ang duplicate rating
    $existingReview = DB::table('product_reviews')
        ->where('buyer_id', $user['id'])
        ->where('order_id', $order->id)
        ->where('product_id', $productId)
        ->exists();

    if ($existingReview) {
        return back()->with(
            'error',
            'You have already rated this product.'
        );
    }

    // Save rating
    DB::table('product_reviews')->insert([
        'buyer_id' => $user['id'],
        'order_id' => $order->id,
        'product_id' => $productId,
        'rating' => $rating,
        'review' => $review !== '' ? $review : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with(
        'success',
        'Thank you! Your product rating has been submitted.'
    );

})->name('buyer.order.rate');


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

Route::get('/admin/login', function () {

    return view('pages.admin.login');

})->name('admin.login');


Route::post('/admin/login', function () {

    $email = strtolower(trim(request('email')));
    $password = trim(request('password'));

    if (
        $email === 'admin@boombuy.com' &&
        $password === 'admin123'
    ) {

        session()->put('admin_logged_in', true);

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
            'rider'
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
$image = request()->file('image');
$description = trim(request('description'));

    if (
        empty($name) ||
        empty($category) ||
        $price <= 0 ||
        empty($icon) ||
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

    $slug = Str::slug($name);

    $products = session()->get('admin_products', []);

    $allProducts = array_merge(
        defaultProducts(),
        $products,
        session()->get('seller_products', [])
    );

    foreach ($allProducts as $product) {

        if (($product['slug'] ?? '') === $slug) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'A product with this name already exists.'
                );
        }
    }

    $products[] = [

        'slug' => $slug,
        'name' => $name,
        'category' => $categoryName,
        'price' => $price,
        'icon' => $icon,
        'description' => $description,
        'rating' => '5.0',
        'reviews' => 0,

        'background' =>
            'linear-gradient(145deg, #e8f2ff, #d5e8ff)',

        'specs' => [

            'Category' => $categoryName,
            'Availability' => 'In Stock',
            'Shipping' => 'Free Delivery',
            'Warranty' => '1 Year Warranty',

        ],

    ];

    session()->put(
        'admin_products',
        $products
    );

    return redirect()
        ->route('admin.products')
        ->with(
            'success',
            $name . ' has been added successfully!'
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

    // GET ALL REGISTERED ACCOUNTS FROM DATABASE
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
            'rider'
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

    return view(
        'pages.admin.accounts',
        compact('users')
    );

})->name('admin.accounts');


Route::get('/admin/settings', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    return view('pages.admin.settings');

})->name('admin.settings');


Route::delete('/admin/accounts/{id}', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $user = DB::table('users')
        ->where('id', $id)
        ->whereIn('role', ['buyer', 'seller', 'rider'])
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


/*
|--------------------------------------------------------------------------
| ADMIN REPORTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/reports', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $users = session()->get('users', []);
    $orders = session()->get('orders', []);

    $products = array_merge(
        session()->get('seller_products', []),
        session()->get('admin_products', []),
        defaultProducts()
    );

    $totalUsers = count($users);
    $totalOrders = count($orders);
    $totalProducts = count($products);

    $totalSales = 0;

    foreach ($orders as $order) {
        if (($order['status'] ?? '') === 'Delivered') {
            $totalSales += (float) ($order['total'] ?? 0);
        }
    }

    $pendingOrders = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Pending';
    }));

    $processingOrders = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Processing';
    }));

    $deliveredOrders = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Delivered';
    }));

    $cancelledOrders = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Cancelled';
    }));

    return view(
        'pages.admin.reports',
        compact(
            'users',
            'orders',
            'products',
            'totalUsers',
            'totalOrders',
            'totalProducts',
            'totalSales',
            'pendingOrders',
            'processingOrders',
            'deliveredOrders',
            'cancelledOrders'
        )
    );

})->name('admin.reports');

/*
|--------------------------------------------------------------------------
| ADMIN — SELLER & RIDER APPLICATIONS
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

    $riderApplications = DB::table('rider_applications')
        ->join('users', 'users.id', '=', 'rider_applications.user_id')
        ->select('rider_applications.*', 'users.email as user_email')
        ->orderByDesc('rider_applications.created_at')
        ->get();

    return view(
        'pages.admin.applications',
        compact('sellerApplications', 'riderApplications')
    );

})->name('admin.applications');


Route::post('/admin/applications/{type}/{id}/approve', function ($type, $id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    if (!in_array($type, ['seller', 'rider'])) {
        abort(404);
    }

    $table = $type === 'seller' ? 'seller_applications' : 'rider_applications';

    $updated = DB::table($table)->where('id', $id)->update([
        'status' => 'Approved',
        'admin_remarks' => null,
        'reviewed_at' => now(),
        'updated_at' => now(),
    ]);

    if (!$updated) {
        return back()->with('error', 'Application not found.');
    }

    return back()->with('success', ucfirst($type) . ' application approved.');

})->name('admin.applications.approve');


Route::post('/admin/applications/{type}/{id}/reject', function ($type, $id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    if (!in_array($type, ['seller', 'rider'])) {
        abort(404);
    }

    $table = $type === 'seller' ? 'seller_applications' : 'rider_applications';

    $remarks = trim((string) request('admin_remarks'));

    $updated = DB::table($table)->where('id', $id)->update([
        'status' => 'Rejected',
        'admin_remarks' => $remarks !== '' ? $remarks : null,
        'reviewed_at' => now(),
        'updated_at' => now(),
    ]);

    if (!$updated) {
        return back()->with('error', 'Application not found.');
    }

    return back()->with('success', ucfirst($type) . ' application rejected.');

})->name('admin.applications.reject');


Route::get('/admin/applications/{type}/{id}/document/{field}', function ($type, $id, $field) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    if (!in_array($type, ['seller', 'rider'])) {
        abort(404);
    }

    $table = $type === 'seller' ? 'seller_applications' : 'rider_applications';

    $allowedFields = $type === 'seller'
        ? ['national_id', 'business_permit']
        : ['national_id', 'drivers_license', 'profile_selfie', 'proof_of_address', 'or_cr'];

    if (!in_array($field, $allowedFields)) {
        abort(404);
    }

    $application = DB::table($table)->where('id', $id)->first();

    if (!$application || empty($application->$field)) {
        abort(404);
    }

    if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($application->$field)) {
        abort(404);
    }

    return \Illuminate\Support\Facades\Storage::disk('local')->response($application->$field);

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
    )->latest()->get();


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

    // 📦 Total Products
    $totalProducts = count($products);


    // 🧾 Total Orders
    $totalOrders = count($sellerOrders);


    // ⏳ Pending Orders
    $pendingOrders = 0;


    // 💰 Total Sales
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
    | SELLER DASHBOARD
    |--------------------------------------------------------------------------
    */

    return view(
        'pages.seller.dashboard',
        compact(
            'user',
            'products',
            'sellerOrders',
            'totalProducts',
            'totalOrders',
            'pendingOrders',
            'totalSales'
        )
    );


})->name('seller.dashboard');
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

    return view('pages.seller.add-product');

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
    $description = trim(request('description'));

    // Validate basic fields
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

    // Prevent duplicate product names
    $existingProduct = Product::where(
        'name',
        $name
    )->first();

    if ($existingProduct) {
        return back()
            ->withInput()
            ->with(
                'error',
                'A product with this name already exists.'
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
    Product::create([
        'seller_id' => $user['id'],
        'name' => $name,
        'category' => $categoryName,
        'price' => $price,
        'stock' => $stock,
        'description' => $description,
        'image' => $imagePath,
    ]);

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

    return view(
        'pages.seller.orders',
        compact('user', 'orders')
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

    return view(
        'pages.seller.order-details',
        compact('user', 'order')
    );

})->name('seller.order.details');


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

    // Pending → Processing
    if (
        $order->status === 'Pending' &&
        $status !== 'Processing'
    ) {

        return back()->with(
            'error',
            'Pending orders must be moved to Processing first.'
        );
    }

    // Processing → Ready for Pickup
    if (
        $order->status === 'Processing' &&
        $status !== 'Ready for Pickup'
    ) {

        return back()->with(
            'error',
            'Processing orders must be moved to Ready for Pickup.'
        );
    }

    // Once Ready for Pickup, seller can no longer update it
    if ($order->status === 'Ready for Pickup') {

        return back()->with(
            'error',
            'This order is already Ready for Pickup and can no longer be updated by the seller.'
        );
    }

    // Delivered orders cannot be updated
    if ($order->status === 'Delivered') {

        return back()->with(
            'error',
            'Delivered orders cannot be updated by the seller.'
        );
    }

    // Update order status
  DB::table('orders')
    ->where('id', $id)
    ->update([
        'status' => $status,
        'updated_at' => now(),
    ]);

    return back()->with(
        'success',
        'Order status updated to ' . $status . '.'
    );

})->name('seller.order.status');

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

            // OR orders already assigned to this rider
            ->orWhere('rider_id', $riderId);

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
        return back()->with('error', 'Delivery not found.');
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

    // Assign rider and mark as Picked Up
    DB::table('orders')
        ->where('id', $id)
        ->update([
            'rider_id' => $riderId,
            'status' => 'Picked Up',
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Order successfully picked up!'
    );

})->name('rider.delivery.claim');

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

    // Check if this order belongs to the logged-in rider
    $isAssigned =
        (int) $delivery->rider_id === (int) $riderId;

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

    // Make sure this delivery belongs to this rider
    if ((int) $order->rider_id !== (int) $riderId) {
        return back()->with(
            'error',
            'This delivery is not assigned to you.'
        );
    }

    // Picked Up → Out for Delivery
    if ($order->status === 'Picked Up') {

        if ($status !== 'Out for Delivery') {
            return back()->with(
                'error',
                'Picked Up orders must be moved to Out for Delivery first.'
            );
        }

    }

    // Out for Delivery → Delivered
    elseif ($order->status === 'Out for Delivery') {

        if ($status !== 'Delivered') {
            return back()->with(
                'error',
                'Out for Delivery orders can only be marked as Delivered.'
            );
        }

    }

    // Prevent invalid updates
    else {

        return back()->with(
            'error',
            'This order cannot be updated from its current status.'
        );

    }

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

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

    return view(
        'pages.product-details',
        compact(
            'product',
            'reviews',
            'reviewCount',
            'averageRating',
            'relatedProducts',
            'isWishlisted'
        )
    );

})->name('product.details');

/*
|--------------------------------------------------------------------------
| BUY NOW
|--------------------------------------------------------------------------
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

    // Check available stock
    if ($quantity > $product->stock) {
        return back()->with('error', 'Not enough stock available.');
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE BUY NOW PRODUCT
    |--------------------------------------------------------------------------
    */

    session()->put('buy_now', [
        $product->id => $quantity
    ]);

    return redirect()
        ->route('checkout');

})->name('buy.now');

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
    | CHECK STOCK
    |--------------------------------------------------------------------------
    */

    $stock = (int) $product->stock;

    if ($stock <= 0) {
        return back()->with(
            'error',
            'This product is currently out of stock.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET CURRENT CART
    |--------------------------------------------------------------------------
    */

    $cart = session()->get('cart', []);

    $currentQuantity = (int) ($cart[$product->id] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | PREVENT EXCEEDING STOCK
    |--------------------------------------------------------------------------
    */

    if ($currentQuantity >= $stock) {
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

    $cart[$product->id] = $currentQuantity + 1;

    /*
    |--------------------------------------------------------------------------
    | SAVE CART
    |--------------------------------------------------------------------------
    */

    session()->put('cart', $cart);

    /*
    |--------------------------------------------------------------------------
    | RETURN TO BUYER DASHBOARD
    |--------------------------------------------------------------------------
    */

   return back()->with(
    'success',
    '✓ Added to cart!'
);

})->name('cart.add');

// Buy Now
Route::post('/buy-now/{id}', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    // Find product
    $product = Product::find($id);

    if (!$product) {
        return back()->with(
            'error',
            'Product not found.'
        );
    }

    // Check stock
    $stock = (int) $product->stock;

    if ($stock <= 0) {
        return back()->with(
            'error',
            'This product is currently out of stock.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUY NOW = 1 QUANTITY
    |--------------------------------------------------------------------------
    */

    session()->put('buy_now', [
        $product->id => 1
    ]);

    /*
    |--------------------------------------------------------------------------
    | DIRECTLY GO TO CHECKOUT
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('checkout');

})->name('buy.now');



// Update cart
Route::post('/cart/update/{slug}', function ($slug) {

    $cart =
        session()->get('cart', []);

    if (!isset($cart[$slug])) {
        return back();
    }

    $action =
        request('action');

    if ($action === 'increase') {

        $cart[$slug]++;

    } elseif ($action === 'decrease') {

        $cart[$slug]--;

        if ($cart[$slug] <= 0) {

            unset($cart[$slug]);
        }
    }

    session()->put(
        'cart',
        $cart
    );

    if (request()->wantsJson()) {

        $newQuantity = $cart[$slug] ?? 0;
        $product = Product::find($slug);

        return response()->json(array_merge(
            [
                'removed' => $newQuantity <= 0,
                'quantity' => $newQuantity,
                'item_total' => $product
                    ? number_format((float) $product->price * $newQuantity, 2)
                    : '0.00',
            ],
            cartSummary($cart)
        ));
    }

    return back();

})->name('cart.update');


// Remove from cart
Route::post('/cart/remove/{slug}', function ($slug) {

    $cart =
        session()->get('cart', []);

    if (isset($cart[$slug])) {

        unset($cart[$slug]);
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

    return view(
        'pages.cart',
        compact('cart')
    );

})->name('cart');


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
    }

    /*
    |--------------------------------------------------------------------------
    | GET PRODUCTS USING DATABASE IDs
    |--------------------------------------------------------------------------
    */

    $productIds = array_map(
        'intval',
        array_keys($cart)
    );

    $products = Product::whereIn('id', $productIds)
        ->get()
        ->keyBy('id');

    /*
    |--------------------------------------------------------------------------
    | CHECK CART PRODUCTS
    |--------------------------------------------------------------------------
    */

    foreach ($cart as $productId => $quantity) {

        if (!$products->has((int) $productId)) {

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
    | SEND TO CHECKOUT PAGE
    |--------------------------------------------------------------------------
    */

    return view(
        'pages.checkout',
        compact(
            'user',
            'cart',
            'products'
        )
    );

})->name('checkout');



/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/

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

    foreach ($cart as $productId => $quantity) {

        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | FIND PRODUCT BY DATABASE ID
        |--------------------------------------------------------------------------
        */

        $product = Product::find((int) $productId);

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
        | CHECK STOCK
        |--------------------------------------------------------------------------
        */

        if ((int) $product->stock < $quantity) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    $product->name .
                    ' does not have enough stock.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CALCULATE SUBTOTAL
        |--------------------------------------------------------------------------
        */

        $subtotal =
            (float) $product->price * $quantity;

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

            'price' =>
                $product->price,

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
    | CREATE ORDER
    |--------------------------------------------------------------------------
    */

    $orderId = DB::table('orders')->insertGetId([

        'buyer_id' =>
            $user['id'],

        'total_amount' =>
            $total,

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
        | REDUCE PRODUCT STOCK
        |--------------------------------------------------------------------------
        */

        Product::where(
            'id',
            $item['product_id']
        )->decrement(
            'stock',
            $item['quantity']
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

        session()->forget('cart');
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
        ->where('id', '!=', $product->id)
        ->first();

    if ($existingProduct) {

        return back()
            ->withInput()
            ->with(
                'error',
                'A product with this name already exists.'
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



Route::get('/categories', function () {
    return view('pages.categories');
})->name('categories');


Route::post('/buyer/order/{orderId}/return-refund', function ($orderId) {
    $user = requireUserRole('buyer');
    if (!is_array($user)) return $user;

    $order = DB::table('orders')
        ->where('id', $orderId)
        ->where('buyer_id', $user['id'])
        ->first();

    if (!$order) {
        return back()->with('error', 'Order not found.');
    }

    if ($order->status !== 'Delivered') {
        return back()->with('error', 'Only delivered orders can be returned or refunded.');
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
        return back()->with('error', 'A return/refund request already exists for this item.');
    }

    $refundAmount = (float) $item->price * (int) $item->quantity;

    DB::table('return_refund_requests')->insert([
        'order_id' => $orderId,
        'order_item_id' => $orderItemId,
        'buyer_id' => $user['id'],
        'seller_id' => $item->seller_id,
        'request_type' => $requestType,
        'reason' => $reason,
        'message' => $message ?: null,
        'evidence' => null,
        'status' => 'pending',
        'refund_amount' => $refundAmount,
        'seller_note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with(
        'success',
        'Your ' . strtolower($requestType) . ' request has been submitted successfully.'
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

    DB::table('return_refund_requests')
        ->where('id', $id)
        ->update([
            'status' => 'rejected',
            'seller_note' => 'Request rejected by seller.',
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

    return view('pages.rider.apply', compact('application'));
})->name('rider.apply');

use Illuminate\Support\Facades\Storage;

// =========================
// RIDER APPLICATION SUBMIT
// =========================

Route::post('/rider/apply', function (\Illuminate\Http\Request $request) {

    $validated = $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
        'address' => ['required', 'string', 'max:1000'],

        'vehicle_type' => [
            'required',
            'in:Motorcycle,Car,Van'
        ],

        'vehicle_model' => ['required', 'string', 'max:255'],
        'plate_number' => ['required', 'string', 'max:50'],

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
            'max:5120'
        ],

        'drivers_license' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:5120'
        ],

        'profile_selfie' => [
            'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120'
        ],

        'proof_of_address' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:5120'
        ],

        'or_cr' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:5120'
        ],

        'terms' => ['accepted'],
    ], [
        'terms.accepted' => 'Please agree to the Terms & Conditions and Privacy Policy.',
    ]);

    $userId = DB::transaction(function () use ($request, $validated) {

        // CREATE RIDER ACCOUNT
        $user = \App\Models\User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make(
                $validated['password']
            ),
            'role' => 'rider',
        ]);

        // FILE STORAGE
        $folder = 'rider-applications/' . $user->id;

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

        // CREATE RIDER APPLICATION
        DB::table('rider_applications')->insert([
            'user_id' => $user->id,

            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],

            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_model' => $validated['vehicle_model'],
            'plate_number' => $validated['plate_number'],

            'national_id' => $nationalIdPath,
            'drivers_license' => $driversLicensePath,
            'profile_selfie' => $selfiePath,
            'proof_of_address' => $proofOfAddressPath,
            'or_cr' => $orCrPath,

            'status' => 'Pending Verification',

            'admin_remarks' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->id;
    });

    return redirect()
        ->route('login')
        ->with(
            'success',
            'Rider application submitted successfully! Your account is pending Admin verification.'
        );

})->name('rider.apply.submit');

// Buyer Registration Page
Route::get('/buyer/register', function () {
    return view('pages.buyer.register');
})->name('buyer.register');


// Buyer Registration Submit
Route::post('/buyer/register', function () {

    $name = trim(request('name'));
    $email = strtolower(trim(request('email')));
    $password = request('password');
    $passwordConfirmation = request('password_confirmation');
    $phone = trim(request('phone'));
    $address = trim(request('address'));

    // VALIDATION
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($passwordConfirmation) ||
        empty($phone) ||
        empty($address)
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

    // Hold registration data until OTP is verified — walang naka-save sa DB pa
    session()->put('pending_registration', [
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'phone' => $phone,
        'address' => $address,
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

    $name = trim($request->input('name'));
    $email = strtolower(trim($request->input('email')));
    $password = $request->input('password');
    $passwordConfirmation = $request->input('password_confirmation');
    $phone = trim($request->input('phone'));
    $address = trim($request->input('address'));

    // VALIDATION
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($passwordConfirmation) ||
        empty($phone) ||
        empty($address)
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
            'max:5120',
        ],

        'business_permit' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:5120',
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
        'email' => $email,
        'password' => Hash::make($password),
        'phone' => $phone,
        'address' => $address,
        'role' => 'seller',
        'national_id' => $nationalIdPath,
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

})->name('seller.register.submit');


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
        return redirect()->route('register')
            ->with('error', 'Your registration session expired. Please register again.');
    }

    $inputCode = trim(request('otp_code'));

    $storedCode = session()->get('otp_code');
    $expiresAt = session()->get('otp_expires_at');

    if (
        empty($storedCode) ||
        $storedCode !== $inputCode ||
        !$expiresAt ||
        now()->greaterThan($expiresAt)
    ) {
        return back()->with('error', 'Invalid or expired code.');
    }

    // Ngayon lang gagawin ang account, matapos ma-verify ang email
    $user = User::create([
        'name' => $pending['name'],
        'email' => $pending['email'],
        'password' => $pending['password'],
        'role' => $pending['role'],
        'phone' => $pending['phone'],
        'address' => $pending['address'],
        'is_verified' => true,
    ]);

    // Sellers also submitted verification documents during registration —
    // create their application record now that the account exists.
    if ($pending['role'] === 'seller') {
        DB::table('seller_applications')->insert([
            'user_id' => $user->id,

            'full_name' => $pending['name'],
            'phone' => $pending['phone'],
            'address' => $pending['address'],

            'national_id' => $pending['national_id'] ?? null,
            'business_permit' => $pending['business_permit'] ?? null,

            'status' => 'Pending Verification',

            'admin_remarks' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    session()->forget(['pending_registration', 'otp_code', 'otp_email', 'otp_expires_at']);

    // Sellers need admin approval before they can access their account —
    // send them to login (which enforces that check) instead of auto
    // logging them in.
    if ($user->role === 'seller') {
        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your seller account has been created! We\'re verifying your documents — you\'ll be able to log in once approved.'
            );
    }

    session()->put('user', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'profile_photo' => $user->profile_photo,
    ]);

    return redirect()
        ->route('buyer.dashboard')
        ->with('success', 'Welcome to BoomBuy!');

})->name('otp.verify');


// Resend OTP
Route::post('/resend-otp', function () {

    $pending = session()->get('pending_registration');

    if (!$pending) {
        return redirect()->route('register');
    }

    if (!generateAndSendOtp($pending['email'], $pending['name'])) {
        return back()->with('error', 'We could not resend the verification code right now. Please try again in a moment.');
    }

    return back()->with('success', 'A new code has been sent to your email.');

})->name('otp.resend');