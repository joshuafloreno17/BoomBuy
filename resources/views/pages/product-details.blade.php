<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $product['name'] }} — GizmoMart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
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

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            width: 100%;
            background: #ffffff;
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
            gap: 32px;
            font-size: 14px;
            color: #64748b;
        }

        .nav-links a:hover {
            color: #1769e0;
        }

        .cart {
            color: #1769e0;
            font-size: 14px;
            font-weight: 700;
        }

        /* =========================
           MAIN
        ========================= */

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #3977d5;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .back:hover {
            color: #1769e0;
        }

        /* =========================
           PRODUCT
        ========================= */

        .product-box {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 20px;
            padding: 35px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 55px;
            box-shadow: 0 15px 40px rgba(39, 84, 150, 0.08);
        }

        /* =========================
           IMAGE
        ========================= */

        .image-area {
            min-height: 500px;
            border-radius: 16px;
            background: {{ $product['background'] }};
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .image-area::after {
            content: "GIZMOMART";
            position: absolute;
            bottom: 20px;
            right: 25px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            color: rgba(23, 105, 224, 0.25);
        }

        .product-icon {
            font-size: 150px;
            filter: drop-shadow(0 20px 20px rgba(0,0,0,0.08));
        }

        /* =========================
           DETAILS
        ========================= */

        .details {
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .category {
            display: inline-block;
            width: fit-content;
            color: #3977d5;
            background: #edf5ff;
            padding: 7px 12px;
            border-radius: 7px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .details h1 {
            font-size: 42px;
            letter-spacing: -1.5px;
            margin-bottom: 12px;
        }

        .rating {
            color: #718096;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .rating span {
            color: #f59e0b;
            font-size: 17px;
        }

        .price {
            color: #1769e0;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .description {
            color: #718096;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        /* =========================
           FEATURES
        ========================= */

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 28px;
        }

        .feature {
            background: #f6f9ff;
            border: 1px solid #e5edfa;
            border-radius: 9px;
            padding: 12px;
        }

        .feature small {
            display: block;
            color: #8995a8;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .feature strong {
            font-size: 12px;
            color: #253047;
        }

        /* =========================
           BUTTONS
        ========================= */

        .actions {
            display: flex;
            gap: 12px;
        }

        .cart-btn,
        .buy-btn {
            border: none;
            padding: 14px 22px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .cart-btn {
            background: #1769e0;
            color: white;
        }

        .cart-btn:hover {
            background: #0f55bd;
            transform: translateY(-2px);
        }

        .buy-btn {
            background: #eaf2ff;
            color: #1769e0;
        }

        .buy-btn:hover {
            background: #dceaff;
        }

        /* =========================
           SPECIFICATIONS
        ========================= */

        .spec-section {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 20px;
            margin-top: 25px;
            padding: 30px;
        }

        .spec-section h2 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .spec-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .spec {
            padding: 18px;
            background: #f7faff;
            border-radius: 10px;
            border: 1px solid #e8eef8;
        }

        .spec span {
            display: block;
            color: #8995a8;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .spec strong {
            font-size: 13px;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #ffffff;
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

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 850px) {

            .nav-links {
                display: none;
            }

            .product-box {
                grid-template-columns: 1fr;
            }

            .image-area {
                min-height: 350px;
            }

            .details h1 {
                font-size: 34px;
            }

            .spec-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 550px) {

            .container {
                width: 92%;
            }

            .product-box {
                padding: 18px;
            }

            .image-area {
                min-height: 280px;
            }

            .product-icon {
                font-size: 100px;
            }

            .features,
            .spec-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .cart-btn,
            .buy-btn {
                width: 100%;
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

<!-- NAVBAR -->

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

 <a href="{{ route('cart') }}" class="cart">
    🛒 Cart
</a>

</nav>


<!-- MAIN -->

<div class="container">

    <a href="/products" class="back">
        ← Back to Products
    </a>


    <div class="product-box">

        <!-- PRODUCT IMAGE -->

        <div class="image-area">

            <div class="product-icon">
                {{ $product['icon'] }}
            </div>

        </div>


        <!-- PRODUCT DETAILS -->

        <div class="details">

            <div class="category">
                {{ $product['category'] }}
            </div>

            <h1>
                {{ $product['name'] }}
            </h1>

            <div class="rating">
                <span>★★★★★</span>
                {{ $product['rating'] }} ·
                {{ $product['reviews'] }} reviews
            </div>

            <div class="price">
                ₱{{ number_format($product['price']) }}
            </div>

            <p class="description">
                {{ $product['description'] }}
            </p>


            <!-- FEATURES -->

            <div class="features">

                <div class="feature">
                    <small>Availability</small>
                    <strong>✓ In Stock</strong>
                </div>

                <div class="feature">
                    <small>Shipping</small>
                    <strong>Free Delivery</strong>
                </div>

                <div class="feature">
                    <small>Warranty</small>
                    <strong>1 Year Warranty</strong>
                </div>

                <div class="feature">
                    <small>Returns</small>
                    <strong>7-Day Returns</strong>
                </div>

            </div>


            <!-- BUTTONS -->

            <div class="actions">

              <form action="{{ route('cart.add', $product['slug']) }}" method="POST">

           <button type="submit" class="cart-btn">
        🛒 Add to Cart
        </button>
      </form>

            </div>

        </div>

    </div>


    <!-- SPECIFICATIONS -->

    <div class="spec-section">

        <h2>
            Product Specifications
        </h2>

        <div class="spec-grid">

            @foreach($product['specs'] as $key => $value)

                <div class="spec">

                    <span>
                        {{ $key }}
                    </span>

                    <strong>
                        {{ $value }}
                    </strong>

                </div>

            @endforeach

        </div>

    </div>

</div>


<!-- FOOTER -->

<footer>

    <div>
        © 2026 GizmoMart
    </div>

    <div>
        Quality tech. Better everyday.
    </div>

</footer>


<script>

function addToCart() {

    const button =
        document.querySelector(".cart-btn");

    button.textContent = "✓ Added to Cart";

    button.style.background = "#16a34a";

    setTimeout(() => {

        button.textContent = "🛒 Add to Cart";

        button.style.background = "#1769e0";

    }, 1200);

}

</script>

</body>
</html>