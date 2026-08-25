<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Your Marketplace for Everything</title>

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
            position: sticky;
            top: 0;
            z-index: 100;
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

        .nav-links a {
            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #1769e0;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .search {
            background: #f5f8ff;
            border: 1px solid #dce7fa;
            border-radius: 9px;
            padding: 10px 14px;
            width: 190px;
            outline: none;
        }

        .search:focus {
            border-color: #4b8df8;
            background: white;
        }

        .cart {
            font-size: 14px;
            font-weight: 600;
            color: #1769e0;
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            width: 86%;
            margin: 35px auto 0;
            min-height: 430px;
            background: linear-gradient(
                120deg,
                #dcecff,
                #edf5ff 55%,
                #e8e3ff
            );
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 55px 65px;
            overflow: hidden;
            position: relative;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            background: #bfdbfe;
            border-radius: 50%;
            right: 170px;
            top: -120px;
            opacity: 0.45;
        }

        .hero-content {
            max-width: 530px;
            position: relative;
            z-index: 2;
        }

        .small-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #3977d5;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-size: 56px;
            line-height: 1.05;
            letter-spacing: -2px;
            margin-bottom: 22px;
            color: #14213d;
        }

        .hero p {
            color: #52627a;
            font-size: 16px;
            line-height: 1.7;
            max-width: 440px;
            margin-bottom: 30px;
        }

        .shop-btn {
            display: inline-block;
            background: #1769e0;
            color: white;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(23, 105, 224, 0.22);
            transition: 0.2s;
        }

        .shop-btn:hover {
            background: #0f55bd;
            transform: translateY(-2px);
        }

        .hero-product {
            width: 330px;
            height: 330px;
            border-radius: 50%;
            background: linear-gradient(
                145deg,
                #ffffff,
                #cfe2ff
            );
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 135px;
            margin-right: 45px;
            box-shadow: 0 20px 45px rgba(48, 91, 160, 0.15);
            position: relative;
            z-index: 2;
        }

        /* =========================
           GENERAL SECTIONS
        ========================= */

        .section {
            width: 86%;
            margin: 75px auto 0;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-heading h2 {
            font-size: 25px;
        }

        .view-all {
            font-size: 13px;
            color: #3977d5;
            font-weight: 600;
        }

        /* =========================
           CATEGORIES
        ========================= */

        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .category-card {
            display: block;
            background: white;
            border: 1px solid #e0e9f8;
            border-radius: 14px;
            padding: 25px;
            transition: 0.25s;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            border-color: #a8c8fa;
            box-shadow: 0 12px 25px rgba(43, 92, 160, 0.09);
        }

        .category-card:nth-child(1) .category-icon {
            background: #dbeafe;
        }

        .category-card:nth-child(2) .category-icon {
            background: #e0e7ff;
        }

        .category-card:nth-child(3) .category-icon {
            background: #dff7ff;
        }

        .category-card:nth-child(4) .category-icon {
            background: #e9e1ff;
        }


        .category-card:nth-child(5) .category-icon { background: #e8f5e9; }
        .category-card:nth-child(6) .category-icon { background: #fff3cd; }
        .category-card:nth-child(7) .category-icon { background: #fce7f3; }
        .category-card:nth-child(8) .category-icon { background: #e0f2fe; }
        .category-card:nth-child(9) .category-icon { background: #ffedd5; }
        .category-card:nth-child(10) .category-icon { background: #e5e7eb; }
        .category-card:nth-child(11) .category-icon { background: #f3e8ff; }
        .category-card:nth-child(12) .category-icon { background: #fef3c7; }
        .category-card:nth-child(13) .category-icon { background: #dcfce7; }

        .category-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 22px;
        }

        .category-card h3 {
            font-size: 16px;
            margin-bottom: 7px;
        }

        .category-card p {
            color: #7b8799;
            font-size: 13px;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            margin-top: 75px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e1e9f6;
            transition: 0.25s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(39, 84, 150, 0.10);
        }

        .product-card:nth-child(1) .product-image {
            background: #e3efff;
        }

        .product-card:nth-child(2) .product-image {
            background: #e8eaff;
        }

        .product-card:nth-child(3) .product-image {
            background: #e1f7ff;
        }

        .product-card:nth-child(4) .product-image {
            background: #eee7ff;
        }

        .product-image {
            height: 235px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 90px;
        }

        .product-info {
            padding: 18px;
        }

        .product-category {
            color: #5790df;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .rating {
            color: #718096;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .price {
            font-weight: 700;
            font-size: 16px;
            color: #1769e0;
        }

        .add-btn {
            background: #1769e0;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-btn:hover {
            background: #0f55bd;
        }

        /* =========================
           PROMO
        ========================= */

        .promo {
            width: 86%;
            margin: 80px auto;
            background: linear-gradient(
                120deg,
                #1559c7,
                #3b82f6,
                #6366f1
            );
            color: white;
            border-radius: 18px;
            padding: 45px 55px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.18);
        }

        .promo h2 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .promo p {
            color: #dbeafe;
            font-size: 14px;
        }

        .promo-btn {
            background: white;
            color: #1769e0;
            padding: 13px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .promo-btn:hover {
            background: #eff6ff;
            transform: translateY(-2px);
        }

        /* =========================
           ABOUT
        ========================= */

        .about {
            width: 86%;
            margin: 75px auto;
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 18px;
            padding: 45px 55px;
            text-align: center;
        }

        .about small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            font-size: 11px;
        }

        .about h2 {
            font-size: 30px;
            margin: 12px 0;
        }

        .about p {
            max-width: 700px;
            margin: auto;
            color: #718096;
            line-height: 1.7;
            font-size: 14px;
        }

        /* =========================
           FOOTER
        ========================= */

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

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .nav-links {
                display: none;
            }

            .hero {
                padding: 45px;
            }

            .hero-product {
                width: 250px;
                height: 250px;
                font-size: 100px;
                margin-right: 0;
            }

            .category-grid,
            .product-grid {
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

            .hero {
                width: 92%;
                padding: 40px 25px;
                flex-direction: column;
                text-align: center;
            }

            .hero h1 {
                font-size: 40px;
            }

            .hero p {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-product {
                width: 190px;
                height: 190px;
                font-size: 75px;
                margin-top: 30px;
            }

            .section {
                width: 92%;
            }

            .category-grid,
            .product-grid {
                grid-template-columns: 1fr;
            }

            .promo,
            .about {
                width: 92%;
                padding: 35px 25px;
            }

            .promo {
                flex-direction: column;
                gap: 25px;
                text-align: center;
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
            Boom<span>Buy</span>
        </a>

        <div class="nav-links">

            <a href="/">
                Home
            </a>

            <a href="/products">
                Shop
            </a>

            <!-- CATEGORIES -->
            <a href="/categories">
                Categories
            </a>

            <a href="#about">
                About
            </a>

        </div>

        <div class="nav-actions">

            <input
                type="text"
                class="search"
                placeholder="Search products..."
            >

            <a href="#" class="cart">
                🛒 Cart (0)
            </a>

        </div>

    </nav>


    <!-- HERO -->

    <section class="hero">

        <div class="hero-content">

            <div class="small-title">
                Welcome to BoomBuy
            </div>

            <h1>
                Everything<br>
                you need.
            </h1>

            <p>
                Discover products from different categories and trusted sellers,
                all in one convenient marketplace made for everyday shopping.
            </p>

            <a href="/products" class="shop-btn">
                Explore products →
            </a>

        </div>

        <div class="hero-product">
            🎧
        </div>

    </section>


    <!-- CATEGORIES -->

    <section class="section" id="categories">

        <div class="section-heading">

            <h2>
                Shop by category
            </h2>

            <a href="/categories" class="view-all">
                View all →
            </a>

        </div>

        <div class="category-grid">
            <a href="/categories" class="category-card">
                <div class="category-icon">🛒</div>
                <h3>Electronics & Gadgets</h3>
                <p>Phones, laptops, gadgets & accessories</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">👗</div>
                <h3>Women's Apparel</h3>
                <p>Fashion, shoes and accessories</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">👕</div>
                <h3>Men's Apparel</h3>
                <p>Clothing, shoes and grooming</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">🧸</div>
                <h3>Kids & Baby</h3>
                <p>Toys, clothes and baby essentials</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">🏠</div>
                <h3>Home & Garden</h3>
                <p>Furniture, kitchen and home essentials</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">⚽</div>
                <h3>Sports & Outdoors</h3>
                <p>Fitness, camping and sports gear</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">💄</div>
                <h3>Health & Beauty</h3>
                <p>Skincare, haircare and personal care</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">📚</div>
                <h3>Books & Media</h3>
                <p>Books, games, music and movies</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">🍔</div>
                <h3>Food & Gourmet</h3>
                <p>Snacks, beverages and food products</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">🚗</div>
                <h3>Automotive & Motorcycle</h3>
                <p>Parts, tools and vehicle accessories</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">🪑</div>
                <h3>Furniture & Office</h3>
                <p>Furniture and office equipment</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">💎</div>
                <h3>Jewelry & Watches</h3>
                <p>Jewelry, watches and accessories</p>
            </a>
            <a href="/categories" class="category-card">
                <div class="category-icon">✏️</div>
                <h3>Office & School Supplies</h3>
                <p>School, office and craft supplies</p>
            </a>
        </div>

                <h3>
                    Smartphones
                </h3>

                <p>
                    Phones for every lifestyle
                </p>

            </a>


            <!-- LAPTOPS -->
            <a href="#" class="category-card">

                <div class="category-icon">
                    💻
                </div>

                <h3>
                    Laptops
                </h3>

                <p>
                    Work, study and entertainment
                </p>

            </a>


            <!-- AUDIO -->
            <a href="#" class="category-card">

                <div class="category-icon">
                    🎧
                </div>

                <h3>
                    Audio
                </h3>

                <p>
                    Headphones and speakers
                </p>

            </a>


            <!-- WEARABLES -->
            <a href="#" class="category-card">

                <div class="category-icon">
                    ⌚
                </div>

                <h3>
                    Wearables
                </h3>

                <p>
                    Smart devices on the go
                </p>

            </a>

        </div>

    </section>


    <!-- PRODUCTS -->

    <section class="section products">

        <div class="section-heading">

            <h2>
                Popular right now
            </h2>

            <a href="/products" class="view-all">
                See all products →
            </a>

        </div>


        <div class="product-grid">
            <div class="product-card">
                <div class="product-image">📱</div>
                <div class="product-info">
                    <div class="product-category">Electronics</div>
                    <div class="product-name">Nova X5 Pro</div>
                    <div class="rating">★ 4.8 · 124 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱18,999</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">👟</div>
                <div class="product-info">
                    <div class="product-category">Sports & Outdoors</div>
                    <div class="product-name">Runner Flex Shoes</div>
                    <div class="rating">★ 4.7 · 86 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱2,499</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">👗</div>
                <div class="product-info">
                    <div class="product-category">Women's Apparel</div>
                    <div class="product-name">Classic Summer Dress</div>
                    <div class="rating">★ 4.9 · 143 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱899</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">☕</div>
                <div class="product-info">
                    <div class="product-category">Home & Garden</div>
                    <div class="product-name">BrewMate Coffee Maker</div>
                    <div class="rating">★ 4.6 · 57 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱3,299</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">🎮</div>
                <div class="product-info">
                    <div class="product-category">Electronics</div>
                    <div class="product-name">GamePad X</div>
                    <div class="rating">★ 4.8 · 61 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱2,199</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">💄</div>
                <div class="product-info">
                    <div class="product-category">Health & Beauty</div>
                    <div class="product-name">GlowCare Skincare Set</div>
                    <div class="rating">★ 4.7 · 91 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱1,299</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">🎒</div>
                <div class="product-info">
                    <div class="product-category">Office & School</div>
                    <div class="product-name">Urban School Backpack</div>
                    <div class="rating">★ 4.8 · 112 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱749</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">🎧</div>
                <div class="product-info">
                    <div class="product-category">Electronics</div>
                    <div class="product-name">SoundCore Pro</div>
                    <div class="rating">★ 4.9 · 216 reviews</div>
                    <div class="product-bottom">
                        <div class="price">₱2,799</div>
                        <button class="add-btn" type="button">Add to cart</button>
                    </div>
                </div>
            </div>
        </div>

                <div class="product-info">

                    <div class="product-category">
                        Smartphone
                    </div>

                    <div class="product-name">
                        Nova X5 Pro
                    </div>

                    <div class="rating">
                        ★ 4.8 · 124 reviews
                    </div>

                    <div class="product-bottom">

                        <div class="price">
                            ₱18,999
                        </div>

                        <button class="add-btn">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <div class="product-card">

                <div class="product-image">
                    💻
                </div>

                <div class="product-info">

                    <div class="product-category">
                        Laptop
                    </div>

                    <div class="product-name">
                        AirBook 14
                    </div>

                    <div class="rating">
                        ★ 4.7 · 89 reviews
                    </div>

                    <div class="product-bottom">

                        <div class="price">
                            ₱34,990
                        </div>

                        <button class="add-btn">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <div class="product-card">

                <div class="product-image">
                    🎧
                </div>

                <div class="product-info">

                    <div class="product-category">
                        Audio
                    </div>

                    <div class="product-name">
                        SoundCore Pro
                    </div>

                    <div class="rating">
                        ★ 4.9 · 216 reviews
                    </div>

                    <div class="product-bottom">

                        <div class="price">
                            ₱2,799
                        </div>

                        <button class="add-btn">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <div class="product-card">

                <div class="product-image">
                    ⌚
                </div>

                <div class="product-info">

                    <div class="product-category">
                        Wearable
                    </div>

                    <div class="product-name">
                        FitWatch S2
                    </div>

                    <div class="rating">
                        ★ 4.6 · 73 reviews
                    </div>

                    <div class="product-bottom">

                        <div class="price">
                            ₱3,499
                        </div>

                        <button class="add-btn">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- PROMO -->

    <section class="promo">

        <div>

            <h2>
                Big deals. More choices.
            </h2>

            <p>
                Discover great products from different sellers and categories.
                Shop more, discover more, and enjoy BoomBuy.
            </p>

        </div>

        <a href="/products" class="promo-btn">
            Shop sale →
        </a>

    </section>


    <!-- ABOUT -->

    <section class="about" id="about">

        <small>
            About BoomBuy
        </small>

        <h2>
            Your Marketplace for Everything.
        </h2>

        <p>
            BoomBuy is a multi-vendor online marketplace designed
            to make shopping easier by bringing
            electronics, fashion, home essentials, beauty, sports, food, and
            many more products together
            in one simple shopping experience for buyers and sellers.
        </p>

    </section>


    <!-- FOOTER -->

    <footer>

        <div>
            © 2026 BoomBuy
        </div>

        <div>
            Your Marketplace for Everything.
        </div>

    </footer>

</body>
</html>