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

        $discoverTabs = [
            'latest' => ['label' => 'Latest', 'empty' => 'No products yet.'],
            'sale' => ['label' => 'On sale', 'empty' => 'Nothing is on sale right now.'],
            'top' => ['label' => 'Top rated', 'empty' => 'No rated products yet — reviews show up here once buyers rate their orders.'],
            'budget' => ['label' => 'Under ₱1,000', 'empty' => 'No products under ₱1,000 right now.'],
        ];
        $discover['sale'] = $onSale;

        $greetHour = (int) now()->format('G');
        $greetText = $greetHour >= 5 && $greetHour < 12 ? 'Good morning' : ($greetHour >= 12 && $greetHour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = \Illuminate\Support\Str::of($panel['name'])->before(' ');

        // My Purchases shortcuts (same tabs as My Orders).
        $purchases = [
            ['label' => 'To Ship', 'icon' => 'bi-box-seam', 'tone' => 'is-amber', 'count' => $panel['to_ship'], 'url' => route('buyer.orders', ['tab' => 'to-ship'])],
            ['label' => 'To Receive', 'icon' => 'bi-truck', 'tone' => 'is-blue', 'count' => $panel['to_receive'], 'url' => route('buyer.orders', ['tab' => 'to-receive'])],
            ['label' => 'To Review', 'icon' => 'bi-star', 'tone' => 'is-green', 'count' => $panel['to_review'], 'url' => route('buyer.orders', ['tab' => 'delivered'])],
            ['label' => 'Cancelled', 'icon' => 'bi-arrow-counterclockwise', 'tone' => 'is-grey', 'count' => 0, 'url' => route('buyer.orders', ['tab' => 'cancelled'])],
            ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle', 'tone' => 'is-red', 'count' => 0, 'url' => route('complaints.index')],
        ];

        // The newest order on its way, on the 5-step tracker.
        $tracked = $activeOrders[0] ?? null;
        $trackSteps = ['Placed', 'Packed', 'Sorted', 'Out', 'Delivered'];
    @endphp

    <main class="dash bh">

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
                    {{-- Follows the phone's clock (script below); the server's hour is only the first guess. --}}
                    <em class="dash-greet-time" data-greet-time>
                        <i class="bi {{ $greetHour >= 5 && $greetHour < 18 ? 'bi-sun-fill' : 'bi-moon-stars-fill' }}" aria-hidden="true"></i>
                        <span>{{ $greetText }}</span>
                    </em>
                    <strong>Hi, {{ $firstName }}!</strong>
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

            {{-- GREETING (desktop) --}}
            <section class="bh-hello">
                <div>
                    <span class="bh-hello-eyebrow">{{ $greetText }}, {{ $firstName }} 👋</span>
                    <h1>What are we buying today?</h1>
                </div>
                @if($cod['blocked'] || $cod['strikes'] > 0)
                    <a href="{{ route('policies') }}#returns" class="dash-cod-chip {{ $codClass }}" aria-label="Cash on Delivery standing: {{ $codLabel }}">
                        <i class="bi {{ $cod['blocked'] ? 'bi-slash-circle' : 'bi-exclamation-circle' }}"></i>{{ $codLabel }}
                    </a>
                @endif
            </section>

            {{-- HERO: promo banners from Admin → Promo Banners (else the announcement, products, free shipping) --}}
            @include('partials.promo-hero', ['layout' => 'wide', 'withAnnouncement' => true])

            {{-- ON ITS WAY · MY PURCHASES · YOUR CART --}}
            <div class="bh-row">

                <section class="bh-card bh-track" aria-labelledby="track-title">
                    <div class="bh-card-head">
                        <h2 id="track-title">{{ $tracked ? 'On its way' : 'No orders on the way' }}</h2>
                        <a href="{{ route('buyer.orders') }}" class="dash-link">My orders →</a>
                    </div>

                    @if($tracked)
                        <a href="{{ route('buyer.orders') }}#order-{{ $tracked['id'] }}" class="bh-track-item">
                            <span class="order-line-thumb">
                                @if($tracked['image'])
                                    <img src="{{ $tracked['image'] }}" alt="" onerror="this.remove()">
                                @endif
                                <i class="bi {{ $tracked['icon'] }}"></i>
                            </span>
                            <span class="bh-track-text">
                                <strong>{{ $tracked['name'] }}</strong>
                                <span>Order #{{ $tracked['id'] }} · ₱{{ number_format($tracked['total'], 2) }}</span>
                            </span>
                            <x-status-pill :status="$tracked['status']" />
                        </a>

                        <ol class="bh-steps" aria-label="Order progress: step {{ $tracked['step'] }} of 5">
                            @foreach($trackSteps as $i => $label)
                                <li class="{{ $i + 1 < $tracked['step'] ? 'is-done' : ($i + 1 === $tracked['step'] ? 'is-now' : '') }}"><span></span>{{ $label }}</li>
                            @endforeach
                        </ol>

                        <p class="bh-track-note">{{ $tracked['note'] }}</p>

                        <div class="bh-track-foot">
                            <a href="{{ route('buyer.orders') }}#order-{{ $tracked['id'] }}" class="bh-btn bh-btn-primary">Track order</a>
                            @if(count($activeOrders) > 1)
                                <span class="bh-more">+{{ count($activeOrders) - 1 }} more on the way</span>
                            @endif
                        </div>
                    @else
                        <p class="bh-track-note">When you place an order, you can follow it here from the seller to your door.</p>
                        <a href="{{ route('products') }}" class="bh-btn bh-btn-primary">Start shopping</a>
                    @endif
                </section>

                <section class="bh-card bh-purchases" aria-labelledby="purchases-title">
                    <div class="bh-card-head">
                        <h2 id="purchases-title">My Purchases</h2>
                        <a href="{{ route('buyer.orders') }}" class="dash-link">View all →</a>
                    </div>
                    <div class="bh-purchase-grid">
                        @foreach($purchases as $p)
                            <a href="{{ $p['url'] }}" class="bh-purchase {{ $p['tone'] }}">
                                <span class="bh-purchase-icon">
                                    <i class="bi {{ $p['icon'] }}" aria-hidden="true"></i>
                                    @if($p['count'] > 0)<em>{{ $p['count'] > 99 ? '99+' : $p['count'] }}</em>@endif
                                </span>
                                <span>{{ $p['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="bh-cart" aria-labelledby="cart-title">
                    <div class="bh-card-head">
                        <h2 id="cart-title">Your cart</h2>
                        <span class="bh-cart-count">{{ $cart['count'] }} {{ \Illuminate\Support\Str::plural('item', $cart['count']) }}</span>
                    </div>
                    @if($cart['count'] > 0)
                        <div class="bh-cart-thumbs" aria-hidden="true">
                            @foreach($cart['thumbs'] as $thumb)
                                <span>
                                    @if($thumb['image'])<img src="{{ $thumb['image'] }}" alt="" onerror="this.remove()">@endif
                                    <i class="bi {{ $thumb['icon'] }}"></i>
                                </span>
                            @endforeach
                        </div>
                        <div class="bh-cart-foot">
                            <div><small>Subtotal</small><strong>₱{{ number_format($cart['subtotal'], 2) }}</strong></div>
                            <a href="{{ route('cart') }}" class="bh-btn bh-btn-white">Checkout</a>
                        </div>
                    @else
                        <p>Your cart is empty. Find something you like below.</p>
                        <a href="{{ route('products') }}" class="bh-btn bh-btn-white">Browse products</a>
                    @endif
                </section>

            </div>

            {{-- SHOP BY CATEGORY --}}
            <section class="dash-section">
                <div class="dash-section-head">
                    <h2>Shop by category</h2>
                    <a href="{{ route('products') }}" class="dash-link">All products →</a>
                </div>
                <div class="bh-cats">
                    @foreach(\App\Support\Categories::LIST as $slug => $label)
                        <a href="{{ route('products', ['category' => $slug]) }}" class="bh-cat">
                            <span class="bh-cat-icon tone-{{ $loop->index % 6 }}"><i class="bi {{ \App\Support\Categories::icon($slug) }}" aria-hidden="true"></i></span>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- VOUCHERS: codes sellers are running now --}}
            @if(count($vouchers) > 0)
                <section class="dash-section" aria-labelledby="vouchers-title">
                    <div class="dash-section-head">
                        <div>
                            <h2 id="vouchers-title">Vouchers from shops</h2>
                            <p>Type the code in your cart to use it.</p>
                        </div>
                    </div>
                    <div class="bh-vouchers">
                        @foreach($vouchers as $v)
                            <div class="bh-voucher tone-{{ $loop->index % 4 }}">
                                <div class="bh-voucher-amount"><strong>{{ $v['big'] }}</strong><small>OFF</small></div>
                                <div class="bh-voucher-body">
                                    <strong>{{ $v['shop'] }}</strong>
                                    <span>{{ $v['min'] > 0 ? 'Min. spend ₱' . number_format($v['min']) : 'No minimum' }}{{ $v['until'] ? ' · until ' . $v['until'] : '' }}</span>
                                    <button type="button" class="bh-voucher-code" data-copy-code="{{ $v['code'] }}" aria-label="Copy voucher code {{ $v['code'] }}">
                                        <i class="bi bi-copy" aria-hidden="true"></i> <span>{{ $v['code'] }}</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ON SALE (phones): products with a discount, side-scrolling --}}
            @if(count($onSale) > 0)
                <section class="dash-section sale-strip" aria-labelledby="sale-title">
                    <div class="dash-section-head">
                        <h2 id="sale-title"><span class="sale-strip-icon" aria-hidden="true"><i class="bi bi-tag-fill"></i></span>On sale</h2>
                        <a href="{{ route('products') }}" class="dash-link">See all →</a>
                    </div>
                    <div class="sale-row">
                        @foreach($onSale as $product)
                            <a href="{{ route('product.details', $product['slug']) }}" class="sale-card">
                                <span class="sale-media">
                                    @if($product['image'])
                                        <img src="{{ $product['image'] }}" alt="" loading="lazy" onerror="this.remove()">
                                    @endif
                                    <i class="bi {{ $product['icon'] }}" aria-hidden="true"></i>
                                    <span class="sale-off">-{{ $product['discount'] }}%</span>
                                </span>
                                <span class="sale-body">
                                    <span class="sale-name">{{ $product['name'] }}</span>
                                    <span class="sale-price">₱{{ number_format($product['price']) }}</span>
                                    <s class="sale-was">₱{{ number_format($product['original_price']) }}</s>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- DISCOVER --}}
            <section class="dash-section">
                <div class="dash-section-head">
                    <h2>Picked for you</h2>
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
                                                    {{-- Needs a color/size: a popup to choose it (same as the Shop cards). --}}
                                                    <a href="{{ route('product.details', $product['slug']) }}?choose=1" data-quick-add="{{ route('product.options', $product['id']) }}" class="btn btn-dark" aria-label="Add {{ $product['name'] }} to cart" aria-haspopup="dialog">
                                                        <i class="bi bi-cart-plus"></i><span class="btn-label">Add</span>
                                                    </a>
                                                @else
                                                    <form action="{{ route('cart.add', $product['id']) }}" method="POST" data-add-cart>
                                                        @csrf
                                                        <button type="submit" class="btn btn-dark" aria-label="Add {{ $product['name'] }} to cart">
                                                            <i class="bi bi-cart-plus"></i><span class="btn-label">Add</span>
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

    {{-- Chat shortcut (desktop; phones have Chat in the tab bar) --}}
    <a href="{{ route('messages.index') }}" class="bh-chat" aria-label="Messages{{ $panel['unread_messages'] > 0 ? ' (' . $panel['unread_messages'] . ' unread)' : '' }}">
        <i class="bi bi-chat-dots-fill" aria-hidden="true"></i> Chat
        @if($panel['unread_messages'] > 0)<em>{{ $panel['unread_messages'] > 99 ? '99+' : $panel['unread_messages'] }}</em>@endif
    </a>


    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;

        /* Greeting follows the phone's clock and changes on its own */
        var greet = document.querySelector('[data-greet-time]');

        function setGreeting() {
            var h = new Date().getHours();
            var day = h >= 5 && h < 18;
            greet.querySelector('span').textContent = h >= 5 && h < 12 ? 'Good morning' : (day ? 'Good afternoon' : 'Good evening');
            greet.querySelector('i').className = 'bi ' + (day ? 'bi-sun-fill' : 'bi-moon-stars-fill');
            greet.classList.toggle('is-night', !day);
        }

        if (greet) {
            setGreeting();
            setInterval(setGreeting, 60000);
        }

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

        /* Voucher codes: click to copy */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-copy-code]');
            if (!btn) return;

            var code = btn.dataset.copyCode;
            var label = btn.querySelector('span');
            var done = function () {
                label.textContent = 'Copied!';
                setTimeout(function () { label.textContent = code; }, 1500);
            };

            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(done, function () { if (window.bbAlert) bbAlert('Voucher code: ' + code); });
            } else if (window.bbAlert) {
                bbAlert('Voucher code: ' + code);
            }
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

    })();
    </script>

    @include('partials.quick-add')

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
