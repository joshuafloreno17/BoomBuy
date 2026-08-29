<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


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

// Check logged-in user role
Route::post('/rider/profile/photo', function (\Illuminate\Http\Request $request) {

    $user = requireUserRole('rider');

    if (!is_array($user)) {
        return $user;
    }

    $request->validate([
        'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $file = $request->file('profile_photo');

    $filename = 'rider_' . ($user['id'] ?? 'profile') . '_' . time() . '.' . $file->getClientOriginalExtension();

    $file->storeAs(
        'profile-photos',
        $filename,
        'public'
    );

    // Save photo filename to logged-in user
    $user['profile_photo'] = $filename;

    // Update session user
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


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// Register page
Route::get('/register', function () {
    return view('pages.register');
})->name('register');


// Register
Route::post('/register', function () {

    $name = trim(request('name'));
    $email = strtolower(trim(request('email')));
    $password = request('password');
    $role = strtolower(trim(request('role')));

    // VALIDATION
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($role)
    ) {
        return back()
            ->withInput()
            ->with('error', 'Please complete all fields.');
    }

    // ALLOWED ROLES
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

    // CHECK EXISTING EMAIL
    if (User::where('email', $email)->exists()) {

        return back()
            ->withInput()
            ->with('error', 'Email is already registered.');
    }

    // CREATE USER IN DATABASE
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => $role,
    ]);

    // SAVE LOGIN SESSION
    session([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]
    ]);

    // REDIRECT BASED ON ROLE
    if ($role === 'seller') {

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Welcome to BoomBuy Seller!');

    }

    if ($role === 'rider') {

        return redirect()
            ->route('rider.dashboard')
            ->with('success', 'Welcome to BoomBuy Rider!');

    }

    return redirect()
        ->route('buyer.dashboard')
        ->with('success', 'Welcome to BoomBuy!');

})->name('register.submit');


// Login page
Route::get('/login', function () {
    return view('pages.login');
})->name('login');


// Login
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

    /*
    |--------------------------------------------------------------------------
    | FIND ACCOUNT IN DATABASE
    |--------------------------------------------------------------------------
    */

    $user = DB::table('users')
        ->where('email', $email)
        ->first();

    if (!$user) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK ROLE
    |--------------------------------------------------------------------------
    */

    if (($user->role ?? '') !== $role) {
        return back()
            ->withInput()
            ->with(
                'error',
                'The selected role does not match this account.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK PASSWORD
    |--------------------------------------------------------------------------
    */

    if (!Hash::check($password, $user->password)) {
        return back()
            ->withInput()
            ->with('error', 'Invalid email or password.');
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE LOGGED-IN USER TO SESSION
    |--------------------------------------------------------------------------
    */

    session()->put('user', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
    ]);

    /*
    |--------------------------------------------------------------------------
    | REDIRECT BASED ON ROLE
    |--------------------------------------------------------------------------
    */

    switch ($user->role) {

        case 'seller':

            return redirect()
                ->route('seller.dashboard')
                ->with(
                    'success',
                    'Welcome to BoomBuy Seller!'
                );

        case 'rider':

            return redirect()
                ->route('rider.dashboard')
                ->with(
                    'success',
                    'Welcome to BoomBuy Rider!'
                );

        case 'buyer':

            return redirect()
                ->route('buyer.dashboard')
                ->with(
                    'success',
                    'Welcome to BoomBuy!'
                );

        default:

            session()->forget('user');

            return redirect('/login')
                ->with(
                    'error',
                    'Invalid account role.'
                );
    }

})->name('login.submit');


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

