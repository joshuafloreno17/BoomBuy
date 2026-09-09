<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Shop Everything You Love</title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fffaf8;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;

            width: 100%;

            background: rgba(255, 255, 255, 0.96);

            backdrop-filter: blur(12px);

            border-bottom: 1px solid #f9e9e4;

            padding: 18px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            color: #6a4e46;
            font-size: 13px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #e8420f;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-btn {
            color: #e8420f;
            border: 1px solid #fcdfd6;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .login-btn:hover {
            background: #fff3f0;
        }

        .register-btn {
            background: #e8420f;
            color: white;
            padding: 11px 19px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .register-btn:hover {
            background: #c43408;
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            padding: 85px 7% 75px;

            display: grid;
            grid-template-columns: 1.05fr 0.95fr;

            gap: 60px;

            align-items: center;

            background:
                radial-gradient(circle at 80% 20%, #ffe4dc 0, transparent 35%),
                linear-gradient(180deg, #ffffff, #fff7f4);
        }

        .hero-content small {
            display: inline-block;

            color: #e8420f;

            background: #ffefea;

            padding: 8px 13px;

            border-radius: 30px;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            margin-bottom: 18px;
        }

        .hero-content h1 {
            font-size: clamp(42px, 5vw, 68px);

            line-height: 1.02;

            letter-spacing: -2px;

            margin-bottom: 22px;
        }

        .hero-content h1 span {
            color: #e8420f;
        }

        .hero-content p {
            max-width: 560px;

            color: #8d6c62;

            font-size: 16px;

            line-height: 1.8;

            margin-bottom: 30px;
        }

        .hero-actions {
            display: flex;

            gap: 12px;

            flex-wrap: wrap;
        }

        .shop-btn {
            background: #e8420f;

            color: white;

            padding: 15px 25px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            box-shadow: 0 10px 25px rgba(23, 105, 224, 0.20);

            transition: 0.2s;
        }

        .shop-btn:hover {
            background: #c43408;
            transform: translateY(-2px);
        }

        .learn-btn {
            background: white;

            color: #563a32;

            border: 1px solid #f6e1db;

            padding: 15px 25px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;
        }

        .learn-btn:hover {
            border-color: #e8420f;
            color: #e8420f;
        }

        /* =========================
           HERO VISUAL
        ========================= */

        .hero-visual {
            position: relative;

            min-height: 420px;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 45px;
        }

        .hero-card {
            width: 390px;

            max-width: 100%;

            background: white;

            border: 1px solid #f8e4de;

            border-radius: 28px;

            padding: 34px;

            box-shadow: 0 30px 70px rgba(45, 86, 145, 0.15);

            display: flex;

            flex-direction: column;

            align-items: center;

            text-align: center;

            margin: 0 auto;

            transform: rotate(2deg);

            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-card:hover {
            transform: rotate(2deg) scale(1.04);

            box-shadow: 0 35px 80px rgba(45, 86, 145, 0.20);
        }
.hero-product {
    width: 100%;

    height: 260px;

    border-radius: 20px;

    overflow: hidden;

    background:
        linear-gradient(145deg, #ffede8, #ffdacf);

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 24px;
}

.hero-product img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

        .hero-card h3 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -0.5px;

            color: #172033;
        }

        .hero-card h3 span {
            color: #e8420f;
        }

        .hero-card .hero-tagline {
            margin-top: 8px;

            font-size: 13px;

            font-weight: 600;

            color: #8d6c62;

            letter-spacing: 0.3px;

            text-align: center;
        }

        /* =========================
           FEATURES
        ========================= */

        .features {
            padding: 28px 7%;

            background: white;

            border-top: 1px solid #f8efed;

            border-bottom: 1px solid #f8efed;

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 20px;
        }

        .feature {
            display: flex;

            align-items: center;

            gap: 14px;

            padding: 10px;
        }

        .feature-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            background: #fff1ed;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }

        .feature h4 {
            font-size: 13px;

            margin-bottom: 4px;
        }

        .feature p {
            color: #977970;

            font-size: 11px;
        }

        /* =========================
           SECTIONS
        ========================= */

        .section {
            padding: 80px 7%;
        }

        .section-header {
            text-align: center;

            margin-bottom: 40px;
        }

        .section-header small {
            color: #e8420f;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 2px;
        }

        .section-header h2 {
            font-size: 32px;

            margin-top: 8px;

            margin-bottom: 10px;
        }

        .section-header p {
            color: #977970;

            font-size: 13px;
        }

        /* =========================
           CATEGORIES
        ========================= */

        .categories {
            display: grid;

            grid-template-columns: repeat(5, 1fr);

            gap: 18px;
        }

        .category {
            background: white;

            border: 1px solid #f6e6e1;

            border-radius: 16px;

            padding: 25px 15px;

            text-align: center;

            transition: 0.25s;
        }

        .category:hover {
            transform: translateY(-5px);

            border-color: #f9c9ba;

            box-shadow: 0 15px 30px rgba(39, 84, 150, 0.08);
        }

        .category-icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 15px;

            border-radius: 18px;

            background: #fff1ed;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 31px;
        }

        .category h3 {
            font-size: 14px;

            margin-bottom: 5px;
        }

        .category p {
            color: #b99c93;

            font-size: 11px;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 20px;
        }

        .product {
            background: white;

            border: 1px solid #f6e6e1;

            border-radius: 16px;

            overflow: hidden;

            transition: 0.25s;
        }

        .product:hover {
            transform: translateY(-5px);

            box-shadow: 0 18px 35px rgba(39, 84, 150, 0.10);
        }

        .product-image {
            height: 190px;

            background: linear-gradient(145deg, #ffede8, #ffdfd5);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 70px;
        }

        .product-info {
            padding: 18px;
        }

        .product-info small {
            color: #8d6c62;

            font-size: 10px;
        }

        .product-info h3 {
            font-size: 15px;

            margin: 7px 0;
        }

        .product-bottom {
            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .product-price {
            color: #e8420f;

            font-size: 16px;

            font-weight: 800;
        }

        .view-btn {
            color: #e8420f;

            font-size: 11px;

            font-weight: 700;
        }

        /* =========================
           CTA
        ========================= */

        .cta {
            margin: 20px 7% 80px;

            padding: 55px;

            border-radius: 24px;

            background:
                radial-gradient(circle at 90% 20%, #ff815b 0, transparent 35%),
                linear-gradient(135deg, #e8420f, #c13206);

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 30px;
        }

        .cta h2 {
            font-size: 32px;

            margin-bottom: 10px;
        }

        .cta p {
            font-size: 13px;

            line-height: 1.6;

            opacity: 0.85;

            max-width: 600px;
        }

        .cta-btn {
            flex-shrink: 0;

            background: white;

            color: #e8420f;

            padding: 14px 23px;

            border-radius: 9px;

            font-size: 12px;

            font-weight: 800;
        }

        .cta-btn:hover {
            background: #fff3f0;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #111827;

            color: white;

            padding: 55px 7% 25px;
        }

        .footer-grid {
            display: grid;

            grid-template-columns: 1.5fr 1fr 1fr 1fr;

            gap: 40px;

            padding-bottom: 40px;

            border-bottom: 1px solid #263244;
        }

        .footer-brand .logo {
            display: inline-block;

            margin-bottom: 15px;
        }

        .footer-brand p {
            color: #b99c93;

            font-size: 12px;

            line-height: 1.7;

            max-width: 300px;
        }

        footer h4 {
            font-size: 12px;

            margin-bottom: 15px;
        }

        footer ul {
            list-style: none;
        }

        footer li {
            margin-bottom: 9px;
        }

        footer li a {
            color: #b99c93;

            font-size: 11px;
        }

        footer li a:hover {
            color: white;
        }

        .copyright {
            padding-top: 22px;

            color: #8d6c62;

            font-size: 10px;

            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .nav-links {
                display: none;
            }

            .hero {
                grid-template-columns: 1fr;

                text-align: center;
            }

            .hero-content p {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-actions {
                justify-content: center;
            }

            .categories {
                grid-template-columns: repeat(3, 1fr);
            }

            .products {
                grid-template-columns: repeat(2, 1fr);
            }

            .features {
                grid-template-columns: 1fr;
            }

            .cta {
                flex-direction: column;

                text-align: center;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {

            .navbar {
                padding: 15px 5%;
            }

            .login-btn {
                display: none;
            }

            .hero {
                padding: 60px 5%;
            }

            .hero-content h1 {
                font-size: 42px;
            }

            .section {
                padding: 60px 5%;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
            }

            .products {
                grid-template-columns: 1fr;
            }

            .cta {
                margin-left: 5%;
                margin-right: 5%;

                padding: 35px 25px;
            }

            .footer-grid {
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


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>


    <div class="nav-links">

        <a href="/">
            Home
        </a>

        <a href="#categories">
            Categories
        </a>

        <a href="#featured">
            Featured
        </a>

        <!-- LOGIN REQUIRED -->
        <a href="{{ route('login') }}">
            Shop
        </a>

    </div>


    <div class="nav-buttons">

        <a href="{{ route('login') }}" class="login-btn">
            Login
        </a>

        <a href="{{ route('register') }}" class="register-btn">
            Register
        </a>

    </div>

</nav>


<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="hero-content">

        <small>
            Your Everyday Marketplace
        </small>

        <h1>
            Shop More.<br>
            <span>Live Better.</span>
        </h1>

        <p>
            Discover amazing products from trusted sellers
            all in one place. From gadgets and fashion to
            everyday essentials, BoomBuy makes online shopping
            simple, convenient, and exciting.
        </p>


        <div class="hero-actions">

            <!-- LOGIN REQUIRED -->
            <a href="{{ route('login') }}" class="shop-btn">
                Start Shopping →
            </a>

            <a href="{{ route('register') }}" class="learn-btn">
                Create Account
            </a>

        </div>

    </div>


    <div class="hero-visual">

        <div class="hero-card">

            <div class="hero-product">
    <img src="{{ asset('images/boombuy-logo.png') }}" alt="BoomBuy Logo">
</div>
            <h3>
                Boom<span>Buy</span>
            </h3>

            <p class="hero-tagline">
                Buy Smart. Shop Easy. BoomBuy.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     FEATURES
========================= -->

<section class="features">

    <div class="feature">

        <div class="feature-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="14" height="11"/><path d="M15 10h4l3 3v4h-7z"/><circle cx="5.5" cy="19.5" r="1.8"/><circle cx="17.5" cy="19.5" r="1.8"/></svg>
        </div>

        <div>

            <h4>
                Fast Delivery
            </h4>

            <p>
                Get your orders delivered quickly.
            </p>

        </div>

    </div>


    <div class="feature">

        <div class="feature-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="11" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
        </div>

        <div>

            <h4>
                Secure Shopping
            </h4>

            <p>
                Your shopping experience stays protected.
            </p>

        </div>

    </div>


    <div class="feature">

        <div class="feature-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>

        <div>

            <h4>
                Multiple Sellers
            </h4>

            <p>
                Explore products from different sellers.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     CATEGORIES
========================= -->

<section class="section" id="categories">

    <div class="section-header">

        <small>
            Explore
        </small>

        <h2>
            Shop by Category
        </h2>

        <p>
            Find what you need faster.
        </p>

    </div>


    <div class="categories">


        <!-- LOGIN REQUIRED -->

        <a href="{{ route('login') }}" class="category">

            <div class="category-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            </div>

            <h3>
                Electronics
            </h3>

            <p>
                Gadgets & devices
            </p>

        </a>


        <a href="{{ route('login') }}" class="category">

            <div class="category-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4l-4 2-4-2-5 3 2 4 3-1v10h14V10l3 1 2-4z"/></svg>
            </div>

            <h3>
                Fashion
            </h3>

            <p>
                Style & clothing
            </p>

        </a>


        <a href="{{ route('login') }}" class="category">

            <div class="category-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><rect x="10" y="14" width="4" height="6"/></svg>
            </div>

            <h3>
                Home
            </h3>

            <p>
                Home essentials
            </p>

        </a>


        <a href="{{ route('login') }}" class="category">

            <div class="category-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6l1 4H8z"/><path d="M6 6h12l1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z"/></svg>
            </div>

            <h3>
                Beauty
            </h3>

            <p>
                Beauty & care
            </p>

        </a>


        <a href="{{ route('login') }}" class="category">

            <div class="category-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="12" rx="6"/><line x1="7" y1="13" x2="7" y2="13"/><line x1="7" y1="10" x2="7" y2="16"/><line x1="4" y1="13" x2="10" y2="13"/><circle cx="16" cy="11" r="1"/><circle cx="18" cy="14" r="1"/></svg>
            </div>

            <h3>
                Gaming
            </h3>

            <p>
                Gaming gear
            </p>

        </a>

    </div>

</section>


<!-- =========================
     FEATURED PRODUCTS
========================= -->

<section class="section" id="featured">

    <div class="section-header">

        <small>
            Popular Now
        </small>

        <h2>
            Featured Products
        </h2>

        <p>
            Some of the products shoppers love.
        </p>

    </div>


    <div class="products">


        <!-- PRODUCT 1 -->

        <div class="product">

            <div class="product-image">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            </div>

            <div class="product-info">

                <small>
                    Smartphone
                </small>

                <h3>
                    Nova X5 Pro
                </h3>

                <div class="product-bottom">

                    <span class="product-price">
                        ₱18,999
                    </span>

                    <!-- LOGIN REQUIRED -->
                    <a href="{{ route('login') }}" class="view-btn">
                        View →
                    </a>

                </div>

            </div>

        </div>


        <!-- PRODUCT 2 -->

        <div class="product">

            <div class="product-image">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="1"/><path d="M1 20h22l-2-4H3z"/></svg>
            </div>

            <div class="product-info">

                <small>
                    Laptop
                </small>

                <h3>
                    AirBook 14
                </h3>

                <div class="product-bottom">

                    <span class="product-price">
                        ₱34,990
                    </span>

                    <!-- LOGIN REQUIRED -->
                    <a href="{{ route('login') }}" class="view-btn">
                        View →
                    </a>

                </div>

            </div>

        </div>


        <!-- PRODUCT 3 -->

        <div class="product">

            <div class="product-image">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v-2a9 9 0 0 1 18 0v2"/><rect x="1" y="14" width="6" height="8" rx="2"/><rect x="17" y="14" width="6" height="8" rx="2"/></svg>
            </div>

            <div class="product-info">

                <small>
                    Audio
                </small>

                <h3>
                    SoundCore Pro
                </h3>

                <div class="product-bottom">

                    <span class="product-price">
                        ₱2,799
                    </span>

                    <!-- LOGIN REQUIRED -->
                    <a href="{{ route('login') }}" class="view-btn">
                        View →
                    </a>

                </div>

            </div>

        </div>


        <!-- PRODUCT 4 -->

        <div class="product">

            <div class="product-image">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="8" width="10" height="8" rx="2"/><path d="M9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3M9 16v3a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-3"/></svg>
            </div>

            <div class="product-info">

                <small>
                    Wearable
                </small>

                <h3>
                    FitWatch S2
                </h3>

                <div class="product-bottom">

                    <span class="product-price">
                        ₱3,499
                    </span>

                    <!-- LOGIN REQUIRED -->
                    <a href="{{ route('login') }}" class="view-btn">
                        View →
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     CALL TO ACTION
========================= -->

<section class="cta">

    <div>

        <h2>
            Ready to start shopping?
        </h2>

        <p>
            Join BoomBuy today and discover products,
            sellers, and deals made for everyday life.
        </p>

    </div>


    <a href="{{ route('register') }}" class="cta-btn">
        Join BoomBuy →
    </a>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer>

    <div class="footer-grid">


        <div class="footer-brand">

            <a href="/" class="logo">
                Boom<span>Buy</span>
            </a>

            <p>
                Your everyday online marketplace for
                products, sellers, and convenient shopping.
            </p>

        </div>


        <div>

            <h4>
                Marketplace
            </h4>

            <ul>

                <!-- LOGIN REQUIRED -->

                <li>
                    <a href="{{ route('login') }}">
                        Shop Products
                    </a>
                </li>

                <li>
                    <a href="#categories">
                        Categories
                    </a>
                </li>

                <li>
                    <a href="#featured">
                        Featured
                    </a>
                </li>

            </ul>

        </div>


        <div>

            <h4>
                Account
            </h4>

            <ul>

                <li>
                    <a href="{{ route('login') }}">
                        Login
                    </a>
                </li>

                <li>
                    <a href="{{ route('register') }}">
                        Register
                    </a>
                </li>

                <!-- LOGIN REQUIRED -->

                <li>
                    <a href="{{ route('login') }}">
                        Cart
                    </a>
                </li>

            </ul>

        </div>


        <div>

            <h4>
                BoomBuy
            </h4>

            <ul>

                <li>
                    <a href="/">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="/">
                        Contact
                    </a>
                </li>

                <li>
                    <a href="/">
                        Help Center
                    </a>
                </li>

            </ul>

        </div>

    </div>


    <div class="copyright">

        © 2026 BoomBuy · Shop smarter. Live better.

    </div>

</footer>


</body>

</html>