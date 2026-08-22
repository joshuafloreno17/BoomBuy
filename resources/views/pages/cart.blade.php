<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart — GizmoMart</title>

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

        /* NAVBAR */

        .navbar {
            background: white;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        /* CONTAINER */

        .container {
            width: 86%;
            max-width: 1150px;
            margin: 50px auto 80px;
        }

        .back {
            color: #3977d5;
            font-size: 13px;
            font-weight: 600;
        }

        .header {
            margin: 25px 0 30px;
        }

        .header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 40px;
            margin-top: 8px;
        }

        /* CART */

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
        }

        .cart-items,
        .summary {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 18px;
        }

        .cart-items {
            padding: 25px;
        }

        .item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #edf1f7;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
        }

        .item-info {
            flex: 1;
        }

        .item-category {
            color: #5790df;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .item-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .item-price {
            color: #1769e0;
            font-size: 14px;
            font-weight: 700;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #dce7fa;
            padding: 7px 10px;
            border-radius: 8px;
        }

        .quantity button {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            color: #1769e0;
            font-weight: bold;
        }

        .quantity span {
            font-size: 13px;
            font-weight: 700;
        }

        /* SUMMARY */

        .summary {
            padding: 25px;
            height: fit-content;
        }

        .summary h2 {
            font-size: 20px;
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .summary-total {
            border-top: 1px solid #e5ebf5;
            padding-top: 18px;
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
        }

        .total-price {
            color: #1769e0;
        }

        .checkout {
            width: 100%;
            margin-top: 25px;
            border: none;
            background: #1769e0;
            color: white;
            padding: 14px;
            border-radius: 9px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
        }

        .checkout:hover {
            background: #0f55bd;
        }

        /* EMPTY */

        .empty {
            text-align: center;
            padding: 70px 20px;
        }

        .empty-icon {
            font-size: 65px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .shop-btn {
            display: inline-block;
            background: #1769e0;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        /* FOOTER */

        footer {
            background: white;
            border-top: 1px solid #e1e9f6;
            padding: 35px 7%;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 13px;
        }

        footer div:first-child {
            color: #1769e0;
            font-weight: 600;
        }

        /* RESPONSIVE */

        @media (max-width: 800px) {

            .nav-links {
                display: none;
            }

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .item {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 500px) {

            .container {
                width: 92%;
            }

            .header h1 {
                font-size: 32px;
            }

            .item {
                align-items: flex-start;
            }

            .quantity {
                margin-left: 110px;
            }

            footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="/" class="logo">
        Gizmo<span>Mart</span>
    </a>

    <div class="nav-links">
        <a href="/">Home</a>
        <a href="/products">Shop</a>
        <a href="/categories">Categories</a>
        <a href="/#about">About</a>
    </div>

    <a href="/cart" class="cart-link">
        🛒 Cart
    </a>

</nav>


<div class="container">

    <a href="/products" class="back">
        ← Continue Shopping
    </a>

    <div class="header">
        <small>GizmoMart</small>
        <h1>Your Shopping Cart</h1>
    </div>


    @if(count($cart) === 0)

        <div class="cart-items">

            <div class="empty">

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
                    Start Shopping
                </a>

            </div>

        </div>

    @else

        <div class="cart-layout">

            <div class="cart-items">

                @php
                    $products = [

                        'nova-x5-pro' => [
                            'name' => 'Nova X5 Pro',
                            'category' => 'Smartphone',
                            'price' => 18999,
                            'icon' => '📱',
                            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)'
                        ],

                        'airbook-14' => [
                            'name' => 'AirBook 14',
                            'category' => 'Laptop',
                            'price' => 34990,
                            'icon' => '💻',
                            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)'
                        ],

                        'soundcore-pro' => [
                            'name' => 'SoundCore Pro',
                            'category' => 'Audio',
                            'price' => 2799,
                            'icon' => '🎧',
                            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)'
                        ],

                        'fitwatch-s2' => [
                            'name' => 'FitWatch S2',
                            'category' => 'Wearable',
                            'price' => 3499,
                            'icon' => '⌚',
                            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)'
                        ],

                        'gamepad-x' => [
                            'name' => 'GamePad X',
                            'category' => 'Accessories',
                            'price' => 2199,
                            'icon' => '🎮',
                            'background' => 'linear-gradient(145deg, #e7fbff, #d5f4ff)'
                        ],

                        'mechakeys-75' => [
                            'name' => 'MechaKeys 75',
                            'category' => 'Accessories',
                            'price' => 3299,
                            'icon' => '⌨️',
                            'background' => 'linear-gradient(145deg, #e8f2ff, #d5e8ff)'
                        ],

                        'glide-mouse-x' => [
                            'name' => 'Glide Mouse X',
                            'category' => 'Accessories',
                            'price' => 1499,
                            'icon' => '🖱️',
                            'background' => 'linear-gradient(145deg, #f0efff, #e1e3ff)'
                        ],

                        'minisound-go' => [
                            'name' => 'MiniSound Go',
                            'category' => 'Audio',
                            'price' => 1899,
                            'icon' => '🔊',
                            'background' => 'linear-gradient(145deg, #f3edff, #e9ddff)'
                        ]

                    ];

                    $subtotal = 0;
                @endphp


                @foreach($cart as $slug => $quantity)

                    @if(isset($products[$slug]))

                        @php
                            $item = $products[$slug];
                            $itemTotal = $item['price'] * $quantity;
                            $subtotal += $itemTotal;
                        @endphp

                        <div class="item">

                            <div
                                class="item-image"
                                style="background: {{ $item['background'] }}"
                            >
                                {{ $item['icon'] }}
                            </div>

                            <div class="item-info">

                                <div class="item-category">
                                    {{ $item['category'] }}
                                </div>

                                <div class="item-name">
                                    {{ $item['name'] }}
                                </div>

                                <div class="item-price">
                                    ₱{{ number_format($item['price']) }}
                                </div>

                            </div>

                            <div class="quantity">

                                <button>
                                    −
                                </button>

                                <span>
                                    {{ $quantity }}
                                </span>

                                <button>
                                    +
                                </button>

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
                    <span>Subtotal</span>

                    <span>
                        ₱{{ number_format($subtotal) }}
                    </span>
                </div>

                <div class="summary-row">
                    <span>Shipping</span>

                    <span>
                        Free
                    </span>
                </div>

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <span class="total-price">
                        ₱{{ number_format($subtotal) }}
                    </span>

                </div>

                <button class="checkout">
                    Proceed to Checkout
                </button>

            </div>

        </div>

    @endif

</div>


<footer>

    <div>
        © 2026 GizmoMart
    </div>

    <div>
        Quality tech. Better everyday.
    </div>

</footer>

</body>
</html>