<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'BoomBuy — Buyer Home'])
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-dashboard.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'home'])

    @php
        $cod = $panel['cod'];
        $codClass = $cod['blocked'] ? 'is-blocked' : ($cod['strikes'] > 0 ? 'is-warn' : 'is-good');
        $codLabel = $cod['blocked'] ? 'COD paused' : ($cod['strikes'] > 0 ? 'COD ' . $cod['strikes'] . '/' . $cod['limit'] : 'COD OK');

        $announcement = \App\Models\PlatformAnnouncement::where('is_active', true)
            ->orderByDesc('created_at')
            ->first();

        $discoverTabs = [
            'latest' => ['label' => 'Latest', 'empty' => 'No products yet.'],
            'top' => ['label' => 'Top rated', 'empty' => 'No rated products yet — reviews show up here once buyers rate their orders.'],
            'budget' => ['label' => 'Under ₱1,000', 'empty' => 'No products under ₱1,000 right now.'],
        ];
    @endphp

    <main class="dash">

        {{-- SIDEBAR (desktop) --}}
        <aside class="dash-side">
            @include('partials.buyer-account-panel', ['panel' => $panel])
        </aside>

        <div class="dash-main">

            {{-- GREETING (mobile) --}}
            <section class="dash-greet">
                <span class="dash-greet-avatar">
                    @if($panel['photo'])
                        <img src="{{ $panel['photo'] }}" alt="">
                    @else
                        {{ strtoupper(substr($panel['name'], 0, 1)) }}
                    @endif
                </span>
                <div class="dash-greet-text">
                    <strong>Hi, {{ \Illuminate\Support\Str::of($panel['name'])->before(' ') }}!</strong>
                    <span>
                        @if($panel['active_orders'] > 0)
                            {{ $panel['active_orders'] }} active {{ \Illuminate\Support\Str::plural('order', $panel['active_orders']) }}
                        @else
                            Welcome back to BoomBuy
                        @endif
                    </span>
                </div>
                @if($cod['blocked'] || $cod['strikes'] >= $cod['limit'] - 1)
                <a href="{{ route('buyer.account') }}" class="dash-cod-chip {{ $codClass }}" aria-label="Cash on Delivery standing: {{ $codLabel }}">
                    <i class="bi {{ $cod['blocked'] ? 'bi-slash-circle' : ($cod['strikes'] > 0 ? 'bi-exclamation-circle' : 'bi-check-lg') }}"></i>{{ $codLabel }}
                </a>
                @endif
            </section>

            {{-- HERO: rotating highlights (announcement, new arrivals, free shipping, COD) --}}
            @include('partials.buyer-hero-carousel', ['announcement' => $announcement, 'latest' => $discover['latest'] ?? []])

            {{-- YOUR ORDERS: one slim line each; the full tracker is on My Orders --}}
            @if(count($activeOrders) > 0)
                <section class="order-strip" aria-label="Your orders">
                    <div class="order-strip-head">
                        <strong><i class="bi bi-truck"></i> {{ count($activeOrders) }} {{ \Illuminate\Support\Str::plural('order', count($activeOrders)) }} on the way</strong>
                        <a href="{{ route('buyer.orders') }}" class="dash-link">All orders →</a>
                    </div>
                    @foreach($activeOrders as $order)
                        <a href="{{ route('buyer.orders') }}#order-{{ $order['id'] }}" class="order-line">
                            <span class="order-line-thumb">
                                @if($order['image'])
                                    <img src="{{ $order['image'] }}" alt="" onerror="this.remove()">
                                @endif
                                <i class="bi {{ $order['icon'] }}"></i>
                            </span>
                            <span class="order-line-text">
                                <span class="order-line-name">{{ $order['name'] }}</span>
                                <span class="order-line-note">Order #{{ $order['id'] }} · {{ $order['note'] }}</span>
                            </span>
                            <x-status-pill :status="$order['status']" />
                            <span class="order-line-go">Track <i class="bi bi-chevron-right"></i></span>
                        </a>
                    @endforeach
                </section>
            @endif
            {{-- SHOP BY CATEGORY --}}
            <section class="dash-section">
                <div class="dash-section-head">
                    <h2>Shop by category</h2>
                    <a href="{{ route('products') }}" class="dash-link">See all →</a>
                </div>
                <div class="cat-row">
                    @foreach(\App\Support\Categories::LIST as $slug => $label)
                        <a href="{{ route('products', ['category' => $slug]) }}" class="cat-chip">
                            <span class="cat-chip-icon"><i class="bi {{ \App\Support\Categories::icon($slug) }}"></i></span>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
            {{-- DISCOVER --}}
            <section class="dash-section">
                <div class="dash-section-head">
                    <h2>Products for you</h2>
                    <div class="discover-tabs" role="tablist" aria-label="Discover products">
                        @foreach($discoverTabs as $key => $tab)
                            <button
                                type="button"
                                class="discover-tab"
                                role="tab"
                                id="discover-tab-{{ $key }}"
                                aria-controls="discover-{{ $key }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                tabindex="{{ $loop->first ? '0' : '-1' }}"
                                data-discover-tab="{{ $key }}"
                            >{{ $tab['label'] }}</button>
                        @endforeach
                    </div>
                </div>

                @foreach($discoverTabs as $key => $tab)
                    <div
                        id="discover-{{ $key }}"
                        role="tabpanel"
                        aria-labelledby="discover-tab-{{ $key }}"
                        @unless($loop->first) hidden @endunless
                    >
                        @if(count($discover[$key]) === 0)
                            <div class="discover-empty">{{ $tab['empty'] }}</div>
                        @else
                            <div class="discover-grid">
                                @foreach($discover[$key] as $product)
                                    <article class="p-card">
                                        <div class="p-media">
                                            @if($product['image'])
                                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy" onerror="this.remove()">
                                            @endif
                                                <i class="bi {{ $product['icon'] }}"></i>

                                            {{-- The whole picture opens the product (the name below is the link screen readers use). --}}
                                            <a href="{{ route('product.details', $product['slug']) }}" class="p-media-link" tabindex="-1" aria-hidden="true"></a>

                                            <button
                                                type="button"
                                                class="p-heart"
                                                data-wishlist="{{ $product['id'] }}"
                                                aria-pressed="{{ $product['in_wishlist'] ? 'true' : 'false' }}"
                                                aria-label="{{ $product['in_wishlist'] ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                            >
                                                <i class="bi {{ $product['in_wishlist'] ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                            </button>

                                            <span class="p-cat">{{ $product['category'] }}</span>
                                            @if($product['discount'] ?? null)<span class="p-off">-{{ $product['discount'] }}%</span>@endif
                                        </div>

                                        <div class="p-body">
                                            <div>
                                                <a href="{{ route('product.details', $product['slug']) }}" class="p-name">{{ $product['name'] }}</a>
                                                <div class="p-sub">
                                                    @if($product['rating'])
                                                        <i class="bi bi-star-fill"></i> {{ $product['rating'] }} ({{ $product['reviews'] }})
                                                    @else
                                                        New
                                                    @endif
                                                    ·
                                                    @if($product['stock'] <= 0)
                                                        <span class="is-low">Sold out</span>
                                                    @elseif($product['stock'] <= 10)
                                                        <span class="is-low">Only {{ $product['stock'] }} left</span>
                                                    @else
                                                        {{ $product['stock'] }} in stock
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="p-bottom">
                                                <span class="p-price">₱{{ number_format($product['price']) }}@if($product['original_price'] ?? null) <s class="p-was">₱{{ number_format($product['original_price']) }}</s>@endif</span>

                                                @if($product['stock'] <= 0)
                                                    <button type="button" class="btn" disabled>Sold out</button>
                                                @elseif($product['has_variations'])
                                                    <a href="{{ route('product.details', $product['slug']) }}" class="btn btn-dark" aria-label="Choose options for {{ $product['name'] }}">
                                                        <i class="bi bi-sliders"></i><span class="btn-label">Options</span>
                                                    </a>
                                                @else
                                                    <form action="{{ route('cart.add', $product['id']) }}" method="POST" data-add-cart>
                                                        @csrf
                                                        <button type="submit" class="btn btn-dark" aria-label="Add {{ $product['name'] }} to cart">
                                                            <i class="bi bi-plus-lg"></i><span class="btn-label">Add</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <div style="text-align:center;">
                    <a href="{{ route('products') }}" class="dash-link">Browse all products →</a>
                </div>
            </section>

            {{-- BUY AGAIN --}}
            @if(count($buyAgain) > 0)
                <section class="dash-section">
                    <div class="dash-section-head">
                        <div>
                            <h2>Buy again</h2>
                            <p>From your delivered orders.</p>
                        </div>
                    </div>

                    <div class="again-grid">
                        @foreach($buyAgain as $product)
                            <div class="again-card">
                                <a href="{{ route('product.details', $product['slug']) }}" class="again-top">
                                    <span class="again-thumb">
                                        @if($product['image'])
                                            <img src="{{ $product['image'] }}" alt="" onerror="this.remove()">
                                        @endif
                                        <i class="bi {{ $product['icon'] }}"></i>
                                    </span>
                                    <span style="min-width:0;">
                                        <span class="again-name">{{ $product['name'] }}</span>
                                        <span class="again-price">₱{{ number_format($product['price'], 2) }}</span>
                                    </span>
                                </a>
                                <a href="{{ route('product.details', $product['slug']) }}" class="again-btn">
                                    <i class="bi bi-arrow-repeat"></i> Buy again
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif


        </div>

    </main>


    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;

        /* Discover tabs */
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-discover-tab]'));

        function select(tab) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById('discover-' + t.dataset.discoverTab).hidden = !on;
            });
        }

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(tab); });
            tab.addEventListener('keydown', function (e) {
                var next = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : null;
                if (next === null) return;
                e.preventDefault();
                var target = tabs[(next + tabs.length) % tabs.length];
                select(target);
                target.focus();
            });
        });

        /* Wishlist hearts */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wishlist]');
            if (!btn) return;

            var body = new FormData();
            body.append('_token', token);

            fetch('/wishlist/toggle/' + btn.dataset.wishlist, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
                .then(function (data) {
                    // The same product can appear under several tabs.
                    document.querySelectorAll('[data-wishlist="' + btn.dataset.wishlist + '"]').forEach(function (b) {
                        b.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                        b.setAttribute('aria-label', data.in_wishlist ? 'Remove from wishlist' : 'Add to wishlist');
                        b.querySelector('i').className = 'bi ' + (data.in_wishlist ? 'bi-heart-fill' : 'bi-heart');
                    });
                })
                .catch(function () {
                    if (window.bbAlert) bbAlert('Could not update your wishlist. Please try again.');
                });
        });

        /* Add to cart without leaving the page */
        document.querySelectorAll('[data-add-cart]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var button = form.querySelector('button');
                var original = button.innerHTML;
                button.disabled = true;

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form)
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        // Only say "Added" when the server actually added it.
                        if (!data.ok) {
                            button.innerHTML = original;
                            button.disabled = false;
                            if (window.bbAlert) bbAlert(data.message || 'Could not add this item to your cart.');
                            return;
                        }

                        button.innerHTML = '<i class="bi bi-check-lg"></i><span class="btn-label">Added</span>';

                        var badge = document.getElementById('cartCount');
                        if (badge && data.cart_count) {
                            badge.textContent = data.cart_count;
                            badge.style.display = '';
                        }

                        setTimeout(function () {
                            button.innerHTML = original;
                            button.disabled = false;
                        }, 1400);
                    })
                    .catch(function () {
                        button.innerHTML = original;
                        button.disabled = false;
                        if (window.bbAlert) bbAlert('Could not add this item to your cart. Please try again.');
                    });
            });
        });
    })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
