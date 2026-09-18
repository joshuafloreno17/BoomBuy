<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoomBuy - Categories</title>

    @include('partials.pwa-head')

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
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 17px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #e8420f;
            white-space: nowrap;
        }

        .logo span {
            color: #172033;
        }

        .nav-links {
            display: flex;
            gap: 28px;
            font-size: 13px;
            color: #8d6c62;
        }

        .nav-links a {
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #e8420f;
        }

        .nav-links a.active {
            color: #e8420f;
            font-weight: 700;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search {
            width: 220px;
            padding: 10px 14px;
            border-radius: 9px;
            border: 1px solid #fbe2db;
            background: #fff7f5;
            outline: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px;
        }

        .search:focus {
            border-color: #ff7044;
            background: #ffffff;
        }

        .cart {
            color: #e8420f;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .cart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            margin-left: 4px;
            background: #e8420f;
            color: #ffffff;
            border-radius: 50px;
            font-size: 10px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            width: 86%;
            max-width: 1300px;
            margin: 50px auto 35px;
            text-align: center;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 800;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #977970;
            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================
           CATEGORY SEARCH
        ========================= */

        .category-search-box {
            width: 86%;
            max-width: 1300px;
            margin: 0 auto 30px;
            position: relative;
        }

        .category-search {
            width: 100%;
            padding: 14px 18px 14px 45px;
            border: 1px solid #f3ddd6;
            border-radius: 12px;
            background: #ffffff;
            outline: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            color: #172033;
            box-shadow: 0 8px 25px rgba(39, 84, 150, 0.05);
        }

        .category-search:focus {
            border-color: #e8420f;
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.08);
        }

        .category-search-icon {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
        }

        /* =========================
           CATEGORY GRID
        ========================= */

        .container {
            width: 86%;
            max-width: 1300px;
            margin: 0 auto 80px;
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .category {
            background: #ffffff;
            border: 1px solid #f3e2dd;
            border-radius: 15px;
            padding: 20px;
            transition: 0.22s ease;
        }

        .category:hover {
            transform: translateY(-3px);
            border-color: #f2cfc3;
            box-shadow: 0 12px 28px rgba(39, 84, 150, 0.08);
        }

        .category.hidden {
            display: none;
        }

        /* =========================
           CATEGORY HEADER
        ========================= */

        .category-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 13px;
            border-bottom: 1px solid #f5e9e5;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .blue {
            background: linear-gradient(145deg, #eaf4ff, #dcecff);
        }

        .pink {
            background: linear-gradient(145deg, #fff0f6, #ffe1ec);
        }

        .yellow {
            background: linear-gradient(145deg, #fffbea, #fff0bd);
        }

        .orange {
            background: linear-gradient(145deg, #fff2e9, #ffe0cf);
        }

        .green {
            background: linear-gradient(145deg, #ecfdf5, #d5f7e4);
        }

        .purple {
            background: linear-gradient(145deg, #f3efff, #e6ddff);
        }

        .cyan {
            background: linear-gradient(145deg, #e9fbff, #d6f5fb);
        }

        .lavender {
            background: linear-gradient(145deg, #f7f0ff, #eadfff);
        }

        .category-title {
            min-width: 0;
        }

        .category-title h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #172033;
            line-height: 1.15;
        }

        .category-title span {
            display: block;
            margin-top: 3px;
            color: #b0958c;
            font-size: 10px;
        }

        /* =========================
           SUBCATEGORIES
        ========================= */

        .subcategories {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .subcategory {
            color: #806b64;
            font-size: 11px;
            line-height: 1.35;
            transition: 0.18s;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .subcategory::before {
            content: "›";
            color: #e8420f;
            font-size: 15px;
            font-weight: 800;
            line-height: 1;
        }

        .subcategory:hover {
            color: #e8420f;
            transform: translateX(3px);
        }

        .view-all {
            display: inline-block;
            margin-top: 14px;
            color: #e8420f;
            font-size: 10px;
            font-weight: 800;
        }

        .view-all:hover {
            color: #c43408;
        }

        /* =========================
           EMPTY SEARCH
        ========================= */

        .empty-search {
            display: none;
            background: #ffffff;
            border: 1px solid #f3e2dd;
            border-radius: 15px;
            padding: 45px 20px;
            text-align: center;
            color: #977970;
        }

        .empty-search.show {
            display: block;
        }

        .empty-search-icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .empty-search h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 20px;
            color: #563a32;
            margin-bottom: 5px;
        }

        .empty-search p {
            font-size: 12px;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #ffffff;
            border-top: 1px solid #f7e5e0;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #977970;
            font-size: 12px;
        }

        footer div:first-child {
            color: #e8420f;
            font-weight: 700;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {
            .categories {
                grid-template-columns: repeat(3, 1fr);
            }

            .nav-links {
                gap: 18px;
            }

            .search {
                width: 180px;
            }
        }

        @media (max-width: 900px) {
            .navbar {
                flex-wrap: wrap;
            }

            .nav-links {
                order: 3;
                width: 100%;
                justify-content: center;
                padding-top: 5px;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-right {
                margin-left: auto;
            }
        }

        @media (max-width: 650px) {
            .navbar {
                padding: 15px 5%;
            }

            .logo {
                font-size: 22px;
            }

            .nav-right {
                gap: 8px;
            }

            .search {
                display: none;
            }

            .nav-links {
                gap: 20px;
                font-size: 12px;
            }

            .page-header,
            .category-search-box,
            .container {
                width: 92%;
            }

            .page-header {
                margin-top: 35px;
            }

            .page-header h1 {
                font-size: 34px;
            }

            .page-header p {
                font-size: 13px;
            }

            .categories {
                grid-template-columns: 1fr;
            }

            .category {
                padding: 18px;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }

        /* =========================
           BOOMBUY DESIGN OVERRIDES
        ========================= */

        h1,
        h2,
        h3,
        .logo {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button,
        .btn {
            border-radius: 12px;
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

        <a href="{{ route('buyer.dashboard') }}" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="nav-links">

            <a href="{{ route('buyer.dashboard') }}">
                Home
            </a>

            <a href="{{ route('products') }}">
                Shop
            </a>

            <a href="{{ route('categories') }}" class="active">
                Categories
            </a>

            <a href="{{ route('buyer.dashboard') }}#about">
                About
            </a>

        </div>

        <div class="nav-right">

            <input
                type="text"
                class="search"
                placeholder="Search anything..."
                id="searchInput"
            >

            <a href="{{ route('cart') }}" class="cart">
                🛒 Cart

                @php
                    $cartCount = array_sum(session('cart', []));
                @endphp

                @if($cartCount > 0)
                    <span class="cart-badge">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>

        </div>

    </nav>


    <!-- =========================
         PAGE HEADER
    ========================= -->

    <section class="page-header">

        <small>
            BoomBuy Marketplace
        </small>

        <h1>
            Shop by Category
        </h1>

        <p>
            Explore everything you need from everyday essentials,
            fashion, electronics, home products, and more.
        </p>

    </section>


    <!-- =========================
         CATEGORY SEARCH
    ========================= -->

    <div class="category-search-box">

        <span class="category-search-icon">
            🔎
        </span>

        <input
            type="text"
            id="categorySearch"
            class="category-search"
            placeholder="Search categories or subcategories..."
        >

    </div>


    <!-- =========================
         CATEGORIES
    ========================= -->

    <main class="container">

        <div class="categories" id="categoriesGrid">


            <!-- PET SUPPLIES -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box green">
                        🐶
                    </div>

                    <div class="category-title">

                        <h2>
                            Pet Supplies
                        </h2>

                        <span>
                            Everything for your pets
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Dog Food & Treats
                    </a>

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Cat Litter & Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Aquariums & Fish Supplies
                    </a>

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Bird Feeders & Food
                    </a>

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Pet Grooming Products
                    </a>

                    <a href="{{ route('products') }}?category=Pet%20Supplies" class="subcategory">
                        Pet Health & Wellness
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Pet%20Supplies" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- ELECTRONICS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box blue">
                        📱
                    </div>

                    <div class="category-title">

                        <h2>
                            Electronics & Gadgets
                        </h2>

                        <span>
                            Tech and electronic products
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Mobile Phones & Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Laptops, Desktops & Monitors
                    </a>

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Audio & Video Equipment
                    </a>

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Smart Home Devices
                    </a>

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Cameras & Photography
                    </a>

                    <a href="{{ route('products') }}?category=Electronics" class="subcategory">
                        Wearable Technology
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Electronics" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- WOMEN -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box pink">
                        👗
                    </div>

                    <div class="category-title">

                        <h2>
                            Women's Apparel
                        </h2>

                        <span>
                            Fashion and accessories
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Dresses & Skirts
                    </a>

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Tops & Blouses
                    </a>

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Activewear & Yoga Pants
                    </a>

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Lingerie & Sleepwear
                    </a>

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Jackets & Coats
                    </a>

                    <a href="{{ route('products') }}?category=Women's%20Fashion" class="subcategory">
                        Shoes & Accessories
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Women's%20Fashion" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- MEN -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box blue">
                        👕
                    </div>

                    <div class="category-title">

                        <h2>
                            Men's Apparel
                        </h2>

                        <span>
                            Men's fashion essentials
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Suits & Blazers
                    </a>

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Casual Shirts & Pants
                    </a>

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Outerwear & Jackets
                    </a>

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Activewear & Fitness Gear
                    </a>

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Shoes & Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Men's%20Fashion" class="subcategory">
                        Grooming Products
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Men's%20Fashion" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- KIDS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box yellow">
                        🧸
                    </div>

                    <div class="category-title">

                        <h2>
                            Kids & Baby
                        </h2>

                        <span>
                            Products for little ones
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Baby Clothes & Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Toys & Games
                    </a>

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Educational Materials
                    </a>

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Strollers & Gear
                    </a>

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Nursery Furniture
                    </a>

                    <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="subcategory">
                        Safety & Health
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Kids%20%26%20Baby" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- HOME -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box orange">
                        🏠
                    </div>

                    <div class="category-title">

                        <h2>
                            Home & Garden
                        </h2>

                        <span>
                            Make your home better
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Kitchen Appliances
                    </a>

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Furniture & Decor
                    </a>

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Gardening Tools
                    </a>

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Outdoor Living
                    </a>

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Home Improvement Tools
                    </a>

                    <a href="{{ route('products') }}?category=Home%20%26%20Living" class="subcategory">
                        Bedding & Bath
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Home%20%26%20Living" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- SPORTS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box green">
                        ⚽
                    </div>

                    <div class="category-title">

                        <h2>
                            Sports & Outdoors
                        </h2>

                        <span>
                            Fitness and outdoor activities
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Fitness Equipment
                    </a>

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Camping & Hiking Gear
                    </a>

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Sports Apparel
                    </a>

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Cycling & Bikes
                    </a>

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Water Sports
                    </a>

                    <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="subcategory">
                        Team Sports Equipment
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Sports%20%26%20Outdoors" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- HEALTH & BEAUTY -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box pink">
                        💄
                    </div>

                    <div class="category-title">

                        <h2>
                            Health & Beauty
                        </h2>

                        <span>
                            Beauty and personal care
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Skincare Products
                    </a>

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Haircare Solutions
                    </a>

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Makeup & Cosmetics
                    </a>

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Personal Care Appliances
                    </a>

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Men's Grooming
                    </a>

                    <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="subcategory">
                        Health Supplements
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Beauty%20%26%20Personal%20Care" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- BOOKS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box purple">
                        📚
                    </div>

                    <div class="category-title">

                        <h2>
                            Books & Media
                        </h2>

                        <span>
                            Books and entertainment
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Fiction & Non-Fiction Books
                    </a>

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Magazines & Periodicals
                    </a>

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Music CDs & Vinyl Records
                    </a>

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Movies & Blu-ray
                    </a>

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Video Games & Consoles
                    </a>

                    <a href="{{ route('products') }}?category=Books%20%26%20Media" class="subcategory">
                        Educational Media
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Books%20%26%20Media" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- FOOD -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box orange">
                        🍔
                    </div>

                    <div class="category-title">

                        <h2>
                            Food & Gourmet
                        </h2>

                        <span>
                            Food and everyday treats
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Baking Supplies & Ingredients
                    </a>

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Coffee, Tea & Beverages
                    </a>

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Snacks & Candy
                    </a>

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Specialty Foods
                    </a>

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Organic & Health Foods
                    </a>

                    <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="subcategory">
                        Prepared Foods
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Food%20%26%20Beverages" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- AUTOMOTIVE -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box blue">
                        🚗
                    </div>

                    <div class="category-title">

                        <h2>
                            Automotive & Motorcycle
                        </h2>

                        <span>
                            Vehicle products and accessories
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Car Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Motorcycle Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Vehicle Parts
                    </a>

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Tires & Wheels
                    </a>

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Automotive Tools
                    </a>

                    <a href="{{ route('products') }}?category=Automotive" class="subcategory">
                        Safety & Protective Gear
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Automotive" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- FURNITURE -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box lavender">
                        🪑
                    </div>

                    <div class="category-title">

                        <h2>
                            Furniture & Office Equipment
                        </h2>

                        <span>
                            Furniture and workspace essentials
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Desks & Tables
                    </a>

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Office Chairs
                    </a>

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Cabinets & Storage
                    </a>

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Workstations
                    </a>

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Home Furniture
                    </a>

                    <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="subcategory">
                        Office Accessories
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Furniture%20%26%20Office%20Equipment" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- JEWELRY -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box yellow">
                        💎
                    </div>

                    <div class="category-title">

                        <h2>
                            Jewelry & Watches
                        </h2>

                        <span>
                            Jewelry and timepieces
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Necklaces
                    </a>

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Rings
                    </a>

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Earrings
                    </a>

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Bracelets
                    </a>

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Watches
                    </a>

                    <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="subcategory">
                        Fashion Jewelry
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Jewelry%20%26%20Accessories" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- OFFICE -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box cyan">
                        ✏️
                    </div>

                    <div class="category-title">

                        <h2>
                            Office & School Supplies
                        </h2>

                        <span>
                            School and office essentials
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        Notebooks & Paper
                    </a>

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        Pens & Writing Tools
                    </a>

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        Backpacks & Bags
                    </a>

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        Printers & Supplies
                    </a>

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        Craft Materials
                    </a>

                    <a href="{{ route('products') }}?category=Office%20%26%20School" class="subcategory">
                        School Accessories
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Office%20%26%20School" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- TOYS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box purple">
                        🎮
                    </div>

                    <div class="category-title">

                        <h2>
                            Toys, Games & Hobbies
                        </h2>

                        <span>
                            Fun and hobby collections
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Action Figures
                    </a>

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Board Games
                    </a>

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Gaming Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Collectibles
                    </a>

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Arts & Crafts
                    </a>

                    <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="subcategory">
                        Hobby Supplies
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Toys%2C%20Games%20%26%20Hobbies" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- SHOES -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box pink">
                        👟
                    </div>

                    <div class="category-title">

                        <h2>
                            Shoes
                        </h2>

                        <span>
                            Footwear for everyone
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Sneakers
                    </a>

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Running Shoes
                    </a>

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Sandals
                    </a>

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Slippers
                    </a>

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Formal Shoes
                    </a>

                    <a href="{{ route('products') }}?category=Shoes" class="subcategory">
                        Kids' Shoes
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Shoes" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- TOOLS -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box orange">
                        🧰
                    </div>

                    <div class="category-title">

                        <h2>
                            Tools & Home Improvement
                        </h2>

                        <span>
                            Tools and repair essentials
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Hand Tools
                    </a>

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Power Tools
                    </a>

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Hardware
                    </a>

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Electrical Supplies
                    </a>

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Plumbing Supplies
                    </a>

                    <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="subcategory">
                        Safety Equipment
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Tools%20%26%20Home%20Improvement" class="view-all">
                    View Products →
                </a>

            </div>


            <!-- GARDEN -->

            <div class="category">

                <div class="category-top">

                    <div class="icon-box green">
                        🌱
                    </div>

                    <div class="category-title">

                        <h2>
                            Garden & Outdoor
                        </h2>

                        <span>
                            Outdoor and gardening products
                        </span>

                    </div>

                </div>

                <div class="subcategories">

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Gardening Tools
                    </a>

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Seeds & Plants
                    </a>

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Plant Pots & Accessories
                    </a>

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Outdoor Furniture
                    </a>

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Grills & Outdoor Cooking
                    </a>

                    <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="subcategory">
                        Outdoor Decorations
                    </a>

                </div>

                <a href="{{ route('products') }}?category=Garden%20%26%20Outdoor" class="view-all">
                    View Products →
                </a>

            </div>


        </div>


        <!-- EMPTY SEARCH -->

        <div class="empty-search" id="emptySearch">

            <div class="empty-search-icon">
                🔎
            </div>

            <h3>
                No category found
            </h3>

            <p>
                Try searching for another category or product type.
            </p>

        </div>

    </main>


    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        <div>
            © 2026 BoomBuy
        </div>

        <div>
            Your Marketplace for Everything.
        </div>

    </footer>


    <!-- =========================
         CATEGORY SEARCH SCRIPT
    ========================= -->

    <script>

        const categorySearch =
            document.getElementById('categorySearch');

        const categoryCards =
            document.querySelectorAll('.category');

        const emptySearch =
            document.getElementById('emptySearch');


        categorySearch.addEventListener('input', function () {

            const search =
                this.value.toLowerCase().trim();

            let visibleCount = 0;


            categoryCards.forEach(function (category) {

                const text =
                    category.textContent.toLowerCase();

                if (text.includes(search)) {

                    category.classList.remove('hidden');
                    visibleCount++;

                } else {

                    category.classList.add('hidden');

                }

            });


            if (visibleCount === 0) {

                emptySearch.classList.add('show');

            } else {

                emptySearch.classList.remove('show');

            }

        });


        /*
         * Navbar search
         * Keeps the existing search box useful.
         */

        const searchInput =
            document.getElementById('searchInput');

        searchInput.addEventListener('keydown', function (event) {

            if (event.key === 'Enter') {

                const value =
                    this.value.trim();

                if (value !== '') {

                    window.location.href =
                        "{{ route('products') }}" +
                        "?search=" +
                        encodeURIComponent(value);

                }

            }

        });

    </script>

    @include('partials.pwa-register')

</body>
</html>