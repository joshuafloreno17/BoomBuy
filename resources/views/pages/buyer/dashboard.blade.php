<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Buyer Home</title>

    @include('partials.pwa-head')

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
           MAIN CONTENT (full width, no sidebar)
        ========================= */

        .main-content {
            width: 100%;
            max-width: 100%;

            min-height: calc(100vh - 72px);

            overflow-x: hidden;
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

            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;

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

        .view-all-link {
            flex-shrink: 0;

            color: #e8420f;
            font-size: 13px;
            font-weight: 700;

            white-space: nowrap;

            padding-top: 4px;

            transition: 0.2s ease;
        }

        .view-all-link:hover {
            color: #c43408;
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
            width: 100%;
            max-width: 100%;

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
           MOBILE NAV TOGGLE
        ========================= */

        .nav-toggle {
            display: none;

            background: none;
            border: none;

            font-size: 22px;

            color: #172033;

            cursor: pointer;
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
        }

        /* =========================
           SMALL TABLET
        ========================= */

        @media (max-width: 900px) {

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

            .section-title {
                flex-wrap: wrap;
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

            .container {
                width: calc(100% - 16px);
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

    @include('partials.buyer-navbar', ['activeNav' => 'home'])


    <!-- =========================
         MAIN (full width, no sidebar)
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
                        Continue Shopping
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
                        My Orders
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
                        My Cart
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

                <div>

                    <h2>
                        Available Products
                    </h2>

                    <p>
                        Explore the latest products available on BoomBuy.
                    </p>

                </div>

                <a href="{{ route('products') }}" class="view-all-link">
                    View All Products →
                </a>

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
                                        style="display:none;"
                                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                        onerror="this.style.display='none';"
                                    >
                                    <span>📦</span>

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

    @include('partials.pwa-register')

</body>
</html>