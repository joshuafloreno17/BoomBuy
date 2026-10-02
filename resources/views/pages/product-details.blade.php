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

    @include('partials.pwa-head')
    @include('partials.design-tokens')

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
            /* Fractions, not 45% + 55% — those plus the gap overflowed the card. */
            grid-template-columns: minmax(0, 45fr) minmax(0, 55fr);

            gap: 40px;
        }

        /* PRODUCT VISUAL */

        .product-visual {
            position: relative;

            min-height: 420px;

            border-radius: 16px;
            overflow: hidden;

            background:
                {{ $product['background'] ?? 'linear-gradient(145deg, #ffede8, #ffdfd5)' }};

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 130px;

            touch-action: pan-y;
            user-select: none;
        }

        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);

            width: 38px;
            height: 38px;

            border: none;
            border-radius: 50%;

            background: rgba(255, 255, 255, 0.85);
            color: #172033;

            font-size: 20px;
            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;
            z-index: 2;

            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .gallery-nav:hover {
            background: #ffffff;
        }

        .gallery-nav.prev { left: 12px; }
        .gallery-nav.next { right: 12px; }

        .variation-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;

            margin-top: 12px;
        }

        .variation-swatch {
            width: 52px;
            height: 52px;

            border: 2px solid #f0ddd5;
            border-radius: 10px;
            overflow: hidden;

            background: #fff7f4;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0;
            cursor: pointer;

            transition: border-color 0.15s ease;
        }

        .variation-swatch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .variation-swatch .swatch-fallback {
            font-size: 10px;
            font-weight: 700;
            color: #977970;
            text-align: center;
            padding: 2px;
            overflow-wrap: anywhere;
        }

        .variation-swatch.active {
            border-color: #e8420f;
        }

        .variation-info {
            margin-top: 10px;

            font-size: 13px;
            font-weight: 600;
            color: #6f5d58;
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

        /* Heart · Add to cart · Buy now (each sits in its own form). */
        .buttons {
            display: grid;
            grid-template-columns: 46px 1fr 1fr;
            align-items: stretch;
            gap: 10px;
        }

        .buttons > form {
            display: flex;
            min-width: 0;
        }

        .wishlist-btn {
            width: 100%;
            min-height: 46px;

            border: 1px solid #f3ddd6;
            background: white;
            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .wishlist-btn svg {
            width: 19px;
            height: 19px;
        }

        .wishlist-btn:hover {
            border-color: #f1c7ba;
            background: #fff6f3;
        }

        .wishlist-btn.active {
            border-color: #f4b3a3;
            background: #fff1ed;
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

        /* REVIEWS */

        .reviews-section {
            margin-top: 35px;
        }

        .reviews-section h2 {
            font-size: 21px;
            margin-bottom: 15px;
        }

        .no-reviews {
            background: #fffaf8;
            border: 1px dashed #f0ddd6;
            border-radius: 12px;

            padding: 26px;

            text-align: center;

            color: #977970;
            font-size: 13px;
        }

        .review-card {
            background: #fffaf8;
            border: 1px solid #f8e7e2;
            border-radius: 12px;

            padding: 16px 18px;

            margin-bottom: 12px;
        }

        .review-card:last-child {
            margin-bottom: 0;
        }

        .review-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            margin-bottom: 6px;
        }

        .review-name {
            font-weight: 700;
            font-size: 13px;
            color: #33241f;
        }

        .review-stars {
            color: var(--gold);
            font-size: 12px;
            white-space: nowrap;
        }

        .review-date {
            color: #b99c93;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .review-text {
            color: #563a32;
            font-size: 13px;
            line-height: 1.6;
        }

        /* RELATED PRODUCTS */

        .related-section {
            margin-top: 40px;
        }

        .related-section h2 {
            font-size: 21px;
            margin-bottom: 15px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .related-card {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 14px;

            overflow: hidden;

            transition: 0.2s ease;
        }

        .related-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(72, 45, 35, 0.09);
            border-color: #f1c7ba;
        }

        .related-image {
            height: 130px;

            background: linear-gradient(145deg, #ffede8, #ffdfd5);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 46px;
            color: var(--accent);

            overflow: hidden;
        }

        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-info {
            padding: 12px 14px;
        }

        .related-name {
            font-size: 13px;
            font-weight: 700;
            color: #2e211d;

            margin-bottom: 6px;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-price {
            color: #e8420f;
            font-size: 13px;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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

            .product-detail {
                grid-template-columns: 1fr;

                padding: 22px;
            }

            .product-visual {
                min-height: 280px;

                font-size: 90px;
            }

            .variation-swatch {
                width: 44px;
                height: 44px;
            }

            .product-name {
                font-size: 28px;
            }

            /* Heart + Add to cart on one row, Buy now full width below. */
            .buttons {
                grid-template-columns: 46px 1fr;
            }

            .buttons > form:last-child {
                grid-column: 1 / -1;
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

        /* =========================
           BREADCRUMB
        ========================= */

        .pd-crumbs {
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .pd-crumbs a {
            color: #6f5a53;
        }

        .pd-crumbs a:hover {
            color: #c43408;
        }

        .pd-crumbs span {
            color: #172033;
        }

        /* =========================
           VARIATION PICKER (next to price)
        ========================= */

        .variation-picker {
            margin-bottom: 18px;
        }

        .variation-picker .variation-swatches {
            margin-top: 8px;
            gap: 8px;
        }

        /* Text options become pills; photo options stay square. */
        .variation-picker .variation-swatch:not(:has(img)) {
            width: auto;
            min-width: 52px;
            height: 42px;
            padding: 0 16px;
            border-radius: 12px;
            background: #fff;
        }

        .variation-picker .variation-swatch .swatch-fallback {
            font-size: 13px;
            color: #172033;
            padding: 0;
        }

        .variation-picker .variation-swatch.active:not(:has(img)) {
            border-color: #172033;
            background: #172033;
        }

        .variation-picker .variation-swatch.active .swatch-fallback {
            color: #fff;
        }

        /* =========================
           DELIVERY / RETURNS
        ========================= */

        .pd-perks {
            list-style: none;
            margin: 20px 0 0;
            padding: 14px 16px;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            background: #fffaf8;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pd-perks li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            line-height: 1.5;
            color: #6f5a53;
        }

        .pd-perks i {
            margin-top: 1px;
            font-size: 16px;
            color: #c43408;
        }

        .pd-perks strong {
            color: #172033;
        }

        /* =========================
           SELLER CARD
        ========================= */

        .pd-seller {
            margin-top: 14px;
            padding: 14px 16px;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pd-seller-avatar {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 50%;
            overflow: hidden;
            background: #e8420f;
            color: #fff;
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pd-seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pd-seller-info {
            flex: 1;
            min-width: 140px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .pd-seller-name {
            font-size: 15px;
            font-weight: 800;
            color: #172033;
        }

        .pd-seller-name:hover {
            color: #c43408;
        }

        .pd-seller-meta {
            font-size: 12px;
            font-weight: 600;
            color: #6f5a53;
        }

        .pd-seller-meta .bi-star-fill {
            color: #f5b70b;
        }

        .pd-seller-actions {
            display: flex;
            gap: 8px;
        }

        .pd-seller-btn {
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid #f0d9d1;
            border-radius: 10px;
            background: #fff;
            color: #172033;
            font-size: 12.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pd-seller-btn:hover {
            background: #fff7f4;
        }

        .pd-seller-btn.is-dark {
            border-color: #172033;
            background: #172033;
            color: #fff;
        }

        .pd-seller-btn.is-dark:hover {
            background: #2a3550;
        }

        /* =========================
           REVIEW SUMMARY
        ========================= */

        .rv-summary {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 24px;
            align-items: center;
            margin-bottom: 16px;
            padding: 18px 20px;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            background: #fff;
        }

        .rv-average {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .rv-average strong {
            font-family: var(--font-display);
            font-size: 44px;
            font-weight: 800;
            line-height: 1;
        }

        .rv-stars {
            color: #f5b70b;
            font-size: 15px;
            letter-spacing: 2px;
        }

        .rv-count {
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .rv-bars {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .rv-bar {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) 32px;
            align-items: center;
            gap: 10px;
            min-height: 28px;
            padding: 0 6px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            color: #172033;
            cursor: pointer;
        }

        .rv-bar:hover:not(:disabled),
        .rv-bar[aria-pressed="true"] {
            background: #fff4f0;
        }

        .rv-bar:disabled {
            cursor: default;
            opacity: 0.5;
        }

        .rv-bar-label i {
            color: #f5b70b;
            font-size: 11px;
        }

        .rv-bar-track {
            height: 8px;
            border-radius: 999px;
            background: #f3e6e1;
            overflow: hidden;
        }

        .rv-bar-track span {
            display: block;
            height: 100%;
            background: #f5b70b;
        }

        .rv-bar-count {
            text-align: right;
            color: #6f5a53;
        }

        .rv-filter-note[hidden],
        .review-card[hidden] {
            display: none;
        }

        .rv-filter-note {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #6f5a53;
        }

        .rv-filter-note button {
            border: none;
            background: none;
            color: #c43408;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        /* =========================
           PRODUCT RAILS
        ========================= */

        .related-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
        }

        .related-head a {
            font-size: 13.5px;
            font-weight: 700;
            color: #c43408;
        }

        @media (max-width: 640px) {
            .rv-summary {
                grid-template-columns: minmax(0, 1fr);
                gap: 14px;
            }

            .pd-seller-actions {
                width: 100%;
            }

            .pd-seller-btn {
                flex: 1;
                justify-content: center;
            }
        }
</style>

</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'shop'])


<!-- MAIN -->

<main class="container">

    @php $pdCategorySlug = \App\Support\Categories::slug($product->category); @endphp
    <nav class="back-link pd-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('products') }}">Shop</a>
        @if($pdCategorySlug)
            / <a href="{{ route('products', ['category' => $pdCategorySlug]) }}">{{ \App\Support\Categories::LIST[$pdCategorySlug] }}</a>
        @endif
        / <span>{{ $product->name }}</span>
    </nav>


    <section class="product-detail">


        <!-- PRODUCT IMAGE / ICON -->

  {{-- PRODUCT IMAGE --}}
<div>

    @php
        $productImage = $product['image'] ?? null;

        $productImageUrl = $productImage
            ? (str_starts_with($productImage, 'http')
                ? $productImage
                : asset('storage/' . ltrim($productImage, '/')))
            : null;
    @endphp

    <div class="product-visual" id="mainImageContainer">

        @if($variations->count() > 1)
            <button type="button" class="gallery-nav prev" onclick="galleryStep(-1)" aria-label="Previous option">‹</button>
            <button type="button" class="gallery-nav next" onclick="galleryStep(1)" aria-label="Next option">›</button>
        @endif

        <img
            id="mainProductImage"
            @if($productImageUrl) src="{{ $productImageUrl }}" @endif
            data-fallback-src="{{ $productImageUrl }}"
            alt="{{ $product['name'] ?? 'Product' }}"
            style="
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: none;
            "
            onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
        >

        <div id="mainImageFallback" style="
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 120px;
            color: var(--accent);
        ">
            <i class="bi bi-box-seam-fill"></i>
        </div>

    </div>


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

                @if($reviewCount > 0)

                    <i class="bi bi-star-fill" style="color: var(--gold);"></i> {{ $averageRating }}

                    <span style="color:#977970;">
                        ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})
                    </span>

                @else

                    <span style="color:#977970;">
                        No reviews yet
                    </span>

                @endif

            </div>


            <p class="description">

                {{ $product['description'] ?? 'No product description available.' }}

            </p>


            <div class="price">

                ₱{{ number_format($product['price'] ?? 0) }}

            </div>


            <!-- VARIATIONS: next to the price and quantity, before the buttons -->
            @if($variations->count() > 0)
                <div class="variation-picker">
                    <span class="quantity-label">Choose a {{ strtolower($variations->first()->variation_type) }}</span>

                <div class="variation-swatches" id="variationSwatches">

                    @foreach($variations as $index => $variation)

                        @php
                            $variationImageUrl = $variation->image
                                ? asset('storage/' . ltrim($variation->image, '/'))
                                : '';
                        @endphp

                        <button
                            type="button"
                            class="variation-swatch {{ $index === 0 ? 'active' : '' }}"
                            data-index="{{ $index }}"
                            data-id="{{ $variation->id }}"
                            data-adjustment="{{ $variation->price_adjustment }}"
                            data-stock="{{ $variation->stock }}"
                            data-label="{{ $variation->variation_type }}: {{ $variation->variation_value }}"
                            data-image="{{ $variationImageUrl }}"
                            onclick="selectVariation({{ $index }})"
                            title="{{ $variation->variation_type }}: {{ $variation->variation_value }}"
                        >
                            @if($variationImageUrl)
                                <img src="{{ $variationImageUrl }}" alt="{{ $variation->variation_value }}">
                            @else
                                <span class="swatch-fallback">{{ $variation->variation_value }}</span>
                            @endif
                        </button>

                    @endforeach

                </div>

                <div class="variation-info" id="variationInfo"></div>

                </div>
            @endif


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

                <!-- WISHLIST -->

                <form
                    action="{{ route('wishlist.toggle', $product->id) }}"
                    method="POST"
                    id="wishlistForm"
                >
                    @csrf

                    <button
                        type="submit"
                        class="wishlist-btn {{ $isWishlisted ? 'active' : '' }}"
                        id="wishlistBtn"
                        aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                    >
                        @if($isWishlisted)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#e8420f" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#8d6c62" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        @endif
                    </button>

                </form>


                <!-- ADD TO CART -->

                <form
                    action="{{ route('cart.add', $product->id) }}"
                    method="POST"
                    style="flex:1;"
                    id="addToCartForm"
                >

                    @csrf

                    <input type="hidden" name="quantity" id="cartQuantity" value="1">
                    <input type="hidden" name="variation_id" id="cartVariationId" value="">

                    <button
                        type="submit"
                        class="btn cart-btn"
                        style="width:100%;"
                    >
                        <i class="bi bi-cart-fill"></i> Add to Cart
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

                    <input type="hidden" name="variation_id" id="buyNowVariationId" value="">

                    <button
                        type="submit"
                        class="btn buy-btn"
                        style="width:100%;"
                    >
                        <i class="bi bi-lightning-fill"></i> Buy Now
                    </button>

                </form>


            </div>


            {{-- DELIVERY & RETURNS: what buyers check before they buy --}}
            <ul class="pd-perks">
                <li>
                    <i class="bi bi-truck"></i>
                    <span>
                        @if(($product['price'] ?? 0) >= $freeDeliveryMin)
                            <strong>Free delivery</strong>
                            — this item alone already reaches ₱{{ number_format($freeDeliveryMin) }}
                        @else
                            <strong>Delivery ₱{{ number_format($deliveryFee) }}</strong>
                            — free on orders of ₱{{ number_format($freeDeliveryMin) }} and up from this shop
                        @endif
                    </span>
                </li>
                <li>
                    <i class="bi bi-cash-coin"></i>
                    <span><strong>Cash on Delivery</strong> — pay the rider when your parcel arrives</span>
                </li>
                <li>
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span><strong>7-day returns</strong> — request a return or refund within 7 days of receiving it · <a href="{{ route('policies') }}#returns" style="color:var(--accent);font-weight:700;">Return policy</a></span>
                </li>
            </ul>

            {{-- SELLER --}}
            @if($shop)
                <div class="pd-seller">
                    <a href="{{ $shop['url'] }}" class="pd-seller-avatar" aria-hidden="true" tabindex="-1">
                        @if($shop['photo'])
                            <img src="{{ $shop['photo'] }}" alt="">
                        @else
                            {{ $shop['initial'] }}
                        @endif
                    </a>
                    <div class="pd-seller-info">
                        <a href="{{ $shop['url'] }}" class="pd-seller-name">{{ $shop['name'] }}</a>
                        <span class="pd-seller-meta">
                            @if($shop['rating'])
                                <i class="bi bi-star-fill"></i> {{ $shop['rating'] }} ·
                            @endif
                            {{ $shop['products'] }} {{ \Illuminate\Support\Str::plural('product', $shop['products']) }}
                            @if($shop['location'])
                                · {{ $shop['location'] }}
                            @endif
                        </span>
                    </div>
                    <div class="pd-seller-actions">
                        @if($canMessageSeller)
                            <a href="{{ route('messages.thread', $product->seller_id) }}" class="pd-seller-btn"><i class="bi bi-chat-dots"></i> Chat</a>
                        @endif
                        <a href="{{ $shop['url'] }}" class="pd-seller-btn is-dark">View shop</a>
                    </div>
                </div>
            @endif

        </div>

    </section>


    <!-- REVIEWS -->

    <section class="reviews-section" id="reviews">

        <h2>
            Customer Reviews
            @if($reviewCount > 0)
                ({{ $reviewCount }})
            @endif
        </h2>

        @if($reviewCount > 0)

            {{-- Average + how the ratings are spread, with a filter per star --}}
            <div class="rv-summary">
                <div class="rv-average">
                    <strong>{{ $averageRating }}</strong>
                    <span class="rv-stars" aria-hidden="true">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $averageRating >= $i ? 'bi-star-fill' : ($averageRating >= $i - 0.5 ? 'bi-star-half' : 'bi-star') }}"></i>
                        @endfor
                    </span>
                    <span class="rv-count">{{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }}</span>
                </div>

                <div class="rv-bars">
                    @foreach($ratingCounts as $stars => $count)
                        <button type="button" class="rv-bar" data-review-filter="{{ $stars }}" @disabled($count === 0) aria-pressed="false" aria-label="Show {{ $stars }}-star reviews ({{ $count }})">
                            <span class="rv-bar-label">{{ $stars }} <i class="bi bi-star-fill"></i></span>
                            <span class="rv-bar-track"><span style="width: {{ $reviewCount ? round($count / $reviewCount * 100) : 0 }}%;"></span></span>
                            <span class="rv-bar-count">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rv-filter-note" id="reviewFilterNote" hidden>
                <span id="reviewFilterText"></span>
                <button type="button" data-review-filter="all">Show all reviews</button>
            </div>

            @foreach($reviews as $review)

                <div class="review-card" data-stars="{{ (int) $review->rating }}">

                    <div class="review-top">
                        <span class="review-name">{{ $review->buyer_name }}</span>
                        <span class="review-stars" aria-label="{{ (int) $review->rating }} out of 5 stars">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}</span>
                    </div>

                    <div class="review-date">
                        {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('F d, Y') }}
                    </div>

                    @if(!empty($review->review))
                        <p class="review-text">{{ $review->review }}</p>
                    @endif

                    @if(!empty($review->seller_reply))
                        <div class="seller-reply-box" style="background:#fff7f4; border:1px solid #f4e2dc; border-radius:10px; padding:10px 12px; margin-top:8px;">
                            <div style="font-size:11px; font-weight:800; color:#c43408; margin-bottom:3px;">Seller Reply</div>
                            <div style="font-size:13px; color:#563a32;">{{ $review->seller_reply }}</div>
                        </div>
                    @endif

                </div>

            @endforeach

        @else

            <div class="no-reviews">
                No reviews yet — be the first to review this product after your purchase!
            </div>

        @endif

    </section>


    <!-- MORE FROM THIS SHOP + SIMILAR PRODUCTS -->

    @foreach([
        ['title' => $shop ? 'More from ' . $shop['name'] : null, 'items' => $moreFromSeller, 'link' => $shop['url'] ?? null, 'linkText' => 'View shop'],
        ['title' => 'Similar products', 'items' => $relatedProducts, 'link' => $pdCategorySlug ? route('products', ['category' => $pdCategorySlug]) : null, 'linkText' => 'See all'],
    ] as $rail)

        @if($rail['title'] && $rail['items']->count() > 0)

            <section class="related-section">

                <div class="related-head">
                    <h2>{{ $rail['title'] }}</h2>
                    @if($rail['link'])
                        <a href="{{ $rail['link'] }}">{{ $rail['linkText'] }} →</a>
                    @endif
                </div>

                <div class="related-grid">

                    @foreach($rail['items'] as $related)

                        <a href="{{ route('product.details', \Illuminate\Support\Str::slug($related->name) . '-' . $related->id) }}" class="related-card">

                            <div class="related-image">
                                <x-product-thumb :image="$related->image" :category="$related->category" size="200" style="width:100%; height:100%; border-radius:0;" />
                            </div>

                            <div class="related-info">
                                <div class="related-name">{{ $related->name }}</div>
                                <div class="related-price">₱{{ number_format($related->price) }}</div>
                            </div>

                        </a>

                    @endforeach

                </div>

            </section>

        @endif

    @endforeach

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

        const cartQuantityInput = document.getElementById('cartQuantity');

        if (cartQuantityInput) {
            cartQuantityInput.value = quantity;
        }

    }

    function selectVariation(index) {

        const swatches = Array.prototype.slice.call(
            document.querySelectorAll('.variation-swatch')
        );

        const swatch = swatches[index];

        if (!swatch) {
            return;
        }

        swatches.forEach(function (s) {
            s.classList.toggle('active', s === swatch);
        });

        const cartVariationInput = document.getElementById('cartVariationId');
        const buyNowVariationInput = document.getElementById('buyNowVariationId');

        if (cartVariationInput) cartVariationInput.value = swatch.dataset.id;
        if (buyNowVariationInput) buyNowVariationInput.value = swatch.dataset.id;

        const img = document.getElementById('mainProductImage');
        const fallback = document.getElementById('mainImageFallback');

        const imageUrl = swatch.dataset.image || img.dataset.fallbackSrc || '';

        if (imageUrl) {
            img.src = imageUrl;
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
            fallback.style.display = 'flex';
        }

        const infoEl = document.getElementById('variationInfo');

        if (infoEl) {

            const adjustment = parseFloat(swatch.dataset.adjustment) || 0;

            let text = swatch.dataset.label;

            if (adjustment > 0) {
                text += ' (+₱' + adjustment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')';
            }

            text += ' — ' + swatch.dataset.stock + ' in stock';

            infoEl.textContent = text;
        }

    }

    function galleryStep(direction) {

        const swatches = document.querySelectorAll('.variation-swatch');

        if (swatches.length === 0) {
            return;
        }

        let currentIndex = 0;

        swatches.forEach(function (s, i) {
            if (s.classList.contains('active')) currentIndex = i;
        });

        let nextIndex = (currentIndex + direction + swatches.length) % swatches.length;

        selectVariation(nextIndex);

    }

    // Swipe support on the main image
    (function () {

        const container = document.getElementById('mainImageContainer');

        if (!container) {
            return;
        }

        let touchStartX = null;

        container.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });

        container.addEventListener('touchend', function (e) {

            if (touchStartX === null) {
                return;
            }

            const deltaX = e.changedTouches[0].clientX - touchStartX;

            if (Math.abs(deltaX) > 40) {
                galleryStep(deltaX < 0 ? 1 : -1);
            }

            touchStartX = null;

        }, { passive: true });

    })();

    // Initialize variation selection on page load
    if (document.querySelector('.variation-swatch')) {
        selectVariation(0);
    }


    document
        .getElementById('quantity')
        .addEventListener(
            'input',
            updateBuyNowQuantity
        );

    (function () {
        var form = document.getElementById('wishlistForm');
        var btn = document.getElementById('wishlistBtn');

        if (!form || !btn) return;

        var HEART_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#e8420f" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        var HEART_OUTLINE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#8d6c62" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('not ok');
                    return res.json();
                })
                .then(function (data) {
                    btn.classList.toggle('active', data.in_wishlist);
                    btn.innerHTML = data.in_wishlist ? HEART_FILLED : HEART_OUTLINE;
                    btn.setAttribute(
                        'aria-label',
                        data.in_wishlist ? 'Remove from wishlist' : 'Add to wishlist'
                    );
                })
                .catch(function () {
                    // Likely a guest (redirected to login) — fall back to a normal submit
                    form.submit();
                });
        });
    })();

</script>

    <script>
    (function () {
        // Review filter: click a star bar to see only those reviews.
        var cards = document.querySelectorAll('.review-card[data-stars]');
        var note = document.getElementById('reviewFilterNote');
        if (!cards.length || !note) return;

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-review-filter]');
            if (!btn) return;

            var stars = btn.dataset.reviewFilter;
            var showAll = stars === 'all' || btn.getAttribute('aria-pressed') === 'true';

            document.querySelectorAll('.rv-bar').forEach(function (bar) {
                bar.setAttribute('aria-pressed', !showAll && bar === btn ? 'true' : 'false');
            });

            var shown = 0;
            cards.forEach(function (card) {
                var visible = showAll || card.dataset.stars === stars;
                card.hidden = !visible;
                if (visible) shown++;
            });

            note.hidden = showAll;
            document.getElementById('reviewFilterText').textContent =
                'Showing ' + shown + ' ' + stars + '-star ' + (shown === 1 ? 'review' : 'reviews');
        });
    })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>