<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Buyer Home</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        /* =========================
           RESET
        ========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            min-height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            width: 100%;
            max-width: 100%;
            height: 72px;

            background: white;
            border-bottom: 1px solid #ffe9e2;

            padding: 16px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            flex-shrink: 0;

            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            align-items: center;

            gap: 22px;

            margin-left: auto;
            margin-right: 25px;

            min-width: 0;
        }

        .nav-links a {
            color: #8d6c62;
            font-size: 13px;
            font-weight: 700;

            padding: 8px 2px;

            white-space: nowrap;

            transition: 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #e8420f;
        }

        .nav-right {
            display: flex;
            align-items: center;

            flex-shrink: 0;
        }

        .nav-right form {
            margin: 0;
        }

        .logout {
            border: none;
            background: #fff3f0;
            color: #dc2626;

            padding: 8px 12px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .logout:hover {
            background: #ffe1e1;
        }

        .cart-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cart-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 20px;
            height: 20px;

            padding: 0 5px;

            background: #ef4444;
            color: white;

            border-radius: 50%;

            font-size: 11px;
            font-weight: 700;
        }

        /* =========================
           MAIN LAYOUT
        ========================= */

        .layout {
            display: block;

            width: 100%;
            max-width: 100%;

            min-height: calc(100vh - 72px);

            overflow-x: hidden;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 230px;

            background: #ffffff;
            border-right: 1px solid #ffe9e2;

            padding: 25px 18px;

            display: flex;
            flex-direction: column;

            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            z-index: 1000;

            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-label {
            font-size: 11px;

            text-transform: uppercase;
            letter-spacing: 0.06em;

            color: #b99c93;

            margin-top: 22px;
            margin-bottom: 4px;

            font-weight: 700;
        }

        .sidebar .menu {
            display: flex;
            flex-direction: column;

            gap: 4px;

            margin-top: 8px;
        }

        .sidebar .menu a {
            display: block;

            width: 100%;

            padding: 12px;

            border-radius: 10px;

            color: #8d6c62;

            font-size: 13px;
            font-weight: 600;

            transition: 0.2s;
        }

        .sidebar .menu a:hover,
        .sidebar .menu a.active {
            background: #fff4f1;
            color: #e8420f;
        }

        .sidebar-footer {
            margin-top: auto;

            padding-top: 16px;

            border-top: 1px solid #ffe9e2;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 230px;

            width: calc(100% - 230px);
            max-width: calc(100% - 230px);

            min-width: 0;

            overflow-x: hidden;

            box-sizing: border-box;
        }

        /* =========================
           CONTAINER
        ========================= */

        .container {
            width: calc(100% - 40px);
            max-width: 1200px;

            margin: 45px auto 80px;

            min-width: 0;

            box-sizing: border-box;
        }

        /* =========================
           WELCOME
        ========================= */

        .welcome {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 16px;

            padding: 30px;

            margin-bottom: 30px;

            overflow: hidden;
        }

        .welcome small {
            color: #db5a33;

            text-transform: uppercase;
            letter-spacing: 2px;

            font-size: 11px;
            font-weight: 700;
        }

        .welcome h1 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 34px;

            margin-top: 8px;

            overflow-wrap: anywhere;
        }

        .welcome p {
            color: #977970;

            font-size: 14px;

            margin-top: 8px;

            overflow-wrap: anywhere;
        }

        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-actions {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            display: grid;

            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 35px;
        }

        .quick-card {
            min-width: 0;
            max-width: 100%;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 18px;

            transition: 0.2s;

            overflow: hidden;
        }

        .quick-card:hover {
            transform: translateY(-2px);

            box-shadow: 0 8px 25px rgba(232, 66, 15, 0.08);
        }

        .quick-card strong {
            display: block;

            color: #172033;

            font-size: 14px;

            margin-bottom: 5px;

            overflow-wrap: anywhere;
        }

        .quick-card span {
            color: #977970;

            font-size: 12px;

            overflow-wrap: anywhere;
        }

        /* =========================
           SECTION TITLE
        ========================= */

        .section-title {
            width: 100%;
            max-width: 100%;

            margin-bottom: 18px;
        }

        .section-title h2 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 23px;
        }

        .section-title p {
            color: #977970;

            font-size: 13px;

            margin-top: 5px;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            display: grid;

            grid-template-columns: repeat(4, minmax(0, 1fr));

            gap: 18px;
        }

        .product-card {
            min-width: 0;
            max-width: 100%;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 18px;

            transition: 0.2s;

            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-3px);

            box-shadow: 0 8px 25px rgba(232, 66, 15, 0.08);
        }

        .product-icon {
            width: 100%;
            max-width: 100%;

            height: 130px;

            border-radius: 10px;

            background: #ffefea;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 55px;

            margin-bottom: 15px;
        }

        .product-icon img {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: cover;

            border-radius: inherit;
        }

        .category {
            color: #db5a33;

            font-size: 10px;

            text-transform: uppercase;

            font-weight: 700;

            letter-spacing: 1px;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-name {
            font-family: 'Baloo 2', sans-serif;

            font-size: 16px;
            font-weight: 700;

            margin-top: 5px;

            overflow-wrap: anywhere;
        }

        .price {
            font-family: 'Baloo 2', sans-serif;

            color: #e8420f;

            font-size: 17px;
            font-weight: 700;

            margin-top: 8px;
        }

        .view-btn {
            display: block;

            width: 100%;
            max-width: 100%;

            background: #e8420f;

            color: white;

            text-align: center;

            padding: 10px;

            border-radius: 8px;

            font-size: 12px;
            font-weight: 700;

            margin-top: 13px;

            white-space: nowrap;

            overflow: hidden;
            text-overflow: ellipsis;
        }

        .view-btn:hover {
            background: #c43408;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            width: 100%;
            max-width: 100%;

            background: white;

            border: 1px solid #f7e5e0;

            border-radius: 14px;

            padding: 50px;

            text-align: center;

            color: #977970;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            width: calc(100% - 230px);
            max-width: calc(100% - 230px);

            margin-left: 230px;

            background: white;

            border-top: 1px solid #f7e5e0;

            padding: 30px 7%;

            display: flex;

            justify-content: space-between;

            color: #977970;

            font-size: 12px;

            box-sizing: border-box;

            overflow: hidden;
        }

        footer strong {
            color: #e8420f;
        }

        /* =========================
           TABLET
        ========================= */

        @media (max-width: 1100px) {

            .container {
                width: calc(100% - 32px);
            }

            .products {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .nav-links {
                gap: 15px;
                margin-right: 15px;
            }

            .nav-links a {
                font-size: 12px;
            }
        }

        /* =========================
           SMALL TABLET
        ========================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 72px;

                padding: 20px 8px;
            }

            .sidebar .label-text,
            .sidebar-label {
                display: none;
            }

            .sidebar .menu a {
                text-align: center;
            }

            .main-content {
                margin-left: 72px;

                width: calc(100% - 72px);
                max-width: calc(100% - 72px);
            }

            footer {
                margin-left: 72px;

                width: calc(100% - 72px);
                max-width: calc(100% - 72px);
            }

            .container {
                width: calc(100% - 30px);
            }

            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .navbar {
                height: auto;

                min-height: 72px;

                flex-wrap: wrap;

                gap: 12px;

                padding: 15px 20px;
            }

            .logo {
                font-size: 21px;
            }

            .nav-links {
                order: 3;

                width: 100%;

                justify-content: center;

                margin: 0;

                gap: 15px;

                flex-wrap: wrap;
            }

            .nav-links a {
                font-size: 12px;
            }

            .nav-right {
                margin-left: auto;
            }

            .container {
                width: calc(100% - 24px);

                max-width: none;

                margin-top: 25px;

                margin-bottom: 50px;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h1 {
                font-size: 27px;
            }

            .welcome p {
                font-size: 13px;
            }

            .quick-actions {
                grid-template-columns: minmax(0, 1fr);
            }

            .products {
                grid-template-columns: minmax(0, 1fr);
            }

            .product-card {
                padding: 15px;
            }

            .product-icon {
                height: 180px;
            }

            footer {
                flex-direction: column;

                gap: 8px;

                text-align: center;

                padding: 25px 15px;
            }
        }

        /* =========================
           VERY SMALL MOBILE
        ========================= */

        @media (max-width: 450px) {

            .sidebar {
                width: 62px;
                padding: 20px 6px;
            }

            .main-content {
                margin-left: 62px;

                width: calc(100% - 62px);
                max-width: calc(100% - 62px);
            }

            footer {
                margin-left: 62px;

                width: calc(100% - 62px);
                max-width: calc(100% - 62px);
            }

            .container {
                width: calc(100% - 16px);
            }

            .nav-links {
                gap: 10px;
            }

            .nav-links a {
                font-size: 11px;
            }

            .logout {
                padding: 7px 9px;
                font-size: 11px;
            }

            .welcome {
                padding: 18px;
            }

            .welcome h1 {
                font-size: 24px;
            }

            .product-icon {
                height: 150px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         NAVBAR
    ========================= -->

    <nav class="navbar">

        <a
            href="{{ route('buyer.dashboard') }}"
            class="logo"
        >
            Boom<span>Buy</span>
        </a>

        <div class="nav-links">

            <a
                href="{{ route('buyer.dashboard') }}"
                class="active"
            >
                Home
            </a>

            <a href="{{ route('products') }}">
                🛍️ Shop
            </a>

            <a href="{{ route('buyer.orders') }}">
                📦 My Orders
            </a>

            @php
                $cartCount = array_sum(
                    session()->get('cart', [])
                );
            @endphp

            <a
                href="{{ route('cart') }}"
                class="cart-link"
            >
                🛒 Cart

                @if($cartCount > 0)
                    <span class="cart-number">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>

        </div>

        <div class="nav-right">

            <form
                action="{{ route('logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >
                @csrf

                <button
                    class="logout"
                    type="submit"
                >
                    Logout
                </button>
            </form>

        </div>

    </nav>


    <!-- =========================
         LAYOUT
    ========================= -->

    <div class="layout">

        <!-- =========================
             SIDEBAR
        ========================= -->

        <aside class="sidebar">

            <div class="sidebar-label">
                My Account
            </div>

            <nav class="menu">

                <a
                    href="{{ route('buyer.dashboard') }}"
                    class="active"
                >
                    🏠
                    <span class="label-text">
                        Overview
                    </span>
                </a>

                <a href="{{ route('buyer.orders') }}">
                    📦
                    <span class="label-text">
                        My Orders
                    </span>
                </a>

                <a href="{{ route('products') }}">
                    🛍️
                    <span class="label-text">
                        Shop
                    </span>
                </a>

                <a href="{{ route('cart') }}">
                    🛒
                    <span class="label-text">
                        Cart
                    </span>
                </a>

            </nav>


            <div class="sidebar-footer">

                <form
                    action="{{ route('logout') }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to log out?');"
                >
                    @csrf

                    <button
                        type="submit"
                        class="logout"
                        style="width:100%;"
                    >
                        Logout
                    </button>
                </form>

            </div>

        </aside>


        <!-- =========================
             MAIN
        ========================= -->

        <main class="main-content">

            <div class="container">

                <!-- =========================
                     WELCOME
                ========================= -->

                <section class="welcome">

                    <small>
                        Welcome to BoomBuy
                    </small>

                    <h1>
                        Welcome,
                        {{ $user['name'] ?? 'Buyer' }}! 👋
                    </h1>

                    <p>
                        Browse products, manage your orders,
                        and shop from BoomBuy.
                    </p>

                </section>


                <!-- =========================
                     QUICK ACTIONS
                ========================= -->

                <div class="quick-actions">

                    <a
                        href="{{ route('products') }}"
                        class="quick-card"
                    >

                        <strong>
                            🛍️ Continue Shopping
                        </strong>

                        <span>
                            Browse all available products.
                        </span>

                    </a>


                    <a
                        href="{{ route('buyer.orders') }}"
                        class="quick-card"
                    >

                        <strong>
                            📦 My Orders
                        </strong>

                        <span>
                            View your previous and current orders.
                        </span>

                    </a>


                    <a
                        href="{{ route('cart') }}"
                        class="quick-card"
                    >

                        <strong>
                            🛒 My Cart
                        </strong>

                        <span>
                            Review the products you want to purchase.
                        </span>

                    </a>

                </div>


                <!-- =========================
                     PRODUCTS
                ========================= -->

                <div class="section-title">

                    <h2>
                        Available Products
                    </h2>

                    <p>
                        Explore the latest products available on BoomBuy.
                    </p>

                </div>


                @if(count($products) > 0)

                    <div class="products">

                        @foreach($products as $product)

                            <div class="product-card">

                                <div class="product-icon">

                                    @php

                                        $pIcon =
                                            $product['icon'] ?? '📦';

                                        $pIsImg =
                                            is_string($pIcon) &&
                                            (
                                                str_contains(
                                                    $pIcon,
                                                    '.jpg'
                                                ) ||
                                                str_contains(
                                                    $pIcon,
                                                    '.jpeg'
                                                ) ||
                                                str_contains(
                                                    $pIcon,
                                                    '.png'
                                                ) ||
                                                str_contains(
                                                    $pIcon,
                                                    '.webp'
                                                ) ||
                                                str_contains(
                                                    $pIcon,
                                                    '/'
                                                )
                                            );

                                    @endphp


                                    @if($pIsImg)

                                        <img
                                            src="{{
                                                str_starts_with(
                                                    $pIcon,
                                                    'http'
                                                )
                                                    ? $pIcon
                                                    : asset(
                                                        'storage/' .
                                                        ltrim(
                                                            $pIcon,
                                                            '/'
                                                        )
                                                    )
                                            }}"
                                            alt="{{
                                                $product['name']
                                                ?? 'Product'
                                            }}"
                                        >

                                    @else

                                        {{ $pIcon }}

                                    @endif

                                </div>


                                <div class="category">
                                    {{ $product['category'] ?? 'Other' }}
                                </div>


                                <div class="product-name">
                                    {{ $product['name'] }}
                                </div>


                                <div class="price">
                                    ₱{{ number_format($product['price'] ?? 0) }}
                                </div>


                                <a
                                    href="{{
                                        route(
                                            'product.details',
                                            $product['slug']
                                        )
                                    }}"
                                    class="view-btn"
                                >
                                    View Product
                                </a>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="empty">

                        <h3>
                            No Products Available
                        </h3>

                        <p>
                            There are currently no products available.
                        </p>

                    </div>

                @endif

            </div>

        </main>

    </div>


    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        <div>
            © 2026
            <strong>BoomBuy</strong>
        </div>

        <div>
            Your Marketplace for Everything
        </div>

    </footer>

</body>
</html>