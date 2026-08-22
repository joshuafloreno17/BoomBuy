<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Authentication Pages
|--------------------------------------------------------------------------
*/

Route::get('/register', function () {
    return view('pages.register');
})->name('register');

Route::get('/login', function () {
    return view('pages.login');
})->name('login');


/*
|--------------------------------------------------------------------------
| Admin Pages
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', function () {
    return view('pages.admin.login');
})->name('admin.login');


Route::get('/admin', function () {
    return view('pages.admin.dashboard');
})->name('admin.dashboard');


/*
|--------------------------------------------------------------------------
| Admin Products
|--------------------------------------------------------------------------
*/

Route::get('/admin/products', function () {

    $defaultProducts = [

        [
            'slug' => 'nova-x5-pro',
            'name' => 'Nova X5 Pro',
            'category' => 'Smartphone',
            'price' => 18999,
            'icon' => '📱',
        ],

        [
            'slug' => 'airbook-14',
            'name' => 'AirBook 14',
            'category' => 'Laptop',
            'price' => 34990,
            'icon' => '💻',
        ],

        [
            'slug' => 'soundcore-pro',
            'name' => 'SoundCore Pro',
            'category' => 'Audio',
            'price' => 2799,
            'icon' => '🎧',
        ],

        [
            'slug' => 'fitwatch-s2',
            'name' => 'FitWatch S2',
            'category' => 'Wearable',
            'price' => 3499,
            'icon' => '⌚',
        ],

        [
            'slug' => 'gamepad-x',
            'name' => 'GamePad X',
            'category' => 'Accessories',
            'price' => 2199,
            'icon' => '🎮',
        ],

        [
            'slug' => 'mechakeys-75',
            'name' => 'MechaKeys 75',
            'category' => 'Accessories',
            'price' => 3299,
            'icon' => '⌨️',
        ],

        [
            'slug' => 'glide-mouse-x',
            'name' => 'Glide Mouse X',
            'category' => 'Accessories',
            'price' => 1499,
            'icon' => '🖱️',
        ],

        [
            'slug' => 'minisound-go',
            'name' => 'MiniSound Go',
            'category' => 'Audio',
            'price' => 1899,
            'icon' => '🔊',
        ],

    ];


    // Mga product na idinagdag gamit ang Add Product
    $addedProducts = session()->get('admin_products', []);


    // Pagsamahin ang default at bagong products
    $products = array_merge(
        $defaultProducts,
        $addedProducts
    );


    return view(
        'pages.admin-products',
        compact('products')
    );

})->name('admin.products');


/*
|--------------------------------------------------------------------------
| Add Product Page
|--------------------------------------------------------------------------
*/

Route::get('/admin/products/create', function () {
    return view('pages.admin-add-product');
})->name('admin.products.create');


/*
|--------------------------------------------------------------------------
| Save New Product
|--------------------------------------------------------------------------
*/

Route::post('/admin/products/create', function () {

    $name = trim(request('name'));
    $category = request('category');
    $price = (float) request('price');
    $icon = trim(request('icon'));
    $description = trim(request('description'));


    // Basic validation
    if (
        empty($name) ||
        empty($category) ||
        $price < 0 ||
        empty($icon) ||
        empty($description)
    ) {

        return back()->with(
            'error',
            'Please complete all product fields.'
        );
    }


    // Convert category to proper display format
    $categoryNames = [

        'smartphone' => 'Smartphone',
        'laptop' => 'Laptop',
        'audio' => 'Audio',
        'wearable' => 'Wearable',
        'accessories' => 'Accessories',

    ];


    $categoryName =
        $categoryNames[$category]
        ?? ucfirst($category);


    // Create slug
    $slug = \Illuminate\Support\Str::slug($name);


    // Get existing added products
    $products =
        session()->get('admin_products', []);


   // Check duplicate slug
foreach ($products as $product) {
    if (($product['slug'] ?? '') === $slug) {
        return back()->withErrors([
            'name' => 'A product with this name already exists.'
        ]);
    }
}

    // New product
    $newProduct = [

        'slug' => $slug,

        'name' => $name,

        'category' => $categoryName,

        'price' => $price,

        'icon' => $icon,

        'description' => $description,

    ];


    // Add product to session
    $products[] = $newProduct;

    session()->put(
        'admin_products',
        $products
    );


    // Return to Products page
    return redirect()
        ->route('admin.products')
        ->with(
            'success',
            $name . ' has been added successfully!'
        );

})->name('admin.products.store');


/*
|--------------------------------------------------------------------------
| Store Pages
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/products', function () {
    return view('pages.products');
});


Route::get('/categories', function () {
    return view('pages.categories');
})->name('categories');


Route::get('/smartphones', function () {
    return view('smartphones');
});


/*
|--------------------------------------------------------------------------
| Product Details
|--------------------------------------------------------------------------
*/