Route::get('/buyer', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

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

    $allOrders = session()->get('orders', []);

    $orders = array_values(
        array_filter(
            $allOrders,
            function ($order) use ($user) {

                return ($order['buyer_id'] ?? null) ===
                    ($user['id'] ?? null);
            }
        )
    );

    // Newest orders first
    $orders = array_reverse($orders);

    return view(
        'pages.buyer.orders',
        compact('user', 'orders')
    );

})->name('buyer.orders');

    /*
    |--------------------------------------------------------------------------
    | NEWEST SELLER PRODUCTS
    |--------------------------------------------------------------------------
    */

    $sellerProducts = session()->get('seller_products', []);

    // Newest seller products first
    $newestProducts = array_reverse($sellerProducts);


    /*
    |--------------------------------------------------------------------------
    | ALL PRODUCTS
    |--------------------------------------------------------------------------
    */

    $products = array_merge(
        $newestProducts,
        session()->get('admin_products', []),
        defaultProducts()
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
| BUYER ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/buyer/orders', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $allOrders = session()->get('orders', []);

    $orders = array_values(
        array_filter(
            $allOrders,
            function ($order) use ($user) {
                return ($order['buyer_id'] ?? null) ===
                    ($user['id'] ?? null);
            }
        )
    );

    $orders = array_reverse($orders);

    return view(
        'pages.buyer.orders',
        compact('user', 'orders')
    );

})->name('buyer.orders');


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

    // ACCOUNTS

    $users = session()->get('users', []);

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




    // ORDERS

    $orders = session()->get('orders', []);

    $totalOrders = count($orders);

    $pendingCount = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Pending';
    }));

    $processingCount = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Processing';
    }));

    $deliveredCount = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Delivered';
    }));

    $cancelledCount = count(array_filter($orders, function ($order) {
        return ($order['status'] ?? '') === 'Cancelled';
    }));


    // PRODUCTS

$products = array_merge(
    session()->get('seller_products', []),
    session()->get('admin_products', []),
    defaultProducts()
);

    $totalProducts = count($products);


    // SALES

    $totalSales = 0;

    foreach ($orders as $order) {
        $totalSales += (float) ($order['total'] ?? 0);
    }


    return view(
        'pages.admin.dashboard',
        compact(
            'users',
            'orders',
            'products',

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
| ADMIN PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/admin/products', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $products = array_merge(
        defaultProducts(),
        session()->get('admin_products', [])
    );

    return view(
        'pages.admin-products',
        compact('products')
    );

})->name('admin.products');


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/products', function () {

    $products = array_merge(
        defaultProducts(),
        session()->get('admin_products', []),
        session()->get('seller_products', [])
    );

    return view(
        'pages.products',
        compact('products')
    );

})->name('products');


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
    $icon = trim(request('icon'));
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

    $users = session()->get('users', []);

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
| ADMIN ORDERS
|--------------------------------------------------------------------------
*/

Route::get('/admin/orders', function () {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $orders = session()->get('orders', []);

    return view(
        'pages.admin.orders',
        compact('orders')
    );

})->name('admin.orders');


Route::get('/admin/order/{id}', function ($id) {

    if (!session()->get('admin_logged_in')) {
        return redirect()->route('admin.login');
    }

    $orders = session()->get('orders', []);

    $order = null;

    foreach ($orders as $item) {

        if (($item['id'] ?? '') === $id) {

            $order = $item;

            break;
        }
    }

    if (!$order) {
        abort(404);
    }

    return view(
        'pages.admin.order-details',
        compact('order')
    );

})->name('admin.order.details');


