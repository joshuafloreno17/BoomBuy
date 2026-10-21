<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filters['category_label'] ?? 'Shop' }} — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ vasset('css/views/products.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'shop'])

    @php
        $total = $paginator->total();
        $hasFilters = $filters['min'] !== null || $filters['max'] !== null || $filters['rating'] || $filters['in_stock'] || $filters['deals'] || $filters['location'] !== '';
        $filterCount = (int) ($filters['min'] !== null || $filters['max'] !== null) + (int) (bool) $filters['rating'] + (int) $filters['in_stock'] + (int) $filters['deals'] + (int) ($filters['location'] !== '');
        $peso = fn ($n) => '₱' . number_format($n);

        // Links that change one thing and keep the rest (always back to page 1).
        $with = fn (array $changes) => request()->fullUrlWithQuery(array_merge(['page' => null], $changes));
        $clearFilters = $with(['min' => null, 'max' => null, 'rating' => null, 'in_stock' => null, 'deals' => null, 'location' => null]);

        $priceLabel = match (true) {
            $filters['min'] !== null && $filters['max'] !== null => $peso($filters['min']) . '–' . $peso($filters['max']),
            $filters['min'] !== null => $peso($filters['min']) . ' and up',
            $filters['max'] !== null => 'Up to ' . $peso($filters['max']),
            default => null,
        };

        $pricePicks = [
            ['label' => 'Under ₱500', 'min' => null, 'max' => 500],
            ['label' => '₱500–₱1,000', 'min' => 500, 'max' => 1000],
            ['label' => '₱1,000–₱3,000', 'min' => 1000, 'max' => 3000],
            ['label' => '₱3,000 & up', 'min' => 3000, 'max' => null],
        ];

        $heading = $filters['search'] !== ''
            ? 'Results for “' . $filters['search'] . '”'
            : ($filters['category_label'] ?? 'All products');
    @endphp

    <main class="shop">

        {{-- SHOP HEADER (on /shop/{seller}) --}}
        @if($shop)
            @if($shop['banner'] ?? null)
                <div class="seller-banner"><img src="{{ $shop['banner'] }}" alt="{{ $shop['name'] }} banner"></div>
            @endif
            <section class="seller-hero" aria-label="Shop">
                <span class="seller-hero-avatar">
                    @if($shop['logo'] ?? null)
                        <img src="{{ $shop['logo'] }}" alt="{{ $shop['name'] }} logo">
                    @elseif($shop['photo'])
                        <img src="{{ $shop['photo'] }}" alt="">
                    @else
                        {{ $shop['initial'] }}
                    @endif
                </span>
                <div class="seller-hero-info">
                    <nav class="shop-crumbs" aria-label="Breadcrumb">
                        <a href="{{ route('products') }}">Shop</a> / <span>{{ $shop['name'] }}</span>
                    </nav>
                    <h1>{{ $shop['name'] }}@if($shop['vacation'] ?? false) <span class="seller-vacation"><i class="bi bi-umbrella-fill"></i> On vacation</span>@endif</h1>
                    @if($shop['description'])
                        <p class="seller-hero-about">{{ $shop['description'] }}</p>
                    @endif
                    <div class="seller-hero-meta">
                        @if($shop['rating'])
                            <span><i class="bi bi-star-fill"></i> {{ $shop['rating'] }} ({{ $shop['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $shop['reviews']) }})</span>
                        @else
                            <span>No reviews yet</span>
                        @endif
                        <span><i class="bi bi-box-seam"></i> {{ $shop['products'] }} {{ \Illuminate\Support\Str::plural('product', $shop['products']) }}</span>
                        @if($shop['location'])
                            <span><i class="bi bi-geo-alt"></i> {{ $shop['location'] }}</span>
                        @endif
                        @if($shop['since'])
                            <span><i class="bi bi-calendar3"></i> On BoomBuy since {{ $shop['since'] }}</span>
                        @endif
                        @if($shop['phone'] ?? null)
                            <span><i class="bi bi-telephone"></i> {{ $shop['phone'] }}</span>
                        @endif
                    </div>
                </div>
                @php
                    $followerCount = \App\Http\Controllers\StoreFollowController::followers($shop['id']);
                    $isFollowing = \App\Http\Controllers\StoreFollowController::follows(session('user'), $shop['id']);
                @endphp
                <div class="seller-hero-actions">
                    <span class="seller-followers" data-followers>{{ $followerCount }} {{ \Illuminate\Support\Str::plural('follower', $followerCount) }}</span>
                    @if(!session('user') || (session('user')['role'] ?? '') === 'buyer')
                        <form action="{{ route('store.follow', $shop['id']) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="{{ $isFollowing ? 'unfollow' : 'follow' }}">
                            <button type="submit" class="{{ $isFollowing ? 'btn-soft' : 'btn-main' }}" data-follow-button>
                                <i class="bi {{ $isFollowing ? 'bi-check2' : 'bi-plus-lg' }}"></i> {{ $isFollowing ? 'Following' : 'Follow' }}
                            </button>
                        </form>
                    @endif
                    @if(session('user') && (int) session('user')['id'] !== $shop['id'])
                        <a href="{{ route('messages.thread', $shop['id']) }}" class="btn-soft seller-hero-chat"><i class="bi bi-chat-dots"></i> Message seller</a>
                    @endif
                </div>
            </section>

            {{-- The store's claimable vouchers --}}
            @include('partials.voucher-strip', ['vouchers' => \App\Models\Voucher::claimable($shop['id'])])

            {{-- The store's own categories --}}
            @if(($storeCategories ?? collect())->isNotEmpty())
                <nav class="store-cats" aria-label="Store categories">
                    <a href="{{ route('shop.seller', $shop['id']) }}" @class(['store-cat', 'is-active' => !($activeStoreCategory ?? null)])>All products</a>
                    @foreach($storeCategories as $storeCat)
                        <a href="{{ route('shop.seller', [$shop['id'], 'store_category' => $storeCat->id]) }}"
                           @class(['store-cat', 'is-sub' => $storeCat->parent_id, 'is-active' => ($activeStoreCategory ?? null)?->id === $storeCat->id])
                           data-store-category="{{ $storeCat->id }}">{{ $storeCat->parent_id ? '› ' : '' }}{{ $storeCat->name }}</a>
                    @endforeach
                </nav>
            @endif
        @endif

        {{-- HEADER --}}
        <header>
            <nav class="shop-crumbs" aria-label="Breadcrumb" @if($shop) hidden @endif>
                <a href="{{ session('user') ? route('buyer.dashboard') : route('home') }}">Home</a> /
                @if($filters['category'] || $filters['search'] !== '')
                    <a href="{{ route('products') }}">Shop</a> /
                    <span>{{ $filters['search'] !== '' ? 'Search' : $filters['category_label'] }}</span>
                @else
                    <span>Shop</span>
                @endif
            </nav>

            <div class="shop-title">
                <h1>{{ $heading }}</h1>

                @if($filters['search'] !== '' && $filters['category'])
                    <span class="scope-chip">
                        in {{ $filters['category_label'] }}
                        <a href="{{ $with(['category' => null]) }}" aria-label="Search all categories instead"><i class="bi bi-x-lg"></i></a>
                    </span>
                @endif

                <span class="shop-count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('product', $total) }}</span>
            </div>
        </header>

        {{-- CATEGORY CHIPS (‹ › buttons on desktop, where the row doesn't fit) --}}
        <div class="cat-scroller">
        <button type="button" class="cat-arrow is-prev" data-cat-scroll="-1" aria-label="Scroll categories left"><i class="bi bi-chevron-left"></i></button>
        <nav class="cat-row" aria-label="Categories">
            <a href="{{ $with(['category' => null]) }}" class="cat-chip {{ $filters['category'] ? '' : 'active' }}" @unless($filters['category']) aria-current="true" @endunless>
                <i class="bi bi-grid-fill"></i> All <small>{{ $totalProducts }}</small>
            </a>
            @foreach($categories as $cat)
                <a href="{{ $with(['category' => $cat['slug']]) }}" class="cat-chip {{ $filters['category'] === $cat['slug'] ? 'active' : '' }} {{ $cat['count'] ? '' : 'is-empty' }}" @if($filters['category'] === $cat['slug']) aria-current="true" @endif @unless($cat['count']) title="No products uploaded yet" @endunless>
                    <i class="bi {{ $cat['icon'] }}"></i> {{ $cat['label'] }} <small>{{ $cat['count'] ?: 'Soon' }}</small>
                </a>
            @endforeach
            @unless($shop)
                <a href="{{ route('categories') }}" class="cat-chip"><i class="bi bi-compass-fill"></i> All categories <i class="bi bi-arrow-right"></i></a>
            @endunless
        </nav>
        <button type="button" class="cat-arrow is-next" data-cat-scroll="1" aria-label="Scroll categories right"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="shop-layout">

            {{-- FILTERS (bottom sheet on phones) --}}
            <form class="filters" id="shopFilters" action="{{ url()->current() }}" method="GET" aria-labelledby="filtersTitle">
                @if($filters['category'])
                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                @endif
                @if($filters['search'] !== '')
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endif

                <div class="filters-head">
                    <h2 id="filtersTitle">Filters</h2>
                    @if($hasFilters)
                        <a href="{{ $clearFilters }}" class="clear-link">Clear all</a>
                    @endif
                    <button type="button" class="filters-close" data-sheet-close aria-label="Close filters"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="filter-group">
                    <span class="filter-label">Price</span>
                    <div class="price-inputs">
                        <input type="number" name="min" min="0" step="1" inputmode="numeric" placeholder="Min ₱" aria-label="Minimum price" value="{{ $filters['min'] !== null ? (int) $filters['min'] : '' }}">
                        <span>–</span>
                        <input type="number" name="max" min="0" step="1" inputmode="numeric" placeholder="Max ₱" aria-label="Maximum price" value="{{ $filters['max'] !== null ? (int) $filters['max'] : '' }}">
                    </div>
                    <div class="price-picks">
                        @foreach($pricePicks as $pick)
                            @php $isPick = $filters['min'] == $pick['min'] && $filters['max'] == $pick['max']; @endphp
                            <a href="{{ $with(['min' => $pick['min'], 'max' => $pick['max']]) }}" class="{{ $isPick ? 'active' : '' }}">{{ $pick['label'] }}</a>
                        @endforeach
                    </div>
                    @if($priceError ?? null)
                        <span class="field-error" role="alert">{{ $priceError }}</span>
                    @endif
                    <button type="submit" class="filter-apply filter-apply-desktop">Apply price</button>
                </div>

                @if(count($locations ?? []) > 0)
                    <div class="filter-group">
                        <label class="filter-label" for="shopLocation">Ships from</label>
                        <select id="shopLocation" name="location" data-autosubmit>
                            <option value="">Anywhere</option>
                            @foreach($locations as $place)
                                <option value="{{ $place }}" @selected($filters['location'] === $place)>{{ \App\Support\PhLocations::provinceLabel($place) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <fieldset class="filter-group">
                    <legend>Rating</legend>
                    <label class="filter-option"><input type="radio" name="rating" value="" @checked(!$filters['rating']) data-autosubmit> Any rating</label>
                    <label class="filter-option"><input type="radio" name="rating" value="4" @checked($filters['rating'] === 4) data-autosubmit> <span class="stars" aria-hidden="true">★★★★</span> 4 &amp; up</label>
                    <label class="filter-option"><input type="radio" name="rating" value="3" @checked($filters['rating'] === 3) data-autosubmit> <span class="stars" aria-hidden="true">★★★</span> 3 &amp; up</label>
                </fieldset>

                <div class="filter-group">
                    <span class="filter-label">Availability</span>
                    <label class="filter-option"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock']) data-autosubmit> In stock only</label>
                    <label class="filter-option"><input type="checkbox" name="deals" value="1" @checked($filters['deals']) data-autosubmit> On sale only</label>
                </div>

                <div class="filters-foot">
                    <a href="{{ $clearFilters }}" class="btn-soft">Clear all</a>
                    <button type="submit" class="btn-main">Show results</button>
                </div>
            </form>

            <div class="sheet-backdrop" data-sheet-close></div>

            {{-- RESULTS --}}
            <section class="shop-results" aria-label="Products">

                <div class="shop-toolbar">
                    <button type="button" class="filter-open" data-sheet-open aria-controls="shopFilters">
                        <i class="bi bi-sliders"></i> Filter
                        @if($filterCount > 0)
                            <b>{{ $filterCount }}</b>
                        @endif
                    </button>

                    @if($hasFilters)
                        <div class="active-filters">
                            <span>Active:</span>
                            @if($priceLabel)
                                <a href="{{ $with(['min' => null, 'max' => null]) }}" class="filter-chip" aria-label="Remove filter: {{ $priceLabel }}">{{ $priceLabel }} <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['rating'])
                                <a href="{{ $with(['rating' => null]) }}" class="filter-chip" aria-label="Remove filter: {{ $filters['rating'] }} stars and up">{{ $filters['rating'] }}★ &amp; up <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['location'] !== '')
                                <a href="{{ $with(['location' => null]) }}" class="filter-chip" aria-label="Remove filter: ships from {{ \App\Support\PhLocations::provinceLabel($filters['location']) }}">{{ \App\Support\PhLocations::provinceLabel($filters['location']) }} <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['in_stock'])
                                <a href="{{ $with(['in_stock' => null]) }}" class="filter-chip" aria-label="Remove filter: In stock only">In stock only <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['deals'])
                                <a href="{{ $with(['deals' => null]) }}" class="filter-chip" aria-label="Remove filter: On sale only">On sale only <i class="bi bi-x-lg"></i></a>
                            @endif
                            <a href="{{ $clearFilters }}" class="clear-link">Clear all</a>
                        </div>
                    @endif

                    <label class="sort-control">
                        <span>Sort by</span>
                        <select name="sort" form="shopFilters" aria-label="Sort products" data-autosubmit>
                            @unless($shop)
                                <option value="foryou" @selected($filters['sort'] === 'foryou')>For you</option>
                            @endunless
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price_low" @selected($filters['sort'] === 'price_low')>Price: Low to High</option>
                            <option value="price_high" @selected($filters['sort'] === 'price_high')>Price: High to Low</option>
                            <option value="rating" @selected($filters['sort'] === 'rating')>Top rated</option>
                            <option value="best_selling" @selected($filters['sort'] === 'best_selling')>Best selling</option>
                        </select>
                    </label>
                </div>

                {{-- Stores whose name matches the search --}}
                @if(($matchingShops ?? collect())->isNotEmpty())
                    <section class="shop-matches" aria-label="Shops">
                        <h2>Shops</h2>
                        <div class="shop-matches-row">
                            @foreach($matchingShops as $match)
                                <a href="{{ $match['url'] }}" class="shop-match">
                                    <span class="shop-match-avatar">
                                        @if($match['logo'] ?? $match['photo'])
                                            <img src="{{ $match['logo'] ?? $match['photo'] }}" alt="">
                                        @else
                                            {{ $match['initial'] }}
                                        @endif
                                    </span>
                                    <span class="shop-match-text">
                                        <strong>{{ $match['name'] }}</strong>
                                        <small>{{ $match['products'] }} {{ \Illuminate\Support\Str::plural('product', $match['products']) }}@if($match['location']) · {{ $match['location'] }}@endif</small>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($total === 0)

                    {{-- EMPTY STATES --}}
                    <div class="shop-empty">
                        <span class="shop-empty-icon"><i class="bi {{ $filters['category'] && $filters['search'] === '' ? \App\Support\Categories::icon($filters['category']) : 'bi-search' }}"></i></span>

                        @if($filters['search'] !== '')
                            <h2>No “{{ $filters['search'] }}”{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }}</h2>
                            @if($didYouMean ?? null)
                                <p class="did-you-mean">Did you mean <a href="{{ $with(['search' => $didYouMean]) }}"><strong>{{ $didYouMean }}</strong></a>?</p>
                            @endif
                            <p>
                                @if($hasFilters)
                                    Some filters are on — try clearing them, or use a different word.
                                @else
                                    Try a different word, check the spelling{{ $filters['category'] ? ', or look for it in every category' : '' }}.
                                @endif
                            </p>
                            <div class="shop-empty-actions">
                                @if($hasFilters)
                                    <a href="{{ $clearFilters }}" class="btn-main">Clear filters</a>
                                @elseif($filters['category'])
                                    <a href="{{ $with(['category' => null]) }}" class="btn-main"><i class="bi bi-search"></i> Search “{{ $filters['search'] }}” in all categories</a>
                                @endif
                                <a href="{{ $filters['category'] ? route('products', ['category' => $filters['category']]) : route('products') }}" class="btn-soft">
                                    Browse {{ $filters['category_label'] ?? 'all products' }}
                                </a>
                            </div>
                        @elseif($hasFilters)
                            <h2>No products match these filters</h2>
                            <p>Try a wider price range or fewer filters.</p>
                            <div class="shop-empty-actions">
                                <a href="{{ $clearFilters }}" class="btn-main">Clear filters</a>
                            </div>
                        @elseif($filters['category'] && !$shop)
                            <h2>No {{ $filters['category_label'] }} yet</h2>
                            <p>No products have been uploaded in {{ $filters['category_label'] }} yet. Sellers are still setting up — check back soon.</p>
                            <div class="shop-empty-actions">
                                <a href="{{ route('products') }}" class="btn-soft">Browse all products</a>
                            </div>
                        @else
                            <h2>No products here yet</h2>
                            <p>Sellers haven't listed anything{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }} yet. Check back soon.</p>
                            <div class="shop-empty-actions">
                                <a href="{{ route('products') }}" class="btn-soft">Browse all products</a>
                            </div>
                        @endif
                    </div>

                @else

                    <div class="sp-grid" id="productGrid">
                        @include('partials.shop-product-cards', ['products' => $products, 'showCategory' => !$filters['category']])
                    </div>

                    @if($paginator->hasMorePages())
                        <div class="load-more" id="loadMore">
                            <span id="loadCount">Showing {{ $paginator->lastItem() }} of {{ number_format($total) }} products</span>
                            <div class="load-bar" aria-hidden="true"><span id="loadBar" style="width: {{ round($paginator->lastItem() / $total * 100) }}%;"></span></div>
                            {{-- Works without JavaScript too (opens the next page). --}}
                            <a href="{{ $paginator->nextPageUrl() }}" class="btn-soft" id="loadMoreBtn">Load more products</a>
                        </div>
                    @elseif($paginator->currentPage() > 1)
                        <div class="load-more">
                            <a href="{{ $with([]) }}" class="clear-link">Back to the first page</a>
                        </div>
                    @elseif($total > 4)
                        <div class="load-more">That's all {{ number_format($total) }} products{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }}.</div>
                    @endif

                @endif

            </section>
        </div>

    </main>



    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;
        var filters = document.getElementById('shopFilters');
        var mobile = window.matchMedia('(max-width: 900px)');

        /* ---------- keep the chosen category chip in view (phones scroll the row) ---------- */

        var activeChip = document.querySelector('.cat-chip.active');
        if (activeChip && activeChip.previousElementSibling) {
            var row = activeChip.parentElement;
            row.scrollLeft += activeChip.getBoundingClientRect().left - row.getBoundingClientRect().left
                - (row.clientWidth - activeChip.offsetWidth) / 2;
        }


        /* ---------- ‹ › on the category row (desktop) ---------- */

        var catScroller = document.querySelector('.cat-scroller');
        var catRow = catScroller && catScroller.querySelector('.cat-row');

        function updateCatArrows() {
            if (!catRow) return;
            catScroller.classList.toggle('can-prev', catRow.scrollLeft > 4);
            catScroller.classList.toggle('can-next', catRow.scrollLeft + catRow.clientWidth < catRow.scrollWidth - 4);
        }

        if (catRow) {
            catRow.addEventListener('scroll', updateCatArrows, { passive: true });
            window.addEventListener('resize', updateCatArrows);
            document.addEventListener('bb:shop-updated', updateCatArrows);
            catScroller.querySelectorAll('[data-cat-scroll]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    catRow.scrollBy({ left: Number(btn.dataset.catScroll) * catRow.clientWidth * 0.7, behavior: 'smooth' });
                });
            });
            updateCatArrows();
        }


        /* ---------- phone filter sheet ---------- */

        var backdrop = document.querySelector('.sheet-backdrop');

        function setSheet(open) {
            filters.classList.toggle('is-open', open);
            backdrop.classList.toggle('is-open', open);
            document.body.style.overflow = open ? 'hidden' : '';
            if (open) {
                filters.setAttribute('role', 'dialog');
                filters.setAttribute('aria-modal', 'true');
                filters.querySelector('[data-sheet-close]').focus();
            } else {
                filters.removeAttribute('role');
                filters.removeAttribute('aria-modal');
                var openBtn = document.querySelector('[data-sheet-open]');
                if (openBtn) openBtn.focus();
            }
        }

        // Delegated: the Filter button and the close button are re-rendered by live filtering.
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-sheet-open]')) setSheet(true);
            else if (e.target.closest('[data-sheet-close]')) setSheet(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && filters.classList.contains('is-open')) setSheet(false);
        });

        /* ---------- filters: update the results in place (no page reload) ---------- */

        var results = document.querySelector('.shop-results');
        var loadController = null;

        // The URL for the form's current state: empty price/rating left out, back to page 1.
        function filtersUrl() {
            var params = new URLSearchParams(new FormData(filters));
            ['min', 'max', 'rating'].forEach(function (key) {
                if (params.get(key) === '') params.delete(key);
            });
            params.delete('page');
            var query = params.toString();
            return filters.getAttribute('action') + (query ? '?' + query : '');
        }

        // Fetch the page for `url` and swap in the parts that depend on the filters.
        function liveLoad(url, push) {
            if (loadController) loadController.abort();
            loadController = new AbortController();
            results.classList.add('is-loading');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: loadController.signal })
                .then(function (res) { if (!res.ok) throw new Error(); return res.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');

                    ['.shop-results', '.shop-title', '.cat-row', '#shopFilters .filters-head', '#shopFilters .price-picks'].forEach(function (sel) {
                        var now = document.querySelector(sel);
                        var fresh = doc.querySelector(sel);
                        if (now && fresh) now.innerHTML = fresh.innerHTML;
                    });

                    // Keep the inputs in step (e.g. after removing a filter chip),
                    // without touching the box the buyer is typing in.
                    var freshForm = doc.getElementById('shopFilters');
                    if (freshForm) {
                        filters.querySelectorAll('input[name]').forEach(function (input) {
                            if (input.type === 'hidden') return;
                            // Back/forward resets everything; otherwise leave the box being typed in alone.
                            if (push !== false && input === document.activeElement) return;
                            var match = input.type === 'radio' || input.type === 'checkbox'
                                ? freshForm.querySelector('input[name="' + input.name + '"][value="' + input.value + '"]')
                                : freshForm.querySelector('input[name="' + input.name + '"]');
                            if (!match) return;
                            if (input.type === 'radio' || input.type === 'checkbox') input.checked = match.checked;
                            else input.value = match.value;
                        });
                    }

                    if (push !== false) history.pushState({ shop: true }, '', url);
                    document.dispatchEvent(new CustomEvent('bb:shop-updated'));
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                    window.location.href = url;
                })
                .finally(function () { results.classList.remove('is-loading'); });
        }

        // Rating, stock and sort apply right away (in the phone sheet, filters wait for "Show results").
        document.addEventListener('change', function (e) {
            var input = e.target.closest('[data-autosubmit]');
            if (!input) return;
            if (mobile.matches && input.name !== 'sort') return;
            liveLoad(filtersUrl());
        });

        // Typing a price applies it after a short pause (desktop).
        var priceTimer = null;
        filters.querySelectorAll('input[name="min"], input[name="max"]').forEach(function (input) {
            input.addEventListener('input', function () {
                if (mobile.matches) return;
                clearTimeout(priceTimer);
                priceTimer = setTimeout(function () { liveLoad(filtersUrl()); }, 700);
            });
        });

        // "Apply price" / "Show results".
        filters.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(priceTimer);
            liveLoad(filtersUrl());
            if (filters.classList.contains('is-open')) setSheet(false);
        });

        // Price picks, filter chips and "Clear all" links update in place too.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.price-picks a, .active-filters a, a.clear-link, .filters-foot a.btn-soft, .shop-empty-actions a');
            if (!link || e.metaKey || e.ctrlKey || e.shiftKey) return;
            if (new URL(link.href, location.href).pathname !== location.pathname) return;
            e.preventDefault();
            liveLoad(link.href);
        });

        window.addEventListener('popstate', function () {
            liveLoad(location.href, false);
        });

        /* ---------- wishlist hearts ---------- */

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
                    btn.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                    btn.setAttribute('aria-label', data.in_wishlist ? 'Remove from wishlist' : 'Add to wishlist');
                    btn.querySelector('i').className = 'bi ' + (data.in_wishlist ? 'bi-heart-fill' : 'bi-heart');
                })
                .catch(function () {
                    // Most likely a guest — the wishlist needs an account.
                    window.location.href = @json(route('login'));
                });
        });

        /* ---------- load more ---------- */

        // Delegated: the button is re-rendered whenever the filters change.
        document.addEventListener('click', function (e) {
            var loadBtn = e.target.closest('#loadMoreBtn');
            if (!loadBtn) return;
                e.preventDefault();
                if (loadBtn.getAttribute('aria-busy') === 'true') return;

                var url = new URL(loadBtn.href);
                url.searchParams.set('partial', '1');

                loadBtn.setAttribute('aria-busy', 'true');
                loadBtn.textContent = 'Loading…';

                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
                    .then(function (data) {
                        document.getElementById('productGrid').insertAdjacentHTML('beforeend', data.html);
                        document.getElementById('loadCount').textContent = 'Showing ' + data.shown + ' of ' + data.total.toLocaleString() + ' products';
                        document.getElementById('loadBar').style.width = Math.round(data.shown / data.total * 100) + '%';

                        if (data.next) {
                            loadBtn.href = data.next;
                            loadBtn.textContent = 'Load more products';
                            loadBtn.removeAttribute('aria-busy');
                        } else {
                            document.getElementById('loadMore').innerHTML = 'That’s all ' + data.total.toLocaleString() + ' products.';
                        }
                    })
                    .catch(function () {
                        // Fall back to opening the next page normally.
                        window.location.href = loadBtn.href;
                    });
        });
    })();
    </script>

    @include('partials.quick-add')

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
