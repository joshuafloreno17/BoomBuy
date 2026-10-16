{{--
    Promo hero: the big rotating banner + two small tiles beside it, at the top
    of the landing page and the buyer home. Content comes from Admin → Promo
    Banners; empty spots get automatic slides (App\Support\PromoHero).

    Needs: $promo (ViewServiceProvider), $layout = 'wide' (buyer home) | 'stacked' (landing hero column).
--}}
@php
    $layout = $layout ?? 'wide';
    $slides = $promo['main'];
    $tiles = [$promo['top'], $promo['bottom']];
@endphp

@once
<link rel="stylesheet" href="{{ vasset('css/partials/promo-hero.css') }}">
@endonce

<section class="ph ph-{{ $layout }}" aria-label="Promotions">

    <div class="ph-main" data-ph-carousel aria-roledescription="carousel" aria-label="Featured promotions">
        <div class="ph-track">
            @foreach($slides as $i => $s)
                <a
                    href="{{ $s['url'] }}"
                    class="ph-slide ph-{{ $s['style'] }} {{ $i === 0 ? 'is-active' : '' }}"
                    role="group"
                    aria-roledescription="slide"
                    aria-label="{{ $i + 1 }} of {{ count($slides) }}: {{ $s['title'] }}"
                    data-ph-slide
                    @if($i > 0) aria-hidden="true" tabindex="-1" @endif
                >
                    <span class="ph-orb is-a" aria-hidden="true"></span>
                    <span class="ph-orb is-b" aria-hidden="true"></span>

                    <span class="ph-copy">
                        @if($s['eyebrow'])
                            <span class="ph-eyebrow">{{ $s['eyebrow'] }}</span>
                        @endif
                        <strong class="ph-title">{{ $s['title'] }}</strong>
                        @if($s['text'])
                            <span class="ph-text">{{ $s['text'] }}</span>
                        @endif
                        <span class="ph-cta">{{ $s['cta'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </span>

                    @if($s['image'])
                        <span class="ph-art ph-photo" aria-hidden="true"><img src="{{ $s['image'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" onerror="this.parentNode.remove()"></span>
                    @elseif($s['big'])
                        <span class="ph-art ph-big" aria-hidden="true">{{ $s['big'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        @if(count($slides) > 1)
            <button type="button" class="ph-arrow is-prev" data-ph-prev aria-label="Previous promotion"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            <button type="button" class="ph-arrow is-next" data-ph-next aria-label="Next promotion"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <div class="ph-dots">
                @foreach($slides as $i => $s)
                    <button type="button" class="{{ $i === 0 ? 'is-active' : '' }}" data-ph-dot="{{ $i }}" aria-label="Show promotion {{ $i + 1 }}" @if($i === 0) aria-current="true" @endif></button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="ph-side">
        @foreach($tiles as $t)
            <a href="{{ $t['url'] }}" class="ph-tile ph-{{ $t['style'] }}">
                <span class="ph-copy">
                    @if($t['eyebrow'])
                        <span class="ph-eyebrow">{{ $t['eyebrow'] }}</span>
                    @endif
                    <strong class="ph-title">{{ $t['title'] }}</strong>
                    @if($t['text'])
                        <span class="ph-text">{{ $t['text'] }}</span>
                    @endif
                </span>
                <span class="ph-cta">{{ $t['cta'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span>

                @if($t['image'])
                    <span class="ph-tile-photo" aria-hidden="true"><img src="{{ $t['image'] }}" alt="" loading="lazy" onerror="this.parentNode.remove()"></span>
                @elseif($t['big'])
                    <span class="ph-tile-big" aria-hidden="true">{{ $t['big'] }}</span>
                @endif
            </a>
        @endforeach
    </div>

</section>

@once
<script>
    // Promo hero carousel: fades every 6s, arrows and dots, pauses on hover/focus or a hidden tab.
    document.querySelectorAll('[data-ph-carousel]').forEach(function (root) {
        var slides = root.querySelectorAll('[data-ph-slide]');
        var dots = root.querySelectorAll('[data-ph-dot]');
        if (slides.length < 2) return;

        var current = 0, timer = null, paused = false;
        var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function show(n) {
            current = (n + slides.length) % slides.length;
            slides.forEach(function (s, i) {
                var on = i === current;
                s.classList.toggle('is-active', on);
                s.setAttribute('aria-hidden', on ? 'false' : 'true');
                s.tabIndex = on ? 0 : -1;
            });
            dots.forEach(function (d, i) {
                d.classList.toggle('is-active', i === current);
                if (i === current) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
            });
        }

        function restart() {
            clearInterval(timer);
            if (still) return;
            timer = setInterval(function () {
                if (!paused && !document.hidden) show(current + 1);
            }, 6000);
        }

        root.querySelector('[data-ph-prev]').addEventListener('click', function () { show(current - 1); restart(); });
        root.querySelector('[data-ph-next]').addEventListener('click', function () { show(current + 1); restart(); });
        dots.forEach(function (d) {
            d.addEventListener('click', function () { show(parseInt(d.getAttribute('data-ph-dot'), 10)); restart(); });
        });

        root.addEventListener('mouseenter', function () { paused = true; });
        root.addEventListener('mouseleave', function () { paused = false; });
        root.addEventListener('focusin', function () { paused = true; });
        root.addEventListener('focusout', function () { paused = false; });

        // Swipe on phones.
        var startX = null;
        root.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        root.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) { show(current + (dx < 0 ? 1 : -1)); restart(); }
            startX = null;
        });

        restart();
    });
</script>
@endonce
