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

    <link rel="stylesheet" href="{{ vasset('css/views/product-details.css') }}">

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

                    <span style="color:#6b6570;">
                        ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})
                    </span>

                @else

                    <span style="color:#6b6570;">
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
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#6b6570" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
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
                            <a href="{{ route('messages.thread', [$product->seller_id, 'product' => $product->id]) }}" class="pd-seller-btn"><i class="bi bi-chat-dots"></i> Chat</a>
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
                        <div class="seller-reply-box" style="background:#fff8f3; border:1px solid #f0e2da; border-radius:10px; padding:10px 12px; margin-top:8px;">
                            <div style="font-size:11px; font-weight:800; color:#c2380f; margin-bottom:3px;">Seller Reply</div>
                            <div style="font-size:13px; color:#4a4449;">{{ $review->seller_reply }}</div>
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
        var HEART_OUTLINE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#6b6570" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch(form.getAttribute('action'), {
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

    <script>
    // Opened from a "Buy now" button (?choose=1): bring the color/size choice into view.
    (function () {
        var params = new URLSearchParams(location.search);
        var picker = document.querySelector('.variation-picker');
        if (!params.has('choose') || !picker) return;

        // Keep the address clean for sharing and refreshing.
        params.delete('choose');
        history.replaceState(history.state, '', location.pathname + (params.toString() ? '?' + params : '') + location.hash);

        var label = picker.querySelector('.quantity-label');
        var hint = document.createElement('p');
        hint.className = 'pick-hint';
        hint.innerHTML = '<i class="bi bi-hand-index-thumb"></i><span></span>';
        hint.querySelector('span').textContent = 'Pick the ' + (label ? label.textContent.replace(/^Choose an? /i, '') : 'option')
            + ' you want, then tap Buy Now.';
        picker.appendChild(hint);
        picker.classList.add('is-asking');

        requestAnimationFrame(function () {
            picker.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
    })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>