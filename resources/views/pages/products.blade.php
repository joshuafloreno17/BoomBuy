<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GizmoMart — Products</title>

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

        .nav-links a.active {
            color: #1769e0;
            font-weight: 600;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .search {
            width: 190px;
            padding: 10px 14px;
            border-radius: 9px;
            border: 1px solid #dce7fa;
            background: #f5f8ff;
            outline: none;
        }

        .search:focus {
            border-color: #4b8df8;
            background: #ffffff;
        }

        .cart {
            color: #1769e0;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            width: 86%;
            margin: 55px auto 30px;
        }

        .page-header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .page-header h1 {
            font-size: 45px;
            letter-spacing: -1.5px;
            margin-top: 10px;
            margin-bottom: 12px;
        }

        .page-header p {
            color: #718096;
            font-size: 15px;
            line-height: 1.6;
        }

        /* =========================
           TOOLBAR
        ========================= */

        .filter-bar {
            width: 86%;
            margin: 0 auto 35px;
            background: #ffffff;
            border: 1px solid #e0e9f8;
            border-radius: 14px;
            padding: 15px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .categories {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .filter {
            border: none;
            background: #f1f6ff;
            color: #3977d5;
            padding: 9px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.2s;
        }

        .filter:hover {
            background: #dceaff;
        }

        .filter.active {
            background: #1769e0;
            color: #ffffff;
        }

        .sort {
            border: 1px solid #dce7fa;
            padding: 9px 12px;
            border-radius: 8px;
            color: #52627a;
            background: #ffffff;
            outline: none;
            cursor: pointer;
        }

        /* =========================
           PRODUCT AREA
        ========================= */

        .products {
            width: 86%;
            margin: 0 auto 80px;
        }

        .results {
            color: #718096;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        /* =========================
           PRODUCT CARD
        ========================= */

        .product-card {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.25s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(39, 84, 150, 0.12);
            border-color: #c9dcf8;
        }

        .product-image {
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 82px;
            position: relative;
        }

        .product-image::after {
            content: "GIZMOMART";
            position: absolute;
            bottom: 13px;
            right: 15px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(23, 105, 224, 0.25);
        }

        .blue {
            background: linear-gradient(145deg, #e8f2ff, #d5e8ff);
        }

        .purple {
            background: linear-gradient(145deg, #f0efff, #e1e3ff);
        }

        .cyan {
            background: linear-gradient(145deg, #e7fbff, #d5f4ff);
        }

        .lavender {
            background: linear-gradient(145deg, #f3edff, #e9ddff);
        }

        .product-info {
            padding: 19px;
        }

        .category {
            color: #5790df;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .product-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .description {
            color: #7b8799;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .rating {
            color: #718096;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .rating span {
            color: #f59e0b;
        }

        .bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .price {
            color: #1769e0;
            font-size: 17px;
            font-weight: 700;
        }

        .add {
            background: #1769e0;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .add:hover {
            background: #0f55bd;
            transform: translateY(-1px);
        }

        /* =========================
           EMPTY RESULT
        ========================= */

        .empty {
            display: none;
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            color: #172033;
            margin-bottom: 8px;
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

        @media (max-width: 1100px) {

            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {

            .nav-links {
                display: none;
            }

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

            .page-header,
            .products,
            .filter-bar {
                width: 92%;
            }

            .page-header h1 {
                font-size: 35px;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .categories {
                flex-wrap: wrap;
            }

            .sort {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: 1fr;
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

            <a href="/">
                Home
            </a>

            <a href="/products" class="active">
                Shop
            </a>

            <a href="/categories">
                Categories
            </a>

            <a href="/#about">
                About
            </a>

        </div>

        <div class="nav-right">

            <input
                type="text"
                class="search"
                id="searchInput"
                placeholder="Search products..."
            >

            <a href="#" class="cart">
                🛒 Cart (<span id="cartCount">0</span>)
            </a>

        </div>

    </nav>


    <!-- PAGE HEADER -->

    <section class="page-header">

        <small>
            GizmoMart Collection
        </small>

        <h1>
            Find your next gadget.
        </h1>

        <p>
            Browse our collection of technology made for
            work, study, entertainment and everyday life.
        </p>

    </section>


    <!-- FILTER BAR -->

    <div class="filter-bar">

        <div class="categories">

            <button class="filter active" data-category="all">
                All
            </button>

            <button class="filter" data-category="smartphone">
                Smartphones
            </button>

            <button class="filter" data-category="laptop">
                Laptops
            </button>

            <button class="filter" data-category="audio">
                Audio
            </button>

            <button class="filter" data-category="wearable">
                Wearables
            </button>

            <button class="filter" data-category="accessories">
                Accessories
            </button>

        </div>

        <select class="sort" id="sortSelect">

            <option value="default">
                Sort by
            </option>

            <option value="low">
                Price: Low to High
            </option>

            <option value="high">
                Price: High to Low
            </option>

        </select>

    </div>


    <!-- PRODUCTS -->

    <section class="products">

        <div class="results" id="results">
            Showing 8 products
        </div>

        <div class="product-grid" id="productGrid">


            <!-- PRODUCT 1 -->

            <div class="product-card"
                 data-category="smartphone"
                 data-price="18999"
                   data-url="/product-details/nova-x5-pro">

                <div class="product-image blue">
                    📱
                </div>

                <div class="product-info">

                    <div class="category">
                        Smartphone
                    </div>

                    <div class="product-name">
                        Nova X5 Pro
                    </div>

                    <div class="description">
                        Powerful smartphone with a vibrant display
                        and long-lasting battery.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.8 · 124 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱18,999
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 2 -->

            <div class="product-card"
                 data-category="laptop"
                 data-price="34990"
                     data-url="/product-details/airbook-14">

                <div class="product-image purple">
                    💻
                </div>

                <div class="product-info">

                    <div class="category">
                        Laptop
                    </div>

                    <div class="product-name">
                        AirBook 14
                    </div>

                    <div class="description">
                        Lightweight laptop designed for work,
                        school and everyday use.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.7 · 89 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱34,990
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 3 -->

            <div class="product-card"
                 data-category="audio"
                 data-price="2799"
                      data-url="/product-details/soundcore-pro">

                <div class="product-image cyan">
                    🎧
                </div>

                <div class="product-info">

                    <div class="category">
                        Audio
                    </div>

                    <div class="product-name">
                        SoundCore Pro
                    </div>

                    <div class="description">
                        Wireless headphones with clear sound
                        and comfortable design.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.9 · 216 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱2,799
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 4 -->

            <div class="product-card"
                 data-category="wearable"
                 data-price="3499"
                 data-url="/product-details/fitwatch-s2">

                <div class="product-image lavender">
                    ⌚
                </div>

                <div class="product-info">

                    <div class="category">
                        Wearable
                    </div>

                    <div class="product-name">
                        FitWatch S2
                    </div>

                    <div class="description">
                        Smart wearable with fitness tracking
                        and everyday features.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.6 · 73 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱3,499
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 5 -->

            <div class="product-card"
                 data-category="accessories"
                 data-price="2199"
                 data-url="/product-details/gamepad-x">

                <div class="product-image cyan">
                    🎮
                </div>

                <div class="product-info">

                    <div class="category">
                        Accessories
                    </div>

                    <div class="product-name">
                        GamePad X
                    </div>

                    <div class="description">
                        Comfortable wireless controller
                        for your gaming setup.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.8 · 61 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱2,199
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 6 -->

            <div class="product-card"
                 data-category="accessories"
                 data-price="3299"
                 data-url="/product-details/mechakeys-75">

                <div class="product-image blue">
                    ⌨️
                </div>

                <div class="product-info">

                    <div class="category">
                        Accessories
                    </div>

                    <div class="product-name">
                        MechaKeys 75
                    </div>

                    <div class="description">
                        Compact mechanical keyboard built
                        for productivity and gaming.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.7 · 95 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱3,299
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 7 -->

            <div class="product-card"
                 data-category="accessories"
                 data-price="1499"
                 data-url="/product-details/glide-mouse-x">

                <div class="product-image purple">
                    🖱️
                </div>

                <div class="product-info">

                    <div class="category">
                        Accessories
                    </div>

                    <div class="product-name">
                        Glide Mouse X
                    </div>

                    <div class="description">
                        Lightweight wireless mouse with
                        a precise sensor.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.6 · 54 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱1,499
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


            <!-- PRODUCT 8 -->

            <div class="product-card"
                 data-category="audio"
                 data-price="1899"
                 data-url="/product-details/minisound-go">

                <div class="product-image lavender">
                    🔊
                </div>

                <div class="product-info">

                    <div class="category">
                        Audio
                    </div>

                    <div class="product-name">
                        MiniSound Go
                    </div>

                    <div class="description">
                        Portable Bluetooth speaker for
                        music anywhere.
                    </div>

                    <div class="rating">
                        <span>★</span> 4.7 · 108 reviews
                    </div>

                    <div class="bottom">

                        <div class="price">
                            ₱1,899
                        </div>

                        <button class="add">
                            Add to cart
                        </button>

                    </div>

                </div>

            </div>


        </div>


        <!-- EMPTY RESULT -->

        <div class="empty" id="empty">

            <div class="empty-icon">
                🔎
            </div>

            <h2>
                No products found
            </h2>

            <p>
                Try another search or category.
            </p>

        </div>

    </section>


    <!-- FOOTER -->

    <footer>

        <div>
            © 2026 GizmoMart
        </div>

        <div>
            Quality tech. Better everyday.
        </div>

    </footer>


    <!-- JAVASCRIPT -->

    <script>

        const filters =
            document.querySelectorAll(".filter");

        const products =
            Array.from(
                document.querySelectorAll(".product-card")
            );

        const searchInput =
            document.getElementById("searchInput");

        const sortSelect =
            document.getElementById("sortSelect");

        const results =
            document.getElementById("results");

        const empty =
            document.getElementById("empty");

        const cartCount =
            document.getElementById("cartCount");

        let selectedCategory = "all";

        let cart = 0;


        /* =========================
           FILTER + SEARCH + SORT
        ========================= */

        function updateProducts() {

            const search =
                searchInput.value
                    .toLowerCase()
                    .trim();

            let visibleProducts =
                products.filter(product => {

                    const category =
                        product.dataset.category;

                    const name =
                        product.querySelector(
                            ".product-name"
                        ).textContent.toLowerCase();

                    const categoryText =
                        product.querySelector(
                            ".category"
                        ).textContent.toLowerCase();

                    const matchesCategory =
                        selectedCategory === "all" ||
                        category === selectedCategory;

                    const matchesSearch =
                        name.includes(search) ||
                        categoryText.includes(search);

                    return matchesCategory &&
                           matchesSearch;

                });


            const sort =
                sortSelect.value;


            if (sort === "low") {

                visibleProducts.sort(
                    (a, b) =>
                        Number(a.dataset.price) -
                        Number(b.dataset.price)
                );

            }


            if (sort === "high") {

                visibleProducts.sort(
                    (a, b) =>
                        Number(b.dataset.price) -
                        Number(a.dataset.price)
                );

            }


            products.forEach(product => {

                product.style.display = "none";

            });


            visibleProducts.forEach(product => {

                product.style.display = "block";

                document
                    .getElementById("productGrid")
                    .appendChild(product);

            });


            results.textContent =
                `Showing ${visibleProducts.length} product${visibleProducts.length !== 1 ? "s" : ""}`;


            if (visibleProducts.length === 0) {

                empty.style.display = "block";

            } else {

                empty.style.display = "none";

            }

        }


        /* =========================
           CATEGORY FILTER
        ========================= */

        filters.forEach(filter => {

            filter.addEventListener(
                "click",
                function() {

                    filters.forEach(btn => {

                        btn.classList.remove(
                            "active"
                        );

                    });


                    this.classList.add(
                        "active"
                    );


                    selectedCategory =
                        this.dataset.category;


                    updateProducts();

                }
            );

        });


        /* =========================
           SEARCH
        ========================= */

        searchInput.addEventListener(
            "input",
            updateProducts
        );


        /* =========================
           SORT
        ========================= */

        sortSelect.addEventListener(
            "change",
            updateProducts
        );


        /* =========================
           ADD TO CART
        ========================= */

        document
            .querySelectorAll(".add")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function(event) {

                        event.stopPropagation();

                        cart++;

                        cartCount.textContent =
                            cart;


                        const original =
                            this.textContent;


                        this.textContent =
                            "Added ✓";


                        this.style.background =
                            "#16a34a";


                        setTimeout(() => {

                            this.textContent =
                                original;

                            this.style.background =
                                "#1769e0";

                        }, 1000);

                    }
                );

            });


        /* =========================
           PRODUCT DETAILS
        ========================= */

        document
            .querySelectorAll(".product-card")
            .forEach(card => {

                card.addEventListener(
                    "click",
                    function() {

                        window.location.href =
                            this.dataset.url;

                    }
                );

            });

    </script>

</body>
</html>