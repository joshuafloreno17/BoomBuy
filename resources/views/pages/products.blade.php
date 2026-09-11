<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy - Products</title>

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        .add.added {
    background: #16a34a;
    pointer-events: none;
}


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
            margin-left: 5px;
            background: #e8420f;
            color: #ffffff;
            border-radius: 20px;
            font-size: 11px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            width: 86%;
            margin: 55px auto 30px;
        }

        .page-header small {
            color: #db5a33;
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
            color: #977970;
            font-size: 15px;
            line-height: 1.6;
        }

        /* =========================
           SORT BAR
        ========================= */

        .filter-bar {
            width: 86%;
            margin: 0 auto 35px;
            background: #ffffff;
            border: 1px solid #f9e5df;
            border-radius: 14px;
            padding: 15px 18px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .sort {
            border: 1px solid #fbe2db;
            padding: 9px 12px;
            border-radius: 8px;
            color: #7c5a50;
            background: #ffffff;
            outline: none;
            cursor: pointer;
            font-family: inherit;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            width: 86%;
            margin: 0 auto 80px;
        }

        .results {
            color: #977970;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .product-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.25s;
            cursor: default;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(39, 84, 150, 0.12);
            border-color: #fad3c7;
        }

        /* =========================
           PRODUCT IMAGE
        ========================= */

        .product-image {
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-image::after {
            content: "BOOMBUY";
            position: absolute;
            bottom: 13px;
            right: 15px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(23, 105, 224, 0.25);
            pointer-events: none;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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

        .lavender {
            background: linear-gradient(145deg, #f3edff, #e9ddff);
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

        /* =========================
           PRODUCT INFO
        ========================= */

        .product-info {
            padding: 19px;
        }

        .category {
            color: #e47452;
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
            color: #9a817a;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .rating {
            color: #977970;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .rating span {
            color: #f5b70b;
        }

        .bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .price {
            color: #e8420f;
            font-size: 17px;
            font-weight: 700;
        }

        .add {
            background: #e8420f;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.2s;
            white-space: nowrap;
        }

        .add:hover {
            background: #c43408;
        }

        .add:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            display: none;
            text-align: center;
            padding: 60px 20px;
            color: #977970;
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
           ABOUT
        ========================= */

        .about-section {
            width: 86%;
            margin: 20px auto 80px;
            padding: 60px;
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            text-align: center;
            scroll-margin-top: 100px;
        }

        .about-content {
            max-width: 750px;
            margin: auto;
        }

        .about-content small {
            color: #e8420f;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .about-content h2 {
            font-size: 32px;
            margin: 12px 0 18px;
        }

        .about-content p {
            color: #977970;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 12px;
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
           BOOMBUY DESIGN SYSTEM
        ========================= */

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
            transition: transform 0.15s ease,
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
            .filter-bar,
            .about-section {
                width: 92%;
            }

            .page-header h1 {
                font-size: 35px;
            }

            .filter-bar {
                justify-content: stretch;
            }

            .sort {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .about-section {
                padding: 40px 25px;
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

    <a href="{{ route('products') }}" class="active">
        Shop
    </a>

    <a href="#about">
        About
    </a>
</div>

        <div class="nav-right">

            <input
                type="text"
                class="search"
                id="searchInput"
                placeholder="Search anything..."
            >

            <a href="{{ route('cart') }}" class="cart">

                🛒 Cart

                @php
                    $cartCount = array_sum(session('cart', []));
                @endphp

                @if($cartCount > 0)

                    <span class="cart-badge" id="cartCount">
                        {{ $cartCount }}
                    </span>

                @else

                    <span
                        class="cart-badge"
                        id="cartCount"
                        style="display:none;"
                    >
                        0
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
            Shop everything you need.
        </h1>

        <p>
            Discover products from different categories and sellers,
            all in one convenient marketplace.
        </p>

    </section>


    <!-- =========================
         SORT BAR
    ========================= -->

    <div class="filter-bar">

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

            <option value="rating">
                Rating: Highest
            </option>

        </select>

    </div>


    <!-- =========================
         PRODUCTS
    ========================= -->

    <section class="products">

        <div class="results" id="results">
            Showing {{ count($products) }} products
        </div>

        <div class="product-grid" id="productGrid">

            @php

                $backgrounds = [
                    'blue',
                    'purple',
                    'cyan',
                    'lavender',
                    'pink',
                    'green',
                    'orange',
                    'yellow',
                ];

            @endphp


            @foreach($products as $index => $product)

                @php

                    $name =
                        $product['name']
                        ?? 'Unnamed Product';

                    $slug =
                        $product['slug']
                        ?? \Illuminate\Support\Str::slug($name);

                    $price =
                        (float) ($product['price'] ?? 0);

                    $description =
                        $product['description']
                        ?? 'No description available.';

                    $image =
                        $product['image']
                        ?? '📦';

                    $stock =
                        (int) ($product['stock'] ?? 0);

                    $rawCategory =
                        strtolower(
                            $product['category']
                            ?? 'other'
                        );


                    if (
                        str_contains(
                            $rawCategory,
                            'smartphone'
                        ) ||
                        str_contains(
                            $rawCategory,
                            'laptop'
                        ) ||
                        str_contains(
                            $rawCategory,
                            'audio'
                        ) ||
                        str_contains(
                            $rawCategory,
                            'wearable'
                        ) ||
                        str_contains(
                            $rawCategory,
                            'electronic'
                        )
                    ) {

                        $filterCategory =
                            'electronics';

                        $displayCategory =
                            'Electronics & Gadgets';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'women'
                        )
                    ) {

                        $filterCategory =
                            'women';

                        $displayCategory =
                            "Women's Apparel";

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'men'
                        )
                    ) {

                        $filterCategory =
                            'men';

                        $displayCategory =
                            "Men's Apparel";

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'kid'
                        )
                    ) {

                        $filterCategory =
                            'kids';

                        $displayCategory =
                            'Kids & Baby';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'home'
                        )
                    ) {

                        $filterCategory =
                            'home';

                        $displayCategory =
                            'Home & Garden';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'sport'
                        )
                    ) {

                        $filterCategory =
                            'sports';

                        $displayCategory =
                            'Sports & Outdoors';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'beauty'
                        )
                    ) {

                        $filterCategory =
                            'beauty';

                        $displayCategory =
                            'Health & Beauty';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'food'
                        )
                    ) {

                        $filterCategory =
                            'food';

                        $displayCategory =
                            'Food & Gourmet';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'auto'
                        )
                    ) {

                        $filterCategory =
                            'automotive';

                        $displayCategory =
                            'Automotive & Motorcycle';

                    } elseif (
                        str_contains(
                            $rawCategory,
                            'office'
                        )
                    ) {

                        $filterCategory =
                            'office';

                        $displayCategory =
                            'Office & School Supplies';

                    } else {

                        $filterCategory =
                            'electronics';

                        $displayCategory =
                            ucfirst($rawCategory);

                    }

                    $background =
                        $backgrounds[
                            $index % count($backgrounds)
                        ];

                @endphp


                <div
    class="product-card"
    data-category="{{ $filterCategory }}"
    data-price="{{ $price }}"
    data-rating="{{ (float) ($product['rating'] ?? 0) }}"
    data-url="/product-details/{{ $slug }}"
    data-product-id="{{ $product['id'] ?? '' }}"
