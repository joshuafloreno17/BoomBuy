<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            background: #fff;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
        }

        .logo span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            font-size: 14px;
            color: #64748b;
        }

        .nav-links a:hover {
            color: #1769e0;
        }

        .cart-link {
            color: #1769e0;
            font-weight: 700;
            font-size: 14px;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

        .back {
            display: inline-block;
            color: #3977d5;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 34px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .cart-box {
            background: #fff;
            border: 1px solid #e1e9f6;
            border-radius: 18px;
            overflow: hidden;
        }

        .cart-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e8eef8;
            font-size: 16px;
            font-weight: 700;
        }

        .cart-item {
            padding: 22px 24px;
            display: grid;
            grid-template-columns: 90px 1fr auto;
            gap: 18px;
            align-items: center;
            border-bottom: 1px solid #edf1f7;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 90px;
            height: 90px;
            border-radius: 13px;
            background: linear-gradient(145deg, #e8f2ff, #d5e8ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 43px;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .category {
            display: inline-block;
            background: #edf5ff;
            color: #3977d5;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .unit-price {
            color: #718096;
            font-size: 12px;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .quantity form {
            display: inline;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #dce7fa;
            background: #f8faff;
            color: #1769e0;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
        }

        .qty-btn:hover {
            background: #eaf2ff;
        }

        .qty-number {
            min-width: 25px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .item-right {
            text-align: right;
        }

        .item-total {
            color: #1769e0;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .remove-btn {
            border: none;
            background: transparent;
            color: #ef4444;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #e1e9f6;
            border-radius: 18px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-icon {
            font-size: 65px;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .empty-cart p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .shop-btn {
            display: inline-block;
            background: #1769e0;
            color: #fff;
            padding: 13px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .summary {
            background: #fff;
            border: 1px solid #e1e9f6;
            border-radius: 18px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }

        .summary h2 {
            font-size: 19px;
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #64748b;
            font-size: 13px;
        }

        .summary-row strong {
            color: #172033;
        }

        .summary-total {
            border-top: 1px solid #e5ebf5;
            margin-top: 18px;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total span {
            font-size: 14px;
            font-weight: 700;
        }

        .summary-total strong {
            color: #1769e0;
            font-size: 24px;
        }

        .checkout-btn {
            width: 100%;
            border: none;
            background: #1769e0;
            color: #fff;
            padding: 14px;
            border-radius: 9px;
            margin-top: 22px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
        }

        .checkout-btn:hover {
            background: #0f55bd;
        }

        .continue {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #3977d5;
            font-size: 12px;
            font-weight: 600;
        }

        footer {
            background: #fff;
            border-top: 1px solid #e1e9f6;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 12px;
        }

        footer strong {
            color: #1769e0;
        }

        @media (max-width: 850px) {

            .nav-links {
                display: none;
            }

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary {
                position: static;
            }
        }

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .cart-item {
                grid-template-columns: 70px 1fr;
            }

            .product-image {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .item-right {
                grid-column: 2;
                text-align: left;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <div class="nav-links">
        <a href="/">Home</a>
        <a href="/products">Shop</a>
        <a href="/categories">Categories</a>
        <a href="/#about">About</a>
    </div>

    <a href="{{ route('cart') }}" class="cart-link">
        🛒 Cart
    </a>

</nav>


<div class="container">

    <a href="/products" class="back">
        ← Continue Shopping
    </a>

    <h1>Shopping Cart</h1>

    <p class="subtitle">
        Review your items before checkout.
    </p>


    @if(session('success'))

        <div class="success">
            ✓ {{ session('success') }}
        </div>

    @endif


    @if(empty($cart))

        <div class="empty-cart">

            <div class="empty-icon">
                🛒
            </div>

            <h2>
                Your cart is empty
            </h2>

            <p>
                Looks like you haven't added anything yet.
            </p>

            <a href="/products" class="shop-btn">
                🛍️ Start Shopping
            </a>

        </div>

    @else

        @php

            /*
            |--------------------------------------------------------------------------
            | Default Products
            |--------------------------------------------------------------------------
            */

            $products = [

                'nova-x5-pro' => [
                    'name' => 'Nova X5 Pro',
                    'category' => 'Smartphone',
                    'price' => 18999,
                    'icon' => '📱',
                ],

                'airbook-14' => [
                    'name' => 'AirBook 14',
                    'category' => 'Laptop',
                    'price' => 34990,
                    'icon' => '💻',
                ],

                'soundcore-pro' => [
                    'name' => 'SoundCore Pro',
                    'category' => 'Audio',
                    'price' => 2799,
                    'icon' => '🎧',
                ],

                'fitwatch-s2' => [
                    'name' => 'FitWatch S2',
                    'category' => 'Wearable',
                    'price' => 3499,
                    'icon' => '⌚',
                ],

                'gamepad-x' => [
                    'name' => 'GamePad X',
                    'category' => 'Accessories',
                    'price' => 2199,
                    'icon' => '🎮',
                ],

                'mechakeys-75' => [
                    'name' => 'MechaKeys 75',
                    'category' => 'Accessories',
                    'price' => 3299,
                    'icon' => '⌨️',
                ],

                'glide-mouse-x' => [
                    'name' => 'Glide Mouse X',
                    'category' => 'Accessories',
                    'price' => 1499,
                    'icon' => '🖱️',
                ],

                'minisound-go' => [
                    'name' => 'MiniSound Go',
                    'category' => 'Audio',
                    'price' => 1899,
                    'icon' => '🔊',
                ],

            ];


            /*
            |--------------------------------------------------------------------------
            | Admin Added Products
            |--------------------------------------------------------------------------
            */

            $addedProducts = session()->get(
                'admin_products',
                []
            );


            foreach ($addedProducts as $added) {

                if (!empty($added['slug'])) {

                    $products[$added['slug']] = [

                        'name' =>
                            $added['name']
                            ?? 'Product',

                        'category' =>
                            $added['category']
                            ?? 'Other',

                        'price' =>
                            $added['price']
                            ?? 0,

                        'icon' =>
                            $added['icon']
                            ?? '📦',

                    ];

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Calculate Totals
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;
            $totalItems = 0;

        @endphp


        <div class="cart-layout">

            <div class="cart-box">

                <div class="cart-header">
                    Cart Items
                </div>


                @foreach($cart as $slug => $quantity)

                    @if(isset($products[$slug]))

                        @php

                            $item = $products[$slug];

                            $itemTotal =
                                $item['price'] * $quantity;

                            $subtotal += $itemTotal;

                            $totalItems += $quantity;

                        @endphp


                        <div class="cart-item">

                            <div class="product-image">
                                {{ $item['icon'] }}
                            </div>


                            <div class="product-info">

                                <span class="category">
                                    {{ $item['category'] }}
                                </span>

                                <h3>
                                    {{ $item['name'] }}
                                </h3>

                                <div class="unit-price">
                                    ₱{{ number_format($item['price'], 2) }} each
                                </div>


                                <div class="quantity">

                                    <form
                                        action="{{ route('cart.update', $slug) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="decrease"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            −
                                        </button>

                                    </form>


                                    <span class="qty-number">
                                        {{ $quantity }}
                                    </span>


                                    <form
                                        action="{{ route('cart.update', $slug) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="increase"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            +
                                        </button>

                                    </form>

                                </div>

                            </div>


                            <div class="item-right">

                                <div class="item-total">
                                    ₱{{ number_format($itemTotal, 2) }}
                                </div>


                                <form
                                    action="{{ route('cart.remove', $slug) }}"
                                    method="POST"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="remove-btn"
                                    >
                                        🗑 Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endif

                @endforeach

            </div>


            <div class="summary">

                <h2>
                    Order Summary
                </h2>


                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong>
                        {{ $totalItems }}
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        ₱{{ number_format($subtotal, 2) }}
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Shipping
                    </span>

                    <strong>
                        FREE
                    </strong>

                </div>


                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        ₱{{ number_format($subtotal, 2) }}
                    </strong>

                </div>


                <a href="{{ route('checkout') }}">
    <button type="button" class="checkout-btn">
        💳 Proceed to Checkout
    </button>
</a>

                <a href="/products" class="continue">
                    Continue Shopping
                </a>

            </div>

        </div>

    @endif

</div>


<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Quality products. Better everyday.
    </div>

</footer>

</body>
</html>