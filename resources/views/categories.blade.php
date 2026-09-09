<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy - Categories</title>

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
            padding: 18px 7%;
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
            gap: 32px;
            font-size: 14px;
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
            font-weight: 600;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .search {
            width: 220px;
            padding: 10px 14px;
            border-radius: 9px;
            border: 1px solid #fbe2db;
            background: #fff7f5;
            outline: none;
        }

        .search:focus {
            border-color: #ff7044;
            background: #ffffff;
        }

        .cart {
            color: #e8420f;
            font-size: 14px;
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
            font-size: 11px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            width: 86%;
            margin: 55px auto 35px;
            text-align: center;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .page-header h1 {
            font-size: 43px;
            letter-spacing: -1.5px;
            margin-top: 10px;
            margin-bottom: 12px;
        }

        .page-header p {
            color: #977970;
            font-size: 15px;
            line-height: 1.6;
        }

        /* =========================
           CATEGORIES
        ========================= */

        .container {
            width: 86%;
            max-width: 1300px;
            margin: 0 auto 80px;
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .category {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.25s;
        }

        .category:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(39, 84, 150, 0.12);
            border-color: #fad3c7;
        }

        .category-link {
            display: block;
            padding: 30px 20px;
            text-align: center;
        }

        .icon-box {
            width: 90px;
            height: 90px;
            margin: 0 auto 18px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .blue {
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
        }

        .purple {
            background: linear-gradient(145deg, #f0efff, #e1e3ff);
        }

        .cyan {
            background: linear-gradient(145deg, #e7fbff, #ffdfd5);
        }

        .pink {
            background: linear-gradient(145deg, #fff0f6, #ffe0ec);
        }

        .green {
            background: linear-gradient(145deg, #ecfdf5, #d1fae5);
        }

        .orange {
            background: linear-gradient(145deg, #fffaed, #fee8aa);
        }

        .yellow {
            background: linear-gradient(145deg, #fffbeb, #fef3c7);
        }

        .lavender {
            background: linear-gradient(145deg, #f3edff, #e9ddff);
        }

        .category h3 {
            font-size: 17px;
            margin-bottom: 9px;
            color: #172033;
        }

        .category p {
            font-size: 12px;
            color: #977970;
            line-height: 1.5;
            min-height: 54px;
        }

        .shop-button {
            display: inline-block;
            margin-top: 18px;
            padding: 9px 16px;
            background: #e8420f;
            color: #ffffff;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .category:hover .shop-button {
            background: #c43408;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #ffffff;
            border-top: 1px solid #f7e5e0;
            padding: 35px 7%;
            display: flex;
            justify-content: space-between;
            color: #977970;
            font-size: 13px;
        }

        footer div:first-child {
            color: #e8420f;
            font-weight: 600;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {
            .categories {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .nav-links {
                display: none;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {

            .navbar {
                padding: 16px 5%;
            }

            .search {
                display: none;
            }

            .page-header,
            .container {
                width: 92%;
            }

            .page-header h1 {
                font-size: 34px;
            }

            .categories {
                grid-template-columns: 1fr;
            }

            footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
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

        <!-- LOGO -->
        <a href="{{ route('buyer.dashboard') }}" class="logo">
            Boom<span>Buy</span>
        </a>


        <!-- NAV LINKS -->
        <div class="nav-links">

            <!-- HOME -->
            <a href="{{ route('buyer.dashboard') }}">
                Home
            </a>

            <!-- SHOP -->
            <a href="{{ route('products') }}">
                Shop
            </a>

            <!-- CATEGORIES -->
            <a href="{{ route('categories') }}" class="active">
                Categories
            </a>

            <!-- ABOUT -->
            <a href="{{ route('buyer.dashboard') }}#about">
                About
            </a>

        </div>


        <!-- RIGHT SIDE -->
        <div class="nav-right">

            <!-- SEARCH -->
            <input
                type="text"
                class="search"
                placeholder="Search anything..."
                id="searchInput"
            >


            <!-- CART -->
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
            Explore products from different categories
            and discover everything you need in one place.
        </p>

    </section>


    <!-- =========================
         CATEGORIES
    ========================= -->

    <main class="container">

        <div class="categories">


            <!-- ELECTRONICS -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=electronics"
                    class="category-link"
                >

                    <div class="icon-box blue">
                        📱
                    </div>

                    <h3>
                        Electronics & Gadgets
                    </h3>

                    <p>
                        Phones, laptops, cameras, audio
                        and electronic accessories.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- WOMEN -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=women"
                    class="category-link"
                >

                    <div class="icon-box pink">
                        👗
                    </div>

                    <h3>
                        Women's Apparel
                    </h3>

                    <p>
                        Dresses, tops, activewear, shoes
                        and fashion accessories.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- MEN -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=men"
                    class="category-link"
                >

                    <div class="icon-box blue">
                        👕
                    </div>

                    <h3>
                        Men's Apparel
                    </h3>

                    <p>
                        Shirts, pants, jackets, shoes,
                        accessories and grooming.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- KIDS -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=kids"
                    class="category-link"
                >

                    <div class="icon-box yellow">
                        🧸
                    </div>

                    <h3>
                        Kids & Baby
                    </h3>

                    <p>
                        Baby essentials, toys, clothes,
                        games and nursery products.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- HOME -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=home"
                    class="category-link"
                >

                    <div class="icon-box orange">
                        🏠
                    </div>

                    <h3>
                        Home & Garden
                    </h3>

                    <p>
                        Kitchen appliances, furniture,
                        decor and gardening supplies.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- SPORTS -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=sports"
                    class="category-link"
                >

                    <div class="icon-box green">
                        ⚽
                    </div>

                    <h3>
                        Sports & Outdoors
                    </h3>

                    <p>
                        Fitness equipment, camping gear,
                        bikes and sports apparel.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- BEAUTY -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=beauty"
                    class="category-link"
                >

                    <div class="icon-box pink">
                        💄
                    </div>

                    <h3>
                        Health & Beauty
                    </h3>

                    <p>
                        Skincare, makeup, haircare,
                        grooming and personal care.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- BOOKS -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=books"
                    class="category-link"
                >

                    <div class="icon-box purple">
                        📚
                    </div>

                    <h3>
                        Books & Media
                    </h3>

                    <p>
                        Books, magazines, music, movies,
                        games and educational media.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- FOOD -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=food"
                    class="category-link"
                >

                    <div class="icon-box orange">
                        🍔
                    </div>

                    <h3>
                        Food & Gourmet
                    </h3>

                    <p>
                        Snacks, beverages, baking supplies,
                        specialty and organic foods.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- AUTOMOTIVE -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=automotive"
                    class="category-link"
                >

                    <div class="icon-box purple">
                        🚗
                    </div>

                    <h3>
                        Automotive & Motorcycle
                    </h3>

                    <p>
                        Vehicle parts, accessories, tools,
                        tires and protective gear.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- FURNITURE -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=furniture"
                    class="category-link"
                >

                    <div class="icon-box lavender">
                        🪑
                    </div>

                    <h3>
                        Furniture & Office Equipment
                    </h3>

                    <p>
                        Desks, chairs, cabinets,
                        workstations and office equipment.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- JEWELRY -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=jewelry"
                    class="category-link"
                >

                    <div class="icon-box yellow">
                        💎
                    </div>

                    <h3>
                        Jewelry & Watches
                    </h3>

                    <p>
                        Necklaces, rings, earrings,
                        bracelets and watches.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>


            <!-- OFFICE -->

            <div class="category">

                <a
                    href="{{ route('products') }}?category=office"
                    class="category-link"
                >

                    <div class="icon-box cyan">
                        ✏️
                    </div>

                    <h3>
                        Office & School Supplies
                    </h3>

                    <p>
                        Notebooks, pens, backpacks,
                        printers and craft materials.
                    </p>

                    <span class="shop-button">
                        Shop Now
                    </span>

                </a>

            </div>

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
         SEARCH
    ========================= -->

    <script>

        const searchInput =
            document.getElementById("searchInput");

        const categories =
            document.querySelectorAll(".category");


        searchInput.addEventListener("input", function () {

            const search =
                this.value.toLowerCase().trim();


            categories.forEach(category => {

                const text =
                    category.textContent.toLowerCase();


                if (text.includes(search)) {

                    category.style.display = "block";

                } else {

                    category.style.display = "none";

                }

            });

        });

    </script>

</body>
</html>