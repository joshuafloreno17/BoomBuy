<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;


/*
|--------------------------------------------------------------------------
| BOOMBUY - HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

// Check logged-in user role
function requireUserRole($role)
{
    $user = session()->get('user');

    if (!$user || ($user['role'] ?? '') !== $role) {
        return redirect()->route('login');
    }

    return $user;
}


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
    $password = trim(request('password'));
    $role = strtolower(trim(request('role')));

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

    $users = session()->get('users', []);

    foreach ($users as $user) {

        if (
            strtolower($user['email'] ?? '') === $email
        ) {
            return back()
                ->withInput()
                ->with('error', 'Email is already registered.');
        }
    }

    $user = [
        'id' => (string) Str::uuid(),
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => $role,
    ];

    $users[] = $user;

    session()->put('users', $users);

    session()->put('user', $user);

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
    $password = trim(request('password'));
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

    $users = session()->get('users', []);

    foreach ($users as $user) {

        if (
            strtolower($user['email'] ?? '') === $email &&
            ($user['password'] ?? '') === $password
        ) {

            if (($user['role'] ?? '') !== $role) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'The selected role does not match this account.'
                    );
            }

            session()->put('user', $user);

            session()->save();

            switch ($user['role']) {

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
        }
    }

    return back()
        ->withInput()
        ->with(
            'error',
            'Invalid email or password.'
        );

})->name('login.submit');


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

    $products = array_merge(
        defaultProducts(),
        session()->get('admin_products', []),
        session()->get('seller_products', [])
    );

    return view(
        'pages.buyer.dashboard',
        compact('user', 'products')
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

                return ($order['buyer_id'] ?? '') ===
                    ($user['id'] ?? '');
            }
        )
    );

    return view(
        'pages.buyer.orders',
        compact('user', 'orders')
    );

})->name('buyer.orders');


Route::get('/buyer/order/{id}', function ($id) {

    $user = requireUserRole('buyer');

    if (!is_array($user)) {
        return $user;
    }

    $orders = session()->get('orders', []);

    $order = null;

    foreach ($orders as $item) {

        if (
            ($item['id'] ?? '') === $id &&
            ($item['buyer_id'] ?? '') ===
            ($user['id'] ?? '')
        ) {

            $order = $item;

            break;
        }
    }

    if (!$order) {
        abort(404);
    }

    return view(
        'pages.buyer.order-details',
        compact('user', 'order')
    );

})->name('buyer.order.details');


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

    // =========================
    // ACCOUNTS
    // =========================

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


    // =========================
    // ORDERS
    // =========================

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


    // =========================
    // PRODUCTS
    // =========================

    $products = array_merge(
        defaultProducts(),
        session()->get('admin_products', []),
        session()->get('seller_products', [])
    );

    $totalProducts = count($products);


    // =========================
    // SALES
    // =========================

    $totalSales = 0;

    foreach ($orders as $order) {
        $totalSales += (float) ($order['total'] ?? 0);
    }


    // =========================
    // DASHBOARD
    // =========================

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
        return redirect('/admin/login');
    }

    $users = session()->get('users', []);

    return view(
        'pages.admin.accounts',
        compact('users')
    );

})->name('admin.accounts');


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

    return view(
        'pages.seller.dashboard',
        compact('user', 'products')
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

    $deliveries = session()->get(
        'rider_deliveries',
        []
    );

    return view(
        'pages.rider.dashboard',
        compact('user', 'deliveries')
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
| STORE PAGES
|--------------------------------------------------------------------------
*/


Route::get('/', function () {

    $user = session()->get('user');

    // Buyer → Buyer Homepage
    if ($user && ($user['role'] ?? '') === 'buyer') {
        return redirect()->route('buyer.dashboard');
    }

    // Seller → Seller Dashboard
    if ($user && ($user['role'] ?? '') === 'seller') {
        return redirect()->route('seller.dashboard');
    }

    // Rider → Rider Dashboard
    if ($user && ($user['role'] ?? '') === 'rider') {
        return redirect()->route('rider.dashboard');
    }

    // Guest → Public Homepage
    return view('welcome');

})->name('home');

/*
|--------------------------------------------------------------------------
| PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

Route::get('/product-details/{slug}', function ($slug) {

    $products = [];

    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

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

Route::post('/cart/add/{slug}', function ($slug) {

    $products = [];

    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

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

    $cart =
        session()->get('cart', []);

    $cart[$slug] =
        ($cart[$slug] ?? 0) + 1;

    session()->put(
        'cart',
        $cart
    );

    return back()->with(
        'success',
        'Product added to cart!'
    );

})->name('cart.add');


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

    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

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

    foreach (defaultProducts() as $product) {

        $products[$product['slug']] =
            $product;
    }

    foreach (
        session()->get('admin_products', [])
        as $product
    ) {

        if (!empty($product['slug'])) {

            $products[$product['slug']] =
                $product;
        }
    }

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
| ADMIN - MANAGE ACCOUNTS
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