>


                    <!-- PRODUCT IMAGE -->

                    <div class="product-image {{ $background }}">

                        @if(
                            $image &&
                            $image !== '📦'
                        )

                            <img
                                src="{{ str_starts_with($image, 'http')
                                    ? $image
                                    : asset('storage/' . ltrim($image, '/')) }}"
                                alt="{{ $name }}"
                            >

                        @else

                            <div
                                style="
                                    width: 100%;
                                    height: 100%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 82px;
                                "
                            >
                                📦
                            </div>

                        @endif

                    </div>


                    <!-- PRODUCT INFO -->

                    <div class="product-info">

                        <div class="category">
                            {{ $displayCategory }}
                        </div>

                        <div class="product-name">
                            {{ $name }}
                        </div>

                        <div class="description">
                            {{ $description }}
                        </div>
<div class="rating">
    <span>★</span>
    {{ number_format((float) ($product['rating'] ?? 0), 1) }}
    ·
    {{ (int) ($product['reviews'] ?? 0) }}
    {{ (int) ($product['reviews'] ?? 0) === 1 ? 'review' : 'reviews' }}
</div>

                        <div class="bottom">

                            <div class="price">
                                ₱{{ number_format($price, 2) }}
                            </div>

                    @if($stock > 0)
    <form
        action="{{ route('cart.add', $product['id']) }}"
        method="POST"
        class="add-to-cart-form"
    >
        @csrf

        <button
            type="submit"
            class="add"
        >
            Add to cart
        </button>
    </form>
@else
    <button
        type="button"
        class="add"
        disabled
        title="Out of stock"
    >
        Out of stock
    </button>
