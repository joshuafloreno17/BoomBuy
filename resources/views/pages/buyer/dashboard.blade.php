<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoomBuy — Buyer Home</title>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

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

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        background: #fff7f4;
        color: #172033;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    /* =========================
       NAVBAR
    ========================= */

    .navbar {
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
        font-size: 23px;
        font-weight: 700;
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
    }

    .nav-links a {
        color: #8d6c62;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 2px;
        transition: 0.2s;
    }

    .nav-links a:hover {
        color: #e8420f;
    }

    .nav-links .active {
        color: #e8420f;
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .cart {
        color: #e8420f;
        font-size: 13px;
        font-weight: 700;
    }

    .logout {
        border: none;
        background: #fff3f0;
        color: #dc2626;
        padding: 8px 12px;
        border-radius: 7px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
    }

    .logout:hover {
        background: #ffe1e1;
    }

    /* =========================
       CONTAINER
    ========================= */

    .container {
        width: 86%;
        max-width: 1200px;
        margin: 45px auto 80px;
    }

    /* =========================
       WELCOME
    ========================= */

    .welcome {
        background: white;
        border: 1px solid #f7e5e0;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .welcome small {
        color: #db5a33;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 11px;
        font-weight: 700;
    }

    .welcome h1 {
        font-size: 34px;
        margin-top: 8px;
    }

    .welcome p {
        color: #977970;
        font-size: 14px;
        margin-top: 8px;
    }

    /* =========================
       QUICK ACTIONS
    ========================= */

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 35px;
    }

    .quick-card {
        background: white;
        border: 1px solid #f7e5e0;
        border-radius: 12px;
        padding: 18px;
        transition: 0.2s;
    }

    .quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(23, 105, 224, 0.08);
    }

    .quick-card strong {
        display: block;
        color: #172033;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .quick-card span {
        color: #977970;
        font-size: 12px;
    }

    /* =========================
       SECTION
    ========================= */

    .section-title {
        margin-bottom: 18px;
    }

    .section-title h2 {
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
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .product-card {
        background: white;
        border: 1px solid #f7e5e0;
        border-radius: 14px;
        padding: 18px;
        transition: 0.2s;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(23, 105, 224, 0.08);
    }

    .product-icon {
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

    .category {
        color: #db5a33;
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .product-name {
        font-size: 16px;
        font-weight: 700;
        margin-top: 5px;
    }

    .price {
        color: #e8420f;
        font-size: 17px;
        font-weight: 700;
        margin-top: 8px;
    }

    .view-btn {
        display: block;
        background: #e8420f;
        color: white;
        text-align: center;
        padding: 10px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        margin-top: 13px;
    }

    .view-btn:hover {
        background: #c43408;
    }

    /* =========================
       EMPTY
    ========================= */

    .empty {
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
        background: white;
        border-top: 1px solid #f7e5e0;
        padding: 30px 7%;
        display: flex;
        justify-content: space-between;
        color: #977970;
        font-size: 12px;
    }

    footer strong {
        color: #e8420f;
    }

    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 1000px) {
        .nav-links {
            gap: 12px;
        }

        .products {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 700px) {
        .navbar {
            flex-wrap: wrap;
            gap: 12px;
            padding: 15px 5%;
        }

        .nav-links {
            order: 3;
            width: 100%;
            justify-content: center;
            margin: 0;
            gap: 15px;
        }

        .nav-right {
            margin-left: auto;
        }

        .container {
            width: 92%;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }

        .products {
            grid-template-columns: 1fr;
        }

        .welcome h1 {
            font-size: 27px;
        }

        footer {
            flex-direction: column;
            gap: 8px;
            text-align: center;
        }
    }

    /* ===== BoomBuy Vibrant Design System Overrides ===== */

    h1,
    h2,
    h3,
    .logo,
    .hero-title,
    .hero h1,
    .section-title,
    .page-title,
    .product-title,
    .price,
    .cta,
    .cta-title,
    .brand,
    .checkout-title,
    .card-title,
    .modal-title,
    .auth-title,
    .form-title,
    .empty-title,
    .step-title,
    .order-title,
    .stat-title,
    .stat-value,
    .banner-title {
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        letter-spacing: -0.01em;
    }

    button,
    .btn,
    [class*="btn-"],
    .add-to-cart,
    .buy-now,
    .checkout-btn,
    .register-btn,
    .login-btn,
    .submit-btn,
    .primary-btn {
        border-radius: 12px !important;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            background 0.15s ease;
    }

    button:hover,
    .btn:hover,
    [class*="btn-"]:hover,
    .add-to-cart:hover,
    .buy-now:hover,
    .primary-btn:hover {
        transform: translateY(-1px);
    }

    .card,
    [class*="-card"],
    .product-card {
        border-radius: 16px !important;
    }

    ::selection {
        background: #ffd7c2;
        color: #7c1a00;
    }

    /* ===== Sidebar layout ===== */

    .layout {
        display: flex;
    }

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

    .sidebar-footer .back {
        display: block;
        padding: 12px;
        border-radius: 10px;
        color: #8d6c62;
        font-size: 13px;
        font-weight: 600;
    }

    .sidebar-footer .back:hover {
        background: #fff4f1;
        color: #e8420f;
    }

    .main-content {
        margin-left: 230px;
        width: calc(100% - 230px);
        min-width: 0;
    }

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
        }
    }
</style>


</head>

<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <!-- LOGO -->

    <a href="{{ route('buyer.dashboard') }}" class="logo">
        Boom<span>Buy</span>
    </a>

    <!-- MAIN NAVIGATION -->

    <div class="nav-links">

        {{-- Buyer Home --}}
        <a href="{{ route('buyer.dashboard') }}" class="active">
            Home
        </a>

        {{-- Shop --}}
        <a href="{{ route('products') }}">
            🛍️ Shop
        </a>

        {{-- Buyer Orders --}}
        <a href="{{ route('buyer.orders') }}">
            📦 My Orders
        </a>

        {{-- Cart --}}
        @php
            $cartCount = array_sum(session()->get('cart', []));
        @endphp

        <a href="{{ route('cart') }}" class="cart-link">
            🛒 Cart

            @if($cartCount > 0)
                <span class="cart-number">
                    {{ $cartCount }}
                </span>
            @endif
        </a>

    </div>

    <!-- USER / LOGOUT -->

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

<div class="layout">

    <aside class="sidebar">

        <div class="sidebar-label">
            My Account
        </div>

        <nav class="menu">

            <a href="{{ route('buyer.dashboard') }}" class="active">
                🏠 <span class="label-text">Overview</span>
            </a>

            <a href="{{ route('buyer.orders') }}">
                📦 <span class="label-text">My Orders</span>
            </a>

            <a href="{{ route('products') }}">
                🛍️ <span class="label-text">Shop</span>
            </a>

            <a href="{{ route('cart') }}">
                🛒 <span class="label-text">Cart</span>
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

            <!-- WELCOME -->

            <section class="welcome">

                <small>
                    Welcome to BoomBuy
                </small>

                <h1>
                    Welcome, {{ $user['name'] ?? 'Buyer' }}! 👋
                </h1>

                <p>
                    Browse products, manage your orders, and shop from BoomBuy.
                </p>

            </section>

            <!-- =========================
                 QUICK ACTIONS
            ========================= -->

            <div class="quick-actions">

                {{-- CONTINUE SHOPPING → PRODUCTS --}}

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

                {{-- MY ORDERS → BUYER ORDERS --}}

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

                {{-- MY CART → CART --}}

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
                                    $pIcon = $product['icon'] ?? '📦';

                                    $pIsImg =
                                        is_string($pIcon) &&
                                        (
                                            str_contains($pIcon, '.jpg') ||
                                            str_contains($pIcon, '.jpeg') ||
                                            str_contains($pIcon, '.png') ||
                                            str_contains($pIcon, '.webp') ||
                                            str_contains($pIcon, '/')
                                        );
                                @endphp

                                @if($pIsImg)

                                    <img
                                        src="{{ str_starts_with($pIcon, 'http') ? $pIcon : asset('storage/' . ltrim($pIcon, '/')) }}"
                                        alt="{{ $product['name'] ?? 'Product' }}"
                                        style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"
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
                                href="{{ route('product.details', $product['slug']) }}"
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

<!-- FOOTER -->

<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Your Marketplace for Everything
    </div>

</footer>


</body>

</html>
