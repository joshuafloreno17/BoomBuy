<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'BoomBuy — Shop Everything You Love'])

    <meta name="description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">

    <meta property="og:type" content="website">
    <meta property="og:title" content="BoomBuy — Shop Everything You Love">
    <meta property="og:description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">
    <meta property="og:image" content="{{ asset('images/boombuy-logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BoomBuy — Shop Everything You Love">
    <meta name="twitter:description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">
    <meta name="twitter:image" content="{{ asset('images/boombuy-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">

    <link rel="stylesheet" href="{{ vasset('css/views/welcome.css') }}">
</head>

<body>

    {{-- Same header as the rest of the shop (guest: Log in / Register). --}}
    @include('partials.buyer-navbar', ['activeNav' => 'home'])

    @php
        $heroProduct = $featuredProducts->first();
        $heroShop = $heroProduct ? ($featuredShops->get($heroProduct->seller_id)['name'] ?? null) : null;
        $freeMin = \App\Support\DeliveryFee::freeShippingMin();
        $landingCats = array_slice(\App\Support\Categories::LIST, 0, 8, true);
        $catNotes = [
            'electronics' => 'Gadgets & devices',
            'womens-fashion' => 'Style & clothing',
            'mens-fashion' => 'Everyday style',
            'kids-baby' => 'For little ones',
            'home-living' => 'Home essentials',
            'sports-outdoors' => 'Gear for moving',
            'beauty-personal-care' => 'Skin, hair & more',
            'food-beverages' => 'Snacks & pantry',
        ];
    @endphp

    <main>

        {{-- Hero + promises: exactly one screen tall on laptops and desktops. --}}
        <div class="lp-first">

        <div class="lp-wrap">
            <section class="lp-hero">
                <div class="lp-hero-copy">
                    <span class="lp-pill">Your everyday marketplace</span>
                    <h1>Shop more.<br><span>Pay your way.</span></h1>
                    <p>Gadgets, fashion and everyday essentials from verified sellers, all in one place. Pay with GCash, Maya, card or cash on delivery.</p>

                    <div class="lp-actions">
                        <a href="{{ route('products') }}" class="lp-btn lp-btn-primary">Start shopping <i class="bi bi-arrow-right"></i></a>
                        <a href="{{ route('register') }}" class="lp-btn lp-btn-ghost">Create account</a>
                    </div>

                    <form class="lp-search" action="{{ route('products') }}" method="GET" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="search" placeholder="Try “earbuds” or “rice cooker”" aria-label="Search products">
                        <button type="submit">Search</button>
                    </form>
                </div>

                <div class="lp-hero-art" aria-hidden="true" data-hero-art>
                    <div class="lp-art-main">
                        <i class="bi bi-bag-heart-fill lp-art-fallback"></i>
                        {{-- Best sellers first, topped up with the newest; they take turns (script at the bottom). --}}
                        @foreach ($heroProducts as $slide)
                            @php $slideSold = (int) ($slide->sold_count ?? 0); @endphp
                            <div class="lp-slide {{ $loop->first ? 'is-active' : '' }}" data-hero-slide>
                                @if ($slideSold > 0)
                                    <span class="lp-new-badge is-hot"><i class="bi bi-fire"></i> Best seller · {{ number_format($slideSold) }} sold</span>
                                @else
                                    <span class="lp-new-badge"><i class="bi bi-stars"></i> Just listed</span>
                                @endif
                                @if ($slide->image)
                                    <img src="{{ productImageUrl($slide->image) }}" alt="" class="lp-slide-bg" onerror="this.remove()">
                                    <img src="{{ productImageUrl($slide->image) }}" alt="" class="lp-slide-img" onerror="this.remove()">
                                @else
                                    <i class="bi {{ \App\Support\Categories::icon($slide->category) }} lp-slide-icon"></i>
                                @endif
                                <a href="{{ route('product.details', $slide->id) }}" class="lp-art-chip" tabindex="-1">
                                    <span>
                                        <strong>{{ \Illuminate\Support\Str::limit($slide->name, 28) }}</strong>
                                        <small>{{ $featuredShops->get($slide->seller_id)['name'] ?? 'Just listed' }}</small>
                                    </span>
                                    <b>₱{{ number_format($slide->price) }}</b>
                                </a>
                            </div>
                        @endforeach
                        @if ($heroProducts->count() > 1)
                            <div class="lp-slide-dots">
                                @foreach ($heroProducts as $slide)
                                    <span class="{{ $loop->first ? 'is-active' : '' }}"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="lp-art-tile is-plain">
                        <i class="bi bi-wallet2 lp-float"></i>
                        <strong>Pay your way</strong>
                        <span>GCash · Maya · Card · COD</span>
                    </div>
                    <div class="lp-art-tile is-accent">
                        <span class="lp-road" aria-hidden="true"><i class="bi bi-truck"></i></span>
                        <strong>Free shipping</strong>
                        <span>on orders ₱{{ number_format($freeMin) }} and up</span>
                    </div>
                </div>
            </section>
        </div>

        <section class="lp-promises" aria-label="Why BoomBuy">
            <div class="lp-wrap">
                <div class="lp-promise-card">
                    <div class="lp-promise bb-reveal"><span class="lp-promise-icon"><i class="bi bi-wallet2"></i></span><div><strong>Flexible payment</strong><span>GCash, Maya, card or cash on delivery</span></div></div>
                    <div class="lp-promise bb-reveal"><span class="lp-promise-icon is-truck"><i class="bi bi-truck"></i></span><div><strong>Free shipping</strong><span>On orders ₱{{ number_format($freeMin) }} and up</span></div></div>
                    <div class="lp-promise bb-reveal"><span class="lp-promise-icon is-return"><i class="bi bi-arrow-counterclockwise"></i></span><div><strong>7-day returns</strong><span>Changed your mind? Send it back</span></div></div>
                    <div class="lp-promise bb-reveal"><span class="lp-promise-icon is-check"><i class="bi bi-patch-check"></i></span><div><strong>Verified sellers</strong><span>Every shop is reviewed by our team</span></div></div>
                </div>
            </div>
        </section>

        </div>

        <section class="lp-section" id="categories">
            <div class="lp-wrap">
                <div class="lp-head">
                    <div>
                        <h2 class="lp-h2">Shop by category</h2>
                        <p class="lp-sub">16 categories, each from sellers who specialise in it.</p>
                    </div>
                    <a href="{{ route('products') }}" class="lp-more">See all categories →</a>
                </div>

                <div class="lp-cats">
                    @foreach ($landingCats as $slug => $label)
                        <a href="{{ route('products', ['category' => $slug]) }}" class="lp-cat bb-reveal">
                            <span class="lp-cat-media">
                                <i class="bi {{ \App\Support\Categories::ICONS[$slug] ?? 'bi-box-seam-fill' }}" aria-hidden="true"></i>
                                {{-- Drop public/images/categories/<slug>.jpg to show a photo here. --}}
                                <img src="{{ asset('images/categories/' . $slug . '.jpg') }}" alt="" loading="lazy" onerror="this.remove()">
                            </span>
                            <strong>{{ $label }}</strong>
                            <small>{{ $catNotes[$slug] ?? '' }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lp-section" id="featured">
            <div class="lp-wrap">
                <div class="lp-head">
                    <div>
                        <h2 class="lp-h2">Just listed</h2>
                        <p class="lp-sub">The newest products from our sellers.</p>
                    </div>
                    <a href="{{ route('products') }}" class="lp-more">Browse the shop →</a>
                </div>

                <div class="lp-products">
                    @forelse ($featuredProducts as $product)
                        <a href="{{ route('product.details', $product->id) }}" class="lp-product bb-reveal">
                            <span class="lp-product-media">
                                <i class="bi {{ \App\Support\Categories::icon($product->category) }}" aria-hidden="true"></i>
                                @if ($product->image)
                                    <img src="{{ productImageUrl($product->image) }}" alt="" loading="lazy" onerror="this.remove()">
                                @endif
                                @if ((float) $product->price >= $freeMin)
                                    <span class="lp-free">Free shipping</span>
                                @endif
                            </span>
                            <span class="lp-product-body">
                                <small>{{ $featuredShops->get($product->seller_id)['name'] ?? $product->category }}</small>
                                <strong>{{ $product->name }}</strong>
                                <b>₱{{ number_format($product->price, 2) }}</b>
                            </span>
                        </a>
                    @empty
                        <p class="lp-empty">New products are on the way. Check back soon!</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="lp-journey" aria-labelledby="journey-title">
            <div class="lp-wrap">
                <div class="lp-journey-copy">
                    <span class="lp-eyebrow">From cart to your door</span>
                    <h2 id="journey-title" class="lp-h2">Know where your order is, every step of the way.</h2>
                    <p>Pay the way you like, then follow your parcel from your Orders page, from the seller’s shelf to your door. Not right? You have 7 days to return it.</p>
                    <a href="{{ route('products') }}" class="lp-btn lp-btn-primary" style="align-self: flex-start;">Start shopping</a>
                </div>

                <div class="lp-journey-flow" data-journey>
                    {{-- Shown finished without JavaScript; the script replays it step by step. --}}
                    <ol class="lp-steps">
                        <li class="lp-step is-done">
                            <button type="button" class="lp-step-btn" data-step="0">
                                <span class="lp-step-top"><span class="lp-step-icon"><i class="bi bi-cart3"></i></span><span class="lp-step-line"><span class="lp-step-fill"></span></span></span>
                                <small>Step 1</small>
                                <strong>Place your order</strong>
                                <span class="lp-step-text">Check out with GCash, Maya, card or cash on delivery.</span>
                            </button>
                        </li>
                        <li class="lp-step is-done">
                            <button type="button" class="lp-step-btn" data-step="1">
                                <span class="lp-step-top"><span class="lp-step-icon"><i class="bi bi-box-seam"></i></span><span class="lp-step-line"><span class="lp-step-fill"></span></span></span>
                                <small>Step 2</small>
                                <strong>Seller packs it</strong>
                                <span class="lp-step-text">A verified seller prepares your order for pickup.</span>
                            </button>
                        </li>
                        <li class="lp-step is-done">
                            <button type="button" class="lp-step-btn" data-step="2">
                                <span class="lp-step-top"><span class="lp-step-icon"><i class="bi bi-truck"></i></span><span class="lp-step-line"><span class="lp-step-fill"></span></span></span>
                                <small>Step 3</small>
                                <strong>Rider delivers</strong>
                                <span class="lp-step-text">A BoomBuy rider brings it straight to your door.</span>
                            </button>
                        </li>
                        <li class="lp-step is-current">
                            <button type="button" class="lp-step-btn" data-step="3">
                                <span class="lp-step-top"><span class="lp-step-icon"><i class="bi bi-house-heart"></i></span><span class="lp-step-line"><span class="lp-step-fill"></span></span></span>
                                <small>Step 4</small>
                                <strong>Receive and enjoy</strong>
                                <span class="lp-step-text">Confirm you got it. You have 7 days to return it.</span>
                            </button>
                        </li>
                    </ol>

                    <div class="lp-sample" aria-live="polite">
                        <span class="lp-sample-thumb" data-sample><i class="bi bi-check2-circle" data-sample-icon></i></span>
                        <span class="lp-sample-info" data-sample>
                            <small>Example order</small>
                            <strong data-sample-title>Delivered to your door</strong>
                        </span>
                        <span class="lp-sample-status is-ok" data-sample data-sample-status>Delivered</span>
                        <span class="lp-sample-pay" data-sample><small data-sample-pay-label>Paid with</small><b class="is-ok" data-sample-pay>GCash ✓</b></span>
                        <span class="lp-sample-bar" aria-hidden="true"><span data-sample-bar></span></span>
                    </div>
                </div>
            </div>
        </section>

        <div class="lp-wrap">
            <section class="lp-join" aria-label="Join BoomBuy">
                <a href="{{ route('seller.register') }}" class="lp-join-card is-sell" id="sell">
                    <small>For sellers</small>
                    <strong>Grow your shop with BoomBuy</strong>
                    <p>Register your shop, list your products and let our riders handle the delivery.</p>
                    <em>Become a seller →</em>
                </a>
                <a href="{{ route('rider.apply') }}" class="lp-join-card is-ride" id="ride">
                    <small>For riders</small>
                    <strong>Deliver on your own schedule</strong>
                    <p>Flexible hours and deliveries near you. Apply with your license and vehicle details.</p>
                    <em>Apply as a rider →</em>
                </a>
            </section>
        </div>

    </main>

    <script>
        // The first screen fills the window below the navbar (its height varies).
        (function () {
            var nav = document.querySelector('.bb-navbar');
            if (!nav) return;
            function measure() {
                document.documentElement.style.setProperty('--lp-nav', nav.offsetHeight + 'px');
            }
            measure();
            window.addEventListener('resize', measure);
        })();
    </script>

    @include('partials.buyer-footer')

    <script>
        // Cards rise in as they scroll into view.
        (function () {
            var els = document.querySelectorAll('.bb-reveal');
            if (!('IntersectionObserver' in window)) {
                els.forEach(function (el) { el.classList.add('bb-in-view'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('bb-in-view');
                        io.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -40px 0px' });
            els.forEach(function (el, i) {
                el.style.transitionDelay = (i % 8) * 60 + 'ms';
                io.observe(el);
            });
        })();
    </script>

    <script>
        // Hero: the newest products take turns, and the cards lean toward the mouse.
        (function () {
            var art = document.querySelector('[data-hero-art]');
            if (!art) return;
            var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var slides = art.querySelectorAll('[data-hero-slide]');
            var dots = art.querySelectorAll('.lp-slide-dots span');
            var current = 0;

            if (slides.length > 1 && !still) {
                setInterval(function () {
                    if (document.hidden) return;
                    slides[current].classList.remove('is-active');
                    if (dots[current]) dots[current].classList.remove('is-active');
                    current = (current + 1) % slides.length;
                    slides[current].classList.add('is-active');
                    if (dots[current]) dots[current].classList.add('is-active');
                }, 4500);
            }

            if (still || !window.matchMedia('(hover: hover) and (min-width: 900px)').matches) return;

            art.addEventListener('mousemove', function (e) {
                var r = art.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - 0.5;
                var y = (e.clientY - r.top) / r.height - 0.5;
                art.style.transform = 'perspective(1200px) rotateY(' + (x * 6) + 'deg) rotateX(' + (-y * 6) + 'deg)';
            });

            art.addEventListener('mouseleave', function () {
                art.style.transform = '';
            });
        })();
    </script>

    <script>
        // "From cart to your door": the steps light up one by one and the example
        // order underneath follows. Clicking a step jumps to it.
        (function () {
            var flow = document.querySelector('[data-journey]');
            if (!flow) return;

            var list = flow.querySelector('.lp-steps');
            var steps = flow.querySelectorAll('.lp-step');
            var sample = flow.querySelector('.lp-sample');
            var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var DWELL = 2600, HOLD = 3800;

            var states = [
                { icon: 'bi-cart-check', title: 'Order placed', status: 'Pending', tone: 'is-waiting' },
                { icon: 'bi-box-seam', title: 'Packed by the seller', status: 'Ready for pickup', tone: '' },
                { icon: 'bi-truck', title: 'On its way to you', status: 'Out for delivery', tone: '' },
                { icon: 'bi-check2-circle', title: 'Delivered to your door', status: 'Delivered', tone: 'is-ok' }
            ];

            // A different way to pay on each replay.
            var methods = [
                { name: 'GCash', cod: false },
                { name: 'Maya', cod: false },
                { name: 'Card', cod: false },
                { name: 'Cash on delivery', cod: true }
            ];
            var method = 0;

            function payFor(index) {
                var m = methods[method];
                if (index === states.length - 1) {
                    return { label: 'Paid with', value: (m.cod ? 'Cash' : m.name) + ' ✓', ok: true };
                }
                if (m.cod) {
                    return { label: 'Paying with', value: index === 2 ? 'Cash, at the door' : 'Cash on delivery', ok: false };
                }
                return { label: 'Paid with', value: m.name, ok: false };
            }

            var el = {
                icon: flow.querySelector('[data-sample-icon]'),
                title: flow.querySelector('[data-sample-title]'),
                status: flow.querySelector('[data-sample-status]'),
                payLabel: flow.querySelector('[data-sample-pay-label]'),
                pay: flow.querySelector('[data-sample-pay]'),
                bar: flow.querySelector('[data-sample-bar]')
            };

            var current = 3, timer = null, playing = false;
            list.style.setProperty('--lp-dwell', DWELL + 'ms');

            function show(index) {
                current = index;
                steps.forEach(function (step, i) {
                    step.classList.toggle('is-done', i < index);
                    step.classList.toggle('is-current', i === index);
                });

                var st = states[index];
                el.icon.className = 'bi ' + st.icon;
                el.title.textContent = st.title;
                el.status.textContent = st.status;
                el.status.className = 'lp-sample-status ' + st.tone;
                var pay = payFor(index);
                el.payLabel.textContent = pay.label;
                el.pay.textContent = pay.value;
                el.pay.classList.toggle('is-ok', pay.ok);
                el.bar.style.width = ((index + 1) / states.length * 100) + '%';

                sample.classList.remove('is-swapping');
                void sample.offsetWidth;
                sample.classList.add('is-swapping');
            }

            function next() {
                if (current >= states.length - 1) method = (method + 1) % methods.length;
                show(current >= states.length - 1 ? 0 : current + 1);
                schedule();
            }

            function schedule() {
                clearTimeout(timer);
                if (!playing) return;
                timer = setTimeout(function () {
                    if (document.hidden) { schedule(); return; }
                    next();
                }, current === states.length - 1 ? HOLD : DWELL);
            }

            function start() {
                if (playing || still) return;
                playing = true;
                list.classList.add('is-playing');
                show(0);
                schedule();
            }

            flow.querySelectorAll('[data-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    show(Number(button.getAttribute('data-step')));
                    schedule();
                });
            });

            if (still) return;

            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    if (entries[0].isIntersecting) { start(); io.disconnect(); }
                }, { threshold: 0.35 });
                io.observe(flow);
            } else {
                start();
            }
        })();
    </script>

    @include('partials.pwa-register')
</body>

</html>