@endif
                        </div>

                    </div>

                </div>

            @endforeach

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
                Try another search.
            </p>

        </div>

    </section>


    <!-- =========================
         ABOUT SECTION
    ========================= -->

    <section
        id="about"
        class="about-section"
    >

        <div class="about-content">

            <small>
                ABOUT BOOMBUY
            </small>

            <h2>
                Your Marketplace for Everything
            </h2>

            <p>
                BoomBuy is an online marketplace where buyers can
                discover products from different categories and sellers
                in one convenient platform.
            </p>

            <p>
                From electronics and fashion to home essentials,
                sports, beauty, food, and more — BoomBuy makes shopping
                simple, convenient, and accessible.
            </p>

        </div>

    </section>


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
         JAVASCRIPT
    ========================= -->

    <script>

        const products =
            Array.from(
                document.querySelectorAll(
                    ".product-card"
                )
            );

        const searchInput =
            document.getElementById(
                "searchInput"
            );

        const sortSelect =
            document.getElementById(
                "sortSelect"
            );

        const results =
            document.getElementById(
                "results"
            );

        const empty =
            document.getElementById(
                "empty"
            );


        /* =========================
           SEARCH + SORT
        ========================= */

        function updateProducts() {

            const search =
                searchInput.value
                    .toLowerCase()
                    .trim();


            let visibleProducts =
                products.filter(product => {

                    const name =
                        product
                            .querySelector(
                                ".product-name"
                            )
                            .textContent
                            .toLowerCase();


                    const categoryText =
                        product
                            .querySelector(
                                ".category"
                            )
                            .textContent
                            .toLowerCase();


                    const description =
                        product
                            .querySelector(
                                ".description"
                            )
                            .textContent
                            .toLowerCase();


                    return (
                        name.includes(search) ||
                        categoryText.includes(search) ||
                        description.includes(search)
                    );

                });


            /* =========================
               SORT
            ========================= */

            const sort =
                sortSelect.value;


            if (sort === "low") {

                visibleProducts.sort(
                    (a, b) =>
                        Number(
                            a.dataset.price
                        ) -
                        Number(
                            b.dataset.price
                        )
                );

            }


            if (sort === "high") {

                visibleProducts.sort(
                    (a, b) =>
                        Number(
                            b.dataset.price
                        ) -
                        Number(
                            a.dataset.price
                        )
                );

            }


            if (sort === "rating") {

                visibleProducts.sort(
                    (a, b) =>
                        Number(
                            b.dataset.rating
                        ) -
                        Number(
                            a.dataset.rating
                        )
                );

            }


            /* =========================
               HIDE ALL PRODUCTS
            ========================= */

            products.forEach(product => {

                product.style.display =
                    "none";

            });


            /* =========================
               SHOW PRODUCTS
            ========================= */

            visibleProducts.forEach(product => {

                product.style.display =
                    "block";

                document
                    .getElementById(
                        "productGrid"
                    )
                    .appendChild(product);

            });


            /* =========================
               RESULT COUNT
            ========================= */

            results.textContent =
                `Showing ${visibleProducts.length} product${
                    visibleProducts.length !== 1
                        ? "s"
                        : ""
                }`;


            /* =========================
               EMPTY
            ========================= */

            empty.style.display =
                visibleProducts.length === 0
                    ? "block"
                    : "none";

        }


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
    .querySelectorAll(".add-to-cart-form")
    .forEach(form => {

        form.addEventListener("submit", async function(event) {

            event.preventDefault();

            const button = form.querySelector(".add");

            if (!button) {
                return;
            }

            const originalText = button.textContent;

            button.disabled = true;
            button.textContent = "Adding...";

            try {

                const response = await fetch(
                    form.action,
                    {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN":
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .getAttribute("content"),

                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: new FormData(form)
                    }
                );

                if (!response.ok) {
                    throw new Error("Failed to add product.");
                }

                button.textContent = "✓ Added!";
                button.classList.add("added");

                /* UPDATE CART BADGE */

                const cartBadge =
                    document.getElementById("cartCount");

                if (cartBadge) {

                    let currentCount =
                        parseInt(
                            cartBadge.textContent
                        ) || 0;

                    currentCount++;

                    cartBadge.textContent =
                        currentCount;

                    cartBadge.style.display =
                        "inline-flex";
                }

                /* RETURN BUTTON TO NORMAL */

                setTimeout(() => {

                    button.textContent =
                        originalText;

                    button.classList.remove("added");

                    button.disabled = false;

                }, 1500);

            } catch (error) {

                console.error(error);

                button.textContent =
                    "Try again";

                button.disabled = false;

                setTimeout(() => {

                    button.textContent =
                        originalText;

                }, 1500);
            }
        });
    });



        /* =========================
           INITIAL LOAD
        ========================= */

        updateProducts();

    </script>

</body>

</html>