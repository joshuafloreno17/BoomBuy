{{--
    Buyer home hero: a rotating banner (announcement → new arrivals → free
    shipping → cash on delivery), with the landing page's motion: drifting
    glow, floating cards, fading text and a shining button.

    Needs: $announcement (PlatformAnnouncement|null), $latest (dashboard product cards).
--}}
@php
    $freeMin = \App\Support\DeliveryFee::FREE_SHIPPING_MIN;
    $baseFee = \App\Support\DeliveryFee::baseFee();
    $fresh = collect($latest)->take(3)->values();

    $slides = [];

    if ($announcement) {
        $slides[] = [
            'key' => 'announcement',
            'theme' => 'peach',
            'eyebrow' => '<i class="bi bi-megaphone-fill"></i> Announcement',
            'title' => e($announcement->title),
            'text' => e(\Illuminate\Support\Str::limit($announcement->message, 170)),
            'cta' => ['Start shopping', route('products')],
            'tiles' => [['bi-megaphone-fill', 'white'], ['bi-bell-fill', 'ink'], ['bi-stars', 'cream']],
        ];
    }

    if ($fresh->isNotEmpty()) {
        $names = $fresh->pluck('name')->map(fn ($n) => e(\Illuminate\Support\Str::limit($n, 28)));

        $slides[] = [
            'key' => 'new',
            'theme' => 'sky',
            'eyebrow' => '<i class="bi bi-lightning-charge-fill"></i> Just listed',
            'title' => 'Fresh in the shop.',
            'text' => $names->count() > 1
                ? $names->slice(0, -1)->implode(', ') . ' and ' . $names->last() . ' — new from BoomBuy sellers.'
                : $names->first() . ' — new from a BoomBuy seller.',
            'cta' => ['See what\'s new', route('products', ['sort' => 'newest'])],
            'products' => $fresh,
        ];
    }

    $slides[] = [
        'key' => 'shipping',
        'theme' => 'mint',
        'eyebrow' => '<i class="bi bi-truck"></i> Free delivery',
        'title' => 'Free shipping from ₱' . number_format($freeMin) . '.',
        'text' => 'Orders of ₱' . number_format($freeMin) . ' or more from one shop ship free'
            . ($baseFee > 0 ? ' — under that, delivery is just ₱' . number_format($baseFee) . '.' : '.'),
        'cta' => ['Start shopping', route('products')],
        'tiles' => [['bi-box-seam-fill', 'white'], ['bi-truck', 'ink'], ['bi-check2-circle', 'cream']],
    ];

    $slides[] = [
        'key' => 'cod',
        'theme' => 'cream',
        'eyebrow' => '<i class="bi bi-cash-coin"></i> Cash on Delivery',
        'title' => 'Pay when it arrives.',
        'text' => 'Every order supports Cash on Delivery. Pay the rider when your parcel reaches your door.',
        'cta' => ['Start shopping', route('products')],
        'tiles' => [['bi-house-door-fill', 'white'], ['bi-cash-stack', 'ink'], ['bi-shield-check', 'cream']],
    ];
@endphp

