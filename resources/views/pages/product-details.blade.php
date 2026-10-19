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

        @if($gallery->count() > 1 || $variations->count() > 1)
            <button type="button" class="gallery-nav prev" onclick="galleryStep(-1)" aria-label="Previous photo">‹</button>
            <button type="button" class="gallery-nav next" onclick="galleryStep(1)" aria-label="Next photo">›</button>
        @endif

        {{-- Phones: share this product (only where the phone can share: the app, mobile browsers). --}}
        <button type="button" class="pd-share" id="pdShare" aria-label="Share this product" hidden><i class="bi bi-share-fill"></i></button>
        <script>
            (function () {
                var btn = document.getElementById('pdShare');
                // The Android app gives pages a BoomBuyShare channel; phone browsers have navigator.share.
                var share = window.BoomBuyShare
                    ? function (data) { BoomBuyShare.postMessage(JSON.stringify(data)); }
                    : (navigator.share ? function (data) { navigator.share(data).catch(function () {}); } : null);
                if (!share) return;
                btn.hidden = false;
                btn.addEventListener('click', function () {
                    share({
                        title: @json($product['name'] ?? 'BoomBuy'),
                        text: @json(($product['name'] ?? 'Check this out') . ' on BoomBuy'),
                        url: location.origin + location.pathname
                    });
                });
            })();
        </script>

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
            onload="this.style.display='block'; var f = document.getElementById('mainImageFallback'); if (f) f.style.display='none';"
            onerror="this.style.display='none'; var f = document.getElementById('mainImageFallback'); if (f) f.style.display='flex';"
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
        {{-- A cached photo can finish loading before the icon above exists: hide the icon then too. --}}
        <script>
            (function () {
                var img = document.getElementById('mainProductImage');
                if (img && img.complete && img.naturalWidth) document.getElementById('mainImageFallback').style.display = 'none';
            })();
        </script>

    </div>

    @if($gallery->count() > 1)
        {{-- Click (or tap) a thumbnail to show it big. --}}
        <div class="pd-thumbs-wrap" id="pdThumbsWrap">
        <button type="button" class="pd-strip-nav prev" data-strip="-1" aria-label="Scroll photos left" hidden>‹</button>
        <div class="pd-thumbs" id="pdThumbs">
            @foreach($gallery as $photo)
                <button
                    type="button"
                    class="pd-thumb {{ $loop->first ? 'is-active' : '' }}"
                    data-src="{{ $photo['url'] }}"
                    data-group="{{ $photo['group'] }}"
                    data-fp="{{ $photo['fp'] }}"
                    aria-label="Show photo {{ $loop->iteration }}"
                >
                    <img src="{{ $photo['url'] }}" alt="" loading="lazy" onerror="this.closest('.pd-thumb').remove()">
                </button>
            @endforeach
        </div>
        <button type="button" class="pd-strip-nav next" data-strip="1" aria-label="Scroll photos right" hidden>›</button>
        </div>
    @endif

    @php
        // The description: its first paragraph is the short version (up top and in the photo viewer).
        $descriptionText = trim((string) ($product['description'] ?? ''));
        $descriptionIntro = $descriptionText !== '' ? preg_split('/\R\s*\R/', $descriptionText)[0] : '';
    @endphp

    {{-- Tap the main photo: every photo big, with ‹ › and a grid of all of them (like a shop app). --}}
    <div class="pd-lb" id="pdLightbox" role="dialog" aria-modal="true" aria-label="Product photos" hidden>
        <div class="pd-lb-box">
            <button type="button" class="pd-lb-close" id="pdLbClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="pd-lb-stage" id="pdLbStage">
                <img id="pdLbImg" alt="{{ $product['name'] ?? 'Product' }}">
                <button type="button" class="pd-lb-nav is-prev" id="pdLbPrev" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="pd-lb-nav is-next" id="pdLbNext" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                <span class="pd-lb-count" id="pdLbCount"></span>
            </div>
            <aside class="pd-lb-side">
                <h3 class="pd-lb-name">{{ $product['name'] ?? 'Product' }}</h3>
                <div class="pd-lb-grid" id="pdLbGrid"></div>
                {{-- A short summary that follows the photo shown (filled in by the viewer script). --}}
                <div class="pd-lb-info">
                    <div class="pd-lb-price" id="pdLbPrice"></div>
                    <dl class="pd-lb-picks" id="pdLbPicks"></dl>
                    @if($descriptionIntro !== '')
                        <p class="pd-lb-intro">{{ $descriptionIntro }}</p>
                    @endif
                    @if($descriptionText !== '')
                        <button type="button" class="pd-lb-more" id="pdLbMore">See full description <i class="bi bi-chevron-down"></i></button>
                    @endif
                </div>
            </aside>
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

                    <span style="color:#645a80;">
                        ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})
                    </span>

                @else

                    <span style="color:#645a80;">
                        No reviews yet
                    </span>

                @endif

            </div>


            {{-- Up top: just the first paragraph; the whole text is in "Product Description" below. --}}
            <p class="description">{{ $descriptionIntro !== '' ? $descriptionIntro : 'No product description available.' }}</p>
            @if($descriptionText !== '')
                <a href="#description" class="pd-desc-more">See full description <i class="bi bi-chevron-down"></i></a>
            @endif


            @php
                // Options can cost more or less: show the range until one is picked.
                $optionPrices = $variations->map(fn ($v) => $product->unitPrice($v));
                $priceLow = $optionPrices->isNotEmpty() ? $optionPrices->min() : $product->unitPrice();
                $priceHigh = $optionPrices->isNotEmpty() ? $optionPrices->max() : $product->unitPrice();
                // A store sale or the product's "% off": the regular prices, crossed out.
                $percentOff = $product->percentOffNow();
                $regularPrices = $variations->map(fn ($v) => $product->regularUnitPrice($v));
                $wasLow = $regularPrices->isNotEmpty() ? $regularPrices->min() : $product->regularUnitPrice();
                $wasHigh = $regularPrices->isNotEmpty() ? $regularPrices->max() : $product->regularUnitPrice();
                $wasExtra = $wasLow - $priceLow;
                $liveSale = $product->liveSale();
                $peso = fn ($amount) => '₱' . number_format($amount, fmod(round($amount, 2), 1) == 0 ? 0 : 2);
            @endphp
            <div class="price">

                <span id="pdPrice" data-base="{{ (float) $product->price }}">{{ $peso($priceLow) }}@if($priceHigh > $priceLow) – {{ $peso($priceHigh) }}@endif</span>

                @if($percentOff)
                    <s class="price-was" id="pdPriceWas" data-extra="{{ $wasExtra }}">{{ $peso($wasLow) }}@if($wasHigh > $wasLow) – {{ $peso($wasHigh) }}@endif</s>
                    <span class="price-off">-{{ $percentOff }}%</span>
                @endif

                @php
                    $liveFlash = $product->liveFlash();
                    $upcomingFlash = $liveFlash ? null : \App\Models\FlashSale::upcomingFor((int) $product->id);
                @endphp

                @if($liveFlash)
                    <span class="price-flash" data-flash-ends="{{ $liveFlash->ends_at->toIso8601String() }}">
                        <i class="bi bi-lightning-charge-fill"></i> Flash sale · ends in <b data-countdown="{{ $liveFlash->ends_at->toIso8601String() }}">{{ gmdate('H:i:s', max(0, now()->diffInSeconds($liveFlash->ends_at, false))) }}</b>
                        · <b data-flash-left>{{ $liveFlash->remaining() }} left</b>
                    </span>
                @elseif($liveSale)
                    <span class="price-sale-ends" data-sale-ends="{{ $liveSale->stopsAt()->toIso8601String() }}">Sale ends {{ $liveSale->stopsAt()->format('M d, g:i A') }}</span>
                @endif

                @if($upcomingFlash)
                    <span class="price-flash is-upcoming"><i class="bi bi-lightning-charge"></i> Upcoming flash sale: {{ peso($upcomingFlash->flash_price) }} at {{ $upcomingFlash->starts_at->format('g:i A') }}</span>
                    @if(session('user') && (session('user')['role'] ?? '') === 'buyer' && \App\Support\FlashReminders::has((int) session('user')['id'], (int) $upcomingFlash->id))
                        <span class="flash-remind is-set"><i class="bi bi-bell-fill"></i> Reminder set</span>
                    @elseif(!session('user') || (session('user')['role'] ?? '') === 'buyer')
                        <form action="{{ route('flash.remind', $upcomingFlash->id) }}" method="POST" class="flash-remind">
                            @csrf
                            <button type="submit" class="btn-soft"><i class="bi bi-bell"></i> Remind me</button>
                        </form>
                    @endif
                @endif

            </div>


            <!-- VARIATIONS: next to the price and quantity, before the buttons -->
            @if($variations->count() > 0)
                @php
                    // Color × Size: one chip row per part; the combination buttons stay hidden below.
                    $twoPart = $variations->contains(fn ($v) => $v->hasSecondOption());

                    // A combination with no photos of its own shows a sibling's with the same first part (Red/M → Red/S).
                    $photoGroupFor = [];
                    foreach ($variations as $v) {
                        if (!empty($variationPhotos[$v->id])) {
                            $photoGroupFor[$v->variation_value] ??= $v->id;
                        }
                    }
                @endphp
                <div class="variation-picker {{ $twoPart ? 'is-two-part' : '' }}">
                    @unless($twoPart)
                        <span class="quantity-label">Choose a {{ $variations->first()->kindLabel() }}</span>
                    @endunless

                @if($twoPart)
                    <div class="pd-part">
                        <small class="pd-part-label">{{ $variations->first()->variation_type }}</small>
                        <div class="variation-swatches">
                            @foreach($variations->pluck('variation_value')->unique() as $value1)
                                @php $chipPhoto = $variationPhotos[$photoGroupFor[$value1] ?? 0] ?? ''; @endphp
                                <button type="button" class="variation-swatch-part pd-part1" data-value="{{ $value1 }}" title="{{ $value1 }}">
                                    @if($chipPhoto)
                                        <img src="{{ $chipPhoto }}" alt="" onerror="this.remove()">
                                    @endif
                                    <span class="swatch-label">{{ $value1 }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="pd-part">
                        <small class="pd-part-label">{{ $variations->first(fn ($v) => $v->hasSecondOption())->option2_type }}</small>
                        <div class="variation-swatches">
                            @foreach($variations->pluck('option2_value')->filter()->unique() as $value2)
                                <button type="button" class="variation-swatch-part pd-part2" data-value="{{ $value2 }}" title="{{ $value2 }}">
                                    <span class="swatch-label">{{ $value2 }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="variation-swatches" id="variationSwatches" @if($twoPart) hidden @endif>

                    @foreach($variations as $index => $variation)

                        @php
                            // Same picture as a gallery photo? Use that one, so its thumbnail lights up.
                            $photoGroup = !empty($variationPhotos[$variation->id]) ? $variation->id : ($photoGroupFor[$variation->variation_value] ?? $variation->id);
                            $variationImageUrl = $variationPhotos[$photoGroup] ?? '';
                        @endphp

                        <button
                            type="button"
                            class="variation-swatch {{ $index === 0 ? 'active' : '' }}"
                            data-index="{{ $index }}"
                            data-id="{{ $variation->id }}"
                            data-photo-group="{{ $photoGroup }}"
                            data-v1="{{ $variation->variation_value }}"
                            data-v2="{{ $variation->option2_value }}"
                            data-adjustment="{{ $variation->price_adjustment }}"
                            data-price="{{ $product->unitPrice($variation) }}"
                            data-regular="{{ $product->regularUnitPrice($variation) }}"
                            data-stock="{{ $variation->stock }}"
                            data-label="{{ $variation->label() }}"
                            data-image="{{ $variationImageUrl }}"
                            onclick="selectVariation({{ $index }})"
                            title="{{ $variation->label() }}"
                        >
                            @if($variationImageUrl)
                                <img src="{{ $variationImageUrl }}" alt="" onerror="this.remove()">
                            @endif
                            <span class="swatch-label">{{ $variation->shortLabel() }}</span>
                        </button>

                    @endforeach

                </div>

                <div class="variation-info" id="variationInfo"></div>

                </div>
            @endif


            <!-- QUANTITY, and how many are available for what is picked -->
            @php $availableNow = $variations->isNotEmpty() ? (int) $variations->sum('stock') : (int) $product->stock; @endphp
            <div class="pd-qty-row">

            <label class="quantity-label" for="quantity">

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

            <span class="pd-available" id="pdAvailable" data-total="{{ $availableNow }}">{{ number_format($availableNow) }} {{ $availableNow === 1 ? 'piece' : 'pieces' }} available</span>

            </div>


            <!-- ACTION BUTTONS -->

            @php $onVacation = !empty($shop['vacation']); @endphp

            {{-- The store's claimable vouchers that cover this product --}}
            @if($product->seller_id)
                @include('partials.voucher-strip', ['vouchers' => \App\Models\Voucher::claimable((int) $product->seller_id)->filter(fn ($v) => $v->covers((int) $product->seller_id, (int) $product->id))->values()])
            @endif

            @if($onVacation)
                <div class="vacation-notice" role="status">
                    <i class="bi bi-umbrella-fill"></i>
                    <span><strong>On vacation.</strong> This store isn't taking orders right now. You can still save this product to your wishlist.</span>
                </div>
            @endif

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
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#6d4ad1" stroke="#6d4ad1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#645a80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
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
                        @disabled($onVacation)
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
                        @disabled($onVacation)
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
                            @if(!empty($deliveryTo))
                                <strong>Delivery ₱{{ number_format($deliveryFee) }} to {{ $deliveryTo }}</strong>
                                · arrives {{ $deliveryEta }}
                            @else
                                <strong>Delivery from ₱{{ number_format($deliveryFee) }}</strong>
                            @endif
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

        </div>

    </section>


    {{-- THE SHOP: who sells it, with Chat Now / View Shop and the shop's numbers --}}
    @if($shop)
        @php
            $shopStats = [
                ['Ratings', $shop['reviews'] ? number_format($shop['reviews']) . ($shop['rating'] ? ' (' . $shop['rating'] . '★)' : '') : 'No ratings yet'],
                ['Response Rate', $shop['response_rate'] !== null ? $shop['response_rate'] . '%' : '—'],
                ['Joined', $shop['joined_ago'] ?? '—'],
                ['Products', number_format($shop['products'])],
                ['Response Time', $shop['response_time'] ?? '—'],
                ['Sold', number_format($shop['sold'])],
            ];
        @endphp
        <section class="pd-shop-card" aria-label="About the shop">
            <div class="pd-shop-main">
                <a href="{{ $shop['url'] }}" class="pd-shop-avatar" aria-hidden="true" tabindex="-1">
                    @if($shop['photo'])
                        <img src="{{ $shop['photo'] }}" alt="">
                    @else
                        {{ $shop['initial'] }}
                    @endif
                </a>
                <div class="pd-shop-who">
                    <a href="{{ $shop['url'] }}" class="pd-shop-name">{{ $shop['name'] }}</a>
                    @if($shop['location'])
                        <span class="pd-shop-place"><i class="bi bi-geo-alt"></i> {{ $shop['location'] }}</span>
                    @endif
                    <div class="pd-shop-actions">
                        @if($canMessageSeller)
                            <a href="{{ route('messages.thread', [$product->seller_id, 'product' => $product->id]) }}" class="pd-shop-btn is-chat"><i class="bi bi-chat-dots-fill"></i> Chat Now</a>
                        @elseif(!session('user') && !session('admin_logged_in'))
                            <a href="{{ route('login') }}" class="pd-shop-btn is-chat"><i class="bi bi-chat-dots-fill"></i> Chat Now</a>
                        @endif
                        <a href="{{ $shop['url'] }}" class="pd-shop-btn"><i class="bi bi-shop"></i> View Shop</a>
                    </div>
                </div>
            </div>
            <dl class="pd-shop-stats">
                @foreach($shopStats as [$label, $value])
                    <div class="pd-shop-stat">
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif


    <!-- SPECIFICATIONS (phones, tablets, laptops) -->
    @php
        // Storage is picked as an option, so list the sizes it comes in here too.
        $storageSizes = \App\Support\DeviceSpecs::sortStorage($variations->map(fn ($v) => \App\Support\DeviceSpecs::isStorage($v->option2_type)
            ? $v->option2_value
            : (\App\Support\DeviceSpecs::isStorage($v->variation_type) ? $v->variation_value : null))->filter());
        $specTable = $specRows;
        if ($storageSizes) {
            $specTable = array_slice($specTable, 0, 3, true) + ['Storage' => implode(' / ', $storageSizes)] + array_slice($specTable, 3, null, true);
        }
    @endphp
    @if($specTable)
        <section class="specs-section" id="specs">
            <h2>Specifications</h2>
            <dl class="specs-table">
                @foreach($specTable as $label => $value)
                    <div class="specs-row">
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- The seller's full description, with its line breaks and bullets kept. --}}
    @if($descriptionText !== '')
        <section class="specs-section pd-desc-section" id="description">
            <h2>Product Description</h2>
            <div class="pd-desc-body">{{ $descriptionText }}</div>
        </section>
    @endif


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
                        {{-- Masked: "Juan Miguel" → "J*** M***". --}}
                        <span class="review-name">{{ collect(preg_split('/\s+/', trim((string) $review->buyer_name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1) . '***')->implode(' ') }}</span>
                        <span class="review-stars" aria-label="{{ (int) $review->rating }} out of 5 stars">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}</span>
                    </div>

                    <div class="review-date">
                        {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('F d, Y') }}
                    </div>

                    @if(!empty($review->variation_label))
                        <div class="review-variant">{{ $review->variation_label }}</div>
                    @endif

                    @if(!empty($review->review))
                        <p class="review-text">{{ $review->review }}</p>
                    @endif

                    @php $reviewPhotos = (array) json_decode((string) ($review->photos ?? ''), true); @endphp
                    @if($reviewPhotos)
                        <div class="review-photos">
                            @foreach($reviewPhotos as $photo)
                                <a href="{{ asset('storage/' . $photo) }}" data-viewer="review-{{ $loop->parent->index }}" data-viewer-type="image"><img src="{{ asset('storage/' . $photo) }}" alt="Review photo" loading="lazy"></a>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($review->seller_reply))
                        <div class="seller-reply-box" style="background:#f8f6fe; border:1px solid #e9e4fa; border-radius:10px; padding:10px 12px; margin-top:8px;">
                            <div style="font-size:11px; font-weight:800; color:#5536b5; margin-bottom:3px;">Seller's Response</div>
                            <div style="font-size:13px; color:#463c5e;">{{ $review->seller_reply }}</div>
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


    <!-- FROM THE SAME SHOP (a row that scrolls) + YOU MAY ALSO LIKE (a grid) -->

    @foreach([
        ['key' => 'same-shop', 'title' => 'From the same shop', 'items' => $shop ? $moreFromSeller : collect(), 'link' => $shop['url'] ?? null, 'row' => true],
        ['key' => 'also-like', 'title' => 'You may also like', 'items' => $relatedProducts, 'link' => $pdCategorySlug ? route('products', ['category' => $pdCategorySlug]) : null, 'row' => false],
    ] as $rail)

        @if($rail['items']->count() > 0)

            <section class="related-section {{ $rail['row'] ? 'is-row' : 'is-grid' }}" aria-labelledby="rail-{{ $rail['key'] }}">

                <div class="related-head">
                    <h2 id="rail-{{ $rail['key'] }}">{{ $rail['title'] }}</h2>
                    @if($rail['link'])
                        <a href="{{ $rail['link'] }}">See All <i class="bi bi-chevron-right"></i></a>
                    @endif
                </div>

                <div class="related-track-wrap">
                    @if($rail['row'])
                        <button type="button" class="related-nav is-prev" data-rail-step="-1" aria-label="Previous products" hidden><i class="bi bi-chevron-left"></i></button>
                    @endif

                    <div class="{{ $rail['row'] ? 'related-row' : 'related-grid' }}">

                        @foreach($rail['items'] as $related)
                            @php
                                $relRating = (int) ($related->reviews_count ?? 0) > 0 ? round((float) $related->reviews_avg_rating, 1) : null;
                                $relSold = (int) ($related->sold_count ?? 0);
                            @endphp

                            <a href="{{ route('product.details', \Illuminate\Support\Str::slug($related->name) . '-' . $related->id) }}" class="related-card">

                                <div class="related-image">
                                    <x-product-thumb :image="$related->image" :category="$related->category" size="200" style="width:100%; height:100%; border-radius:0;" />
                                    @if($related->isDiscounted())
                                        <span class="related-off">-{{ $related->discount_percent }}%</span>
                                    @endif
                                </div>

                                <div class="related-info">
                                    <div class="related-name">{{ $related->name }}</div>
                                    <div class="related-price">₱{{ number_format($related->price) }}</div>
                                    <div class="related-meta">
                                        @if($relRating)
                                            <span class="related-stars"><i class="bi bi-star-fill"></i> {{ number_format($relRating, 1) }}</span>
                                        @endif
                                        @if($relSold > 0)
                                            <span class="related-sold">{{ $relSold >= 1000 ? number_format($relSold / 1000, 1) . 'K+' : $relSold }} sold</span>
                                        @endif
                                    </div>
                                </div>

                            </a>
                        @endforeach

                    </div>

                    @if($rail['row'])
                        <button type="button" class="related-nav is-next" data-rail-step="1" aria-label="More products" hidden><i class="bi bi-chevron-right"></i></button>
                    @endif
                </div>

            </section>

        @endif

    @endforeach

    <script>
        // "From the same shop": ‹ › arrows show only when the row scrolls.
        document.querySelectorAll('.related-section.is-row').forEach(function (section) {
            var row = section.querySelector('.related-row');
            var prev = section.querySelector('.related-nav.is-prev');
            var next = section.querySelector('.related-nav.is-next');
            if (!row || !prev || !next) return;

            function sync() {
                var max = row.scrollWidth - row.clientWidth;
                prev.hidden = row.scrollLeft <= 4;
                next.hidden = row.scrollLeft >= max - 4;
            }

            [prev, next].forEach(function (btn) {
                btn.addEventListener('click', function () {
                    row.scrollBy({ left: row.clientWidth * 0.9 * parseInt(btn.dataset.railStep, 10), behavior: 'smooth' });
                });
            });

            row.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync);
            sync();
        });
    </script>

</main>


<script>

    function increaseQuantity() {

        const input =
            document.getElementById('quantity');

        // Not more than what is available for the current pick.
        const available = parseInt(document.getElementById('pdAvailable')?.dataset.now || '0', 10);
        const next = parseInt(input.value || 1) + 1;

        input.value = available > 0 ? Math.min(next, available) : next;

        updateBuyNowQuantity();
    }

    // ---- Price and "N pieces available" for what is picked so far ----
    function peso(amount) {
        // Centavos only when there are some (₱179.10, ₱450).
        const cents = Math.round(amount * 100) % 100 !== 0;
        return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: cents ? 2 : 0, maximumFractionDigits: cents ? 2 : 0 });
    }

    // options: the option buttons that match the pick (all of them before any pick).
    function showPick(options) {
        const priceEl = document.getElementById('pdPrice');
        const wasEl = document.getElementById('pdPriceWas');
        const availableEl = document.getElementById('pdAvailable');
        const base = parseFloat(priceEl.dataset.base) || 0;

        // Each option's price comes from the server (sales included).
        const priceOf = function (s, key) { return s.dataset[key] !== undefined ? parseFloat(s.dataset[key]) : base + (parseFloat(s.dataset.adjustment) || 0); };
        const range = function (key) {
            const list = options.map(function (s) { return priceOf(s, key); });
            const low = Math.min.apply(null, list);
            const high = Math.max.apply(null, list);
            return peso(low) + (high > low ? ' – ' + peso(high) : '');
        };

        if (options.length) {
            priceEl.textContent = range('price');
            if (wasEl) wasEl.textContent = range('regular');
        }

        const stock = options.reduce(function (sum, s) { return sum + (parseInt(s.dataset.stock, 10) || 0); }, 0);
        availableEl.dataset.now = stock;
        availableEl.textContent = stock > 0
            ? stock.toLocaleString('en-PH') + (stock === 1 ? ' piece' : ' pieces') + ' available'
            : 'Out of stock';
        availableEl.classList.toggle('is-out', stock <= 0);

        // Keep the quantity within it.
        const qty = document.getElementById('quantity');
        if (stock > 0 && parseInt(qty.value, 10) > stock) {
            qty.value = stock;
            updateBuyNowQuantity();
        }
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

    // keepPhoto: on page load the cover stays up; the option photo shows once the buyer picks.
    function selectVariation(index, keepPhoto) {

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

        // The option's own photo (lit up in the row), else the first photo for all options.
        filterThumbs();
        const group = swatch.dataset.photoGroup || swatch.dataset.id;
        const ownThumb = allThumbs().find(function (t) { return t.dataset.group === group; })
            || allThumbs().find(function (t) { return t.dataset.group === 'all'; });
        const imageUrl = swatch.dataset.image || (ownThumb && ownThumb.dataset.src) || img.dataset.fallbackSrc || '';

        // A click while hovering keeps the option's photo (no restore on leave).
        previewBack = null;

        if (keepPhoto) {
            // leave the cover as is
        } else if (imageUrl) {
            img.src = imageUrl;
            markThumb(imageUrl);
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
            fallback.style.display = 'flex';
        }

        // One kind of option (just a color): its own price and stock.
        if (!document.querySelector('.pd-part1')) {
            showPick([swatch]);
        }

    }

    // ---- Color × Storage (or Size): pick each part on its own, like a shop app ----
    // Picking a color shows how many that color has and greys out the sizes it
    // doesn't have (or has none left of); both picked = that exact option.
    const pick = { v1: '', v2: '' };

    function comboSwatches() {
        return Array.prototype.slice.call(document.querySelectorAll('.variation-swatch'));
    }

    function inStock(s) {
        return parseInt(s.dataset.stock, 10) > 0;
    }

    function matching() {
        return comboSwatches().filter(function (s) {
            return (!pick.v1 || s.dataset.v1 === pick.v1) && (!pick.v2 || s.dataset.v2 === pick.v2);
        });
    }

    function renderParts(keepPhoto) {
        const all = comboSwatches();

        document.querySelectorAll('.pd-part1').forEach(function (chip) {
            const options = all.filter(function (s) { return s.dataset.v1 === chip.dataset.value && (!pick.v2 || s.dataset.v2 === pick.v2); });
            const open = options.some(inStock);
            chip.classList.toggle('active', chip.dataset.value === pick.v1);
            chip.classList.toggle('is-out', !open);
            chip.disabled = !open;
        });

        document.querySelectorAll('.pd-part2').forEach(function (chip) {
            const options = all.filter(function (s) { return s.dataset.v2 === chip.dataset.value && (!pick.v1 || s.dataset.v1 === pick.v1); });
            const open = options.some(inStock);
            chip.classList.toggle('active', chip.dataset.value === pick.v2);
            chip.classList.toggle('is-out', !open);
            chip.disabled = !open;
        });

        const options = matching();
        const exact = pick.v1 && pick.v2 ? options[0] : null;

        if (exact) {
            // The exact option: its photo, and it goes to the cart / Buy Now.
            selectVariation(all.indexOf(exact), keepPhoto);
        } else {
            if (pick.v1 && options.length) {
                // Just a color: show that color's photo, nothing to buy yet.
                selectVariation(all.indexOf(options.find(inStock) || options[0]), keepPhoto);
            }
            all.forEach(function (s) { s.classList.remove('active'); });
            ['cartVariationId', 'buyNowVariationId'].forEach(function (id) {
                const input = document.getElementById(id);
                if (input) input.value = '';
            });
        }

        showPick(options.length ? options : all);
        document.querySelectorAll('.pd-part').forEach(function (part) { part.classList.remove('needs-pick'); });
        const info = document.getElementById('variationInfo');
        if (info) info.textContent = '';
    }

    document.querySelectorAll('.pd-part1, .pd-part2').forEach(function (chip) {
        chip.addEventListener('click', function () {
            const key = chip.classList.contains('pd-part1') ? 'v1' : 'v2';
            // Clicking the picked one again un-picks it.
            pick[key] = pick[key] === chip.dataset.value ? '' : chip.dataset.value;
            renderParts();
        });
    });

    // Add to Cart / Buy Now before both parts are picked: say which one is missing.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!document.querySelector('.pd-part1') || (form.id !== 'addToCartForm' && form.id !== 'buyNowForm')) return;
        if (pick.v1 && pick.v2) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        const parts = document.querySelectorAll('.pd-part');
        const missing = !pick.v1 ? parts[0] : parts[1];
        missing.classList.add('needs-pick');
        const info = document.getElementById('variationInfo');
        if (info) info.textContent = 'Please select ' + missing.querySelector('.pd-part-label').textContent.trim() + '.';
        missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, true);

    // ---- Photo thumbnails: click (or tap) shows the photo big. ----
    // Read fresh each time: a thumbnail whose photo fails to load removes itself.
    const allThumbs = () => Array.from(document.querySelectorAll('.pd-thumb')).filter(function (t) { return !t.hidden; });

    // The row shows every photo (each option's, then the ones for all options), each
    // picture once; the chosen option's photo is the one lit up (see markThumb).
    function filterThumbs() {
        const seen = new Set();
        document.querySelectorAll('.pd-thumb').forEach(function (t) {
            t.hidden = seen.has(t.dataset.fp);
            seen.add(t.dataset.fp);
        });
        const wrap = document.getElementById('pdThumbsWrap');
        if (wrap) wrap.hidden = allThumbs().length < 2;
        window.dispatchEvent(new Event('resize'));
    }

    // A photo of one option, clicked or swiped to: pick that option too (like a shop app).
    // A color with nothing left only shows its photo.
    function followThumb(thumb) {
        if (!thumb || thumb.dataset.group === 'all') return;
        const swatches = Array.from(document.querySelectorAll('.variation-swatch'));
        const index = swatches.findIndex(function (s) { return (s.dataset.photoGroup || s.dataset.id) === thumb.dataset.group; });
        if (index < 0) return;

        if (document.querySelector('.pd-part1')) {
            const color = swatches[index].dataset.v1;
            if (pick.v1 === color || !swatches.some(function (s) { return s.dataset.v1 === color && inStock(s); })) return;
            pick.v1 = color;
            // Keep the picked size/storage only if this color has it in stock.
            if (pick.v2 && !swatches.some(function (s) { return s.dataset.v1 === color && s.dataset.v2 === pick.v2 && inStock(s); })) pick.v2 = '';
            renderParts(true);
        } else if (!swatches[index].classList.contains('active')) {
            selectVariation(index, true);
        }
    }

    function markThumb(url) {
        allThumbs().forEach(function (t) {
            const on = t.dataset.src === url;
            t.classList.toggle('is-active', on);
            if (on) {
                // Keep the active thumbnail in view inside the row (not the page).
                const row = t.parentElement;
                if (t.offsetLeft < row.scrollLeft || t.offsetLeft + t.offsetWidth > row.scrollLeft + row.clientWidth) {
                    row.scrollLeft = t.offsetLeft - row.clientWidth / 2 + t.offsetWidth / 2;
                }
            }
        });
    }

    function showPhoto(index) {
        const thumb = allThumbs()[index];
        if (!thumb) return;
        const img = document.getElementById('mainProductImage');
        img.src = thumb.dataset.src;
        markThumb(thumb.dataset.src);
    }

    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    // Like a shop app: hovering (or tabbing to) a photo shows it big; clicking or tapping it
    // picks its color and opens the photo viewer at it.
    allThumbs().forEach(function (thumb) {
        const preview = function () { showPhoto(allThumbs().indexOf(thumb)); };
        thumb.addEventListener('click', function () {
            followThumb(thumb);
            preview();
            if (window.pdOpenViewer) window.pdOpenViewer(thumb.dataset.src);
        });
        thumb.addEventListener('focus', preview);
        if (canHover) thumb.addEventListener('mouseenter', preview);
    });

    // Hovering an option with a photo previews it; leaving puts the shown photo back.
    let previewBack = null;

    document.querySelectorAll('.variation-swatch').forEach(function (swatch) {
        if (!canHover || !swatch.dataset.image) return;

        swatch.addEventListener('mouseenter', function () {
            const img = document.getElementById('mainProductImage');
            previewBack = img.getAttribute('src');
            img.src = swatch.dataset.image;
            markThumb(swatch.dataset.image);
        });

        swatch.addEventListener('mouseleave', function () {
            if (previewBack === null) return;
            const img = document.getElementById('mainProductImage');
            img.src = previewBack;
            markThumb(img.src);
            previewBack = null;
        });
    });

    // Strip arrows: only when the thumbnails don't all fit.
    (function () {
        const row = document.getElementById('pdThumbs');
        if (!row) return;
        const navs = document.querySelectorAll('.pd-strip-nav');
        const update = function () {
            const overflow = row.scrollWidth > row.clientWidth + 2;
            navs.forEach(function (b) {
                const dir = parseInt(b.dataset.strip, 10);
                b.hidden = !overflow || (dir < 0 ? row.scrollLeft <= 2 : row.scrollLeft + row.clientWidth >= row.scrollWidth - 2);
            });
        };
        navs.forEach(function (b) {
            b.addEventListener('click', function () {
                row.scrollBy({ left: parseInt(b.dataset.strip, 10) * row.clientWidth * 0.8, behavior: 'smooth' });
            });
        });
        row.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        window.addEventListener('load', update);
        update();
    })();

    function galleryStep(direction) {

        // With a photo row, the arrows and swipes go through the photos.
        const thumbs = allThumbs();
        if (thumbs.length > 1) {
            let current = thumbs.findIndex(function (t) { return t.classList.contains('is-active'); });
            const next = ((current < 0 ? 0 : current) + direction + thumbs.length) % thumbs.length;
            followThumb(thumbs[next]);
            showPhoto(next);
            return;
        }

        const swatches = document.querySelectorAll('.variation-swatch');

        // Two parts (Color × Storage) are picked with their own buttons.
        if (swatches.length === 0 || document.querySelector('.pd-part1')) {
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

    // ---- Photo viewer: tap the main photo for every photo big, ‹ ›, and a grid of them ----
    // It walks the same photos as the row under the main photo, and a color's photo
    // picks that color on the page too. The phone's back button closes it.
    (function () {
        const box = document.getElementById('pdLightbox');
        const mainImg = document.getElementById('mainProductImage');
        if (!box || !mainImg) return;

        const big = document.getElementById('pdLbImg');
        const grid = document.getElementById('pdLbGrid');
        const count = document.getElementById('pdLbCount');
        const prev = document.getElementById('pdLbPrev');
        const next = document.getElementById('pdLbNext');
        let photos = [];   // { src, thumb (row button or null) }
        let current = 0;
        let pushed = false;

        function show(i) {
            if (!photos.length) return;
            current = (i + photos.length) % photos.length;
            const photo = photos[current];
            big.src = photo.src;
            count.textContent = photos.length > 1 ? (current + 1) + ' / ' + photos.length : '';
            grid.querySelectorAll('button').forEach(function (b, n) {
                b.classList.toggle('is-active', n === current);
                if (n === current) b.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
            // Keep the page in step: same photo under the viewer, and its color picked.
            if (photo.thumb) {
                followThumb(photo.thumb);
                showPhoto(allThumbs().indexOf(photo.thumb));
            }
            refreshInfo();
        }

        // The summary beside the photos: the page's current price and what is picked.
        function refreshInfo() {
            const priceEl = document.getElementById('pdPrice');
            document.getElementById('pdLbPrice').textContent = priceEl ? priceEl.textContent.trim() : '';

            const picks = document.getElementById('pdLbPicks');
            picks.textContent = '';
            const row = function (label, build) {
                const wrap = document.createElement('div');
                const dt = document.createElement('dt');
                const dd = document.createElement('dd');
                dt.textContent = label;
                build(dd);
                wrap.append(dt, dd);
                picks.appendChild(wrap);
            };
            const values = function (dd, chips, chosen) {
                chips.forEach(function (chip, n) {
                    if (n) dd.append(' · ');
                    const v = document.createElement(chip.dataset.value === chosen ? 'b' : 'span');
                    v.textContent = chip.dataset.value;
                    if (chip.classList.contains('is-out')) v.className = 'is-out';
                    dd.appendChild(v);
                });
            };

            const parts = document.querySelectorAll('.pd-part');
            if (document.querySelector('.pd-part1') && parts.length > 1) {
                const label = function (part) { return part.querySelector('.pd-part-label').textContent.trim(); };
                row(label(parts[0]), function (dd) { dd.textContent = pick.v1 || 'Not picked yet'; });
                row(label(parts[1]), function (dd) { values(dd, Array.from(document.querySelectorAll('.pd-part2')), pick.v2); });
            } else {
                const active = document.querySelector('.variation-swatch.active .swatch-label');
                if (active) row('Option', function (dd) { dd.textContent = active.textContent.trim(); });
            }
            picks.hidden = !picks.children.length;
        }

        const more = document.getElementById('pdLbMore');
        let scrollToDescription = false;
        if (more) {
            more.addEventListener('click', function () {
                // Closing goes back one step in history; scroll once that has happened.
                scrollToDescription = true;
                const viaHistory = pushed;
                close(false);
                if (!viaHistory) setTimeout(goToDescription, 60);
            });
        }

        function goToDescription() {
            if (!scrollToDescription) return;
            scrollToDescription = false;
            document.getElementById('description').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function open(startSrc) {
            const src = (typeof startSrc === 'string' && startSrc) || mainImg.getAttribute('src');
            if (!src || mainImg.style.display === 'none') return;

            const thumbs = allThumbs();
            photos = thumbs.length
                ? thumbs.map(function (t) { return { src: t.dataset.src, thumb: t }; })
                : [{ src: src, thumb: null }];

            grid.textContent = '';
            photos.forEach(function (photo, n) {
                const b = document.createElement('button');
                b.type = 'button';
                b.setAttribute('aria-label', 'Photo ' + (n + 1));
                b.innerHTML = '<img alt="" loading="lazy">';
                b.querySelector('img').src = photo.src;
                b.addEventListener('click', function () { show(n); });
                grid.appendChild(b);
            });
            grid.hidden = photos.length < 2;
            prev.hidden = next.hidden = photos.length < 2;

            box.hidden = false;
            document.body.classList.add('pd-lb-open');
            const at = photos.findIndex(function (p) { return p.src === src; });
            show(at < 0 ? 0 : at);
            history.pushState({ pdLightbox: true }, '');
            pushed = true;
            document.getElementById('pdLbClose').focus();
        }

        function close(fromHistory) {
            if (box.hidden) return;
            box.hidden = true;
            document.body.classList.remove('pd-lb-open');
            if (pushed && !fromHistory) {
                pushed = false;
                history.back();
            }
            pushed = false;
        }

        mainImg.addEventListener('click', function () { open(); });
        window.pdOpenViewer = open;
        document.getElementById('pdLbClose').addEventListener('click', function () { close(false); });
        prev.addEventListener('click', function () { show(current - 1); });
        next.addEventListener('click', function () { show(current + 1); });
        // A click on the dark area around the box closes it.
        box.addEventListener('click', function (e) { if (e.target === box) close(false); });
        window.addEventListener('popstate', function () {
            close(true);
            setTimeout(goToDescription, 60);
        });

        document.addEventListener('keydown', function (e) {
            if (box.hidden) return;
            if (e.key === 'Escape') close(false);
            else if (e.key === 'ArrowLeft') show(current - 1);
            else if (e.key === 'ArrowRight') show(current + 1);
        });

        // Swipe left/right on the big photo.
        const stage = document.getElementById('pdLbStage');
        let startX = null;
        stage.addEventListener('touchstart', function (e) { startX = e.changedTouches[0].clientX; }, { passive: true });
        stage.addEventListener('touchend', function (e) {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
            startX = null;
        }, { passive: true });
    })();

    // Initialize variation selection on page load
    if (document.querySelector('.variation-swatch')) {
        // Start on the option the cover photo belongs to, so the photo and the chosen option match.
        const mainImg = document.getElementById('mainProductImage');
        const coverThumb = Array.from(document.querySelectorAll('.pd-thumb')).find(function (t) { return t.dataset.src === mainImg.dataset.fallbackSrc; });
        let start = 0;

        if (coverThumb && coverThumb.dataset.group !== 'all') {
            const owner = Array.from(document.querySelectorAll('.variation-swatch')).findIndex(function (s) { return (s.dataset.photoGroup || s.dataset.id) === coverThumb.dataset.group; });
            if (owner >= 0) start = owner;
        }

        if (document.querySelector('.pd-part1')) {
            // Two parts: start on the cover photo's color; the buyer picks the rest.
            pick.v1 = document.querySelectorAll('.variation-swatch')[start].dataset.v1;
            renderParts(true);
        } else {
            selectVariation(start, true);
        }

        // The cover's thumbnail is hidden (a repeat of this option's photo, or another
        // option's): show the same picture if it is shown, else the first shown photo.
        const shown = allThumbs();
        if (shown.length && !shown.some(function (t) { return t.dataset.src === mainImg.getAttribute('src'); })) {
            const same = coverThumb ? shown.findIndex(function (t) { return t.dataset.fp === coverThumb.dataset.fp; }) : -1;
            showPhoto(same >= 0 ? same : 0);
        }
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

        var HEART_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#6d4ad1" stroke="#6d4ad1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        var HEART_OUTLINE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#645a80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

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

    @include('partials.viewer-modal')

    @include('partials.pwa-register')

<script>
    // Flash sale countdown; at zero the page reloads to show the normal price.
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
        var end = new Date(el.dataset.countdown).getTime();
        function tick() {
            var left = Math.max(0, Math.floor((end - Date.now()) / 1000));
            var h = String(Math.floor(left / 3600)).padStart(2, '0');
            var m = String(Math.floor(left % 3600 / 60)).padStart(2, '0');
            var s = String(left % 60).padStart(2, '0');
            el.textContent = h + ':' + m + ':' + s;
            if (left === 0) { clearInterval(timer); setTimeout(function () { location.reload(); }, 1500); }
        }
        var timer = setInterval(tick, 1000);
        tick();
    });
</script>

</body>

</html>