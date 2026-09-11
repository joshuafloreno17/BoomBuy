<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        BoomBuy — {{ $product['name'] }}
    </title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');


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

        /* NAVBAR */

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
        }

        .nav-links a:hover {
            color: #e8420f;
        }

        .cart-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #e8420f !important;
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

        .nav-right {
            display: flex;
            align-items: center;
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

        /* CONTAINER */

        .container {
            width: 86%;
            max-width: 1100px;

            margin: 45px auto 80px;
        }

        /* BACK */

        .back-link {
            display: inline-block;

            color: #db5a33;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #e8420f;
        }

        /* PRODUCT */

        .product-detail {
            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 18px;

            padding: 35px;

            display: grid;
            grid-template-columns: 45% 55%;

            gap: 40px;
        }

        /* PRODUCT VISUAL */

        .product-visual {
            min-height: 420px;

            border-radius: 16px;
            overflow: hidden;

            background:
                {{ $product['background'] ?? 'linear-gradient(145deg, #ffede8, #ffdfd5)' }};

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 130px;
        }

        /* INFO */

        .category {
            color: #db5a33;

            font-size: 11px;
            text-transform: uppercase;

            font-weight: 700;

            letter-spacing: 1.5px;

            margin-bottom: 8px;
        }

        .product-name {
            font-size: 34px;
            line-height: 1.2;

            margin-bottom: 12px;
        }

        .rating {
            color: #f5b70b;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 18px;
        }

        .description {
            color: #8d6c62;

            font-size: 14px;
            line-height: 1.7;

            margin-bottom: 22px;
        }

        .price {
            color: #e8420f;

            font-size: 30px;
            font-weight: 700;

            margin-bottom: 25px;
        }

        /* QUANTITY */

        .quantity-label {
            display: block;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 8px;
        }

        .quantity-box {
            display: inline-flex;

            align-items: center;

            border: 1px solid #f1ddd7;

            border-radius: 8px;

            overflow: hidden;

            margin-bottom: 22px;
        }

        .quantity-btn {
            width: 38px;
            height: 38px;

            border: none;

            background: #fff7f4;

            font-size: 18px;
            font-weight: 700;

            cursor: pointer;

            color: #e8420f;
        }

        .quantity-btn:hover {
            background: #ffefea;
        }

        .quantity-input {
            width: 48px;
            height: 38px;

            border: none;

            text-align: center;

            font-size: 14px;
            font-weight: 700;

            outline: none;
        }

        /* BUTTONS */

        .buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;

            min-height: 46px;

            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            border: none;
        }

        .cart-btn {
            background: #ffefea;
            color: #e8420f;

            border: 1px solid #ffdacf;
        }

        .cart-btn:hover {
            background: #ffe4dc;
        }

        .buy-btn {
            background: #e8420f;
            color: white;
        }

        .buy-btn:hover {
            background: #c43408;
        }

        /* SPECS */

        .specs {
            margin-top: 35px;
        }

        .specs h2 {
            font-size: 21px;

            margin-bottom: 15px;
        }

        .spec-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 10px;
        }

        .spec {
            background: #fffaf8;

            border: 1px solid #f8e7e2;

            border-radius: 9px;

            padding: 13px 15px;
        }

        .spec strong {
            display: block;

            font-size: 11px;

            color: #977970;

            margin-bottom: 4px;
        }

        .spec span {
            font-size: 13px;

            font-weight: 700;
        }

        /* MOBILE */

        @media (max-width: 800px) {

            .navbar {
                flex-wrap: wrap;
                gap: 12px;
            }

            .nav-links {
                order: 3;

                width: 100%;

                justify-content: center;

                margin: 0;

                gap: 15px;
            }

            .product-detail {
                grid-template-columns: 1fr;

                padding: 22px;
            }

            .product-visual {
                min-height: 280px;

                font-size: 90px;
            }

            .product-name {
                font-size: 28px;
            }

            .buttons {
                flex-direction: column;
            }

            .spec-grid {
                grid-template-columns: 1fr;
            }

        }

    