/*
|--------------------------------------------------------------------------
| ADMIN UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

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
        'Delivered',
        'Cancelled',
    ];

    if (!in_array($status, $allowedStatuses)) {

        return back()->with(
            'error',
            'Invalid order status.'
        );
    }

    $orders = session()->get('orders', []);

    $found = false;

    foreach ($orders as $index => $order) {

        if (($order['id'] ?? '') === $id) {

            $orders[$index]['status'] = $status;

            $found = true;

            break;
        }
    }

    if (!$found) {

        return back()->with(
            'error',
            'Order not found.'
        );
    }

    session()->put(
        'orders',
        $orders
    );

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

    $allProducts = session()->get('seller_products', []);

    $products = array_values(
        array_filter(
            $allProducts,
            function ($product) use ($user) {

                return ($product['seller_id'] ?? '') ===
                    ($user['id'] ?? '');
            }
        )
    );


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
    $icon = trim(request('icon'));
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

    $products = session()->get(
        'seller_products',
        []
    );

    $allProducts = array_merge(
        defaultProducts(),
        session()->get('admin_products', []),
        $products
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

        'seller_id' => $user['id'] ?? null,
        'seller_name' => $user['name'] ?? 'Seller',

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
        'seller_products',
        $products
    );

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

    $allOrders = session()->get('orders', []);

    $orders = [];

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

        if (!empty($sellerItems)) {

            $sellerOrder = $order;

            $sellerOrder['items'] = $sellerItems;

            $sellerOrder['seller_total'] = array_sum(
                array_column($sellerItems, 'subtotal')
            );

            $orders[] = $sellerOrder;
        }
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

    $orders = session()->get('orders', []);

    $order = null;

    foreach ($orders as $item) {

        if (($item['id'] ?? '') !== $id) {
            continue;
        }

        $sellerItems = [];

        foreach (($item['items'] ?? []) as $orderItem) {

            if (
                ($orderItem['seller_id'] ?? null) ===
                ($user['id'] ?? null)
            ) {

                $sellerItems[] = $orderItem;
            }
        }

        if (!empty($sellerItems)) {

            $order = $item;

            $order['items'] = $sellerItems;

            $order['seller_total'] = array_sum(
                array_column($sellerItems, 'subtotal')
            );

            break;
        }
    }

    if (!$order) {
        abort(404);
    }

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

    $orders = session()->get('orders', []);

    $found = false;

    foreach ($orders as $index => $order) {

        if (($order['id'] ?? '') !== $id) {
            continue;
        }

        $hasSellerProduct = false;

        foreach (($order['items'] ?? []) as $item) {

            if (
                ($item['seller_id'] ?? null) ===
                ($user['id'] ?? null)
            ) {

                $hasSellerProduct = true;

                break;
            }
        }

        if ($hasSellerProduct) {

            $orders[$index]['status'] = $status;

            $found = true;

            break;
        }
    }

    if (!$found) {

        return back()->with(
            'error',
            'Order not found or this order does not belong to you.'
        );
    }

    session()->put(
        'orders',
        $orders
    );

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

    // Get all BoomBuy orders
    $orders = session()->get('orders', []);

    $riderId = $user['id'] ?? null;

    // Orders assigned to this rider
    $myDeliveries = array_values(array_filter(
        $orders,
        function ($order) use ($riderId) {
            return ($order['rider_id'] ?? null) === $riderId;
        }
    ));

    // Orders available for pickup
    $availableOrders = array_values(array_filter(
        $orders,
        function ($order) {
            return ($order['status'] ?? '') === 'Ready for Pickup'
                && empty($order['rider_id'] ?? null);
        }
    ));

    // In-transit orders assigned to this rider
    $inTransit = array_values(array_filter(
        $myDeliveries,
        function ($order) {
            return in_array(
                ($order['status'] ?? ''),
                ['Picked Up', 'On the Way']
            );
        }
    ));

    // Delivered orders assigned to this rider
    $delivered = array_values(array_filter(
        $myDeliveries,
        function ($order) {
            return ($order['status'] ?? '') === 'Delivered';
        }
    ));

    return view(
        'pages.rider.dashboard',
        compact(
            'user',
            'myDeliveries',
            'availableOrders',
            'inTransit',
            'delivered'
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

    $orders = session()->get('orders', []);

    $deliveries = [];

    foreach ($orders as $order) {

        $status = $order['status'] ?? 'Pending';

        $assignedRider =
            $order['rider_id'] ?? null;

        // Orders ready for pickup
        if (
            $status === 'Ready for Pickup' &&
            empty($assignedRider)
        ) {

            $deliveries[] = $order;
        }

        // Rider's own deliveries
        if (
            $assignedRider ===
            ($user['id'] ?? null)
        ) {

            $deliveries[] = $order;
        }
    }

    return view(
        'pages.rider.deliveries',
        compact('user', 'deliveries')
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

    $orders = session()->get('orders', []);

    $found = false;

    foreach ($orders as $index => $order) {

        if (($order['id'] ?? '') !== $id) {
            continue;
        }

        if (
            ($order['status'] ?? '') !==
            'Ready for Pickup'
        ) {

            return back()->with(
                'error',
                'This order is not ready for pickup.'
            );
        }

        if (!empty($order['rider_id'])) {

            return back()->with(
                'error',
                'This order has already been assigned to another rider.'
            );
        }

        $orders[$index]['rider_id'] =
            $user['id'] ?? null;

        $orders[$index]['rider_name'] =
            $user['name'] ?? 'Rider';

        $orders[$index]['status'] =
            'Picked Up';

        $orders[$index]['rider_claimed_at'] =
            now()->format('M d, Y h:i A');

        $found = true;

        break;
    }

    if (!$found) {

        return back()->with(
            'error',
            'Delivery not found.'
        );
    }

    session()->put(
        'orders',
        $orders
    );

    return back()->with(
        'success',
        'Delivery successfully claimed!'
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

    $orders = session()->get('orders', []);

    $delivery = null;

    foreach ($orders as $item) {

        if (($item['id'] ?? '') !== $id) {
            continue;
        }

        $isAvailable =
            (
                ($item['status'] ?? '') ===
                'Ready for Pickup'
                &&
                empty($item['rider_id'])
            );

        $isAssigned =
            (
                ($item['rider_id'] ?? '') ===
                ($user['id'] ?? '')
            );

        if ($isAvailable || $isAssigned) {

            $delivery = $item;

            break;
        }
    }

    if (!$delivery) {
        abort(404);
    }

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

    $status = trim(request('status'));

    $allowedStatuses = [
        'Picked Up',
        'On the Way',
        'Delivered',
    ];

    if (!in_array($status, $allowedStatuses)) {

        return back()->with(
            'error',
            'Invalid delivery status.'
        );
    }

    $orders = session()->get('orders', []);

    $found = false;

    foreach ($orders as $index => $order) {

        if (
            ($order['id'] ?? '') === $id &&
            ($order['rider_id'] ?? '') ===
            ($user['id'] ?? '')
        ) {

            $orders[$index]['status'] =
                $status;

            $orders[$index]['updated_at'] =
                now()->format('M d, Y h:i A');

            if ($status === 'Delivered') {

                $orders[$index]['delivered_at'] =
                    now()->format('M d, Y h:i A');
            }

            $found = true;

            break;
        }
    }

    if (!$found) {

        return back()->with(
            'error',
            'Delivery not found or this delivery is not assigned to you.'
        );
    }

    session()->put(
        'orders',
        $orders
    );

    return back()->with(
        'success',
        'Delivery status updated to ' . $status . '.'
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

    // Logged-in Rider
    if ($user && ($user['role'] ?? '') === 'rider') {
        return redirect()->route('rider.dashboard');
    }

    // Guest → Landing Page
    return view('welcome');

})->name('home');


/*
|--------------------------------------------------------------------------
| PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/product-details/{slug}', function ($slug) {

    $products = [];

    // Default products
    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    // Admin products
    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

    // Seller products
    foreach (
        session()->get('seller_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

    if (!isset($products[$slug])) {
        abort(404);
    }

    $product =
        $products[$slug];

    return view(
        'pages.product-details',
        compact('product')
    );

})->name('product.details');


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/


// Add to cart
Route::post('/cart/add/{slug}', function ($slug) {

    $products = [];

    // Default products
    foreach (defaultProducts() as $product) {
        $products[$product['slug']] = $product;
    }

    // Admin products
    foreach (session()->get('admin_products', []) as $product) {

        if (!empty($product['slug'])) {
            $products[$product['slug']] = $product;
        }
    }

    // Seller products
    foreach (session()->get('seller_products', []) as $product) {

        if (!empty($product['slug'])) {
            $products[$product['slug']] = $product;
        }
    }

    // Product does not exist
    if (!isset($products[$slug])) {

        return back()->with('error', 'Product not found.');
    }

    // Get cart
    $cart = session()->get('cart', []);

    // Add product
    $cart[$slug] = ($cart[$slug] ?? 0) + 1;

    // Save cart
    session()->put('cart', $cart);

    // Return to buyer page
    return redirect('/buyer')
        ->with('success', 'Product added to cart!');

})->name('cart.add');



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

    $cart =
        session()->get('cart', []);

    if (empty($cart)) {

        return redirect()
            ->route('cart')
            ->with(
                'error',
                'Your cart is empty.'
            );
    }

    $products = [];

    // Default products
    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    // Admin products
    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

    // Seller products
    foreach (
        session()->get('seller_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

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

Route::post('/checkout/place-order', function () {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $cart =
        session()->get('cart', []);

    if (empty($cart)) {

        return redirect()
            ->route('cart')
            ->with(
                'error',
                'Your cart is empty.'
            );
    }

    $address =
        trim(request('address'));

    $phone =
        trim(request('phone'));

    $payment =
        trim(request('payment'));

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

    $products = [];

    // Default products
    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    // Admin products
    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

    // Seller products
    foreach (
        session()->get('seller_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

    $orderItems = [];

    $total = 0;

    foreach ($cart as $slug => $quantity) {

        if (!isset($products[$slug])) {
            continue;
        }

        $product =
            $products[$slug];

        $quantity =
            (int) $quantity;

        if ($quantity <= 0) {
            continue;
        }

        $subtotal =
            $product['price'] *
            $quantity;

        $orderItems[] = [

            'slug' =>
                $product['slug'],

            'name' =>
                $product['name'],

            'price' =>
                $product['price'],

            'quantity' =>
                $quantity,

            'subtotal' =>
                $subtotal,

            'seller_id' =>
                $product['seller_id']
                ?? null,

            'seller_name' =>
                $product['seller_name']
                ?? 'BoomBuy Official',

        ];

        $total +=
            $subtotal;
    }

    if (empty($orderItems)) {

        return redirect()
            ->route('cart')
            ->with(
                'error',
                'No valid products found in your cart.'
            );
    }

    $orders =
        session()->get(
            'orders',
            []
        );

    $order = [

        'id' =>
            'BB-' .
            strtoupper(
                Str::random(8)
            ),

        'buyer_id' =>
            $user['id']
            ?? null,

        'buyer_name' =>
            $user['name']
            ?? 'Buyer',

        'buyer_email' =>
            $user['email']
            ?? '',

        'address' =>
            $address,

        'phone' =>
            $phone,

        'payment' =>
            $payment,

        'status' =>
            'Pending',

        'items' =>
            $orderItems,

        'total' =>
            $total,

        'date' =>
            now()->format(
                'M d, Y h:i A'
            ),

        'rider_id' =>
            null,

        'rider_name' =>
            null,

    ];

    $orders[] =
        $order;

    session()->put(
        'orders',
        $orders
    );

    session()->forget(
        'cart'
    );

    return redirect()
        ->route(
            'orders.success',
            $order['id']
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

    $orders =
        session()->get(
            'orders',
            []
        );

    $order = null;

    foreach ($orders as $item) {

        if (
            ($item['id'] ?? '') === $id &&
            ($item['buyer_id'] ?? '') ===
            ($user['id'] ?? '')
        ) {

            $order =
                $item;

            break;
        }
    }

    if (!$order) {
        abort(404);
    }

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

Route::get('/seller/products/{slug}/edit', function ($slug) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $products = session()->get('seller_products', []);

    $product = null;

    foreach ($products as $item) {

        if (
            ($item['slug'] ?? '') === $slug &&
            ($item['seller_id'] ?? '') === ($user['id'] ?? '')
        ) {

            $product = $item;

            break;
        }
    }

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

Route::put('/seller/products/{slug}', function ($slug) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $products = session()->get('seller_products', []);

    $found = false;

    foreach ($products as $index => $product) {

        if (
            ($product['slug'] ?? '') === $slug &&
            ($product['seller_id'] ?? '') === ($user['id'] ?? '')
        ) {

            $name = trim(request('name'));
            $category = trim(request('category'));
            $price = (float) request('price');
            $icon = trim(request('icon'));
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

            $products[$index]['name'] = $name;
            $products[$index]['category'] = $categoryName;
            $products[$index]['price'] = $price;
            $products[$index]['icon'] = $icon;
            $products[$index]['description'] = $description;

            $found = true;

            break;
        }
    }

    if (!$found) {

        abort(404);
    }

    session()->put(
        'seller_products',
        $products
    );

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

Route::delete('/seller/products/{slug}', function ($slug) {

    $user = requireUserRole('seller');

    if (!is_array($user)) {
        return $user;
    }

    $products = session()->get('seller_products', []);

    $found = false;

    foreach ($products as $index => $product) {

        if (
            ($product['slug'] ?? '') === $slug &&
            ($product['seller_id'] ?? '') === ($user['id'] ?? '')
        ) {

            unset($products[$index]);

            $found = true;

            break;
        }
    }

    if (!$found) {

        return back()->with(
            'error',
            'Product not found.'
        );
    }

    $products = array_values($products);

    session()->put(
        'seller_products',
        $products
    );

    return back()->with(
        'success',
        'Product deleted successfully!'
    );

})->name('seller.products.delete');

Route::get('/categories', function () {
    return view('categories');
})->name('categories');