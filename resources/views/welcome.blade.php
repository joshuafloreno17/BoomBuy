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
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fffaf8;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           TYPOGRAPHY
        ========================= */

        h1,
        h2,
        h3,
        .logo,
        .price,
        .cta h2 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
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

            padding: 17px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            box-shadow: 0 2px 12px rgba(70, 40, 30, 0.03);
        }

        .logo {
            font-size: 27px;
            font-weight: 800;
            color: #e8420f;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links a {
            color: #6a4e46;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .nav-links a:hover {
            color: #e8420f;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .login-btn {
            color: #e8420f;
            background: #fff;
            border: 1px solid #f7d9cf;

            padding: 10px 18px;
            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .login-btn:hover {
            background: #fff3f0;
            transform: translateY(-1px);
        }

        .register-btn {
            background: #e8420f;
            color: white;

            padding: 11px 19px;
            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
            box-shadow: 0 7px 18px rgba(232, 66, 15, 0.15);
        }

        .register-btn:hover {
            background: #cf380b;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(232, 66, 15, 0.20);
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            padding: 82px 7% 75px;

            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 55px;
            align-items: center;

            background:
                radial-gradient(circle at 80% 20%, #ffe5dc 0, transparent 35%),
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

            letter-spacing: 1.4px;
            text-transform: uppercase;

            margin-bottom: 18px;
        }

        .hero-content h1 {
            font-size: clamp(44px, 5vw, 68px);
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

            font-size: 15px;
            line-height: 1.8;

            margin-bottom: 30px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .shop-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: #e8420f;
            color: white;

            padding: 14px 24px;
            border-radius: 12px;

            font-size: 13px;
            font-weight: 800;

            box-shadow: 0 10px 25px rgba(232, 66, 15, 0.18);

            transition: 0.2s ease;
        }

        .shop-btn:hover {
            background: #cf380b;
            transform: translateY(-2px);
        }

        .learn-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: white;
            color: #563a32;

            border: 1px solid #f3ddd6;

            padding: 14px 24px;
            border-radius: 12px;

            font-size: 13px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .learn-btn:hover {
            border-color: #e8420f;
            color: #e8420f;
            transform: translateY(-1px);
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
        }

        .hero-card {
            width: 390px;
            max-width: 100%;

            background: white;

            border: 1px solid #f5e1da;
            border-radius: 25px;

            padding: 28px;

            box-shadow: 0 25px 65px rgba(77, 45, 35, 0.12);

            transform: rotate(1.5deg);

            transition: 0.3s ease;
        }

        .hero-card:hover {
            transform: rotate(0deg) translateY(-4px);
        }

        .hero-product {
            height: 230px;

            border-radius: 19px;

            background:
                linear-gradient(145deg, #ffede8, #ffdacf);

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 21px;

            overflow: hidden;
        }

        .hero-product img {
            width: 180px;
            height: 180px;

            object-fit: contain;
        }

        .hero-card h3 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .hero-card p {
            color: #977970;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .price {
            color: #e8420f;
            font-size: 23px;
            font-weight: 800;
        }

        .floating-card {
            position: absolute;

            background: white;

            border: 1px solid #f2e0da;
            border-radius: 14px;

            padding: 14px 17px;

            box-shadow: 0 13px 30px rgba(65, 45, 35, 0.10);

            font-size: 11px;
            font-weight: 800;

            z-index: 2;
        }

        .floating-one {
            top: 25px;
            right: 0;
        }

        .floating-two {
            bottom: 30px;
            left: 5px;
        }

        /* =========================
           FEATURES
        ========================= */

        .features {
            padding: 27px 7%;

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

            gap: 13px;
            padding: 9px;
        }

        .feature-icon {
            width: 46px;
            height: 46px;

            min-width: 46px;

            border-radius: 13px;

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
            line-height: 1.5;
        }

        /* =========================
           SECTIONS
        ========================= */

        .section {
            padding: 78px 7%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 38px;
        }

        .section-header small {
            color: #e8420f;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .section-header h2 {
            font-size: 33px;

            margin-top: 7px;
            margin-bottom: 9px;
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

            grid-template-columns: repeat(4, 1fr);

            gap: 16px;
        }

        .category {
            display: block;

            background: white;

            border: 1px solid #f4e2dc;
            border-radius: 17px;

            padding: 22px 15px;

            text-align: center;

            transition: 0.22s ease;

            cursor: pointer;
        }

        .category:hover {
            transform: translateY(-5px);

            border-color: #f4b9a8;

            box-shadow: 0 14px 30px rgba(72, 45, 35, 0.09);
        }

        .category-icon {
            width: 58px;
            height: 58px;

            margin: 0 auto 13px;

            border-radius: 17px;

            background: #fff1ed;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 27px;

            transition: 0.2s ease;
        }

        .category:hover .category-icon {
            background: #ffe4dc;
            transform: scale(1.04);
        }

        .category h3 {
            color: #33241f;

            font-size: 14px;
            line-height: 1.3;

            margin-bottom: 5px;
        }

        .category p {
            color: #b99c93;

            font-size: 10px;
            line-height: 1.4;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 19px;
        }

        .product {
            background: white;

            border: 1px solid #f4e2dc;
            border-radius: 17px;

            overflow: hidden;

            transition: 0.22s ease;
        }

        .product:hover {
            transform: translateY(-5px);

            box-shadow: 0 17px 34px rgba(72, 45, 35, 0.09);

            border-color: #f1c7ba;
        }

        .product-image {
            height: 185px;

            background:
                linear-gradient(145deg, #ffede8, #ffdfd5);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 67px;
        }

        .product-info {
            padding: 17px;
        }

        .product-info small {
            color: #9a7b72;
            font-size: 10px;
            font-weight: 600;
        }

        .product-info h3 {
            color: #2e211d;

            font-size: 16px;

            margin: 6px 0 12px;
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
            font-weight: 800;

            transition: 0.2s ease;
        }

        .view-btn:hover {
            color: #c43408;
        }

        /* =========================
           CTA
        ========================= */

        .cta {
            margin: 15px 7% 75px;

            padding: 52px;

            border-radius: 23px;

            background:
                radial-gradient(circle at 90% 20%, #ff815b 0, transparent 35%),
                linear-gradient(135deg, #e8420f, #c13206);

            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 30px;

            box-shadow: 0 18px 40px rgba(180, 52, 12, 0.14);
        }

        .cta h2 {
            font-size: 32px;
            margin-bottom: 9px;
        }

        .cta p {
            max-width: 600px;

            font-size: 13px;
            line-height: 1.7;

            opacity: 0.88;
        }

        .cta-btn {
            flex-shrink: 0;

            background: white;
            color: #e8420f;

            padding: 14px 23px;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .cta-btn:hover {
            background: #fff3f0;
            transform: translateY(-1px);
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #111827;

            color: white;

            padding: 52px 7% 24px;
        }

        .footer-grid {
            display: grid;

            grid-template-columns: 1.5fr 1fr 1fr 1fr;

            gap: 38px;

            padding-bottom: 38px;

            border-bottom: 1px solid #263244;
        }

        .footer-brand .logo {
            display: inline-block;
            margin-bottom: 14px;
        }

        .footer-brand p {
            color: #b99c93;

            font-size: 11px;
            line-height: 1.7;

            max-width: 300px;
        }

        footer h4 {
            font-size: 12px;
            margin-bottom: 14px;
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

            transition: 0.2s ease;
        }

        footer li a:hover {
            color: white;
        }

        .copyright {
            padding-top: 21px;

            color: #8d6c62;

            font-size: 10px;

            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1050px) {

            .categories {
                grid-template-columns: repeat(4, 1fr);
            }

            .products {
                grid-template-columns: repeat(2, 1fr);
            }
        }

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

            .hero-visual {
                min-height: 390px;
            }

            .categories {
                grid-template-columns: repeat(3, 1fr);
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
                padding: 14px 5%;
            }

            .logo {
                font-size: 24px;
            }

            .login-btn {
                display: none;
            }

            .hero {
                padding: 58px 5% 60px;
            }

            .hero-content h1 {
                font-size: 43px;
            }

            .hero-content p {
                font-size: 14px;
            }

            .hero-card {
                width: 330px;
                padding: 21px;
            }

            .hero-product {
                height: 190px;
            }

            .hero-product img {
                width: 145px;
                height: 145px;
            }

            .floating-card {
                font-size: 9px;
                padding: 11px 12px;
            }

            .floating-one {
                right: -4px;
            }

            .floating-two {
                left: -4px;
            }

            .section {
                padding: 58px 5%;
            }

            .section-header h2 {
                font-size: 29px;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .category {
                padding: 18px 10px;
            }

            .category-icon {
                width: 52px;
                height: 52px;
                font-size: 24px;
            }

            .category h3 {
                font-size: 12px;
            }

            .category p {
                font-size: 9px;
            }

            .products {
                grid-template-columns: 1fr;
            }

            .cta {
                margin-left: 5%;
                margin-right: 5%;

                padding: 35px 24px;
            }

            .cta h2 {
                font-size: 28px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
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

                <a href="{{ route('login') }}" class="shop-btn">
                    Start Shopping →
                </a>

                <a href="{{ route('register') }}" class="learn-btn">
                    Create Account
                </a>

            </div>

        </div>


        <div class="hero-visual">

            <div class="floating-card floating-one">
                ⭐ 4.9 Customer Rating
            </div>

            <div class="hero-card">

                <div class="hero-product">

                    <img
                        src="{{ asset('images/boombuy-logo.png') }}"
                        alt="BoomBuy Logo"
                    >

                </div>

                <h3>
                    BOOMBUY
                </h3>

                <p>
                    Featured product · Free delivery
                </p>

            </div>

            <div class="floating-card floating-two">
                🚚 Fast & Reliable Delivery
            </div>

        </div>

    </section>


    <!-- =========================
         FEATURES
    ========================= -->

    <section class="features">

        <div class="feature">

            <div class="feature-icon">
                🚚
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
                🔒
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
                🛍️
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

            <!-- 1 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    📱
                </div>

                <h3>
                    Electronics
                </h3>

                <p>
                    Gadgets & devices
                </p>

            </a>


            <!-- 2 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    👗
                </div>

                <h3>
                    Women's Fashion
                </h3>

                <p>
                    Style & clothing
                </p>

            </a>


            <!-- 3 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    👕
                </div>

                <h3>
                    Men's Fashion
                </h3>

                <p>
                    Everyday style
                </p>

            </a>


            <!-- 4 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    👶
                </div>

                <h3>
                    Kids & Baby
                </h3>

                <p>
                    For little ones
                </p>

            </a>


            <!-- 5 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🏠
                </div>

                <h3>
                    Home & Living
                </h3>

                <p>
                    Home essentials
                </p>

            </a>


            <!-- 6 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    ⚽
                </div>

                <h3>
                    Sports & Outdoors
                </h3>

                <p>
                    Active lifestyle
                </p>

            </a>


            <!-- 7 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    💄
                </div>

                <h3>
                    Beauty & Personal Care
                </h3>

                <p>
                    Beauty & care
                </p>

            </a>


            <!-- 8 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🍔
                </div>

                <h3>
                    Food & Beverages
                </h3>

                <p>
                    Food & drinks
                </p>

            </a>


            <!-- 9 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🚗
                </div>

                <h3>
                    Automotive
                </h3>

                <p>
                    Auto essentials
                </p>

            </a>


            <!-- 10 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    📚
                </div>

                <h3>
                    Office & School
                </h3>

                <p>
                    Study & work
                </p>

            </a>


            <!-- 11 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🐶
                </div>

                <h3>
                    Pet Supplies
                </h3>

                <p>
                    For your pets
                </p>

            </a>


            <!-- 12 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🎮
                </div>

                <h3>
                    Toys, Games & Hobbies
                </h3>

                <p>
                    Fun & entertainment
                </p>

            </a>


            <!-- 13 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    💍
                </div>

                <h3>
                    Jewelry & Accessories
                </h3>

                <p>
                    Everyday accessories
                </p>

            </a>


            <!-- 14 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    👟
                </div>

                <h3>
                    Shoes
                </h3>

                <p>
                    Step in style
                </p>

            </a>


            <!-- 15 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🧰
                </div>

                <h3>
                    Tools & Home Improvement
                </h3>

                <p>
                    Build & improve
                </p>

            </a>


            <!-- 16 -->

            <a
                href="{{ route('login') }}"
                class="category"
            >

                <div class="category-icon">
                    🌱
                </div>

                <h3>
                    Garden & Outdoor
                </h3>

                <p>
                    Outdoor essentials
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
                    📱
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

                        <a
                            href="{{ route('login') }}"
                            class="view-btn"
                        >
                            View →
                        </a>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 2 -->

            <div class="product">

                <div class="product-image">
                    💻
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

                        <a
                            href="{{ route('login') }}"
                            class="view-btn"
                        >
                            View →
                        </a>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 3 -->

            <div class="product">

                <div class="product-image">
                    🎧
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

                        <a
                            href="{{ route('login') }}"
                            class="view-btn"
                        >
                            View →
                        </a>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 4 -->

            <div class="product">

                <div class="product-image">
                    ⌚
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

                        <a
                            href="{{ route('login') }}"
                            class="view-btn"
                        >
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

        <a
            href="{{ route('register') }}"
            class="cta-btn"
        >
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