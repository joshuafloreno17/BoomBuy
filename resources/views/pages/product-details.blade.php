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
            align-items: stretch;
            gap: 10px;
        }

        .wishlist-btn {
            flex: 0 0 46px;

            width: 46px;

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
            color: #e8420f;
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

@include('partials.buyer-navbar', ['activeNav' => 'shop'])


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
                display: none;
            "
            onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
            onerror="this.style.display='none';"
        >

    @endif

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

                    ⭐ {{ $averageRating }}

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


    <!-- REVIEWS -->

    <section class="reviews-section">

        <h2>
            Customer Reviews
            @if($reviewCount > 0)
                ({{ $reviewCount }})
            @endif
        </h2>

        @if($reviewCount > 0)

            @foreach($reviews as $review)

                <div class="review-card">

                    <div class="review-top">
                        <span class="review-name">{{ $review->buyer_name }}</span>
                        <span class="review-stars">{{ str_repeat('⭐', (int) $review->rating) }}</span>
                    </div>

                    <div class="review-date">
                        {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('F d, Y') }}
                    </div>

                    @if(!empty($review->review))
                        <p class="review-text">{{ $review->review }}</p>
                    @endif

                </div>

            @endforeach

        @else

            <div class="no-reviews">
                No reviews yet — be the first to review this product after your purchase!
            </div>

        @endif

    </section>


    <!-- RELATED PRODUCTS -->

    @if($relatedProducts->count() > 0)

        <section class="related-section">

            <h2>
                You Might Also Like
            </h2>

            <div class="related-grid">

                @foreach($relatedProducts as $related)

                    <a href="{{ route('product.details', $related->id) }}" class="related-card">

                        <div class="related-image">
                            @if($related->image)
                                <img
                                    src="{{ str_starts_with($related->image, 'http') ? $related->image : asset('storage/' . ltrim($related->image, '/')) }}"
                                    alt="{{ $related->name }}"
                                    style="display:none;"
                                    onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                    onerror="this.style.display='none';"
                                >
                            @endif
                            <span>📦</span>
                        </div>

                        <div class="related-info">
                            <div class="related-name">{{ $related->name }}</div>
                            <div class="related-price">₱{{ number_format($related->price, 2) }}</div>
                        </div>

                    </a>

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

    @include('partials.pwa-register')

</body>

</html>