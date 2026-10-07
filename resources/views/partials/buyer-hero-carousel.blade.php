{{--
    Buyer home hero: a rotating banner (announcement → new arrivals → free
    shipping → payment options), with the landing page's motion: drifting
    glow, floating cards, fading text and a shining button.

    Needs: $announcement (PlatformAnnouncement|null), $latest (dashboard product cards).
--}}
@php
    $freeMin = \App\Support\DeliveryFee::freeShippingMin();
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
        'eyebrow' => '<i class="bi bi-wallet2"></i> Flexible payment',
        'title' => 'Pay your way.',
        'text' => 'GCash, Maya, card or cash on delivery. Pick what works for you at checkout.',
        'cta' => ['Start shopping', route('products')],
        'tiles' => [['bi-phone-fill', 'white'], ['bi-wallet2', 'ink'], ['bi-credit-card-fill', 'cream']],
    ];
@endphp

@once
<link rel="stylesheet" href="{{ vasset('css/partials/buyer-hero-carousel.css') }}">
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