@once
<style>
    .dash-hero.bb-carousel {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        background: transparent;
        isolation: isolate;
    }

    /* Every slide sits in the same grid cell; only the active one shows. */
    .bb-slide {
        grid-area: 1 / 1;
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
        min-height: 250px;
        border-radius: inherit;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transition: opacity .6s ease, visibility 0s linear .6s;
    }

    .bb-slide.is-active {
        opacity: 1;
        visibility: visible;
        transition: opacity .6s ease;
        z-index: 1;
    }

    .bb-slide.theme-peach { background: linear-gradient(120deg, #ffe7de 0%, #ffd9cb 100%); }
    .bb-slide.theme-sky   { background: linear-gradient(120deg, #e8eeff 0%, #ffe7de 100%); }
    .bb-slide.theme-mint  { background: linear-gradient(120deg, #e3f7ee 0%, #fff1e6 100%); }
    .bb-slide.theme-cream { background: linear-gradient(120deg, #fff4d6 0%, #ffe7de 100%); }

    /* Soft drifting glow, like the landing page hero. */
    .bb-slide-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(36px);
        opacity: .55;
        pointer-events: none;
        animation: bbOrbDrift 14s ease-in-out infinite alternate;
    }

    .bb-slide-orb.a { width: 260px; height: 260px; right: 8%; top: -90px; background: #ff9a76; }
    .bb-slide-orb.b { width: 200px; height: 200px; left: 30%; bottom: -110px; background: #ffd36e; animation-delay: -6s; }
    .theme-sky .bb-slide-orb.a { background: #9fb3ff; }
    .theme-mint .bb-slide-orb.a { background: #7fdcb0; }

    @keyframes bbOrbDrift {
        from { transform: translate(0, 0) scale(1); }
        to   { transform: translate(-40px, 30px) scale(1.15); }
    }

    /* Text comes in with a small rise, one line after another. */
    .bb-slide .dash-hero-text > * {
        opacity: 0;
        transform: translateY(14px);
    }

    .bb-slide.is-active .dash-hero-text > * {
        animation: bbRise .6s cubic-bezier(.2, .7, .2, 1) forwards;
    }

    .bb-slide.is-active .dash-hero-text > *:nth-child(2) { animation-delay: .08s; }
    .bb-slide.is-active .dash-hero-text > *:nth-child(3) { animation-delay: .16s; }
    .bb-slide.is-active .dash-hero-text > *:nth-child(4) { animation-delay: .24s; }

    @keyframes bbRise {
        to { opacity: 1; transform: translateY(0); }
    }

    .bb-slide .dash-hero-eyebrow i { margin-right: 4px; }

    /* Cards pop in, then float. --r keeps each card's own tilt. */
    .bb-slide .dash-hero-tile {
        transform: rotate(var(--r, 0deg)) scale(.85);
        opacity: 0;
        overflow: hidden;
    }

    .bb-slide.is-active .dash-hero-tile {
        animation:
            bbPop .7s cubic-bezier(.2, .9, .3, 1.2) forwards,
            bbFloat 5s ease-in-out .7s infinite;
    }

    .bb-slide.is-active .dash-hero-tile.t2 { animation-delay: .1s, .8s; }
    .bb-slide.is-active .dash-hero-tile.t3 { animation-delay: .2s, .9s; }

    @keyframes bbPop {
        to { opacity: 1; transform: rotate(var(--r, 0deg)) scale(1); }
    }

    @keyframes bbFloat {
        0%, 100% { opacity: 1; transform: rotate(var(--r, 0deg)) translateY(0); }
        50%      { opacity: 1; transform: rotate(var(--r, 0deg)) translateY(-10px); }
    }

    .bb-slide .dash-hero-tile.t1 { --r: -7deg; }
    .bb-slide .dash-hero-tile.t2 { --r: 4deg; }
    .bb-slide .dash-hero-tile.t3 { --r: -3deg; }

    /* The centre card sits in front. */
    .bb-slide .dash-hero-tile.t2 { z-index: 2; }

    .dash-hero-tile.tone-white { background: #fff; color: #e8420f; }
    .dash-hero-tile.tone-ink   { background: var(--ink, #172033); color: #ff9a76; }
    .dash-hero-tile.tone-cream { background: #fff4d6; color: #8a6300; }

    /* "Just listed": real product photos on the cards. */
    .dash-hero-tile.is-photo {
        background: #fff;
        padding: 8px;
        flex-direction: column;
        gap: 6px;
        color: #172033;
        text-decoration: none;
    }

    .dash-hero-tile.is-photo img {
        width: 100%;
        flex: 1;
        min-height: 0;
        object-fit: cover;
        border-radius: 12px;
        background: #f6f1ef;
    }

    .dash-hero-tile.is-photo i {
        flex: 1;
        display: flex;
        align-items: center;
        color: #e8420f;
    }

    .dash-hero-tile.is-photo .tile-price {
        font-size: 12px;
        font-weight: 800;
        color: #c43408;
    }

    /* Shine sweeping across the button. */
    .bb-slide .dash-hero-cta {
        position: relative;
        overflow: hidden;
    }

    .bb-slide .dash-hero-cta::after {
        content: "";
        position: absolute;
        top: 0;
        left: -60%;
        width: 40%;
        height: 100%;
        background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .35), transparent);
        transform: skewX(-20deg);
        animation: bbSweep 3.6s ease-in-out 1.2s infinite;
    }

    @keyframes bbSweep {
        0%, 60% { left: -60%; }
        100%    { left: 130%; }
    }

    /* Dots with a progress fill on the active one. */
    .bb-carousel-dots {
        position: absolute;
        left: 40px;
        bottom: 16px;
        z-index: 2;
        display: flex;
        gap: 6px;
    }

    .bb-dot {
        width: 8px;
        height: 8px;
        border: none;
        border-radius: 999px;
        background: rgba(23, 32, 51, .2);
        cursor: pointer;
        padding: 0;
        overflow: hidden;
        position: relative;
        transition: width .3s ease;
    }

    .bb-dot.is-active {
        width: 34px;
        background: rgba(23, 32, 51, .15);
    }

    .bb-dot.is-active::after {
        content: "";
        position: absolute;
        inset: 0;
        width: 0;
        background: #e8420f;
        border-radius: inherit;
        animation: bbDotFill var(--bb-slide-ms, 6000ms) linear forwards;
    }

    .bb-carousel.is-paused .bb-dot.is-active::after {
        animation-play-state: paused;
    }

    @keyframes bbDotFill {
        to { width: 100%; }
    }

    @media (max-width: 760px) {
        .bb-slide {
            grid-template-columns: minmax(0, 1fr);
            min-height: 230px;
        }

        .bb-carousel-dots {
            left: 22px;
            bottom: 12px;
        }

        .dash-hero-tile.is-photo .tile-price { display: none; }
        .dash-hero-tile.is-photo { padding: 5px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bb-slide-orb,
        .bb-slide.is-active .dash-hero-tile,
        .bb-slide .dash-hero-cta::after,
        .bb-slide.is-active .dash-hero-text > * {
            animation: none !important;
        }

        .bb-slide .dash-hero-text > *,
        .bb-slide .dash-hero-tile {
            opacity: 1;
            transform: rotate(var(--r, 0deg));
        }
    }
</style>
@endonce

<section
    class="dash-hero bb-carousel"
    id="buyerHero"
    aria-roledescription="carousel"
    aria-label="BoomBuy highlights"
    style="--bb-slide-ms: 6000ms;"
>
    @foreach($slides as $i => $slide)
        <div
            class="bb-slide theme-{{ $slide['theme'] }} {{ $i === 0 ? 'is-active' : '' }}"
            role="group"
            aria-roledescription="slide"
            aria-label="{{ $i + 1 }} of {{ count($slides) }}"
            @if($i !== 0) aria-hidden="true" @endif
        >
            <span class="bb-slide-orb a" aria-hidden="true"></span>
            <span class="bb-slide-orb b" aria-hidden="true"></span>

            <div class="dash-hero-text">
                <span class="dash-hero-eyebrow">{!! $slide['eyebrow'] !!}</span>
                <h1>{!! $slide['title'] !!}</h1>
                <p>{!! $slide['text'] !!}</p>
                <a href="{{ $slide['cta'][1] }}" class="dash-hero-cta" @if($i !== 0) tabindex="-1" @endif>
                    {{ $slide['cta'][0] }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="dash-hero-art" aria-hidden="true">
                @if(!empty($slide['products']))
                    @php
                        // Newest product in the front (centre) card; with fewer than
                        // three, the side cards are decoration until more arrive.
                        $spots = ['t2', 't1', 't3'];
                        $fillers = [['bi-bag-heart-fill', 'cream'], ['bi-stars', 'ink']];
                    @endphp
                    @foreach($spots as $s => $spot)
                        @if(!isset($slide['products'][$s]))
                            @php [$fillIcon, $fillTone] = $fillers[$s - 1] ?? $fillers[0]; @endphp
                            <span class="dash-hero-tile {{ $spot }} tone-{{ $fillTone }}"><i class="bi {{ $fillIcon }}"></i></span>
                            @continue
                        @endif
                        @php $product = $slide['products'][$s]; @endphp
                        <a href="{{ route('product.details', $product['slug']) }}" class="dash-hero-tile is-photo {{ $spot }}" tabindex="-1">
                            @if($product['image'])
                                <img src="{{ $product['image'] }}" alt="" loading="lazy">
                            @else
                                <i class="bi {{ $product['icon'] }}"></i>
                            @endif
                            <span class="tile-price">₱{{ number_format($product['price']) }}</span>
                        </a>
                    @endforeach
                @else
                    @foreach($slide['tiles'] as $n => [$icon, $tone])
                        <span class="dash-hero-tile t{{ $n + 1 }} tone-{{ $tone }}"><i class="bi {{ $icon }}"></i></span>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach

    @if(count($slides) > 1)
        <div class="bb-carousel-dots" role="tablist" aria-label="Choose a slide">
            @foreach($slides as $i => $slide)
                <button
                    type="button"
                    class="bb-dot {{ $i === 0 ? 'is-active' : '' }}"
                    role="tab"
                    aria-label="Show slide {{ $i + 1 }}"
                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    data-slide="{{ $i }}"
                ></button>
            @endforeach
        </div>
    @endif
</section>

<script>
(function () {
    var root = document.getElementById('buyerHero');
    if (!root) return;

    var slides = Array.prototype.slice.call(root.querySelectorAll('.bb-slide'));
    var dots = Array.prototype.slice.call(root.querySelectorAll('.bb-dot'));
    if (slides.length < 2) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var delay = 6000;
    var current = 0;
    var timer = null;
    var paused = false;

    function show(index) {
        current = (index + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            var on = i === current;
            slide.classList.toggle('is-active', on);
            slide.setAttribute('aria-hidden', on ? 'false' : 'true');
            var cta = slide.querySelector('.dash-hero-cta');
            if (cta) cta.tabIndex = on ? 0 : -1;
        });

        dots.forEach(function (dot, i) {
            var on = i === current;
            dot.classList.toggle('is-active', on);
            dot.setAttribute('aria-selected', on ? 'true' : 'false');
            // Restart the progress fill.
            if (on) { dot.classList.remove('is-active'); void dot.offsetWidth; dot.classList.add('is-active'); }
        });
    }

    function schedule() {
        clearTimeout(timer);
        if (paused || reduceMotion || document.hidden) return;
        timer = setTimeout(function () { show(current + 1); schedule(); }, delay);
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(parseInt(dot.dataset.slide, 10));
            schedule();
        });
    });

    // Hold still while the buyer is reading or using it.
    function pause() { paused = true; root.classList.add('is-paused'); clearTimeout(timer); }
    function resume() { paused = false; root.classList.remove('is-paused'); schedule(); }

    root.addEventListener('mouseenter', pause);
    root.addEventListener('mouseleave', resume);
    root.addEventListener('focusin', pause);
    root.addEventListener('focusout', resume);
    document.addEventListener('visibilitychange', schedule);

    // Swipe on phones.
    var startX = null;
    root.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        startX = null;
        if (Math.abs(dx) > 40) { show(current + (dx < 0 ? 1 : -1)); schedule(); }
    });

    if (reduceMotion) root.classList.add('is-paused');
    schedule();
})();
</script>