/* ===== BoomBuy Vibrant Design System Overrides ===== */
h1, h2, h3, .logo, .hero-title, .hero h1, .section-title, .page-title,
.product-title, .price, .cta, .cta-title, .brand, .checkout-title,
.card-title, .modal-title, .auth-title, .form-title, .empty-title,
.step-title, .order-title, .stat-title, .stat-value, .banner-title {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}
button, .btn, [class*="btn-"], .add-to-cart, .buy-now, .checkout-btn,
.register-btn, .login-btn, .submit-btn, .primary-btn {
    border-radius: 12px !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
button:hover, .btn:hover, [class*="btn-"]:hover, .add-to-cart:hover,
.buy-now:hover, .primary-btn:hover {
    transform: translateY(-1px);
}
.card, [class*="-card"], .product-card {
    border-radius: 16px !important;
}
::selection {
    background: #ffd7c2;
    color: #7c1a00;
}
</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <a
        href="{{ route('buyer.dashboard') }}"
        class="logo"
    >
        Boom<span>Buy</span>
    </a>

    <div class="nav-links">

        <a href="{{ route('buyer.dashboard') }}">
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
        >

            @csrf

            <button
                type="submit"
                class="logout"
            >
                Logout
            </button>

        </form>

    </div>

</nav>


<!-- MAIN -->

<main class="container">

    <a
        href="{{ route('products') }}"
        class="back-link"
    >
        ← Back to Shop
    </a>


    <section class="product-detail">


        <!-- PRODUCT IMAGE / ICON -->

  {{-- PRODUCT IMAGE --}}
<div class="product-visual">

    @php
        $productImage = $product['image'] ?? null;
    @endphp

    @if($productImage)

        <img
            src="{{ str_starts_with($productImage, 'http')
                ? $productImage
                : asset('storage/' . ltrim($productImage, '/')) }}"
            alt="{{ $product['name'] ?? 'Product' }}"
            style="
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            "
        >

    @else

        <div style="
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 120px;
        ">
            📦
        </div>

    @endif

</div>


        <!-- PRODUCT INFORMATION -->

        <div class="product-info">

            <div class="category">

                {{ $product['category'] ?? 'Other' }}

            </div>


            <h1 class="product-name">

                {{ $product['name'] }}

            </h1>


            <div class="rating">

                ⭐ {{ $product['rating'] ?? '5.0' }}

                <span style="color:#977970;">
                    ({{ $product['reviews'] ?? 0 }} reviews)
                </span>

            </div>


            <p class="description">

                {{ $product['description'] ?? 'No product description available.' }}

            </p>


            <div class="price">

                ₱{{ number_format($product['price'] ?? 0) }}

            </div>


            <!-- QUANTITY -->

            <label class="quantity-label">

                Quantity

            </label>

            <div class="quantity-box">

                <button
                    type="button"
                    class="quantity-btn"
                    onclick="decreaseQuantity()"
                >
                    −
                </button>

                <input
                    type="number"
                    id="quantity"
                    value="1"
                    min="1"
                    class="quantity-input"
                >

                <button
                    type="button"
                    class="quantity-btn"
                    onclick="increaseQuantity()"
                >
                    +
                </button>

            </div>


            <!-- ACTION BUTTONS -->

            <div class="buttons">


                <!-- ADD TO CART -->

                <form
                    action="{{ route('cart.add', $product->id) }}"
                    method="POST"
                    style="flex:1;"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn cart-btn"
                        style="width:100%;"
                    >
                        🛒 Add to Cart
                    </button>

                </form>


                <!-- BUY NOW -->

                <form
                    action="{{ route('buy.now', $product->id) }}"
                    method="POST"
                    style="flex:1;"
                    id="buyNowForm"
                >

                    @csrf

                    <input
                        type="hidden"
                        name="quantity"
                        id="buyNowQuantity"
                        value="1"
                    >

                    <button
                        type="submit"
                        class="btn buy-btn"
                        style="width:100%;"
                    >
                        ⚡ Buy Now
                    </button>

                </form>


            </div>

        </div>

    </section>


    <!-- SPECIFICATIONS -->

    @if(!empty($product['specs']))

        <section class="specs">

            <h2>
                Product Specifications
            </h2>

            <div class="spec-grid">

                @foreach($product['specs'] as $key => $value)

                    <div class="spec">

                        <strong>
                            {{ $key }}
                        </strong>

                        <span>
                            {{ $value }}
                        </span>

                    </div>

                @endforeach

            </div>

        </section>

    @endif

</main>


<script>

    function increaseQuantity() {

        const input =
            document.getElementById('quantity');

        input.value =
            parseInt(input.value || 1) + 1;

        updateBuyNowQuantity();
    }


    function decreaseQuantity() {

        const input =
            document.getElementById('quantity');

        let value =
            parseInt(input.value || 1);

        if (value > 1) {

            value--;

        }

        input.value = value;

        updateBuyNowQuantity();
    }


    function updateBuyNowQuantity() {

        const quantity =
            document.getElementById('quantity').value;

        document.getElementById(
            'buyNowQuantity'
        ).value = quantity;

    }


    document
        .getElementById('quantity')
        .addEventListener(
            'input',
            updateBuyNowQuantity
        );

</script>

</body>

</html>