Route::get('/product-details/{slug}', function ($slug) {

    $products = [

        'nova-x5-pro' => [
            'slug' => 'nova-x5-pro',
            'name' => 'Nova X5 Pro',
            'category' => 'Smartphone',
            'price' => 18999,
            'rating' => '4.8',
            'reviews' => 124,
            'icon' => '📱',
            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)',
            'description' => 'Powerful smartphone with a vibrant display and long-lasting battery.',
            'specs' => [
                'Display' => '6.7-inch AMOLED',
                'Processor' => 'Octa-Core',
                'RAM' => '12GB',
                'Storage' => '256GB',
                'Battery' => '5000mAh',
                'Camera' => '50MP Main Camera'
            ]
        ],

        'airbook-14' => [
            'slug' => 'airbook-14',
            'name' => 'AirBook 14',
            'category' => 'Laptop',
            'price' => 34990,
            'rating' => '4.7',
            'reviews' => 89,
            'icon' => '💻',
            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)',
            'description' => 'Lightweight laptop designed for work, school and everyday use.',
            'specs' => [
                'Display' => '14-inch Full HD',
                'Processor' => 'Intel Core i5',
                'RAM' => '16GB',
                'Storage' => '512GB SSD',
                'Battery' => 'Up to 10 hours',
                'Weight' => '1.4 kg'
            ]
        ],

        'soundcore-pro' => [
            'slug' => 'soundcore-pro',
            'name' => 'SoundCore Pro',
            'category' => 'Audio',
            'price' => 2799,
            'rating' => '4.9',
            'reviews' => 216,
            'icon' => '🎧',
            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)',
            'description' => 'Wireless headphones with clear sound and deep bass.',
            'specs' => [
                'Type' => 'Wireless Headphones',
                'Connection' => 'Bluetooth 5.3',
                'Battery' => '40 Hours',
                'Driver' => '40mm',
                'Microphone' => 'Built-in',
                'Charging' => 'USB-C'
            ]
        ],

        'fitwatch-s2' => [
            'slug' => 'fitwatch-s2',
            'name' => 'FitWatch S2',
            'category' => 'Wearable',
            'price' => 3499,
            'rating' => '4.6',
            'reviews' => 73,
            'icon' => '⌚',
            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)',
            'description' => 'Smart wearable with fitness tracking and smart functions.',
            'specs' => [
                'Display' => '1.8-inch AMOLED',
                'Battery' => '7 Days',
                'Water Resistance' => '5 ATM',
                'Connectivity' => 'Bluetooth 5.2',
                'Sensors' => 'Heart Rate + SpO2',
                'Compatibility' => 'Android / iOS'
            ]
        ],

        'gamepad-x' => [
            'slug' => 'gamepad-x',
            'name' => 'GamePad X',
            'category' => 'Accessories',
            'price' => 2199,
            'rating' => '4.8',
            'reviews' => 61,
            'icon' => '🎮',
            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)',
            'description' => 'Comfortable wireless controller designed for gaming.',
            'specs' => [
                'Connection' => 'Wireless',
                'Battery' => '20 Hours',
                'Compatibility' => 'PC / Android',
                'Charging' => 'USB-C',
                'Vibration' => 'Dual Vibration',
                'Buttons' => 'Programmable'
            ]
        ],

        'mechakeys-75' => [
            'slug' => 'mechakeys-75',
            'name' => 'MechaKeys 75',
            'category' => 'Accessories',
            'price' => 3299,
            'rating' => '4.7',
            'reviews' => 95,
            'icon' => '⌨️',
            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)',
            'description' => 'Compact mechanical keyboard built for productivity and gaming.',
            'specs' => [
                'Layout' => '75%',
                'Switches' => 'Mechanical',
                'Connection' => 'USB-C',
                'Lighting' => 'RGB',
                'Keycaps' => 'PBT',
                'Compatibility' => 'Windows / Mac'
            ]
        ],

        'glide-mouse-x' => [
            'slug' => 'glide-mouse-x',
            'name' => 'Glide Mouse X',
            'category' => 'Accessories',
            'price' => 1499,
            'rating' => '4.6',
            'reviews' => 54,
            'icon' => '🖱️',
            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)',
            'description' => 'Lightweight wireless mouse with a precise sensor.',
            'specs' => [
                'Sensor' => '12,000 DPI',
                'Connection' => 'Wireless',
                'Battery' => '70 Hours',
                'Weight' => '68g',
                'Buttons' => '6 Buttons',
                'Compatibility' => 'Windows / Mac'
            ]
        ],

        'minisound-go' => [
            'slug' => 'minisound-go',
            'name' => 'MiniSound Go',
            'category' => 'Audio',
            'price' => 1899,
            'rating' => '4.7',
            'reviews' => 108,
            'icon' => '🔊',
            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)',
            'description' => 'Portable Bluetooth speaker made for music anywhere.',
            'specs' => [
                'Connection' => 'Bluetooth 5.3',
                'Battery' => '15 Hours',
                'Power' => '20W',
                'Water Resistance' => 'IPX7',
                'Charging' => 'USB-C',
                'Range' => '15 meters'
            ]
        ],

    ];


    if (!isset($products[$slug])) {

        abort(404);

    }


    $product = $products[$slug];


    return view(
        'pages.product-details',
        compact('product')
    );

})->name('product.details');


/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

Route::post('/cart/add/{slug}', function ($slug) {

    $cart = session()->get('cart', []);

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


Route::get('/cart', function () {

    $cart = session()->get(
        'cart',
        []
    );

    return view(
        'pages.cart',
        compact('cart')
    );

})->name('cart');

Route::get('/admin/products/add', function () {
    return view('pages.add-product');
});

use App\Http\Controllers\ProductController;

Route::post('/admin/products/store', [ProductController::class, 'store']);