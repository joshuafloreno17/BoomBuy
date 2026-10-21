<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ vasset('css/views/categories.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'categories'])

    @php
        // A tint per category (background, accent) so the tiles don't all look alike.
        $tints = [
            'electronics' => ['#e8f0ff', '#2f5bd3'],
            'womens-fashion' => ['#ffe9f3', '#c2337a'],
            'beauty-personal-care' => ['#fdebf0', '#c23a5b'],
            'home-living' => ['#fff3e2', '#b8650b'],
            'mens-fashion' => ['#e9eefb', '#34508f'],
            'shoes' => ['#efe9ff', '#6d4ad1'],
            'food-beverages' => ['#fff0e0', '#c25a10'],
            'kids-baby' => ['#e6f7f6', '#0b7f76'],
            'sports-outdoors' => ['#e7f6ec', '#1d7a43'],
            'jewelry-accessories' => ['#fbf3dc', '#9a6b00'],
            'toys-games-hobbies' => ['#fdeee6', '#c24a1f'],
            'pet-supplies' => ['#f3ede4', '#8a5a2b'],
            'automotive' => ['#eceef2', '#3c4658'],
            'office-school' => ['#e8f4fc', '#1f6f9e'],
            'tools-home-improvement' => ['#f6efe1', '#8f5f12'],
            'garden-outdoor' => ['#eaf6e3', '#3f7d1f'],
        ];
        $tintStyle = fn ($slug) => '--tint:' . ($tints[$slug][0] ?? '#eee8ff') . ';--tint-ink:' . ($tints[$slug][1] ?? '#6d4ad1');

        $top = $categories->take(4);
        $rest = $categories->slice(4);
        $heroPhotos = $categories->flatMap(fn ($c) => array_slice($c['photos'], 0, 1))->take(3)->values();
        $peso = fn ($n) => '₱' . number_format($n, $n == floor($n) ? 0 : 2);
        $meta = fn ($c) => $c['count']
            ? number_format($c['count']) . ' ' . Str::plural('product', $c['count']) . ' · ' . $c['shops'] . ' ' . Str::plural('shop', $c['shops'])
            : 'Coming soon';
    @endphp

    <main class="cx">

        {{-- Hero --}}
        <section class="cx-hero">
            <div class="cx-hero-copy">
                <span class="cx-pill"><i class="bi bi-fire"></i> Best sellers first</span>
                <h1>What are you <span>shopping for</span> today?</h1>
                <p>{{ count($categories) }} categories, each from sellers who specialise in it. Start with what everyone's buying, or jump straight to yours.</p>

                <div class="cx-stats">
                    <div><strong>{{ number_format($totalProducts) }}</strong><span>products</span></div>
                    <div><strong>{{ number_format($totalShops) }}</strong><span>verified shops</span></div>
                    <div><strong>{{ count($categories) }}</strong><span>categories</span></div>
                </div>
            </div>

            @if($heroPhotos->isNotEmpty())
                <div class="cx-hero-art" aria-hidden="true">
                    @foreach($heroPhotos as $i => $photo)
                        <span class="cx-float is-{{ $i + 1 }}"><img src="{{ $photo['url'] }}" alt="" loading="eager"></span>
                    @endforeach
                    <span class="cx-spark is-a"><i class="bi bi-stars"></i></span>
                    <span class="cx-spark is-b"><i class="bi bi-bag-heart-fill"></i></span>
                </div>
            @endif
        </section>

        {{-- Quick jump: every category as an icon --}}
        <nav class="cx-jump" aria-label="Jump to a category">
            @foreach($categories as $c)
                <a href="{{ route('products', ['category' => $c['slug']]) }}" class="cx-jump-item {{ $c['count'] ? '' : 'is-empty' }}" style="{{ $tintStyle($c['slug']) }}">
                    <span class="cx-jump-icon"><i class="bi {{ $c['icon'] }}" aria-hidden="true"></i></span>
                    <span class="cx-jump-label">{{ $c['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- The four best-selling categories --}}
        <section class="cx-section">
            <div class="cx-head">
                <div>
                    <span class="cx-eyebrow"><i class="bi bi-trophy-fill"></i> Top categories</span>
                    <h2>What everyone's buying</h2>
                </div>
                <a href="{{ route('products', ['sort' => 'best_selling']) }}" class="cx-more">Shop best sellers <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="cx-top">
                @foreach($top as $i => $c)
                    <article class="cx-feature {{ $i === 0 ? 'is-lead' : '' }} cx-reveal" style="{{ $tintStyle($c['slug']) }}">
                        <div class="cx-feature-photos is-{{ min(count($c['photos']), $i === 0 ? 4 : 3) }}">
                            @forelse(array_slice($c['photos'], 0, $i === 0 ? 4 : 3) as $photo)
                                <span><img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}" loading="lazy"></span>
                            @empty
                                <span class="cx-noimg"><i class="bi {{ $c['icon'] }}"></i></span>
                            @endforelse
                        </div>

                        <div class="cx-feature-body">
                            <span class="cx-rank">#{{ $i + 1 }}</span>
                            <div class="cx-title">
                                <span class="cx-icon"><i class="bi {{ $c['icon'] }}" aria-hidden="true"></i></span>
                                <div>
                                    <h3><a href="{{ route('products', ['category' => $c['slug']]) }}" class="cx-link">{{ $c['label'] }}</a></h3>
                                    <p>{{ $c['tagline'] }}</p>
                                </div>
                            </div>

                            <div class="cx-meta">
                                <span>{{ $meta($c) }}</span>
                                @if($c['from'] !== null)<span class="cx-from">from <strong>{{ $peso($c['from']) }}</strong></span>@endif
                            </div>

                            @if($c['shelves'])
                                <div class="cx-shelves">
                                    @foreach($c['shelves'] as $shelf)
                                        <a href="{{ $shelf['url'] }}">{{ $shelf['name'] }}</a>
                                    @endforeach
                                </div>
                            @endif

                            <span class="cx-cta">Shop {{ $c['label'] }} <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Everything else, still in best-seller order --}}
        <section class="cx-section">
            <div class="cx-head">
                <div>
                    <span class="cx-eyebrow"><i class="bi bi-compass-fill"></i> More to explore</span>
                    <h2>Every other category</h2>
                </div>
                <a href="{{ route('products') }}" class="cx-more">Browse all products <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="cx-grid">
                @foreach($rest as $c)
                    <article class="cx-card {{ $c['count'] ? '' : 'is-empty' }} cx-reveal" style="{{ $tintStyle($c['slug']) }}">
                        <div class="cx-card-photos">
                            @forelse(array_slice($c['photos'], 0, 3) as $photo)
                                <span><img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}" loading="lazy"></span>
                            @empty
                                <span class="cx-noimg"><i class="bi {{ $c['icon'] }}"></i></span>
                            @endforelse
                        </div>
                        <div class="cx-card-body">
                            <div class="cx-title">
                                <span class="cx-icon"><i class="bi {{ $c['icon'] }}" aria-hidden="true"></i></span>
                                <div>
                                    <h3><a href="{{ route('products', ['category' => $c['slug']]) }}" class="cx-link">{{ $c['label'] }}</a></h3>
                                    <p>{{ $c['tagline'] }}</p>
                                </div>
                            </div>
                            <div class="cx-meta">
                                <span>{{ $meta($c) }}</span>
                                @if($c['from'] !== null)<span class="cx-from">from <strong>{{ $peso($c['from']) }}</strong></span>@endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

    </main>

    @include('partials.buyer-footer')

    <script>
        // Tiles rise in as they scroll into view.
        (function () {
            var tiles = document.querySelectorAll('.cx-reveal');
            if (!('IntersectionObserver' in window)) {
                tiles.forEach(function (t) { t.classList.add('is-in'); });
                return;
            }
            var seen = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        seen.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -40px 0px' });
            tiles.forEach(function (t) { seen.observe(t); });
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>
